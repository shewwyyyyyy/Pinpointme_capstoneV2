<?php

namespace App\Interfaces\Fetches;

interface DepartmentFetchInterface
{
    /**
     * Display a listing of the departments.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified department.
     */
    public function show(int $departmentId): array;
}