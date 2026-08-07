<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StorageServer;
use App\Models\User;
use App\Services\Interfaces\AdminServiceInterface;
use App\Services\Interfaces\StatsServiceInterface;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __invoke(AdminServiceInterface $adminService, StatsServiceInterface $statsService)
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => $statsService->getGlobalStats(),
            'latestUsers' => User::latest()->limit(10)->get(),
            'storageServers' => StorageServer::latest()->limit(10)->get(),
        ]);
    }
}