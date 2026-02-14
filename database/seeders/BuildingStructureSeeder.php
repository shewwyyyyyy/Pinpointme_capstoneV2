<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
        // Create buildings with explicit IDs to match CSV data
        $buildings = [
            ['id' => 1, 'name' => 'GD 1'],
            ['id' => 2, 'name' => 'GD 2'],
            ['id' => 3, 'name' => 'GD 3'],
        ];

        foreach ($buildings as $bData) {
            // Check if building exists by ID
            $building = Building::find($bData['id']);
            
            if ($building) {
                // Update existing building
                $building->update(['name' => $bData['name']]);
            } else {
                // Create new building with explicit ID
                \DB::table('buildings')->insert([
                    'id' => $bData['id'],
                    'name' => $bData['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        $this->command->info('Buildings seeded with IDs 1, 2, 3.');
    }
}
