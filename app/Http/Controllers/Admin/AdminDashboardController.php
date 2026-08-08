<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Contracts\Services\Admin\AdminServiceInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Concurrency;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __invoke(AdminServiceInterface $adminService, StatsServiceInterface $statsService)
    {
        [$globalStats, $latestUsers, $storageServers] = Concurrency::run([
            fn () => $statsService->getGlobalStats(),
            fn () => $adminService->getLatestUsers(10),
            fn () => $adminService->getLatestStorageServers(10),
        ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $globalStats,
            'latestUsers' => $latestUsers,
            'storageServers' => $storageServers,
        ]);
    }
}
