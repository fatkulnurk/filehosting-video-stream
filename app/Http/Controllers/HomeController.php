<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Repositories\Storage\StoragePackageRepositoryInterface;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke(StoragePackageRepositoryInterface $packageRepo)
    {
        return Inertia::render('Home', [
            'packages' => $packageRepo->getActive(),
        ]);
    }
}
