<?php

namespace App\Services;

use App\Helpers\Helper;
use App\Interfaces\MessageInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MessageService implements MessageInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait;

    /**
     * Store a newly created resource in storage.
     */
    public function storeMessage($conversationId, array $data, $file = null): array
    {
        try {
            return DB::transaction(function () use ($conversationId, $data, $file) {
                $conversation = Conversation::findOrFail($conversationId);

                // Check if sender is participant
                $participant = $conversation->participants()->where('user_id', $data['sender_id'])->first();
                if (!$participant) {
                    return $this->returnModel(403, 'error', 'Sender is not a participant');
                }

                $attachmentMeta = [
                    'attachment_url' => null,
                    'attachment_type' => null,
                    'attachment_name' => null,
                ];

                // Handle file upload
                if ($file) {
                    $path = $file->store('conversation_attachments/' . $conversationId, 'public');
                    $attachmentMeta = [
                        'attachment_url' => Storage::url($path),
                        'attachment_type' => $file->getMimeType(),
                        'attachment_name' => $file->getClientOriginalName(),
                    ];

                    if (empty($data['content'])) {
                        $data['content'] = '[Attachment]';
                    }
                }

                $senderName = $participant->user->name ?? 'Unknown';

                $message = $conversation->messages()->create(array_merge([
                    'sender_id' => $data['sender_id'],
                    'content' => $data['content'] ?? null,
                    'sender_name' => $senderName,
                    'status' => 'sent',
                    'sent_at' => now(),
                ], $attachmentMeta));

                // Update last message
                $conversation->update([
                    'last_message' => [
                        'content' => $message->content,
                        'sender_id' => $message->sender_id,
                        'sender_name' => $senderName,
                        'timestamp' => $message->sent_at?->toIso8601String(),
                    ],
                    'updated_at' => now(),
                ]);

                // Increment unread for other participants
                $conversation->participants()
                    ->where('user_id', '!=', $message->sender_id)
                    ->increment('unread_count');

                return $this->returnModel(201, 'success', 'Message sent successfully!', $message, $message->id);
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function deleteMessage($conversationId, $messageId): array
    {
        try {
            return DB::transaction(function () use ($conversationId, $messageId) {
                $message = Message::where('conversation_id', $conversationId)->findOrFail($messageId);
                
                $message->reads()->delete();
                $message->delete();

                return $this->returnModel(200, 'success', 'Message deleted successfully!');
            });
        } catch (\Throwable $th) {
            $code = $this->httpCode($th);
            return $this->returnModel($code, 'error', $th->getMessage());
        }
    }
}
