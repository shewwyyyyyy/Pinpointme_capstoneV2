<?php

namespace App\Interfaces\Fetches;

interface EmployeeFetchInterface
{
    /**
     * Display a listing of the scan histories.
     */
    public function indexEmployee(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;
}
