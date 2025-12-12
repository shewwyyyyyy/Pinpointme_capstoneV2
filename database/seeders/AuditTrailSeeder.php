<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AuditTrail;
use App\Models\User;

class AuditTrailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $rescuer = User::where('role', 'rescuer')->first();
        $student = User::where('role', 'student')->first();

        if (!$admin || !$rescuer || !$student) {
            return;
        }

        $entries = [
            [
                'action' => 'user_created',
                'initiator' => $admin->email,
                'initiator_role' => 'admin',
                'account_updated' => $student->email,
                'details' => 'Admin created student account',
            ],
            [
                'action' => 'rescue_assigned',
                'initiator' => $admin->email,
                'initiator_role' => 'admin',
                'account_updated' => $rescuer->email,
                'details' => 'Admin assigned rescuer to request',
            ],
            [
                'action' => 'rescue_status_update',
                'initiator' => $rescuer->email,
                'initiator_role' => 'rescuer',
                'account_updated' => $student->email,
                'details' => 'Rescuer updated status to en_route',
            ],
        ];

        foreach ($entries as $e) {
            AuditTrail::create($e);
        }
    }
}
