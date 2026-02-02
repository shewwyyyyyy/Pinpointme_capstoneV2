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
        $csvPath = storage_path('imports/buildings.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }

        $file = fopen($csvPath, 'r');

        // Skip header row
        $header = fgetcsv($file, 0, ';');

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            // Map CSV columns: id, name, created_at, updated_at, deleted_at
            $data = array_combine($header, $row);

            // Skip soft-deleted records
            if (!empty($data['deleted_at']) && $data['deleted_at'] !== 'NULL') {
                continue;
            }

            Building::firstOrCreate(['name' => $data['name']], []);
        }

        fclose($file);

        $this->command->info('Buildings seeded from CSV successfully.');
    }
}
