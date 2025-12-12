<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'username' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            // PinPointMe seeders
            UserSeeder::class,
            BuildingStructureSeeder::class, // Now uses GD 1, GD 2, GD 3
            RescueRequestSeeder::class,
            ConversationMessagingSeeder::class,
            AuditTrailSeeder::class,
        ]);
    }
}
