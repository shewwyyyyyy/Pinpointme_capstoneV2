<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\ConversationInterface;
use App\Models\Conversation;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;

class ConversationService implements ConversationInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeConversation(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                $conversation = Conversation::create([]);

                // Add participants
                if (isset($data['participants']) && is_array($data['participants'])) {
                    foreach ($data['participants'] as $participant) {
                        $conversation->participants()->create([
                            'user_id' => $participant['user_id'],
                            'participant_type' => $participant['participant_type'] ?? 'user',
                            'unread_count' => 0,
                        ]);
                    }
                }

                return $this->returnModel(201, 'success', 'Conversation created successfully!', $conversation->load('participants.user'), $conversation->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Add participant to conversation.
     */
    public function addParticipant($conversationId, array $data): array
    {
        try {
            return DB::transaction(function () use ($conversationId, $data) {
                $conversation = Conversation::findOrFail($conversationId);

                // Check if participant already exists
                $existing = $conversation->participants()->where('user_id', $data['user_id'])->first();
                if ($existing) {
                    return $this->returnModel(400, 'error', 'User already in conversation');
                }

                $participant = $conversation->participants()->create([
                    'user_id' => $data['user_id'],
                    'participant_type' => $data['participant_type'] ?? 'user',
                    'unread_count' => 0,
                ]);

                return $this->returnModel(201, 'success', 'Participant added successfully!', $participant, $participant->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Mark conversation as read for user.
     */
    public function markRead($conversationId, int $userId): array
    {
        try {
            return DB::transaction(function () use ($conversationId, $userId) {
                $conversation = Conversation::findOrFail($conversationId);

                $conversation->participants()
                    ->where('user_id', $userId)
                    ->update(['unread_count' => 0]);

                return $this->returnModel(200, 'success', 'Marked as read!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteConversation($conversationId): array
    {
        try {
            return DB::transaction(function () use ($conversationId) {
                $conversation = Conversation::findOrFail($conversationId);
                
                // Delete related messages and participants
                $conversation->messages()->each(function ($message) {
                    $message->reads()->delete();
                    $message->delete();
                });
                $conversation->participants()->delete();
                $conversation->delete();

                return $this->returnModel(200, 'success', 'Conversation deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
