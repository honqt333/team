<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the default admin user for the system panel (/system/*).
 * Uses the `admin` guard, separate from the regular web User.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            [
                'name' => 'System Admin',
                'email' => 'admin@khidmh.pro',
                'password' => '11223344',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Support',
                'email' => 'support@khidmh.pro',
                'password' => '11223344',
                'role' => 'support',
            ],
        ];

        foreach ($defaults as $row) {
            AdminUser::firstOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => Hash::make($row['password']),
                    'role' => $row['role'],
                    'is_active' => true,
                    'permissions' => [],
                ]
            );
        }

        $this->command->info('✅ Admin users seeded:');

        foreach ($defaults as $row) {
            $this->command->info("  • {$row['email']} ({$row['role']})");
        }
    }
}
