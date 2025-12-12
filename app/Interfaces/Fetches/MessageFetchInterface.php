<?php

namespace App\Interfaces\Fetches;

interface MessageFetchInterface
{
    /**
     * Display a listing of the messages for a conversation.
     */
    public function index(int $conversationId, array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array;

    /**
     * Display the specified message.
     */
    public function show(int $messageId): array;
}
