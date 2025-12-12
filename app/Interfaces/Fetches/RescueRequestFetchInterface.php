<?php

namespace App\Interfaces\Fetches;

interface RescueRequestFetchInterface
{
    /**
     * Display a listing of the rescue requests.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified rescue request.
     */
    public function show(int $rescueRequestId): array;

    /**
     * Display rescue request by code.
     */
    public function showByCode(string $rescueCode): array;

    /**
     * Get rescuer feed.
     */
    public function rescuerFeed(int $rescuerId): array;

    /**
     * Get user history.
     */
    public function userHistory(int $userId): array;
}
