<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\MessageFetchInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class MessageFetchService implements MessageFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the messages for a conversation.
     */
    public function index(int $conversationId, array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $conversation = Conversation::find($conversationId);
        
        if (!$conversation) {
            return $this->returnModelCollection(404, Helper::ERROR, 'Conversation not found', collect([]));
        }

        $query = $conversation->messages();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        } else {
            $query->with(['sender', 'reads']);
        }

        if ($isPaginated) {
            $allowedFields = ['id', 'sent_at', 'created_at'];

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $messages = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {
            $messages = $query->orderByDesc('sent_at')->limit(50)->get()->reverse()->values();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $messages);
    }

    /**
     * Display the specified message.
     */
    public function show(int $messageId): array
    {
        $message = Message::with(['sender', 'reads', 'conversation'])->find($messageId);

        return $this->returnModel(200, Helper::SUCCESS, 'Message retrieved successfully!', $message);
    }
}
