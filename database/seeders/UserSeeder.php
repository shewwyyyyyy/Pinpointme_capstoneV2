<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('sdca2025');

        $users = [
            [
                'username' => 'alice.student',
                'first_name' => 'Alice',
                'last_name' => 'Anderson',
                'email' => 'alice.student@example.com',
                'role' => 'student',
                'isAdmin' => false,
                'is_able_to_login' => true,
            ],
            [
                'username' => 'bob.student',
                'first_name' => 'Bob',
                'last_name' => 'Brown',
                'email' => 'bob.student@example.com',
                'role' => 'student',
                'isAdmin' => false,
                'is_able_to_login' => true,
            ],
            [
                'username' => 'rita.rescuer',
                'first_name' => 'Rita',
                'last_name' => 'Rescuer',
                'email' => 'rita.rescuer@example.com',
                'role' => 'rescuer',
                'isAdmin' => false,
                'is_able_to_login' => true,
            ],
            [
                'username' => 'ryan.rescuer',
                'first_name' => 'Ryan',
                'last_name' => 'Responder',
                'email' => 'ryan.rescuer@example.com',
                'role' => 'rescuer',
                'isAdmin' => false,
                'is_able_to_login' => true,
            ],
            [
                'username' => 'admin1',
                'first_name' => 'Adam',
                'last_name' => 'Admin',
                'email' => 'admin1@example.com',
                'role' => 'admin',
                'isAdmin' => true,
                'is_able_to_login' => true,
            ],
            [
                'username' => 'admin2',
                'first_name' => 'Ava',
                'last_name' => 'Admin',
                'email' => 'admin2@example.com',
                'role' => 'admin',
                'isAdmin' => true,
                'is_able_to_login' => true,
            ],
        ];

        foreach ($users as $u) {
            // Rescuers should have 'available' status, others have 'active'
            $status = ($u['role'] === 'rescuer') ? 'available' : 'active';
            
            User::updateOrCreate(
                ['email' => $u['email']],
                [
                    'username' => $u['username'],
                    'first_name' => $u['first_name'],
                    'last_name' => $u['last_name'],
                    'password' => $password,
                    'role' => $u['role'],
                    'status' => $status,
                    'isAdmin' => $u['isAdmin'],
                    'is_able_to_login' => $u['is_able_to_login'],
                    'must_change_password' => false,
                    'require_otp' => false,
                ]
            );
        }
    }
}


# TODO 
