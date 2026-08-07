<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Http\Requests\StoreTorrentRequest;
use App\Http\Requests\StoreUrlRequest;
use App\Models\DownloadTask;
use App\Repositories\Interfaces\DownloadTaskRepositoryInterface;
use App\Services\Interfaces\DirectoryServiceInterface;
use App\Services\Interfaces\DownloaderServiceInterface;
use App\Services\TorrentHashService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DownloadController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private DownloadTaskRepositoryInterface $taskRepository,
        private DownloaderServiceInterface $downloaderService,
        private TorrentHashService $hashService,
        private DirectoryServiceInterface $directoryService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $tasks = $this->applyTableQuery(
            DownloadTask::query()->where('user_id', auth()->id()),
            $params,
            searchable: ['source'],
            sortable: [
                'type' => 'type',
                'status' => 'status',
                'progress' => 'progress',
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )
            ->with(['file', 'torrent', 'directory'])
            ->paginate($params['per_page'])
            ->withQueryString();

        return Inertia::render('CloudDownloads/Index', [
            'tasks' => $tasks,
            'filters' => $this->tableFilters($params),
            'directoryTree' => $this->directoryService->getTree(auth()->id()),
        ]);
    }

    public function storeUrl(StoreUrlRequest $request)
    {
        // One task per URL; each gets its own background job.
        $task = $this->taskRepository->create([
            'user_id' => auth()->id(),
            'type' => 'url',
            'source' => $request->url,
            'directory_id' => $request->directory_id,
            'disk' => $request->disk,
            'status' => 'queued',
            'progress' => 0,
        ]);

        $this->downloaderService->dispatch($task);

        return redirect()->route('cloud-downloads.index')->with('success', 'URL download queued.');
    }

    public function storeTorrent(StoreTorrentRequest $request)
    {
        $userId = auth()->id();

        $torrentFile = $request->torrentFile();
        $magnet = $request->magnet();

        if ($torrentFile) {
            $path = 'torrents/'.md5(uniqid($userId, true)).'.torrent';
            Storage::disk('local')->put($path, file_get_contents($torrentFile->getRealPath()));
            $infoHash = $this->hashService->fromTorrentFile(Storage::disk('local')->path($path));
            $source = Storage::disk('local')->path($path);
        } elseif ($magnet) {
            if (! str_starts_with(strtolower($magnet), 'magnet:')) {
                return redirect()->back()->with('error', 'Invalid magnet link.');
            }

            $infoHash = $this->hashService->fromMagnet($magnet);
            $source = $magnet;
        } else {
            return redirect()->back()->with('error', 'Provide a magnet link or .torrent file.');
        }

        if (! $infoHash) {
            return redirect()->back()->with('error', 'Could not read the torrent info hash from the provided source.');
        }

        $task = $this->taskRepository->create([
            'user_id' => $userId,
            'type' => 'torrent',
            'source' => $source,
            'directory_id' => $request->directory_id,
            'disk' => $request->disk,
            'status' => 'queued',
            'progress' => 0,
            'info_hash' => $infoHash,
        ]);

        $this->downloaderService->dispatch($task);

        return redirect()->route('cloud-downloads.index')->with('success', 'Torrent download queued.');
    }

    public function retry(string $id)
    {
        $task = $this->taskRepository->findById($id);

        if (! $task || $task->user_id !== auth()->id() || ! in_array($task->status->value, ['failed', 'cancelled'], true)) {
            abort(404);
        }

        $this->downloaderService->retry($task);

        return redirect()->back()->with('success', 'Download requeued.');
    }

    public function cancel(string $id)
    {
        $task = $this->taskRepository->findById($id);

        if (! $task || $task->user_id !== auth()->id()) {
            abort(404);
        }

        $this->downloaderService->cancel($task);

        return redirect()->back()->with('success', 'Download cancelled.');
    }
}
