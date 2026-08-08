<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Downloads\DownloaderServiceInterface;
use App\Enums\DownloaderDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Downloads\DownloadServerRequest;
use Inertia\Inertia;

class AdminDownloaderController extends Controller
{
    public function __construct(
        private DownloaderServiceInterface $downloaderService
    ) {}

    public function index()
    {
        $servers = $this->downloaderService->getServers();

        return Inertia::render('Admin/Downloaders/Index', [
            'servers' => $servers,
            'drivers' => DownloaderDriver::toSelectArray(),
            'driverFields' => collect(DownloaderDriver::cases())->mapWithKeys(fn ($d) => [$d->value => $d->requiredFields()]),
        ]);
    }

    public function store(DownloadServerRequest $request)
    {
        $data = $request->validated();

        $server = $this->downloaderService->createServer([
            'name' => $data['name'],
            'driver' => $data['driver'],
            'config' => $data['config'] ?? [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.downloaders.index')->with('success', "Downloader '{$server->name}' added");
    }

    public function update(DownloadServerRequest $request, string $id)
    {
        $data = $request->validated();

        $server = $this->downloaderService->updateServer($id, [
            'name' => $data['name'],
            'driver' => $data['driver'],
            'config' => $data['config'] ?? [],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.downloaders.index')->with('success', "Downloader '{$server->name}' updated");
    }

    public function test(string $id)
    {
        $server = $this->downloaderService->getServerById($id);

        if (! $server->driver()->isTorrent()) {
            return back()->with('error', 'Connection test is only supported for torrent drivers.');
        }

        $ok = $this->downloaderService->testServerConnection($id);

        return back()->with($ok ? 'success' : 'error', $ok
            ? 'Connected to torrent client successfully.'
            : 'Could not reach the torrent client.'
        );
    }

    public function destroy(string $id)
    {
        $this->downloaderService->deleteServer($id);

        return redirect()->route('admin.downloaders.index')->with('success', 'Downloader deleted');
    }
}
