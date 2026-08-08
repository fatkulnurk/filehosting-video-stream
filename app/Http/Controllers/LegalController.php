<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    public function terms(): Response
    {
        return Inertia::render('Legal/Terms', [
            'appName' => config('app.name', 'FHV Stream'),
            'updatedAt' => now()->format('F j, Y'),
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', [
            'appName' => config('app.name', 'FHV Stream'),
            'updatedAt' => now()->format('F j, Y'),
        ]);
    }
}
