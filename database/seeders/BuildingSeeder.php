<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Building;

class BuildingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $buildings = [
            'GD 1',
            'GD 2',
            'GD 3',
            
        ];

        foreach ($buildings as $name) {
            Building::firstOrCreate(['name' => $name], []);
        }
    }
}
