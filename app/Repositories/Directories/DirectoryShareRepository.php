<?php

declare(strict_types=1);

namespace App\Repositories\Directories;

use App\Contracts\Repositories\Directories\DirectoryShareRepositoryInterface;
use App\Models\Files\DirectoryShare;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DirectoryShareRepository implements DirectoryShareRepositoryInterface
{
    public function create(array $data): DirectoryShare
    {
        return DirectoryShare::create($data);
    }

    public function findById(string $id): ?DirectoryShare
    {
        return DirectoryShare::find($id);
    }

    public function delete(string $id): bool
    {
        return DirectoryShare::where('id', $id)->delete() > 0;
    }

    public function getSharedWithMe(string $userId): Collection
    {
        return DirectoryShare::where('shared_with', $userId)
            ->with(['directory.files', 'directory.children'])
            ->get();
    }

    public function findActiveShare(string $directoryId, string $userId): ?DirectoryShare
    {
        return DirectoryShare::where('directory_id', $directoryId)
            ->where('shared_with', $userId)
            ->first();
    }

    public function paginateSharedWithMe(string $userId, array $params): LengthAwarePaginator
    {
        $sortable = [
            'dir' => \DB::raw('(SELECT d.name FROM directories d WHERE d.id = directory_shares.directory_id)'),
            'shared_by' => \DB::raw('(SELECT u.name FROM users u WHERE u.id = directory_shares.shared_by)'),
            'permission' => 'permission',
            'created_at' => 'created_at',
        ];

        $sorts = $params['sort'] ?? [];
        $permission = $params['filters']['permission'] ?? null;

        $query = DirectoryShare::query()
            ->where('shared_with', $userId)
            ->with('directory', 'sharedByUser', 'sharedWithUser');

        if ($permission && $permission !== '' && $permission !== 'all') {
            $query->where('permission', $permission);
        }

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! isset($sortable[$field])) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
                $target = $sortable[$field];

                if ($target instanceof Expression) {
                    $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
                } else {
                    $query->orderBy($target, $direction);
                }
            }
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($params['per_page'])->withQueryString();
    }
}
