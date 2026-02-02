<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = storage_path('imports/rooms.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found: {$csvPath}");
            return;
        }

        $file = fopen($csvPath, 'r');

        // Skip header row
        $header = fgetcsv($file, 0, ';');

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            // Map CSV columns: id, floor_id, room_name, file, created_at, updated_at, deleted_at
            $data = array_combine($header, $row);

            // Skip soft-deleted records
            if (!empty($data['deleted_at']) && $data['deleted_at'] !== 'NULL') {
                continue;
            }

            Room::firstOrCreate([
                'floor_id' => $data['floor_id'],
                'room_name' => $data['room_name'],
            ], [
                'file' => $data['file'] !== 'NULL' ? $data['file'] : null,
            ]);
        }

        fclose($file);

        $this->command->info('Rooms seeded from CSV successfully.');
    }
}