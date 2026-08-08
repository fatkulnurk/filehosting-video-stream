<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesTableParams
{
    /**
     * Allowed per-page values.
     */
    protected array $perPageOptions = [10, 25, 50, 100];

    /**
     * Resolve table params (search / sort / per_page / filters) from the request.
     */
    protected function tableParams(Request $request, int $defaultPerPage = 10): array
    {
        $sort = $request->input('sort', []);

        if (is_string($sort) && $sort !== '') {
            $sort = [$sort => strtolower((string) $request->input('direction', 'asc'))];
        }

        return [
            'search' => trim((string) $request->input('search', '')),
            'sort' => is_array($sort) ? $sort : [],
            'per_page' => $this->resolvePerPage($request->input('per_page', $defaultPerPage), $defaultPerPage),
            'filters' => (array) $request->input('filter', []),
        ];
    }

    /**
     * Validate per_page against the allowed set.
     */
    protected function resolvePerPage(mixed $perPage, int $default = 10): int
    {
        $perPage = (int) $perPage;

        return in_array($perPage, $this->perPageOptions, true) ? $perPage : $default;
    }

    /**
     * Build the filters payload for the frontend.
     */
    protected function tableFilters(array $params): array
    {
        return [
            'search' => $params['search'],
            'sort' => $params['sort'] ?? [],
            'per_page' => $params['per_page'],
        ];
    }

    /**
     * Apply search + sort to a query builder.
     *
     * @param  Builder  $query
     * @param  array  $searchable  columns to LIKE-search
     * @param  array  $sortable  key => column/sql allowed to orderBy
     */
    protected function applyTableQuery(
        $query,
        array $params,
        array $searchable = [],
        array $sortable = [],
        ?string $defaultSort = null,
        string $defaultDirection = 'desc',
    ) {
        if (($params['search'] ?? '') !== '' && $searchable) {
            $query->where(function (Builder $q) use ($params, $searchable) {
                foreach ($searchable as $column) {
                    $q->orWhere($column, 'LIKE', "%{$params['search']}%");
                }
            });
        }

        $sorts = $params['sort'] ?? [];

        if ($sorts) {
            foreach ($sorts as $field => $direction) {
                if (! array_key_exists($field, $sortable)) {
                    continue;
                }

                $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
                $target = $sortable[$field];

                if ($target instanceof Expression) {
                    $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
                } else {
                    $query->orderBy($target, $direction);
                }
            }
        } elseif ($defaultSort) {
            $query->orderBy($defaultSort, $defaultDirection);
        }

        return $query;
    }

    /**
     * Apply a set of exact-match categorical filters to a query builder.
     *
     * @param  Builder  $query
     * @param  array  $filters  map of filter key => target column (or array of allowed values)
     */
    protected function applyFilters($query, Request $request, array $filters = [])
    {
        foreach ($filters as $key => $target) {
            if (! $request->filled('filter.'.$key)) {
                continue;
            }

            $value = $request->input('filter.'.$key);

            if ($value === 'all' || $value === '') {
                continue;
            }

            // If target is a closures map: key => Closure($query, $value)
            if (is_array($target)) {
                if (isset($target[$value]) && is_callable($target[$value])) {
                    $target[$value]($query, $value);
                }

                continue;
            }

            // If target is a single Closure
            if (is_callable($target)) {
                $target($query, $value);

                continue;
            }

            $query->where($target, $value);
        }

        return $query;
    }

    /**
     * Build a filters payload that also includes the given category filter values.
     */
    protected function tableFiltersWith(array $params, Request $request, array $keys): array
    {
        $filters = $this->tableFilters($params);

        foreach ($keys as $key) {
            $filters[$key] = $request->filled('filter.'.$key) ? $request->input('filter.'.$key) : '';
        }

        return $filters;
    }
}
