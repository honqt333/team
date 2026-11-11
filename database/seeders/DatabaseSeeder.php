<?php

declare(strict_types=1);

namespace Database\Seeders;

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

        // 2b. Create default admin user for /system/* panel (admin guard)
        $this->call(AdminUserSeeder::class);

        // Technical data and communication templates are seeded by InitialSystemSeeder.

        $this->call(NationalitiesSeeder::class);
    }
}
