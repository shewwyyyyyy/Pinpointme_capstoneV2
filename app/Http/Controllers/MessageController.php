<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MessageController extends Controller
{
    /**
     * Display messages for a conversation
     */
    public function index(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);
        $messages = $conversation->messages()
            ->with(['sender:id,first_name,last_name,email,profile_picture'])
            ->orderBy('sent_at', 'asc')
            ->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $messages]);
        }

        return Inertia::render('System/Messages', [
            'conversation' => $conversation->load(['participants.user']),
            'messages' => $messages,
            'can' => []
        ]);
    }

    /**
     * Store Message
     */
    public function store(Request $request, $conversationId)
    {
        $conversation = Conversation::findOrFail($conversationId);

        // Get sender_id from request or localStorage user
        $senderId = $request->input('sender_id');
        
        if (!$senderId) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'sender_id is required'], 400);
            }
            return redirect()->back()->with('error', 'sender_id is required');
        }

        // Validate sender is a participant
        $participant = $conversation->participants()->where('user_id', $senderId)->first();
        if (!$participant) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['error' => 'Sender is not a participant'], 403);
            }
            return redirect()->back()->with('error', 'Sender is not a participant');
        }

        $data = $request->validate([
            'content' => 'required_without:file|string|nullable',
            'file' => 'nullable|file|max:10240',
            'message_type' => 'nullable|string|in:text,audio,image,location'
        ]);

        $attachmentMeta = [
            'attachment_url' => null,
            'attachment_type' => null,
            'attachment_name' => null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('conversation_attachments/' . $conversationId, 'public');
            $mime = $file->getMimeType();
            
            $attachmentMeta = [
                'attachment_url' => Storage::url($path),
                'attachment_type' => $mime,
                'attachment_name' => $file->getClientOriginalName(),
            ];

            if (!$data['content']) {
                $data['content'] = '[Attachment]';
            }
        }

        // Handle audio file upload
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $path = $file->store('conversation_audio/' . $conversationId, 'public');
            $mime = $file->getMimeType();
            
            $attachmentMeta = [
                'attachment_url' => Storage::url($path),
                'attachment_type' => $mime,
                'attachment_name' => $file->getClientOriginalName(),
            ];

            $data['content'] = $data['content'] ?? '🎤 Voice message';
        }

        $sender = User::find($senderId);
        $senderName = $sender ? trim($sender->first_name . ' ' . $sender->last_name) : 'Unknown';

        $message = $conversation->messages()->create(array_merge([
            'sender_id' => $senderId,
            'content' => $data['content'],
            'sender_name' => $senderName,
            'status' => 'sent',
            'sent_at' => now(),
        ], $attachmentMeta));

        // Update conversation's last_message
        $conversation->update([
            'last_message' => [
                'content' => $message->content,
                'sender_id' => $message->sender_id,
                'sender_name' => $senderName,
                'timestamp' => $message->sent_at?->toIso8601String(),
            ],
            'updated_at' => now(),
        ]);

        // Increment unread count for other participants
        $conversation->participants()
            ->where('user_id', '!=', $senderId)
            ->increment('unread_count');

        $message->load('sender:id,first_name,last_name,email,profile_picture');

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $message], 201);
        }

        return redirect()->back()->with('success', 'Message sent successfully');
    }

    /**
     * Show a single message
     */
    public function show(Request $request, $id)
    {
        $message = Message::with('sender:id,first_name,last_name,email,profile_picture')->findOrFail($id);
        
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $message]);
        }

        return response()->json(['data' => $message]);
    }

    /**
     * Update a message
     */
    public function update(Request $request, $id)
    {
        $message = Message::findOrFail($id);

        $data = $request->validate([
            'content' => 'required|string',
        ]);

        $message->update([
            'content' => $data['content'],
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['data' => $message]);
        }

        return redirect()->back()->with('success', 'Message updated successfully');
    }

    /**
     * Delete message
     */
    public function destroy(Request $request, $id)
    {
        $message = Message::findOrFail($id);
        
        $message->reads()->delete();
        $message->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Message deleted successfully');
    }
}
