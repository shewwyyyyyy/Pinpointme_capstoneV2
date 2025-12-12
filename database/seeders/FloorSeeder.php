<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;
use App\Models\Floor;

class FloorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buildingFloors = [
            'GD 1' => [
                'Basement Floor',
                'Ground Floor',
                '2nd Floor',
                '3rd Floor',
                '4th Floor',
                '5th Floor',
                '6th Floor',
                '7th Floor',
                '8th Floor',
            ],
            'GD 2' => [
                'Ground Floor',
                'First Floor',
                'Second Floor',
            ],
            'GD 3' => [
                'Ground Floor',
                'First Floor',
            ],
        ];

        foreach ($buildingFloors as $buildingName => $floors) {
            $building = Building::where('name', $buildingName)->first();

            if ($building) {
                foreach ($floors as $floorName) {
                    Floor::firstOrCreate([
                        'building_id' => $building->id,
                        'floor_name' => $floorName,
                    ], []);
                }
            }
        }
    }
}
