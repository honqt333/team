<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Roles and Permissions first
        $this->call(RolesSeeder::class);

        // 2. Create Initial System Admin (Production)
        $this->call(InitialSystemSeeder::class);

        // 3. Create Test Data (Optional, but useful for now)
        $this->call(TestDataSeeder::class);

        // 3. Keep Nationalities
        $this->call(NationalitiesSeeder::class);
    }
}
