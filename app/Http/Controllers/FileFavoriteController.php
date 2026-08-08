<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Services\Files\FileFavoriteServiceInterface;
use App\Contracts\Services\Files\FileServiceInterface;
use App\Traits\AppliesTableParams;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FileFavoriteController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileFavoriteServiceInterface $favoriteService,
        private FileServiceInterface $fileService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $favorites = $this->favoriteService->paginateForUser(auth()->id(), $params);

        return Inertia::render('Files/Favorites', [
            'favorites' => $favorites,
            'filters' => $this->tableFilters($params),
        ]);
    }

    public function toggle(string $id)
    {
        $file = $this->fileService->getOwnedFile(auth()->id(), $id);

        $isFavorited = $this->favoriteService->toggle(auth()->id(), $file->id);

        return redirect()->back()->with(
            'success',
            $isFavorited ? "Added '{$file->name}' to favorites" : "Removed '{$file->name}' from favorites"
        );
    }
}
