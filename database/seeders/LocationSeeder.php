<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $location = [
            [
                'name' => 'APZ-Sadang',
                'code' => 'apz-sadang',
                'description' => '',
                'property_id' => 1

            ],
            [
                'name' => 'AC3-Sadang',
                'code' => 'ac3-sadang',
                'description' => '',
                'property_id' => 2

            ],
        ];

        foreach ($location as $location) {
            Location::create($location);
        }
    }
}
