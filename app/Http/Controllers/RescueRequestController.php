<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RescueRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class RescueRequestController extends Controller
{
    /**
     * Display rescue requests
     *
     * @return void
     */
    public function index()
    {
        // Handle API requests - return all rescue requests with relationships
        if (request()->expectsJson() || request()->is('api/*')) {
            $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $rescueRequests
            ]);
        }

        $isAdmin = Auth::check() && Auth::user()->isAdmin;

        if (!$isAdmin) {
            return Inertia::render('Error', [
                'code' => 404,
                'message' => 'This page unauthorized to access'
            ]);
        }

        return Inertia::render('System/RescueRequests', [
            'can' => []
        ]);
    }

    /**
     * Store Rescue Request
     *
     * @param Request $request
     * @return void
     */
    public function store(Request $request)
    {
        $data = $request->only([
            'assigned_rescuer',
            'user_id',
            'status',
            'building_id',
            'floor_id',
            'room_id',
            'description',
            'mobility_status',
            'injuries',
            'urgency_level',
            'additional_info',
            'firstName',
            'lastName'
        ]);

        $validator = Validator::make($data, [
            'user_id' => 'nullable|exists:users,id',
            'assigned_rescuer' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,in_progress,completed,cancelled',
            'building_id' => 'nullable|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'room_id' => 'nullable|exists:rooms,id',
            'description' => 'nullable|string',
            'mobility_status' => 'nullable|string',
            'injuries' => 'nullable|string',
            'urgency_level' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'firstName' => 'nullable|string',
            'lastName' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $data['rescue_code'] = $this->generateUniqueRescueCode();
        $data['status'] = $data['status'] ?? 'pending';

        $rescueRequest = RescueRequest::create($data);

        // Handle API requests differently
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Rescue request created successfully',
                'rescueCode' => $rescueRequest->rescue_code,
                'requestId' => $rescueRequest->id,
                'data' => $rescueRequest
            ], 201);
        }

        return redirect()->back()->with('success', 'Rescue request created successfully');
    }

    /**
     * Display the specified rescue request.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])->findOrFail($id);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $rescueRequest
            ]);
        }

        return Inertia::render('RescueRequest/Show', [
            'rescueRequest' => $rescueRequest
        ]);
    }

    /**
     * Display rescue request by code.
     *
     * @param string $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function showByCode($code)
    {
        try {
            $rescueRequest = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
                ->where('rescue_code', $code)
                ->first();

            if (!$rescueRequest) {
                if (request()->expectsJson() || request()->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rescue request not found'
                    ], 404);
                }
                abort(404);
            }

            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'data' => $rescueRequest
                ]);
            }

            return Inertia::render('RescueRequest/Show', [
                'rescueRequest' => $rescueRequest
            ]);
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error retrieving rescue request: ' . $e->getMessage()
                ], 500);
            }
            abort(500);
        }
    }

    /**
     * Get user history.
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userHistory($userId)
    {
        $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'rescuer'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rescueRequests
        ]);
    }

    /**
     * Get user's active (open) rescue request.
     * Active statuses: pending, accepted, in_progress, en_route
     * Once marked as 'rescued', 'safe', or 'completed', user can request again
     *
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userActiveRescue($userId)
    {
        // Only these statuses are truly "active" - user cannot create new request
        // 'rescued' means waiting for user to confirm safe, but they can still create new request
        // 'safe' and 'completed' mean rescue is done
        $activeStatuses = ['pending', 'accepted', 'assigned', 'in_progress', 'en_route'];
        
        $activeRequest = RescueRequest::with(['building', 'floor', 'room', 'rescuer'])
            ->where('user_id', $userId)
            ->whereIn('status', $activeStatuses)
            ->orderByDesc('created_at')
            ->first();

        if ($activeRequest) {
            return response()->json([
                'success' => true,
                'has_active' => true,
                'data' => $activeRequest
            ]);
        }

        return response()->json([
            'success' => true,
            'has_active' => false,
            'data' => null
        ]);
    }

    /**
     * Get location details
     *
     * @param int $buildingId
     * @param int $floorId
     * @param int $roomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationDetails($buildingId, $floorId, $roomId)
    {
        try {
            $building = Building::find($buildingId);
            $floor = Floor::find($floorId);
            $room = Room::find($roomId);

            return response()->json([
                'success' => true,
                'data' => [
                    'building' => $building,
                    'floor' => $floor,
                    'room' => $room,
                    'location_string' => trim(sprintf(
                        '%s - %s - %s',
                        $building?->name ?? '',
                        $floor?->floor_name ?? '',
                        $room?->room_name ?? ''
                    ))
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving location details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update rescue request
     *
     * @param Request $request
     * @param int $id
     * @return void
     */
    public function update(Request $request, $id)
    {
        $data = $request->only([
            'assigned_rescuer',
            'status',
            'description',
            'mobility_status',
            'injuries',
            'urgency_level',
            'additional_info'
        ]);

        $validator = Validator::make($data, [
            'assigned_rescuer' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,assigned,in_progress,rescued,safe,completed,cancelled',
            'description' => 'nullable|string',
            'mobility_status' => 'nullable|string',
            'injuries' => 'nullable|string',
            'urgency_level' => 'nullable|string',
            'additional_info' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            // Handle API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        try {
            $rescueRequest = RescueRequest::findOrFail($id);
            
            // Update timestamp for status changes
            if (isset($data['status']) && $data['status'] !== $rescueRequest->status) {
                $data['updated_at'] = now();
            }
            
            $rescueRequest->update($data);
            
            // Load relationships for API response
            $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer']);

            // Handle API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Rescue request updated successfully',
                    'data' => $rescueRequest
                ]);
            }

            return redirect()->back()->with('success', 'Rescue request updated successfully');
        } catch (\Exception $e) {
            // Handle API requests
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update rescue request: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to update rescue request');
        }
    }

    /**
     * Get rescue requests for a specific rescuer
     *
     * @param int $rescuerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function rescuerFeed($rescuerId)
    {
        $rescueRequests = RescueRequest::with(['building', 'floor', 'room', 'requester', 'rescuer'])
            ->where(function($query) use ($rescuerId) {
                $query->where('assigned_rescuer', $rescuerId)
                      ->orWhere('status', 'pending'); // Show all pending requests
            })
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $rescueRequests
        ]);
    }

    /**
     * Delete rescue request
     *
     * @param int $id
     * @return void
     */
    public function destroy($id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);
        $rescueRequest->delete();

        return redirect()->back()->with('success', 'Rescue request deleted successfully');
    }

    /**
     * Update status by rescue code
     *
     * @param Request $request
     * @param string $code
     * @return void
     */
    public function updateStatus(Request $request, $code)
    {
        $data = $request->only('status', 'assigned_rescuer');

        $validator = Validator::make($data, [
            'status' => 'required|string|in:pending,assigned,in_progress,rescued,safe,completed,cancelled',
            'assigned_rescuer' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->with('error', $validator->errors()->first());
        }

        $rescueRequest = RescueRequest::where('rescue_code', $code)->firstOrFail();
        $rescueRequest->update($data);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully',
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        return redirect()->back()->with('success', 'Rescue request status updated successfully');
    }

    /**
     * Mark rescue request as safe
     *
     * @param int $id
     * @return void
     */
    public function markSafe($id)
    {
        $rescueRequest = RescueRequest::findOrFail($id);
        $rescueRequest->update(['status' => 'safe']);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Marked as safe successfully',
                'data' => $rescueRequest->load(['building', 'floor', 'room', 'requester', 'rescuer'])
            ]);
        }

        return redirect()->back()->with('success', 'Rescue request marked as safe');
    }

    /**
     * Generate unique rescue code
     *
     * @return string
     */
    private function generateUniqueRescueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (RescueRequest::where('rescue_code', $code)->exists());

        return $code;
    }
}
