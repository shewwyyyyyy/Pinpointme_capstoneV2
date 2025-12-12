<?php

namespace Database\Seeders;

use App\Helpers\Helper;
use App\Models\Profile;
use App\Models\ProfileUserGroup;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@mail.com',
                'password' => bcrypt('admin'),
                'isAdmin' => true,
                'status' => 'active',
                'first_name' => 'Admin',
                'last_name' => 'User',
                'is_able_to_login' => true,
                'position' => 'Manager',
                'meal_entitlement' => null,
                'start_date' => null,
                'end_date' => null,
            ],
        ];

        foreach ($users as $user) {
            $createdUser = User::create([
                'username' => $user['username'],
                'email' => $user['email'],
                'password' => $user['password'],
                'isAdmin' => $user['isAdmin'],
                'status' => $user['status'],
                'is_able_to_login' => $user['is_able_to_login'],

            ]);

            Profile::create([
                'user_id' => $createdUser->id,
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'property_id' => 1,
                'department_id' => 40,
                'location_id' => 1,
                'unique_identifier' => 12345,
                'position' => $user['position'],
                'meal_entitlement' => $user['meal_entitlement'],
                'start_date' => $user['start_date'],
                'end_date' => $user['end_date'],
            ]);
        }
    }
}
