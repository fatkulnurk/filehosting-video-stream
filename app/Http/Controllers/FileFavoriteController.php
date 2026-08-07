<?php

namespace App\Http\Controllers;

use App\Http\Concerns\AppliesTableParams;
use App\Models\File;
use App\Models\FileFavorite;
use App\Services\Interfaces\FileFavoriteServiceInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FileFavoriteController extends Controller
{
    use AppliesTableParams;

    public function __construct(
        private FileFavoriteServiceInterface $favoriteService
    ) {}

    public function index(Request $request)
    {
        $params = $this->tableParams($request);

        $favorites = $this->applyTableQuery(
            FileFavorite::query()->where('user_id', auth()->id())->with('file.media'),
            $params,
            searchable: [],
            sortable: [
                'name' => \DB::raw('(SELECT f.name FROM files f WHERE f.id = file_favorites.file_id)'),
                'size' => \DB::raw("(SELECT COALESCE(SUM(size),0) FROM media WHERE media.model_id = file_favorites.file_id AND media.model_type = '" . addslashes(\App\Models\File::class) . "')"),
                'views' => \DB::raw('(SELECT f.views FROM files f WHERE f.id = file_favorites.file_id)'),
                'downloads' => \DB::raw('(SELECT f.downloads FROM files f WHERE f.id = file_favorites.file_id)'),
                'created_at' => 'created_at',
            ],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
        )->paginate($params['per_page'])->withQueryString();

        return Inertia::render('Files/Favorites', [
            'favorites' => $favorites,
            'filters' => $this->tableFilters($params),
        ]);
    }

    public function toggle(string $id)
    {
        $file = File::where('user_id', auth()->id())->findOrFail($id);

        $isFavorited = $this->favoriteService->toggle(auth()->id(), $file->id);

        return redirect()->back()->with(
            'success',
            $isFavorited ? "Added '{$file->name}' to favorites" : "Removed '{$file->name}' from favorites"
        );
    }
}