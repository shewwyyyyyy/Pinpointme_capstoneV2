<?php

namespace App\Interfaces\Fetches;

interface AuditTrailFetchInterface
{
    /**
     * Display a listing of the audit trails.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified audit trail.
     */
    public function show(int $auditTrailId): array;
}
