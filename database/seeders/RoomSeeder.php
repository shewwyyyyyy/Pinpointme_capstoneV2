<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roomData = [
            'GD 1' => [
                'Basement Floor' => [
                    'Prayer Room',
                    'Electrical Room',
                    'Hyflex 1',
                    'Hyflex 2',
                    'Tags Security',
                    'Tags Housekeeping',
                    'Room GD 101',
                    'Room GD 102',
                    'Room GD 103',
                    'Room GD 104',
                    'Room GD 105',
                    'Room GD 106',
                    'Room GD 107',
                    'Room GD 108',
                    'Room GD 109',
                ],
                'Ground Floor' => [
                    'Information Center',
                    'Registrar',
                    'Accounting',
                    'Cashier',
                    'Guidance Office',
                    'Campus Ministry',
                    'CCSC',
                    'Student Council',
                    'Publication',
                    'Accreditation Room',
                    'Electrical Room',
                    'Waiting Area',
                    'Control Room',
                ],
                '2nd Floor' => [
                    'Chemistry Laboratory 1',
                    'Chemistry Laboratory 2',
                    'Chemical Stock Room',
                    'General Science Laboratory 1',
                    'General Science Laboratory 2',
                    'General Science Storage Room',
                    'Cell and Molecular Laboratory',
                    'Biology Laboratory',
                    'Microbiology Laboratory',
                    'Analytical Chemistry Laboratory',
                    'Research Laboratory',
                    'Electrical Room',
                    'Canopy',
                ],
                '3rd Floor' => [
                    'Library',
                    'Technical Section',
                    'Computer Section',
                    'Media Resource Centre',
                    'Reading Area',
                    'Books',
                    'Circulation Counter',
                    'Baggage Counter',
                    'Collaboration',
                    'Graduate Studies',
                    'Electrical Room',
                    'Canopy',
                ],
                '4th Floor' => [
                    'Basic Kitchen Laboratory',
                    'Workskills Kitchen',
                    'SDCA Culinary',
                    'Demo Theater',
                    'Travel',
                    "Luigi's Bar",
                    'Bar',
                    'Front Office',
                    'Suite Room',
                    'Laboratory',
                    'Dean',
                    'Coordinator',
                    'Faculty',
                    "Program Chair's Desk",
                    'Laundry',
                    'Storage Room',
                    'Electrical Room',
                    'Office BA, TM, HM',
                    'Room',
                ],
                '5th Floor' => [
                    'Computer Laboratory 1',
                    'Computer Lab 2 GD 502',
                    'IMAC Laboratory 1 GD 508',
                    'IMAC Laboratory 2',
                    'Server Room',
                    'Incubation Room',
                    'Ride Office',
                    'Studio Theater',
                    'Travel Bureau Simulation Room',
                    'MMA Equipment Room',
                    'Accountancy Simulation Room',
                    'Air Bus Simulation',
                    'SCMCS',
                    'Electrical Room',
                    'Laboratory',
                ],
                '6th Floor' => [
                    'Medtech Laboratory',
                    'Conference Lecture Room',
                    'Language Studies Center',
                    'Room GD 601',
                    'Room GD 602',
                    'Room GD 603',
                    'Room GD 604',
                    'Room GD 605',
                    'Room GD 606',
                    'Room GD 607',
                    'Room GD 608',
                    'Room GD 609',
                    'Electrical Room',
                    'School of Graduate Studies',
                ],
                '7th Floor' => [
                    'Psychology Laboratory',
                    'Pharmacy Laboratory 1',
                    'Pharmacy Laboratory 2',
                    'Pharmacy Production',
                    'Pharmacy Instrumentation Room',
                    'Reagent',
                    'Medtech Laboratory 1',
                    'Medtech Laboratory 2',
                    'SMLS Faculty Room',
                    "SMLS Dean's Office",
                    'Room GD 708',
                    'Room 113',
                    'Electrical Room',
                ],
                '8th Floor' => [
                    'Skyline',
                    "Men's Room",
                    'Ladies Room',
                    'Prop',
                    'PE Storage',
                    'Electrical Room',
                ],
            ],
            'GD 2' => [
                'Ground Floor' => ['Lobby', 'Security Office', 'First Aid Station'],
                'First Floor' => ['Chemistry Lab 1', 'Chemistry Lab 2', 'Preparation Room'],
                'Second Floor' => ['Physics Lab 1', 'Physics Lab 2', 'Server Room'],
            ],
            'GD 3' => [
                'Ground Floor' => ['Reception', 'Cafeteria', 'Auditorium'],
                'First Floor' => ['Dean Office', 'Registrar', 'Finance Office'],
            ],
        ];

        foreach ($roomData as $buildingName => $floors) {
            $building = Building::where('name', $buildingName)->first();

            if (!$building) {
                continue;
            }

            foreach ($floors as $floorName => $rooms) {
                $floor = Floor::where('building_id', $building->id)
                    ->where('floor_name', $floorName)
                    ->first();

                if (!$floor) {
                    continue;
                }

                foreach ($rooms as $roomName) {
                    Room::firstOrCreate([
                        'floor_id' => $floor->id,
                        'room_name' => $roomName,
                    ], []);
                }
            }
        }
    }
}