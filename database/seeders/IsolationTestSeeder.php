<?php

namespace Database\Seeders;

use App\Models\Center;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class IsolationTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ========== TENANT 1: الشركة الأولى (موجودة) ==========
        $tenant1 = Tenant::where('slug', 'test-company')->first();
        $center1 = Center::where('slug', 'main')->where('tenant_id', $tenant1->id)->first();
        $user1 = User::where('email', 'admin@test.com')->first();

        // إضافة عميل ومركبة وأمر عمل للمركز الأول
        $customer1 = Customer::firstOrCreate(
            ['phone' => '0501111111', 'center_id' => $center1->id],
            [
                'tenant_id' => $tenant1->id,
                'center_id' => $center1->id,
                'name' => 'أحمد محمد - عميل المركز الأول',
                'type' => 'individual',
            ]
        );

        $vehicle1 = Vehicle::firstOrCreate(
            ['plate_number' => 'أ ب ج 1234', 'center_id' => $center1->id],
            [
                'tenant_id' => $tenant1->id,
                'center_id' => $center1->id,
                'customer_id' => $customer1->id,
                'make_other' => 'Toyota',
                'model_other' => 'Camry',
                'year' => 2022,
            ]
        );

        $workOrder1 = WorkOrder::firstOrCreate(
            ['code' => 'WO-000001', 'center_id' => $center1->id],
            [
                'tenant_id' => $tenant1->id,
                'center_id' => $center1->id,
                'customer_id' => $customer1->id,
                'vehicle_id' => $vehicle1->id,
                'status' => 'open',
                'notes' => 'أمر عمل تجريبي للمركز الأول - يجب أن لا يظهر للمركز الثاني',
                'opened_at' => now(),
            ]
        );

        // ========== TENANT 2: الشركة الثانية (جديدة) ==========
        $tenant2 = Tenant::firstOrCreate(
            ['slug' => 'another-company'],
            ['name' => 'شركة أخرى للاختبار']
        );

        $center2 = Center::firstOrCreate(
            ['slug' => 'branch-2', 'tenant_id' => $tenant2->id],
            [
                'tenant_id' => $tenant2->id,
                'name' => 'الفرع الثاني - شركة أخرى',
                'is_active' => true,
            ]
        );

        // إنشاء مستخدم للشركة الثانية
        $user2 = User::firstOrCreate(
            ['email' => 'admin2@test.com'],
            [
                'name' => 'مدير الشركة الثانية',
                'tenant_id' => $tenant2->id,
                'current_center_id' => $center2->id,
                'password' => bcrypt('password'),
            ]
        );

        // ربط المستخدم بالمركز
        if (!$user2->centers()->where('center_id', $center2->id)->exists()) {
            $user2->centers()->attach($center2->id, ['tenant_id' => $tenant2->id]);
        }

        // إعطاء الصلاحيات
        $permissions = Permission::all();
        foreach ($permissions as $permission) {
            if (!$user2->hasPermissionTo($permission->name)) {
                $user2->givePermissionTo($permission->name);
            }
        }

        // إضافة عميل ومركبة وأمر عمل للمركز الثاني
        $customer2 = Customer::firstOrCreate(
            ['phone' => '0502222222', 'center_id' => $center2->id],
            [
                'tenant_id' => $tenant2->id,
                'center_id' => $center2->id,
                'name' => 'خالد سعد - عميل المركز الثاني',
                'type' => 'individual',
            ]
        );

        $vehicle2 = Vehicle::firstOrCreate(
            ['plate_number' => 'ه و ز 5678', 'center_id' => $center2->id],
            [
                'tenant_id' => $tenant2->id,
                'center_id' => $center2->id,
                'customer_id' => $customer2->id,
                'make_other' => 'Honda',
                'model_other' => 'Accord',
                'year' => 2023,
            ]
        );

        $workOrder2 = WorkOrder::firstOrCreate(
            ['code' => 'WO-000001', 'center_id' => $center2->id],
            [
                'tenant_id' => $tenant2->id,
                'center_id' => $center2->id,
                'customer_id' => $customer2->id,
                'vehicle_id' => $vehicle2->id,
                'status' => 'in_progress',
                'notes' => 'أمر عمل تجريبي للمركز الثاني - يجب أن لا يظهر للمركز الأول',
                'opened_at' => now(),
            ]
        );

        $this->command->info('');
        $this->command->info('✅ تم إنشاء بيانات اختبار العزل بنجاح!');
        $this->command->info('');
        $this->command->info('========== المستخدم الأول (الشركة الأولى) ==========');
        $this->command->info('📧 البريد: admin@test.com');
        $this->command->info('🔑 كلمة المرور: password');
        $this->command->info('👤 العميل: أحمد محمد');
        $this->command->info('🚗 المركبة: Toyota Camry (أ ب ج 1234)');
        $this->command->info('');
        $this->command->info('========== المستخدم الثاني (الشركة الثانية) ==========');
        $this->command->info('📧 البريد: admin2@test.com');
        $this->command->info('🔑 كلمة المرور: password');
        $this->command->info('👤 العميل: خالد سعد');
        $this->command->info('🚗 المركبة: Honda Accord (ه و ز 5678)');
        $this->command->info('');
        $this->command->info('🔒 اختبار العزل: سجل بكل حساب وتأكد أنك ترى فقط بيانات شركتك!');
    }
}
