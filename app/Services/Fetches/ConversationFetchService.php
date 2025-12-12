<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\ConversationFetchInterface;
use App\Models\Conversation;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class ConversationFetchService implements ConversationFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the conversations.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = Conversation::query();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        } else {
            $query->with(['participants.user']);
        }

        // User filter - get conversations where user is participant
        if (isset($request['user_id']) && !empty($request['user_id'])) {
            $userId = $request['user_id'];
            $query->whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        if ($isPaginated) {
            $allowedFields = ['id', 'created_at', 'updated_at'];

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $conversations = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {
            $conversations = $query->orderByDesc('updated_at')->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $conversations);
    }

    /**
     * Display the specified conversation.
     */
    public function show(int $conversationId): array
    {
        $conversation = Conversation::with(['participants.user'])->find($conversationId);

        return $this->returnModel(200, Helper::SUCCESS, 'Conversation retrieved successfully!', $conversation);
    }
}
