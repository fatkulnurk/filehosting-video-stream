<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\StoragePackageRepositoryInterface;
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
