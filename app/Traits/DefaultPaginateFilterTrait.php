<?php

namespace App\Traits;

/**
 * Trait DefaultPaginateFilterTrait
 *
 * Provides a method to set default filters for pagination, including per_page, sort_by, sort_direction, and current_page.
 */
trait DefaultPaginateFilterTrait
{
    /**
     * Set default filters for pagination.
     *
     * @param array $request
     * @param array $allowedSortFields
     * @return array
     */
    public function paginateFilter(array $request, array $allowedSortFields = []): array
    {
        // Validate per_page with a safe default and upper limit
        $perPage = isset($request['per_page']) && is_numeric($request['per_page']) && $request['per_page'] > 0
            ? (int) $request['per_page']
            : 10;
        $perPage = min($perPage, 100);

        // Merge default sortable fields and user-defined ones (without duplicates)
        $allowedSortFields = $this->resolveSortableFields($allowedSortFields);

        // Validate and set sort_by
        $sortBy = isset($request['sort_by']) && in_array($request['sort_by'], $allowedSortFields, true)
            ? $request['sort_by']
            : $allowedSortFields[0];

        // Validate and normalize sort_direction
        $sortDirection = strtolower($request['sort_direction'] ?? 'desc');
        $sortDirection = in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'desc';

        $currentPage = isset($request['current_page']) && is_numeric($request['current_page']) && $request['current_page'] > 0
            ? (int) $request['current_page']
            : 1;

        return [
            'per_page' => $perPage,
            'sort_by' => $sortBy,
            'sort' => $sortDirection,
            'current_page' => $currentPage
        ];
    }

    /**
     * Merge default sortable fields with user-defined ones.
     *
     * @param array $customFields
     * @return array
     */
    private function resolveSortableFields(array $customFields): array
    {
        $defaultFields = ['id', 'created_at', 'updated_at'];
        return array_unique(array_merge($defaultFields, $customFields));
    }
}
