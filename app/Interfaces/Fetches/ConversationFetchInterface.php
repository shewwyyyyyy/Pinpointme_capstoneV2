<?php

namespace App\Interfaces\Fetches;

interface ConversationFetchInterface
{
    /**
     * Display a listing of the conversations.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified conversation.
     */
    public function show(int $conversationId): array;
}
