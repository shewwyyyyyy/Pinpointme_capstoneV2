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
        // Only create buildings - floors and rooms come from CSV seeders
        $buildings = [
            ['name' => 'GD 1'],
            ['name' => 'GD 2'],
            ['name' => 'GD 3'],
        ];

        foreach ($buildings as $bData) {
            Building::firstOrCreate(['name' => $bData['name']], []);
        }
        
        $this->command->info('Buildings seeded. Run FloorSeeder and RoomSeeder for floors and rooms.');
    }
}
