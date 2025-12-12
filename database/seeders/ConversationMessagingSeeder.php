<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use Carbon\Carbon;

class ConversationMessagingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        $rescuers = User::where('role', 'rescuer')->get();
        $admins = User::where('role', 'admin')->get();

        if ($students->isEmpty() || $rescuers->isEmpty()) {
            return; // prerequisites not met
        }

        // 1 student + 1 rescuer conversation
        $student = $students->first();
        $rescuer = $rescuers->first();
        $conv1 = Conversation::create();

        ConversationParticipant::create([
            'conversation_id' => $conv1->id,
            'user_id' => $student->id,
            'participant_type' => 'student',
            'unread_count' => 0
        ]);
        ConversationParticipant::create([
            'conversation_id' => $conv1->id,
            'user_id' => $rescuer->id,
            'participant_type' => 'rescuer',
            'unread_count' => 0
        ]);

        $messages1 = [
            ['sender_id' => $student->id, 'content' => 'Hello, I need assistance in the Chemistry Lab 1.', 'sent_at' => Carbon::now()->subMinutes(10)],
            ['sender_id' => $rescuer->id, 'content' => 'Copy that. What is the nature of the emergency?', 'sent_at' => Carbon::now()->subMinutes(8)],
            ['sender_id' => $student->id, 'content' => 'Spill caused fumes, feeling dizzy.', 'sent_at' => Carbon::now()->subMinutes(6)],
        ];

        foreach ($messages1 as $m) {
            $msg = Message::create([
                'conversation_id' => $conv1->id,
                'sender_id' => $m['sender_id'],
                'content' => $m['content'],
                'sent_at' => $m['sent_at'],
                'status' => 'sent',
            ]);
            MessageRead::create(['message_id' => $msg->id, 'user_id' => $student->id, 'read_at' => Carbon::now()]);
            MessageRead::create(['message_id' => $msg->id, 'user_id' => $rescuer->id, 'read_at' => Carbon::now()]);
        }

        // Group conversation: student + rescuer + admin
        if ($admins->isNotEmpty()) {
            $admin = $admins->first();
            $conv2 = Conversation::create();
            ConversationParticipant::insert([
                ['conversation_id' => $conv2->id, 'user_id' => $student->id, 'participant_type' => 'student', 'unread_count' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['conversation_id' => $conv2->id, 'user_id' => $rescuer->id, 'participant_type' => 'rescuer', 'unread_count' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['conversation_id' => $conv2->id, 'user_id' => $admin->id, 'participant_type' => 'admin', 'unread_count' => 0, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $messages2 = [
                ['sender_id' => $admin->id, 'content' => 'Admin here. Coordinating response. Stay calm.', 'sent_at' => Carbon::now()->subMinutes(4)],
                ['sender_id' => $rescuer->id, 'content' => 'Approaching the lab now.', 'sent_at' => Carbon::now()->subMinutes(3)],
                ['sender_id' => $student->id, 'content' => 'Thank you, I am sitting near the exit.', 'sent_at' => Carbon::now()->subMinutes(2)],
            ];
            foreach ($messages2 as $m) {
                $msg = Message::create([
                    'conversation_id' => $conv2->id,
                    'sender_id' => $m['sender_id'],
                    'content' => $m['content'],
                    'sent_at' => $m['sent_at'],
                    'status' => 'sent',
                ]);
                MessageRead::insert([
                    ['message_id' => $msg->id, 'user_id' => $student->id, 'read_at' => Carbon::now()],
                    ['message_id' => $msg->id, 'user_id' => $rescuer->id, 'read_at' => Carbon::now()],
                    ['message_id' => $msg->id, 'user_id' => $admin->id, 'read_at' => Carbon::now()],
                ]);
            }
        }
    }
}
