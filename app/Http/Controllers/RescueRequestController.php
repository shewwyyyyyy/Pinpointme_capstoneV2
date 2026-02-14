<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;
use App\Models\RescueRequest;
use App\Services\PushNotificationService;
use App\Services\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        // Check if user must complete profile first
        if (isset($data['user_id'])) {
            $user = \App\Models\User::find($data['user_id']);
            if ($user && $this->userMustUpdateProfile($user)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'must_update_profile' => true,
                        'message' => 'You must complete your profile information before you can submit emergency reports. Please update your personal information, emergency contact, and medical details.'
                    ], 400);
                }
                
                return redirect()->back()->with('error', 'You must complete your profile information before you can submit emergency reports.');
            }
        }

        $data['rescue_code'] = $this->generateUniqueRescueCode();
        $data['status'] = $data['status'] ?? 'pending';

        // Handle media file uploads
        if ($request->hasFile('media_files')) {
            $mediaAttachments = [];
            $files = $request->file('media_files');
            
            foreach ($files as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('rescue_media', $filename, 'public');
                
                $mediaAttachments[] = [
                    'path' => $path,
                    'url' => '/storage/' . $path,
                    'type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image',
                    'mime_type' => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize()
                ];
            }
            
            $data['media_attachments'] = $mediaAttachments;
        }

        // Translate text fields to English for rescuers
        $data = $this->translateTextFields($data);

        $rescueRequest = RescueRequest::create($data);

        // Send push notification to all rescuers
        $this->notifyRescuers($rescueRequest);

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
     * Send push notification to all rescuers about a new rescue request
     *
     * @param RescueRequest $rescueRequest
     * @return void
     */
    protected function notifyRescuers(RescueRequest $rescueRequest): void
    {
        try {
            $pushService = new PushNotificationService();

            // Build the notification payload
            $location = [];
            if ($rescueRequest->building) {
                $location[] = $rescueRequest->building->name ?? 'Building';
            }
            if ($rescueRequest->floor) {
                $location[] = $rescueRequest->floor->name ?? 'Floor';
            }
            if ($rescueRequest->room) {
                $location[] = $rescueRequest->room->name ?? 'Room';
            }
            $locationStr = !empty($location) ? implode(' - ', $location) : 'Unknown Location';

            $urgencyLevel = $rescueRequest->urgency_level ?? 'normal';
            $urgencyPrefix = $urgencyLevel === 'critical' ? '🚨 CRITICAL: ' :
                           ($urgencyLevel === 'high' ? '⚠️ URGENT: ' : '');

            $payload = [
                'title' => $urgencyPrefix . 'New Rescue Request',
                'body' => "Location: {$locationStr}\nCode: {$rescueRequest->rescue_code}",
                'icon' => '/images/logos/pinpointme.png',
                'badge' => '/images/logos/pinpointme.png',
                'tag' => 'rescue-' . $rescueRequest->rescue_code,
                'type' => 'rescue_request',
                'requireInteraction' => true,
                'data' => [
                    'type' => 'rescue_request',
                    'rescue_code' => $rescueRequest->rescue_code,
                    'request_id' => $rescueRequest->id,
                    'urgency_level' => $urgencyLevel,
                    'url' => '/rescuer/dashboard?rescue=' . $rescueRequest->rescue_code,
                ],
                'actions' => [
                    ['action' => 'view', 'title' => 'View Request'],
                    ['action' => 'dismiss', 'title' => 'Dismiss'],
                ],
            ];

            // Send to all rescuers
            $result = $pushService->sendToRole('rescuer', $payload);
            
            \Illuminate\Support\Facades\Log::info('Push notifications sent to rescuers', [
                'rescue_code' => $rescueRequest->rescue_code,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            // Log the error but don't fail the rescue request creation
            \Illuminate\Support\Facades\Log::error('Failed to send push notifications to rescuers', [
                'rescue_code' => $rescueRequest->rescue_code,
                'error' => $e->getMessage()
            ]);
        }
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
            'building_id',
            'floor_id',
            'room_id',
            'description',
            'mobility_status',
            'injuries',
            'urgency_level',
            'additional_info'
        ]);

        $validator = Validator::make($data, [
            'assigned_rescuer' => 'nullable|exists:users,id',
            'status' => 'nullable|string|in:pending,assigned,in_progress,rescued,safe,completed,cancelled',
            'building_id' => 'nullable|exists:buildings,id',
            'floor_id' => 'nullable|exists:floors,id',
            'room_id' => 'nullable|exists:rooms,id',
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
            
            // Check if rescuer is available (not off_duty or unavailable)
            if (isset($data['assigned_rescuer']) && $data['assigned_rescuer'] !== $rescueRequest->assigned_rescuer) {
                $rescuer = \App\Models\User::find($data['assigned_rescuer']);
                if ($rescuer && $rescuer->role === 'rescuer') {
                    // Check if rescuer can accept new requests
                    if (in_array($rescuer->status, ['off_duty', 'unavailable'])) {
                        if ($request->expectsJson() || $request->is('api/*')) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Rescuer is currently ' . str_replace('_', ' ', $rescuer->status) . ' and cannot accept rescue requests.'
                            ], 422);
                        }
                        return redirect()->back()->with('error', 'Rescuer is not available.');
                    }
                    
                    // Check if rescuer already has an active rescue
                    $hasActiveRescue = RescueRequest::where('assigned_rescuer', $rescuer->id)
                        ->whereIn('status', ['assigned', 'in_progress', 'en_route'])
                        ->where('id', '!=', $id)
                        ->exists();
                    
                    if ($hasActiveRescue) {
                        if ($request->expectsJson() || $request->is('api/*')) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Rescuer already has an active rescue assignment.'
                            ], 422);
                        }
                        return redirect()->back()->with('error', 'Rescuer already has an active rescue.');
                    }
                }
            }
            
            // Update timestamp for status changes
            if (isset($data['status']) && $data['status'] !== $rescueRequest->status) {
                $data['updated_at'] = now();
            }
            
            $rescueRequest->update($data);
            
            // Update rescuer's status when rescue request status changes
            if (isset($data['assigned_rescuer'])) {
                $rescuer = \App\Models\User::find($data['assigned_rescuer']);
                if ($rescuer && $rescuer->role === 'rescuer') {
                    // Set rescuer status to 'on_rescue' when assigned
                    $rescuer->update(['status' => 'on_rescue']);
                }
            } elseif (isset($data['status']) && $rescueRequest->assigned_rescuer) {
                $rescuer = \App\Models\User::find($rescueRequest->assigned_rescuer);
                if ($rescuer && $rescuer->role === 'rescuer') {
                    // If rescue is completed, rescued, safe, or cancelled, set rescuer back to available
                    if (in_array($data['status'], ['completed', 'rescued', 'safe', 'cancelled'])) {
                        $rescuer->update(['status' => 'available']);
                    }
                    // If rescue is assigned or in_progress, ensure rescuer is on_rescue
                    elseif (in_array($data['status'], ['assigned', 'in_progress'])) {
                        $rescuer->update(['status' => 'on_rescue']);
                    }
                }
            }
            
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

    /**
     * Check if user must update profile before creating rescue request
     *
     * @param \App\Models\User $user
     * @return bool
     */
    private function userMustUpdateProfile($user): bool
    {
        // Check if user has must_update_profile flag (from registration)
        if (\Schema::hasColumn('users', 'must_update_profile') && $user->must_update_profile) {
            return true;
        }

        // Check if essential profile fields are missing
        $requiredFields = [
            'first_name',
            'last_name',
            'phone',
            'emergency_contact_name',
            'emergency_contact_phone',
            'blood_type'
        ];

        foreach ($requiredFields as $field) {
            if (empty($user->$field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Translate text fields to English for rescuers.
     * Stores originals in original_* columns and overwrites main columns with English.
     */
    private function translateTextFields(array $data): array
    {
        try {
            $translationService = app(TranslationService::class);
            $fieldsToCheck = ['description', 'additional_info', 'injuries'];

            $hasNonEnglish = false;

            foreach ($fieldsToCheck as $field) {
                if (!empty($data[$field]) && is_string($data[$field])) {
                    if (!$translationService->isLikelyEnglish($data[$field])) {
                        $hasNonEnglish = true;
                        break;
                    }
                }
            }

            // Just flag whether the text is non-English; don't auto-translate
            $data['is_translated'] = $hasNonEnglish;
        } catch (\Exception $e) {
            Log::error('Language detection failed during rescue request creation', [
                'error' => $e->getMessage(),
            ]);
        }

        return $data;
    }

    /**
     * Translate a rescue request's text fields on-demand.
     *
     * @param  \App\Models\RescueRequest  $rescueRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function translateRequest(RescueRequest $rescueRequest)
    {
        try {
            $translationService = app(TranslationService::class);
            $fieldsToTranslate = [
                'description'     => 'original_description',
                'additional_info' => 'original_additional_info',
                'injuries'        => 'original_injuries',
            ];

            $translations = [];

            foreach ($fieldsToTranslate as $field => $originalField) {
                if (!empty($rescueRequest->$field) && is_string($rescueRequest->$field)) {
                    if (!$translationService->isLikelyEnglish($rescueRequest->$field)) {
                        // Preserve original and translate
                        $original = $rescueRequest->$field;
                        $translated = $translationService->translateToEnglish($original);
                        
                        $rescueRequest->$originalField = $original;
                        $rescueRequest->$field = $translated;
                        $translations[$field] = $translated;
                    }
                }
            }

            $rescueRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Translation completed',
                'translations' => $translations,
                'data' => $rescueRequest->fresh(['building', 'floor', 'room', 'rescuer', 'requester']),
            ]);
        } catch (\Exception $e) {
            Log::error('On-demand translation failed', [
                'rescue_request_id' => $rescueRequest->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Translation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin: Mark a pending rescue request for force-alert.
     * This sets force_alert = true so that the rescuer dashboard plays an unstoppable ringtone.
     *
     * @param  \App\Models\RescueRequest  $rescueRequest
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceAlert(RescueRequest $rescueRequest)
    {
        // Only allow force-alert on pending requests
        if (!in_array($rescueRequest->status, ['pending'])) {
            return response()->json([
                'success' => false,
                'message' => 'Force alert can only be triggered for pending requests.'
            ], 422);
        }

        $rescueRequest->update([
            'force_alert' => true,
            'force_alert_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Force alert activated. Available rescuers will receive an unstoppable notification.',
            'data' => $rescueRequest->fresh(['building', 'floor', 'room'])
        ]);
    }

    /**
     * Admin: Get pending rescue requests that have been waiting longer than
     * their urgency-based threshold with no rescuer accepting.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingTooLong()
    {
        $requests = RescueRequest::with(['building', 'floor', 'room', 'requester'])
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($request) {
                $urgency = strtolower($request->urgency_level ?? 'medium');
                $thresholds = [
                    'critical' => 10,
                    'high'     => 30,
                    'medium'   => 120,
                    'low'      => 300,
                ];
                $requiredSeconds = $thresholds[$urgency] ?? 120;
                return now()->diffInSeconds($request->created_at) >= $requiredSeconds;
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $requests,
            'count' => $requests->count()
        ]);
    }

    /**
     * Get all rescuer user IDs for FCM notifications
     * Returns only active rescuers with valid status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRescuerIds()
    {
        try {
            $rescuerIds = \App\Models\User::where('role', 'rescuer')
                ->whereIn('status', ['active', 'available'])
                ->pluck('id')
                ->toArray();

            return response()->json([
                'success' => true,
                'rescuer_ids' => $rescuerIds,
                'count' => count($rescuerIds)
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to get rescuer IDs', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve rescuer IDs',
                'rescuer_ids' => []
            ], 500);
        }
    }
}
