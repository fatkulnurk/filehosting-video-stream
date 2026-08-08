<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileServiceInterface;
use App\Contracts\Services\Files\StatsServiceInterface;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(
        StatsServiceInterface $statsService,
        FileServiceInterface $fileService,
    ) {
        $userId = auth()->id();

        return Inertia::render('Dashboard/Index', [
            'stats' => $statsService->getDashboardStats($userId),
            'recentFiles' => $fileService->getRecentFiles($userId, 5),
        ]);
    }
}
