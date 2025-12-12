<?php

namespace App\Interfaces\Fetches;

interface RoomFetchInterface
{
    /**
     * Display a listing of the rooms.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified room.
     */
    public function show(int $roomId): array;
}
