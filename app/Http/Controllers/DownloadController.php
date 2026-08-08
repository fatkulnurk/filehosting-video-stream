<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Directories\DirectoryServiceInterface;
use App\Contracts\Services\Downloads\DownloaderServiceInterface;
use App\Enums\DownloadTaskStatus;
use App\Http\Requests\Downloads\DownloadIndexRequest;
use App\Http\Requests\Files\StoreTorrentRequest;
use App\Http\Requests\Files\StoreUrlRequest;
use App\Services\Downloads\TorrentHashService;
use App\Traits\AppliesTableParams;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class DownloadController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private DownloaderServiceInterface $downloaderService,
        private TorrentHashService $hashService,
        private DirectoryServiceInterface $directoryService
    ) {}

    public function index(DownloadIndexRequest $request)
    {
        $params = $this->tableParams($request);
        $params['sort'] = $request->getSorts();
        $params['filters'] = $request->getFilters();

        return Inertia::render('CloudDownloads/Index', [
            'tasks' => $this->downloaderService->paginateForUser(auth()->id(), $params),
            'filters' => $this->tableFiltersWith($params, $request, ['status', 'type']),
            'directoryTree' => $this->directoryService->getTree(auth()->id()),
        ]);
    }

    public function storeUrl(StoreUrlRequest $request)
    {
        // One task per URL; each gets its own background job.
        $task = $this->downloaderService->createTask([
            'user_id' => auth()->id(),
            'type' => 'url',
            'source' => $request->url,
            'directory_id' => $request->directory_id,
            'disk' => $request->disk,
            'status' => DownloadTaskStatus::Queued,
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

        $task = $this->downloaderService->createTask([
            'user_id' => $userId,
            'type' => 'torrent',
            'source' => $source,
            'directory_id' => $request->directory_id,
            'disk' => $request->disk,
            'status' => DownloadTaskStatus::Queued,
            'progress' => 0,
            'info_hash' => $infoHash,
        ]);

        $this->downloaderService->dispatch($task);

        return redirect()->route('cloud-downloads.index')->with('success', 'Torrent download queued.');
    }

    public function retry(string $id)
    {
        $task = $this->downloaderService->findOwnedTask(auth()->id(), $id);

        if (! $task || ! in_array($task->status->value, ['failed', 'cancelled'], true)) {
            abort(404);
        }

        $this->downloaderService->retry($task);

        return redirect()->back()->with('success', 'Download requeued.');
    }

    public function cancel(string $id)
    {
        $task = $this->downloaderService->findOwnedTask(auth()->id(), $id);

        if (! $task) {
            abort(404);
        }

        $this->downloaderService->cancel($task);

        return redirect()->back()->with('success', 'Download cancelled.');
    }
}
