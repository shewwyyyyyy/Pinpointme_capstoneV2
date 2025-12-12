<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RescueRequest;
use App\Models\User;
use App\Models\Room;
use Illuminate\Support\Str;

class RescueRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $student = User::where('role', 'student')->first();
        $rescuer = User::where('role', 'rescuer')->first();
        $room = Room::with('floor.building')->inRandomOrder()->first();

        if (!$student || !$rescuer || !$room) {
            return;
        }

        $requests = [
            [
                'description' => 'Chemical spill causing strong fumes',
                'mobility_status' => 'limited',
                'injuries' => 'breathing',
                'urgency_level' => 'high',
                'additional_info' => 'Student reports dizziness and mild coughing.',
            ],
            [
                'description' => 'Student fainted in hallway',
                'mobility_status' => 'immobile',
                'injuries' => 'unconscious',
                'urgency_level' => 'critical',
                'additional_info' => 'Pulse present, shallow breathing.',
            ],
            [
                'description' => 'Minor fracture from fall on stairs',
                'mobility_status' => 'limited',
                'injuries' => 'fracture',
                'urgency_level' => 'medium',
                'additional_info' => 'Able to speak, leg pain localized.',
            ],
        ];

        foreach ($requests as $data) {
            $targetRoom = Room::with('floor.building')->inRandomOrder()->first();
            if (!$targetRoom) {
                break;
            }
            RescueRequest::create([
                'rescue_code' => strtoupper(Str::random(6)),
                'assigned_rescuer' => $rescuer->id,
                'user_id' => $student->id,
                'status' => 'open',
                'building_id' => $targetRoom->floor->building->id,
                'floor_id' => $targetRoom->floor->id,
                'room_id' => $targetRoom->id,
                'description' => $data['description'],
                'mobility_status' => $data['mobility_status'],
                'injuries' => $data['injuries'],
                'urgency_level' => $data['urgency_level'],
                'additional_info' => $data['additional_info'],
                'firstName' => $student->first_name ?? 'Student',
                'lastName' => $student->last_name ?? 'User',
            ]);
        }
    }
}
