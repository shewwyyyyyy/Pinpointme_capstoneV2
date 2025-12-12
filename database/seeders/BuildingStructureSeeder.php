<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Room;

class BuildingStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $structures = [
            [
                'name' => 'GD 1',
                'floors' => [
                    [
                        'floor_name' => 'Basement Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => 'Ground Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => '2nd Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => '3rd Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => '4th Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => '5th Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => '6th Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => '7th Floor',
                        'rooms' => [
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
                        ]
                    ],
                    [
                        'floor_name' => '8th Floor',
                        'rooms' => [
                            'Skyline',
                            "Men's Room",
                            'Ladies Room',
                            'Prop',
                            'PE Storage',
                            'Electrical Room',
                        ]
                    ],
                ]
            ],
            [
                'name' => 'GD 2',
                'floors' => [
                    ['floor_name' => 'Ground Floor', 'rooms' => ['Lobby', 'Security Office', 'First Aid Station']],
                    ['floor_name' => 'First Floor', 'rooms' => ['Chemistry Lab 1', 'Chemistry Lab 2', 'Preparation Room']],
                    ['floor_name' => 'Second Floor', 'rooms' => ['Physics Lab 1', 'Physics Lab 2', 'Server Room']],
                ]
            ],
            [
                'name' => 'GD 3',
                'floors' => [
                    ['floor_name' => 'Ground Floor', 'rooms' => ['Reception', 'Cafeteria', 'Auditorium']],
                    ['floor_name' => 'First Floor', 'rooms' => ['Dean Office', 'Registrar', 'Finance Office']],
                ]
            ],
            // Removed Dormitory A - keeping only GD 1, GD 2, GD 3
            /*[
                'name' => 'Dormitory A',
                'floors' => [
                    ['floor_name' => 'Ground Floor', 'rooms' => ['Dorm A101', 'Dorm A102', 'Dorm A103']],
                    ['floor_name' => 'First Floor', 'rooms' => ['Dorm A201', 'Dorm A202', 'Dorm A203']],
                ]
            ]*/
        ];

        foreach ($structures as $bData) {
            $building = Building::firstOrCreate(['name' => $bData['name']], []);
            foreach ($bData['floors'] as $fData) {
                $floor = Floor::firstOrCreate([
                    'building_id' => $building->id,
                    'floor_name' => $fData['floor_name'],
                ], []);
                foreach ($fData['rooms'] as $roomName) {
                    Room::firstOrCreate([
                        'floor_id' => $floor->id,
                        'room_name' => $roomName,
                    ], []);
                }
            }
        }
    }
}
