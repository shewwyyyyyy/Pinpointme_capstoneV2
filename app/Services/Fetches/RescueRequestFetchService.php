<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\RescueRequestFetchInterface;
use App\Models\RescueRequest;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class RescueRequestFetchService implements RescueRequestFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the rescue requests.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = RescueRequest::query();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        } else {
            $query->with(['building', 'floor', 'room', 'requester', 'rescuer']);
        }

        // Search filter
        if (isset($request['search']) && !empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('rescue_code', 'like', "%{$search}%")
                  ->orWhere('firstName', 'like', "%{$search}%")
                  ->orWhere('lastName', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if (isset($request['status']) && !empty($request['status'])) {
            $query->where('status', $request['status']);
        }

        // Building filter
        if (isset($request['building_id']) && !empty($request['building_id'])) {
            $query->where('building_id', $request['building_id']);
        }

        // Rescuer filter
        if (isset($request['assigned_rescuer']) && !empty($request['assigned_rescuer'])) {
            $query->where('assigned_rescuer', $request['assigned_rescuer']);
        }

        // User filter
        if (isset($request['user_id']) && !empty($request['user_id'])) {
            $query->where('user_id', $request['user_id']);
        }

        if ($isPaginated) {
            $allowedFields = (new RescueRequest())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $rescueRequests = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {
            $rescueRequests = $query->orderByDesc('created_at')->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $rescueRequests);
    }

    /**
     * Display the specified rescue request.
     */
    public function show(int $rescueRequestId): array
    {
        $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])->find($rescueRequestId);

        return $this->returnModel(200, Helper::SUCCESS, 'Rescue request retrieved successfully!', $rescueRequest);
    }

    /**
     * Display rescue request by code.
     */
    public function showByCode(string $rescueCode): array
    {
        $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
            ->where('rescue_code', $rescueCode)
            ->first();

        return $this->returnModel(200, Helper::SUCCESS, 'Rescue request retrieved successfully!', $rescueRequest);
    }

    /**
     * Get rescuer feed.
     */
    public function rescuerFeed(int $rescuerId): array
    {
        $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'requester'])
            ->where('assigned_rescuer', $rescuerId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByDesc('created_at')
            ->get();

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $rescueRequests);
    }

    /**
     * Get user history.
     */
    public function userHistory(int $userId): array
    {
        $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'rescuer'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $rescueRequests);
    }
}
