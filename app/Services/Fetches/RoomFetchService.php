<?php

namespace App\Services\Fetches;

use App\Helpers\Helper;
use App\Interfaces\Fetches\RoomFetchInterface;
use App\Models\Room;
use App\Traits\DefaultPaginateFilterTrait;
use App\Traits\HttpErrorCodeTrait;
use App\Traits\ReturnModelCollectionTrait;
use App\Traits\ReturnModelTrait;
use Illuminate\Pagination\Paginator;

class RoomFetchService implements RoomFetchInterface
{
    use HttpErrorCodeTrait,
        ReturnModelCollectionTrait,
        ReturnModelTrait,
        DefaultPaginateFilterTrait;

    /**
     * Display a listing of the rooms.
     */
    public function index(array $request = [], bool $isPaginated = false, ?string $resourceClass = null): array
    {
        $query = Room::query();

        if ($resourceClass !== null && isset($resourceClass::$relations)) {
            $query->with($resourceClass::$relations ?? []);
        } else {
            $query->with(['floor.building']);
        }

        // Search filter
        if (isset($request['search']) && !empty($request['search'])) {
            $search = $request['search'];
            $query->where(function ($q) use ($search) {
                $q->where('room_name', 'like', "%{$search}%");
            });
        }

        // Floor filter
        if (isset($request['floor_id']) && !empty($request['floor_id'])) {
            $query->where('floor_id', $request['floor_id']);
        }

        if ($isPaginated) {
            $allowedFields = (new Room())->getFillable();

            [
                'per_page' => $per_page,
                'sort_by' => $sort_by,
                'sort' => $sort,
                'current_page' => $current_page
            ] = $this->paginateFilter($request, $allowedFields);

            Paginator::currentPageResolver(fn() => $current_page ?? 1);

            $rooms = $query->orderBy($sort_by, $sort)->paginate($per_page);
        } else {
            $rooms = $query->get();
        }

        return $this->returnModelCollection(200, Helper::SUCCESS, 'Successfully fetched!', $rooms);
    }

    /**
     * Display the specified room.
     */
    public function show(int $roomId): array
    {
        $room = Room::with(['floor.building'])->find($roomId);

        return $this->returnModel(200, Helper::SUCCESS, 'Room retrieved successfully!', $room);
    }
}
