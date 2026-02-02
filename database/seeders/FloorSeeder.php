<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Floor;

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

            Floor::firstOrCreate([
                'building_id' => $data['building_id'],
                'floor_name' => $data['floor_name'],
            ], [
                'floor_plan_url' => $data['floor_plan_url'] !== 'NULL' ? $data['floor_plan_url'] : null,
                'floor_plan_data' => $data['floor_plan_data'] !== 'NULL' ? $data['floor_plan_data'] : null,
            ]);
        }

        fclose($file);

        $this->command->info('Floors seeded from CSV successfully.');
    }
}
