<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\MealSchedule;
use App\Models\MealScheduleItem;
use App\Models\Profile;
use App\Models\ProfileMealSchedule;
use App\Models\PropertyMealSchedule;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfilePropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $profiles =
            [
                [
                    'username' => 'astoriaplaza',
                    'email' => 'astoriaplaza@example.com',
                    'is_able_to_login' => 1,
                    'first_name' => 'Astoria',
                    'last_name' => 'Plaza',
                    'unique_identifier' => 1,
                    'position' => '',
                    'department_id' => 40,
                    'property_id' => 1,
                    'location_id' => 1,
                ],
            ];

        foreach ($profiles as $profile) {
            $user = User::create([
                'username' => $profile['username'],
                'email' => $profile['email'],
                'password' => bcrypt($profile['username']),
                'isAdmin' => false,
                'is_able_to_login' => true
            ]);

            $profile = Profile::create([
                'first_name' => $profile['first_name'] ?? null,
                'middle_name' => $profile['middle_name'] ?? null,
                'last_name' => $profile['last_name'] ?? null,
                'unique_identifier' => $profile['unique_identifier'] ?? null,
                'position' => $profile['position'] ?? null,
                'property_id' => $profile['property_id'] ?? 1,
                'department_id' => $profile['department_id'] ?? 1,
                'location_id' => $profile['location_id'] ?? 1,
                'user_id' => $user->id ?? null,
            ]);
        }
    }
}
