<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\RescueRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ConversationController extends Controller
{
    /**
     * Display conversations for a user (API)
     */
    public function index(Request $request)
    {
        // Check if API request
        if ($request->expectsJson() || $request->is('api/*')) {
            $userId = $request->query('user_id');
            
            if (!$userId) {
                return response()->json(['error' => 'user_id is required'], 400);
            }

            $conversations = Conversation::whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->with([
                'participants.user:id,first_name,last_name,email,profile_picture',
                'rescueRequest:id,conversation_id,rescue_code,status,urgency_level,user_id,assigned_rescuer',
                'rescueRequest.requester:id,first_name,last_name,email,profile_picture',
                'rescueRequest.rescuer:id,first_name,last_name,email,profile_picture'
            ])
            ->orderBy('updated_at', 'desc')
            ->get();

            // Transform with last_message and unread_count for current user
            $conversations->transform(function ($conversation) use ($userId) {
                $participant = $conversation->participants->firstWhere('user_id', $userId);
                $conversation->unread_count = $participant?->unread_count ?? 0;
                return $conversation;
            });

            return response()->json(['data' => $conversations]);
        }

        // Inertia render for web
        return Inertia::render('System/Conversations', [
            'can' => []
        ]);
    }

    /**
     * Store Conversation - creates a conversation for a rescue request
     */
    public function store(Request $request)
    {
        // API: Create conversation for rescue request
        if ($request->expectsJson() || $request->is('api/*')) {
            $data = $request->validate([
                'rescue_request_id' => 'required|exists:rescue_requests,id',
            ]);

            $rescueRequest = RescueRequest::with(['requester', 'rescuer'])->findOrFail($data['rescue_request_id']);

            // Check if rescuer is assigned
            if (!$rescueRequest->assigned_rescuer) {
                return response()->json([
                    'error' => 'Cannot create conversation without an assigned rescuer'
                ], 400);
            }

            // Check if conversation already exists
            if ($rescueRequest->conversation_id) {
                $conversation = Conversation::with(['participants.user', 'rescueRequest'])->find($rescueRequest->conversation_id);
                return response()->json(['data' => $conversation]);
            }

            // Create new conversation
            $result = DB::transaction(function () use ($rescueRequest) {
                $conversation = Conversation::create([]);

                // Add requester (user)
                if ($rescueRequest->user_id) {
                    $conversation->participants()->create([
                        'user_id' => $rescueRequest->user_id,
                        'participant_type' => 'user',
                        'unread_count' => 0
                    ]);
                }

                // Add rescuer
                $conversation->participants()->create([
                    'user_id' => $rescueRequest->assigned_rescuer,
                    'participant_type' => 'rescuer',
                    'unread_count' => 0
                ]);

                // Link conversation to rescue request
                $rescueRequest->update(['conversation_id' => $conversation->id]);

                return $conversation->load(['participants.user', 'rescueRequest']);
            });

            return response()->json(['data' => $result], 201);
        }

        // Original web behavior
        $data = $request->validate([
            'participants' => 'required|array|min:2',
            'participants.*.user_id' => 'required|exists:users,id',
            'participants.*.participant_type' => 'nullable|string|in:user,rescuer,admin'
        ]);

        $result = DB::transaction(function () use ($data) {
            $conversation = Conversation::create([]);

            foreach ($data['participants'] as $participant) {
                $conversation->participants()->create([
                    'user_id' => $participant['user_id'],
                    'participant_type' => $participant['participant_type'] ?? 'user',
                    'unread_count' => 0
                ]);
            }

            return $conversation;
        });

        return redirect()->back()->with('success', 'Conversation created successfully');
    }

    /**
     * Get or create conversation for a rescue request
     */
    public function getOrCreateForRescue(Request $request, $rescueRequestId)
    {
        $rescueRequest = RescueRequest::with(['requester', 'rescuer'])->findOrFail($rescueRequestId);

        // Check if rescuer is assigned
        if (!$rescueRequest->assigned_rescuer && !$rescueRequest->rescuer_id) {
            return response()->json([
                'error' => 'Cannot create conversation without an assigned rescuer',
                'has_rescuer' => false
            ], 400);
        }

        // Return existing conversation if exists
        if ($rescueRequest->conversation_id) {
            $conversation = Conversation::with([
                'participants.user:id,first_name,last_name,email,profile_picture,role',
                'rescueRequest.requester:id,first_name,last_name,email,profile_picture',
                'rescueRequest.rescuer:id,first_name,last_name,email,profile_picture',
                'rescueRequest.room',
                'rescueRequest.floor',
                'rescueRequest.building'
            ])->find($rescueRequest->conversation_id);
            
            return response()->json([
                'data' => $conversation,
                'created' => false
            ]);
        }

        // Create new conversation
        $conversation = DB::transaction(function () use ($rescueRequest) {
            $conversation = Conversation::create([]);

            // Add requester (user)
            if ($rescueRequest->user_id) {
                $conversation->participants()->create([
                    'user_id' => $rescueRequest->user_id,
                    'participant_type' => 'user',
                    'unread_count' => 0
                ]);
            }

            // Add rescuer (use rescuer_id or assigned_rescuer)
            $rescuerId = $rescueRequest->rescuer_id ?? $rescueRequest->assigned_rescuer;
            if ($rescuerId) {
                $conversation->participants()->create([
                    'user_id' => $rescuerId,
                    'participant_type' => 'rescuer',
                    'unread_count' => 0
                ]);
            }

            // Link conversation to rescue request
            $rescueRequest->update(['conversation_id' => $conversation->id]);

            return $conversation->load([
                'participants.user:id,first_name,last_name,email,profile_picture,role',
                'rescueRequest.requester:id,first_name,last_name,email,profile_picture',
                'rescueRequest.rescuer:id,first_name,last_name,email,profile_picture',
                'rescueRequest.room',
                'rescueRequest.floor',
                'rescueRequest.building'
            ]);
        });

        return response()->json([
            'data' => $conversation,
            'created' => true
        ], 201);
    }

    /**
     * Show conversation (API)
     */
    public function show(Request $request, $id)
    {
        $conversation = Conversation::with([
            'participants.user:id,first_name,last_name,email,profile_picture,role',
            'rescueRequest:id,conversation_id,rescue_code,status,urgency_level,user_id,assigned_rescuer,building_id,floor_id,room_id',
            'rescueRequest.requester:id,first_name,last_name,email,profile_picture',
            'rescueRequest.rescuer:id,first_name,last_name,email,profile_picture',
            'rescueRequest.building:id,name',
            'rescueRequest.floor:id,floor_name',
            'rescueRequest.room:id,room_name'
        ])->findOrFail($id);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $conversation]);
        }

        return Inertia::render('System/ConversationDetail', [
            'conversation' => $conversation,
            'can' => []
        ]);
    }

    /**
     * Add participant to conversation
     */
    public function addParticipant(Request $request, $id)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'participant_type' => 'nullable|string|in:user,rescuer,admin'
        ]);

        $conversation = Conversation::findOrFail($id);

        $existing = $conversation->participants()->where('user_id', $data['user_id'])->first();
        if ($existing) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'User already in conversation'], 400);
            }
            return redirect()->back()->with('error', 'User already in conversation');
        }

        $participant = $conversation->participants()->create([
            'user_id' => $data['user_id'],
            'participant_type' => $data['participant_type'] ?? 'user',
            'unread_count' => 0
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $participant->load('user')]);
        }

        return redirect()->back()->with('success', 'Participant added successfully');
    }

    /**
     * Mark conversation as read
     */
    public function markRead(Request $request, $id)
    {
        $userId = $request->input('user_id') ?? $request->query('user_id');
        
        if (!$userId) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'user_id is required'], 400);
            }
            return redirect()->back()->with('error', 'user_id is required');
        }

        $conversation = Conversation::findOrFail($id);
        
        // Reset unread count for this user
        $conversation->participants()
            ->where('user_id', $userId)
            ->update(['unread_count' => 0]);

        // Update all messages NOT sent by this user to 'read' status
        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->where('status', '!=', 'read')
            ->update(['status' => 'read']);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Marked as read');
    }

    /**
     * Delete conversation
     */
    public function destroy(Request $request, $id)
    {
        $conversation = Conversation::findOrFail($id);

        DB::transaction(function () use ($conversation) {
            // Update any rescue requests that reference this conversation
            RescueRequest::where('conversation_id', $conversation->id)->update(['conversation_id' => null]);
            
            $conversation->messages()->delete();
            $conversation->participants()->delete();
            $conversation->delete();
        });

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Conversation deleted successfully');
    }
}
