<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Floor;
use Illuminate\Support\Facades\DB;

class FloorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = storage_path('imports/floors.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }

        $file = fopen($csvPath, 'r');

        // Skip header row
        $header = fgetcsv($file, 0, ';');

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            // Map CSV columns: id, building_id, floor_name, floor_plan_url, floor_plan_data, created_at, updated_at, deleted_at
            $data = array_combine($header, $row);

            // Skip soft-deleted records
            if (!empty($data['deleted_at']) && $data['deleted_at'] !== 'NULL') {
                continue;
            }

            // Check if floor with this ID already exists
            $existing = Floor::find($data['id']);
            
            if ($existing) {
                // Update existing record
                $existing->update([
                    'building_id' => $data['building_id'],
                    'floor_name' => $data['floor_name'],
                    'floor_plan_url' => $data['floor_plan_url'] !== 'NULL' ? $data['floor_plan_url'] : null,
                    'floor_plan_data' => $data['floor_plan_data'] !== 'NULL' ? $data['floor_plan_data'] : null,
                ]);
            } else {
                // Insert with exact CSV ID using raw DB insert
                DB::table('floors')->insert([
                    'id' => $data['id'],
                    'building_id' => $data['building_id'],
                    'floor_name' => $data['floor_name'],
                    'floor_plan_url' => $data['floor_plan_url'] !== 'NULL' ? $data['floor_plan_url'] : null,
                    'floor_plan_data' => $data['floor_plan_data'] !== 'NULL' ? $data['floor_plan_data'] : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        fclose($file);

        $this->command->info('Floors seeded from CSV successfully.');
    }
}
