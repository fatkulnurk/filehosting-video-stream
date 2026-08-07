<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\Interfaces\StatsServiceInterface;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(StatsServiceInterface $statsService)
    {
        $userId = auth()->id();

        $recentFiles = File::where('user_id', $userId)
            ->with('media')
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => $statsService->getDashboardStats($userId),
            'recentFiles' => $recentFiles,
        ]);
    }
}
