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

        // 3. Create Technical Data (Cars, Colors, Services, Units, HR Metadata)
        $this->call(MetadataSeeder::class);
        $this->call(ServiceSeeder::class);
        $this->call(VehicleConditionSeeder::class);

        // 4. Create Communication Templates (email/sms)
        // Without these, /system/communication/templates is empty and
        // notifications fall back to hard-coded HTML in their notifier.
        $this->call(CommunicationTemplateSeeder::class);

        // 5. Create Test Data (Optional, but useful for now)
        $this->call(TestDataSeeder::class);

        // 6. Keep Nationalities
        $this->call(NationalitiesSeeder::class);
    }
}
