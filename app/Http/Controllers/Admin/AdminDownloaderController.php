<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DownloaderDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\DownloadServerRequest;
use App\Models\DownloadServer;
use App\Services\TorrentService;
use Inertia\Inertia;

class AdminDownloaderController extends Controller
{
    public function __construct(
        private TorrentService $torrentService
    ) {}

    public function index()
    {
        $servers = DownloadServer::orderBy('driver')->orderBy('name')->get();

        return Inertia::render('Admin/Downloaders/Index', [
            'servers' => $servers,
            'drivers' => DownloaderDriver::toSelectArray(),
            'driverFields' => collect(DownloaderDriver::cases())->mapWithKeys(fn ($d) => [$d->value => $d->requiredFields()]),
        ]);
    }

    public function store(DownloadServerRequest $request)
    {
        $data = $request->validated();

        $server = DownloadServer::create([
            'name' => $data['name'],
            'driver' => $data['driver'],
            'config' => $data['config'] ?? [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.downloaders.index')->with('success', "Downloader '{$server->name}' added");
    }

    public function update(DownloadServerRequest $request, string $id)
    {
        $server = DownloadServer::findOrFail($id);
        $data = $request->validated();

        $server->update([
            'name' => $data['name'],
            'driver' => $data['driver'],
            'config' => $data['config'] ?? [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.downloaders.index')->with('success', "Downloader '{$server->name}' updated");
    }

    public function test(string $id)
    {
        $server = DownloadServer::findOrFail($id);

        if (! $server->driver()->isTorrent()) {
            return back()->with('error', 'Connection test is only supported for torrent drivers.');
        }

        $ok = $this->torrentService->serverReachable($server);

        return back()->with($ok ? 'success' : 'error', $ok
            ? 'Connected to torrent client successfully.'
            : 'Could not reach the torrent client.'
        );
    }

    public function destroy(string $id)
    {
        DownloadServer::findOrFail($id)->delete();

        return redirect()->route('admin.downloaders.index')->with('success', 'Downloader deleted');
    }
}
