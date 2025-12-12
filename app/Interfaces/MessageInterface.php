<?php

namespace App\Interfaces;

interface MessageInterface
{
    /**
     * Store a newly created resource in storage.
     */
    public function storeMessage($conversationId, array $data, $file = null): array;

    /**
     * Remove the specified resource from storage.
     */
    public function deleteMessage($conversationId, $messageId): array;
}
