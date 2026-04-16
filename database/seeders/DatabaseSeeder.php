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

        // 2. Create Test Data (Tenants, Centers, Admin User)
        $this->call(TestDataSeeder::class);

        // 3. Keep Nationalities
        $this->call(NationalitiesSeeder::class);
    }
}
