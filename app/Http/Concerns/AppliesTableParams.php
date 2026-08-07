<?php

namespace App\Http\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesTableParams
{
    /**
     * Allowed per-page values.
     */
    protected array $perPageOptions = [10, 25, 50, 100];

    /**
     * Resolve table params (search / sort / direction / per_page) from the request.
     */
    protected function tableParams(Request $request, int $defaultPerPage = 10): array
    {
        $sort = $request->input('sort');
        $direction = strtolower((string) $request->input('direction', 'asc'));

        return [
            'search' => trim((string) $request->input('search', '')),
            'sort' => is_string($sort) && $sort !== '' ? $sort : null,
            'direction' => in_array($direction, ['asc', 'desc']) ? $direction : 'asc',
            'per_page' => $this->resolvePerPage($request->input('per_page', $defaultPerPage), $defaultPerPage),
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
            'sort' => $params['sort'],
            'direction' => $params['direction'],
            'per_page' => $params['per_page'],
        ];
    }

    /**
     * Apply search + sort to a query builder.
     *
     * @param  Builder  $query
     * @param  array  $searchable  columns to LIKE-search
     * @param  array  $sortable    key => column/sql allowed to orderBy
     * @param  string|null  $defaultSort
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

        $sort = $params['sort'] ?? null;
        $direction = $params['direction'] ?? $defaultDirection;

        if ($sort && array_key_exists($sort, $sortable)) {
            $target = $sortable[$sort];
            if ($target instanceof \Illuminate\Contracts\Database\Query\Expression) {
                $query->orderByRaw($target->getValue($query->getQuery()->getGrammar()).' '.$direction);
            } else {
                $query->orderBy($target, $direction);
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
     * @param  Request  $request
     * @param  array  $filters  map of filter key => target column (or array of allowed values)
     */
    protected function applyFilters($query, Request $request, array $filters = [])
    {
        foreach ($filters as $key => $target) {
            if (! $request->filled($key)) {
                continue;
            }

            $value = $request->input($key);

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
            $filters[$key] = $request->filled($key) ? $request->input($key) : '';
        }

        return $filters;
    }
}