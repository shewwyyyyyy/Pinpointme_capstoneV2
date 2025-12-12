<?php

namespace App\Interfaces;

interface ConversationInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeConversation(array $data): array;

    /**
     * Add participant to conversation.
     */
    public function addParticipant($conversationId, array $data): array;

    /**
     * Mark conversation as read for user.
     */
    public function markRead($conversationId, int $userId): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteConversation($conversationId): array;
}
