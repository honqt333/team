<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Billing\Plan;
use App\Models\Billing\Subscription;
use App\Models\Center;
use App\Models\Customer;
use App\Models\Department;
use App\Models\HR\Employee;
use App\Models\HR\JobTitle;
use App\Models\InventoryBalance;
use App\Models\InventoryCategory;
use App\Models\InventoryMove;
use App\Models\InventoryUnit;
use App\Models\Part;
use App\Models\Role;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMake;
use App\Models\Warehouse;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\TenantSetupService;
use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class TestingEnvironmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new LogicException('Demo data can only be seeded in local or testing environments.');
        }

        $registrar = app(PermissionRegistrar::class);
        $previousTeam = $registrar->getPermissionsTeamId();

        try {
            DB::transaction(function (): void {
                foreach (Permissions::all() as $permission) {
                    Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
                }

                if (! Tenant::where('slug', 'active-workshop')->exists()) {
                    $this->seedActiveWorkshop(Plan::where('slug', 'pro')->firstOrFail());
                }

                if (! Tenant::where('slug', 'new-workshop')->exists()) {
                    $this->seedNewWorkshop(Plan::where('slug', 'trial')->firstOrFail());
                }

                if (! Tenant::where('slug', 'expiring-workshop')->exists()) {
                    $this->seedExpiringWorkshop(Plan::where('slug', 'basic')->firstOrFail());
                }

                if (! Tenant::where('slug', 'expired-workshop')->exists()) {
                    $this->seedExpiredWorkshop(Plan::where('slug', 'basic')->firstOrFail());
                }
                app(TenantScenariosSeeder::class)->seedAdditionalLifecycleCases();
            });
        } finally {
            $registrar->setPermissionsTeamId($previousTeam);
            $registrar->forgetCachedPermissions();
        }
    }

    private function seedActiveWorkshop(Plan $plan): void
    {
        $tenant = Tenant::create([
            'name' => 'مركز الخدمة المتقدمة',
            'slug' => 'active-workshop',
            'legal_name' => 'شركة الخدمة المتقدمة لصيانة السيارات',
            'trade_name' => 'Advanced Service Center',
            'phone' => '+966501112233',
            'email' => 'manager.active@example.test',
            'status' => 'active',
            'vat_number' => '300199988800003',
            'cr_number' => '1010887766',
            'invoice_number_format' => 'INV-{CENTER}-{YYYY}-{SEQ}',
            'work_order_number_format' => 'WO-{SEQ}',
            'quote_number_format' => 'QT-{SEQ}',
            'quote_title' => 'تقييم كشف وأسعار',
            'work_order_title' => 'كرت أمر عمل',
            'invoice_title' => 'فاتورة ضريبية',
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'المركز الرئيسي - الرياض',
            'slug' => 'active-main-branch',
            'is_main' => true,
            'is_active' => true,
            'phone' => '0112233445',
            'email' => 'center@active.test',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonths(11),
            'trial_ends_at' => null,
            'price' => $plan->price_monthly * 10,
            'auto_renew' => true,
            'payment_method' => 'card',
        ]);

        $this->setupTenantRolesAndUser(
            $tenant,
            $center,
            'manager.active@example.test',
            'مدير الورشة',
            '11223344'
        );

        $this->seedCustomersAndVehicles($tenant, $center);
        $this->seedTrialCustomers($tenant, $center);
        $this->seedEmployeesAndStaff($tenant, $center);
        $this->seedSuppliers($tenant, $center);
        $this->seedDepartmentsAndServices($tenant, $center);
        $this->seedInventoryAndParts($tenant, $center);
        $this->seedWorkOrdersWithTechnicians($tenant, $center);
    }

    private function seedNewWorkshop(Plan $plan): void
    {
        $tenant = Tenant::create([
            'name' => 'مركز الانطلاقة الجديدة (New Tenant)',
            'slug' => 'new-workshop',
            'legal_name' => 'ورشة الانطلاقة السريعة',
            'trade_name' => 'New Horizon Workshop',
            'phone' => '+966502223344',
            'email' => 'new@carag.test',
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'الفرع الرئيسي',
            'slug' => 'new-main-branch',
            'is_main' => true,
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'billing_cycle' => 'yearly',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
            'ends_at' => now()->addDays(14),
            'price' => 0,
            'auto_renew' => false,
        ]);

        $this->setupTenantRolesAndUser(
            $tenant,
            $center,
            'new@carag.test',
            'مدير الورشة الجديدة',
            '11223344'
        );
    }

    private function seedExpiringWorkshop(Plan $plan): void
    {
        $tenant = Tenant::create([
            'name' => 'مركز الأفق للصيانة (Expiring Soon)',
            'slug' => 'expiring-workshop',
            'legal_name' => 'مركز الأفق المتميز',
            'trade_name' => 'Expiring Soon Auto',
            'phone' => '+966503334455',
            'email' => 'expiring@carag.test',
            'status' => 'active',
            'trial_ends_at' => now()->addDays(3),
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'الفرع الرئيسي',
            'slug' => 'expiring-main-branch',
            'is_main' => true,
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subDays(362),
            'ends_at' => now()->addDays(3),
            'trial_ends_at' => null,
            'price' => $plan->price_yearly,
            'auto_renew' => false,
        ]);

        $this->setupTenantRolesAndUser(
            $tenant,
            $center,
            'expiring@carag.test',
            'مدير ورشة الأفق',
            '11223344'
        );
    }

    private function seedExpiredWorkshop(Plan $plan): void
    {
        $tenant = Tenant::create([
            'name' => 'مركز الدقة القديم (Expired Tenant)',
            'slug' => 'expired-workshop',
            'legal_name' => 'ورشة الدقة القديمة',
            'trade_name' => 'Expired Auto Care',
            'phone' => '+966504445566',
            'email' => 'expired@carag.test',
            'status' => 'readonly',
            'suspension_reason' => 'subscription_expired',
        ]);

        $center = Center::create([
            'tenant_id' => $tenant->id,
            'name' => 'الفرع القديم',
            'slug' => 'expired-main-branch',
            'is_main' => true,
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'expired',
            'billing_cycle' => 'yearly',
            'starts_at' => now()->subDays(390),
            'ends_at' => now()->subDays(25),
            'trial_ends_at' => null,
            'price' => $plan->price_yearly,
            'auto_renew' => false,
        ]);

        $this->setupTenantRolesAndUser(
            $tenant,
            $center,
            'expired@carag.test',
            'مدير الورشة المنتهية',
            '11223344'
        );
    }

    /**
     * Helper to seed roles, lookups, and admin user for a tenant.
     */
    private function setupTenantRolesAndUser(Tenant $tenant, Center $center, string $email, string $name, string $password): User
    {
        $setupService = app(TenantSetupService::class);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $setupService->seedRolesForTenant($tenant->id);
        $setupService->seedDefaultsForTenant($tenant->id);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'tenant_id' => $tenant->id,
            'current_center_id' => $center->id,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $superAdmin = Role::where('name', 'super_admin')->where('tenant_id', $tenant->id)->first();

        if ($superAdmin) {
            $user->assignRole($superAdmin);
        }

        return $user;
    }

    /**
     * Seed customers of all types (individual, company, government) and their vehicles.
     */
    private function seedCustomersAndVehicles(Tenant $tenant, Center $center): void
    {
        // 1. عميل أفراد عادي
        $c1 = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => Customer::TYPE_INDIVIDUAL,
            'name' => 'فهد بن عبدالعزيز العتيبي',
            'phone' => '0501234567',
            'whatsapp' => '0501234567',
            'email' => 'fahad.otaibi@example.com',
            'city' => 'الرياض',
            'district' => 'الياسمين',
            'address_line' => 'شارع أنس بن مالك',
            'notes' => 'عميل دائم - يفضل الزيوت التخليقية',
        ]);

        // 2. عميل أفراد VIP
        $c2 = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => Customer::TYPE_INDIVIDUAL,
            'name' => 'سارة بنت محمد القحطاني',
            'phone' => '0559876543',
            'whatsapp' => '0559876543',
            'email' => 'sara.qahtani@example.com',
            'city' => 'الرياض',
            'district' => 'حطين',
            'address_line' => 'طريق الأمير تركي الأول',
            'notes' => 'عميل VIP - دقة في المواعيد',
        ]);

        // 3. عميل شركات بحد ائتماني وأسطول
        $c3 = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => Customer::TYPE_COMPANY,
            'name' => 'شركة السهم للنقليات والخدمات اللوجستية',
            'contact_name' => 'م. خالد المنصور (مدير الحركة)',
            'phone' => '0541122334',
            'whatsapp' => '0541122334',
            'email' => 'fleet@alsahm-logistics.sa',
            'tax_number' => '300123456700003',
            'credit_limit' => 50000.00,
            'payment_terms_days' => 30,
            'city' => 'الرياض',
            'district' => 'السلي',
            'address_line' => 'المنطقة الصناعية الثانية - مجمع النقليات',
            'notes' => 'أسطول سيارات ديزل ونقل خفيف - الفوترة شهرية',
        ]);

        // 4. عميل جهة حكومية
        $c4 = Customer::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'type' => Customer::TYPE_GOVERNMENT,
            'name' => 'هيئة تطوير المنطقة والمشاريع',
            'contact_name' => 'أ. عبدالرحمن السالم (إدارة الحركة)',
            'phone' => '0563344556',
            'whatsapp' => '0563344556',
            'email' => 'transport@authority.gov.sa',
            'tax_number' => '310987654300003',
            'credit_limit' => 100000.00,
            'payment_terms_days' => 60,
            'city' => 'الرياض',
            'district' => 'الواحة',
            'notes' => 'أوامر شراء معتمدة وسداد بعد التدقيق والمطابقة',
        ]);

        // Look up vehicle makes
        $toyota = VehicleMake::where('name_en', 'Toyota')->orWhere('name_ar', 'تويوتا')->first();
        $hyundai = VehicleMake::where('name_en', 'Hyundai')->orWhere('name_ar', 'هيونداي')->first();
        $mercedes = VehicleMake::where('name_en', 'Mercedes-Benz')->orWhere('name_ar', 'LIKE', '%مرسيدس%')->first();
        $isuzu = VehicleMake::where('name_en', 'Isuzu')->orWhere('name_ar', 'LIKE', '%إيسوزو%')->first();
        $ford = VehicleMake::where('name_en', 'Ford')->orWhere('name_ar', 'LIKE', '%فورد%')->first();
        $lexus = VehicleMake::where('name_en', 'Lexus')->orWhere('name_ar', 'LIKE', '%لكزس%')->first();

        // ------------------ مركبات فهد العتيبي ------------------
        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $c1->id,
            'make_id' => $toyota?->id,
            'make_other' => $toyota ? null : 'تويوتا',
            'model_other' => 'لاندكروزر VXR',
            'year' => 2023,
            'color' => 'أبيض لؤلؤي',
            'plate_number' => 'أ ح د 1234',
            'vin' => 'JTEBU5JR8K5123456',
            'odometer' => 45200,
            'notes' => 'مركبة شخصية بحالة الوكالة',
        ]);

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $c1->id,
            'make_id' => $hyundai?->id,
            'make_other' => $hyundai ? null : 'هيونداي',
            'model_other' => 'سوناتا سمارت',
            'year' => 2022,
            'color' => 'فضي ميتاليك',
            'plate_number' => 'ب ر ك 5678',
            'vin' => 'KMHL34JA5NA654321',
            'odometer' => 62150,
        ]);

        // ------------------ مركبة سارة القحطاني ------------------
        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $c2->id,
            'make_id' => $mercedes?->id,
            'make_other' => $mercedes ? null : 'مرسيدس بنز',
            'model_other' => 'C200 AMG Line',
            'year' => 2024,
            'color' => 'أسود ملكي',
            'plate_number' => 'د ل ع 9999',
            'vin' => 'WDD2050421F987654',
            'odometer' => 18400,
            'notes' => 'تحت الضمان - صيانة دورية فقط',
        ]);

        // ------------------ أسطول شركة السهم ------------------
        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $c3->id,
            'make_id' => $isuzu?->id,
            'make_other' => 'إيسوزو',
            'model_other' => 'ديماكس غمارتين ديزل',
            'year' => 2023,
            'color' => 'أبيض',
            'plate_number' => 'ن ق ل 1010',
            'vin' => 'MP1TFR85JH1010101',
            'odometer' => 98400,
        ]);

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $c3->id,
            'make_id' => $toyota?->id,
            'make_other' => 'تويوتا',
            'model_other' => 'هايلكس غمارتين GLX',
            'year' => 2024,
            'color' => 'أبيض',
            'plate_number' => 'ن ق ل 2020',
            'vin' => 'MR0BA3CD402020202',
            'odometer' => 34500,
        ]);

        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $c3->id,
            'make_id' => $ford?->id,
            'make_other' => 'فورد',
            'model_other' => 'إكسبيديشن XLT',
            'year' => 2021,
            'color' => 'كحلي',
            'plate_number' => 'ن ق ل 3030',
            'vin' => '1FMJU1J54MEA30303',
            'odometer' => 124000,
        ]);

        // ------------------ مركبة الهيئة الحكومية ------------------
        Vehicle::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $c4->id,
            'make_id' => $lexus?->id,
            'make_other' => 'لكزس',
            'model_other' => 'LX600 VIP Edition',
            'year' => 2023,
            'color' => 'رصاصي تيتانيوم',
            'plate_number' => 'ح ك م 1111',
            'vin' => 'JTJHY7AX8P4111111',
            'odometer' => 29300,
        ]);
    }

    /**
     * Seed 1000 trial customers for load and concurrency testing.
     *
     * Bulk-inserted in batches of 200 to keep memory flat and the
     * transaction size sane. Names follow a deterministic pattern so the
     * generated rows are greppable and reproducible across re-seeds.
     */
    private function seedTrialCustomers(Tenant $tenant, Center $center): void
    {
        $cities = [
            'الرياض' => ['الياسمين', 'النجر', 'الدرعية', 'القطيف', 'الحمراء'],
            'جدة' => ['الشاطئ', 'الروابي', 'البلد', 'الكورنيش', 'البوم'],
            'الدمام' => ['الفيصلية', 'السياحية', 'الеж', 'المنطقة', 'السعودية'],
            'المدينة المنورة' => ['القديم', 'الجديد', 'المركز', 'الحرام', 'القآبت'],
            'تibre' => ['الوادي', 'المنطقة', 'المنطقة', 'المنطقة', 'المنطقة'],
        ];

        $cityKeys = array_keys($cities);
        $batch = [];
        $now = now();

        for ($i = 1; $i <= 1000; $i++) {
            $city = $cityKeys[($i - 1) % count($cityKeys)];
            $district = $cities[$city][($i - 1) % count($cities[$city])];
            $type = $i % 10 === 0 ? Customer::TYPE_COMPANY : Customer::TYPE_INDIVIDUAL;

            $batch[] = [
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'type' => $type,
                'name' => 'عميل تجريبي '.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'contact_name' => 'الاتصالات '.$i,
                'phone' => '05'.str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT),
                'whatsapp' => '05'.str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT),
                'email' => 'trial.customer'.$i.'@example.test',
                'city' => $city,
                'district' => $district,
                'address_line' => 'شارع تجريبي رقم '.$i,
                'notes' => 'عميل تجريبي مُولَّد تلقائياً ل اختبار الأداء والحمولة',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) === 200) {
                Customer::insert($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            Customer::insert($batch);
        }

        $this->command->info('✅ Seeded 1000 trial customers.');
    }

    /**
     * Seed suppliers with complete commercial details.
     */
    private function seedSuppliers(Tenant $tenant, Center $center): void
    {
        Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'SUP-00001',
            'name' => 'شركة الخريف لقطع الغيار الأصلية',
            'type' => 'parts',
            'contact_person' => 'عبدالله الخريف',
            'phone' => '0114567890',
            'whatsapp' => '0507788990',
            'email' => 'sales@alkhorayef-parts.com',
            'city' => 'الرياض',
            'district' => 'صناعية أم الحمام',
            'tax_number' => '300555666700003',
            'cr_number' => '1010456789',
            'bank_name' => 'مصرف الراجحي',
            'iban' => 'SA4480000123608010123456',
            'notes' => 'وكيل معتمد ومورد رئيسي لقطع تويوتا وهيونداي الأصلية',
            'is_active' => true,
        ]);

        Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'SUP-00002',
            'name' => 'مؤسسة بترومين للتوزيع والزيوت',
            'type' => 'consumables',
            'contact_person' => 'سامي الغامدي',
            'phone' => '0123456789',
            'whatsapp' => '0554433221',
            'email' => 'orders@petromin-dist.sa',
            'city' => 'جدة',
            'district' => 'الرويس',
            'tax_number' => '300888999100003',
            'cr_number' => '4030123456',
            'bank_name' => 'البنك الأهلي السعودي',
            'iban' => 'SA0310000001234567890123',
            'notes' => 'توزيع زيوت المحركات وسوائل التبريد والفلاتر',
            'is_active' => true,
        ]);

        Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'SUP-00003',
            'name' => 'شركة الجزيرة لمواد الطلاء والسمكرة',
            'type' => 'paint',
            'contact_person' => 'م. عادل الشريف',
            'phone' => '0138887766',
            'whatsapp' => '0569988771',
            'email' => 'paint@aljazeera-coatings.com',
            'city' => 'الدمام',
            'district' => 'الخالدية',
            'tax_number' => '300333222400003',
            'cr_number' => '2050987654',
            'bank_name' => 'بنك الرياض',
            'iban' => 'SA1220000009876543210123',
            'notes' => 'توريد دهانات فرن ألمانية ومعاجين وأوراق صنفرة ومواد التلميع',
            'is_active' => true,
        ]);

        Supplier::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'code' => 'SUP-00004',
            'name' => 'مخرطة النور الهندسية المعتمدة',
            'type' => 'subcontractor',
            'contact_person' => 'المعلم نور الدين',
            'phone' => '0531239874',
            'whatsapp' => '0531239874',
            'city' => 'الرياض',
            'district' => 'صناعية الدائري',
            'tax_number' => '300999111200003',
            'cr_number' => '1010654321',
            'notes' => 'خدمات خرط هوبات، تعديل عمود كردان، وتوضيب رؤوس محركات',
            'is_active' => true,
        ]);
    }

    /**
     * Seed departments and rich services with both flexible (allow_price_override=true)
     * and fixed pricing (allow_price_override=false) across internal, external, and packages.
     */
    private function seedDepartmentsAndServices(Tenant $tenant, Center $center): void
    {
        $departmentsData = [
            'mechanical' => [
                'name_ar' => 'الميكانيكا والمحركات',
                'name_en' => 'Mechanical & Engine',
                'description' => 'صيانة المحركات والجربكسات ونظام التعليق والفرامل',
                'sort_order' => 1,
                'services' => [
                    [
                        'name_ar' => 'توضيب قير أوتوماتيك كامل مع الضمان',
                        'name_en' => 'Complete Automatic Transmission Rebuild',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 3500.00,
                        'min_price' => 2800.00,
                        'allow_price_override' => true, // سعر قابل للتفاوض
                        'warranty_value' => 6,
                        'warranty_unit' => 'months',
                        'duration_value' => 3,
                        'duration_unit' => 'days',
                    ],
                    [
                        'name_ar' => 'استبدال فحمات فرامل أمامية مع تنظيف الكليبرات',
                        'name_en' => 'Front Brake Pads Replacement & Caliper Service',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 150.00,
                        'min_price' => 150.00,
                        'allow_price_override' => false, // سعر ثابت لا يمكن تعديله
                        'warranty_value' => 1,
                        'warranty_unit' => 'months',
                        'duration_value' => 45,
                        'duration_unit' => 'minutes',
                    ],
                    [
                        'name_ar' => 'خرط هوبات فرامل أمامية (مخرطة خارجية)',
                        'name_en' => 'Front Brake Rotors Resurfacing (External)',
                        'type' => Service::TYPE_EXTERNAL, // خدمة خارجية
                        'base_price' => 120.00,
                        'min_price' => 120.00,
                        'allow_price_override' => false,
                        'duration_value' => 2,
                        'duration_unit' => 'hours',
                    ],
                    [
                        'name_ar' => 'استبدال سير التيمن ومضخة الماء',
                        'name_en' => 'Timing Belt & Water Pump Replacement',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 450.00,
                        'min_price' => 400.00,
                        'allow_price_override' => true,
                        'warranty_value' => 3,
                        'warranty_unit' => 'months',
                        'duration_value' => 4,
                        'duration_unit' => 'hours',
                    ],
                ],
            ],

            'electrical' => [
                'name_ar' => 'الكهرباء والإلكترونيات',
                'name_en' => 'Electrical & Electronics',
                'description' => 'فحص الدوائر الكهربائية، الحساسات، والبطاريات والكمبيوتر',
                'sort_order' => 2,
                'services' => [
                    [
                        'name_ar' => 'فحص وبرمجة كمبيوتر المحرك والحساسات',
                        'name_en' => 'Engine ECU Diagnostics & Programming',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 200.00,
                        'min_price' => 200.00,
                        'allow_price_override' => false, // سعر فحص قياسي ثابت
                        'duration_value' => 1,
                        'duration_unit' => 'hours',
                    ],
                    [
                        'name_ar' => 'إصلاح وترميم ضفيرة كهرباء رئيسية متضررة',
                        'name_en' => 'Wiring Harness Major Repair & Reconditioning',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 650.00,
                        'min_price' => 450.00,
                        'allow_price_override' => true, // يختلف حسب حجم التلف
                        'warranty_value' => 3,
                        'warranty_unit' => 'months',
                        'duration_value' => 5,
                        'duration_unit' => 'hours',
                    ],
                    [
                        'name_ar' => 'استبدال دينامو شحن أصلي',
                        'name_en' => 'Alternator Replacement Service',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 220.00,
                        'min_price' => 200.00,
                        'allow_price_override' => true,
                        'warranty_value' => 1,
                        'warranty_unit' => 'months',
                    ],
                ],
            ],

            'body_paint' => [
                'name_ar' => 'السمكرة والتعديل',
                'name_en' => 'Body & Collision Repair',
                'description' => 'سحب شاصيه، سمكرة متقدمة، وتعديل صدمات على البارد',
                'sort_order' => 3,
                'services' => [
                    [
                        'name_ar' => 'سحب وتعديل شاصيه على جهاز الليزر المتقدم',
                        'name_en' => 'Laser Chassis Realignment & Pulling',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 1800.00,
                        'min_price' => 1400.00,
                        'allow_price_override' => true,
                        'warranty_value' => 1,
                        'warranty_unit' => 'years',
                        'duration_value' => 2,
                        'duration_unit' => 'days',
                    ],
                    [
                        'name_ar' => 'تعديل صدمة بدون بوية PDR (للقطعة الواحدة)',
                        'name_en' => 'Paintless Dent Repair (PDR - Single Panel)',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 350.00,
                        'min_price' => 250.00,
                        'allow_price_override' => true,
                        'duration_value' => 3,
                        'duration_unit' => 'hours',
                    ],
                    [
                        'name_ar' => 'فك وتركيب واجهة أمامية وصدام كامل',
                        'name_en' => 'Front End & Bumper Assembly R&I',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 300.00,
                        'min_price' => 300.00,
                        'allow_price_override' => false,
                    ],
                ],
            ],

            'paint' => [
                'name_ar' => 'الدهان والفرن الحراري',
                'name_en' => 'Paint & Oven Finishing',
                'description' => 'رش فرن حراري بألوان مطابقة للوكالة مع معالجة الخدوش',
                'sort_order' => 4,
                'services' => [
                    [
                        'name_ar' => 'رش دهان كامل للسيارة فرن حراري مع ضمان سنتين',
                        'name_en' => 'Full Vehicle Oven Paint with 2-Year Warranty',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 5500.00,
                        'min_price' => 4800.00,
                        'allow_price_override' => true,
                        'warranty_value' => 2,
                        'warranty_unit' => 'years',
                        'duration_value' => 5,
                        'duration_unit' => 'days',
                    ],
                    [
                        'name_ar' => 'رش قطعة واحدة (صدام / رفرف / باب)',
                        'name_en' => 'Single Panel Oven Spray Paint',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 450.00,
                        'min_price' => 450.00,
                        'allow_price_override' => false, // سعر موحد للقطعة
                        'warranty_value' => 1,
                        'warranty_unit' => 'years',
                        'duration_value' => 1,
                        'duration_unit' => 'days',
                    ],
                    [
                        'name_ar' => 'معالجة خدوش وصنفرة وتلميع بالصاروخ',
                        'name_en' => 'Scratch Compound Buffing & Machine Polishing',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 250.00,
                        'min_price' => 200.00,
                        'allow_price_override' => true,
                    ],
                ],
            ],

            'maintenance' => [
                'name_ar' => 'الصيانة الدورية والسريعة',
                'name_en' => 'Periodic & Quick Maintenance',
                'description' => 'تغيير الزيوت، الفلاتر، باقات الكيلومتر، والتشحيم',
                'sort_order' => 5,
                'services' => [
                    [
                        'name_ar' => 'باقة الصيانة الكبرى الشاملة 40,000 كم (عرض متكامل)',
                        'name_en' => 'Major 40K km Service Package (All inclusive)',
                        'type' => Service::TYPE_PACKAGE, // باقة متكاملة
                        'base_price' => 850.00,
                        'min_price' => 750.00,
                        'allow_price_override' => true,
                        'warranty_value' => 3,
                        'warranty_unit' => 'months',
                        'duration_value' => 3,
                        'duration_unit' => 'hours',
                    ],
                    [
                        'name_ar' => 'أجرة تغيير زيت محرك وسيفون وفحص 15 نقطة',
                        'name_en' => 'Engine Oil & Filter Labor + 15-Point Inspection',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 60.00,
                        'min_price' => 60.00,
                        'allow_price_override' => false,
                        'duration_value' => 30,
                        'duration_unit' => 'minutes',
                    ],
                    [
                        'name_ar' => 'تربيط وتشحيم شاصيه ومقصات وبطن السيارة كامل',
                        'name_en' => 'Chassis & Suspension Complete Tightening & Greasing',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 180.00,
                        'min_price' => 150.00,
                        'allow_price_override' => true,
                    ],
                ],
            ],

            'cooling' => [
                'name_ar' => 'التكييف والتبريد',
                'name_en' => 'AC & Cooling System',
                'description' => 'تعبئة فريون، كمبروسرات، راديترات، وغسيل دورة التكييف',
                'sort_order' => 6,
                'services' => [
                    [
                        'name_ar' => 'تعبئة فريون أصلي أمريكي R134a مع كشف تسريب بالنيتروجين',
                        'name_en' => 'R134a AC Gas Refill & Nitrogen Leak Test',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 180.00,
                        'min_price' => 180.00,
                        'allow_price_override' => false,
                        'warranty_value' => 1,
                        'warranty_unit' => 'months',
                    ],
                    [
                        'name_ar' => 'استبدال كمبروسر مكيف وغسيل دورة التبريد كاملة',
                        'name_en' => 'AC Compressor Replacement & Full Circuit Flush',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 550.00,
                        'min_price' => 450.00,
                        'allow_price_override' => true,
                        'warranty_value' => 3,
                        'warranty_unit' => 'months',
                    ],
                    [
                        'name_ar' => 'استبدال راديتر ماء المحرك مع تغيير ماء التبريد',
                        'name_en' => 'Engine Radiator Replacement & Coolant Flush',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 200.00,
                        'min_price' => 200.00,
                        'allow_price_override' => false,
                    ],
                ],
            ],

            'tires' => [
                'name_ar' => 'الإطارات والميزان',
                'name_en' => 'Tires & Alignment',
                'description' => 'ميزان أذرعة بالليزر، ترصيص أرضي، وتبديل إطارات',
                'sort_order' => 7,
                'services' => [
                    [
                        'name_ar' => 'ميزان أذرعة ومقصات أمامي وخلفي كمبيوتر 3D',
                        'name_en' => '3D Computerized 4-Wheel Alignment',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 150.00,
                        'min_price' => 150.00,
                        'allow_price_override' => false,
                        'warranty_value' => 15,
                        'warranty_unit' => 'days',
                    ],
                    [
                        'name_ar' => 'ترصيص إطارات ليزر دقيق (لأربعة إطارات)',
                        'name_en' => 'Precision Laser Wheel Balancing (Set of 4)',
                        'type' => Service::TYPE_INTERNAL,
                        'base_price' => 100.00,
                        'min_price' => 100.00,
                        'allow_price_override' => false,
                    ],
                ],
            ],
        ];

        foreach ($departmentsData as $deptKey => $data) {
            $department = Department::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'description' => $data['description'],
                'sort_order' => $data['sort_order'],
                'is_active' => true,
            ]);

            foreach ($data['services'] as $idx => $s) {
                Service::create([
                    'tenant_id' => $tenant->id,
                    'center_id' => $center->id,
                    'department_id' => $department->id,
                    'name_ar' => $s['name_ar'],
                    'name_en' => $s['name_en'],
                    'type' => $s['type'],
                    'base_price' => $s['base_price'],
                    'min_price' => $s['min_price'],
                    'allow_price_override' => $s['allow_price_override'],
                    'warranty_value' => $s['warranty_value'] ?? null,
                    'warranty_unit' => $s['warranty_unit'] ?? null,
                    'duration_value' => $s['duration_value'] ?? 60,
                    'duration_unit' => $s['duration_unit'] ?? 'minutes',
                    'sort_order' => $idx + 1,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Seed warehouses, inventory categories, spare parts of various types and units,
     * and establish live stock balances with opening moves.
     */
    private function seedInventoryAndParts(Tenant $tenant, Center $center): void
    {
        // 1. المستودع الرئيسي
        $warehouse = Warehouse::create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'name' => 'المستودع الرئيسي (Main Warehouse)',
            'code' => 'WH-01',
            'is_default' => true,
            'is_active' => true,
        ]);

        // 2. وحدات القياس
        $piece = InventoryUnit::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name_ar' => 'حبة'],
            ['name_en' => 'Piece', 'is_active' => true]
        );
        $set = InventoryUnit::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name_ar' => 'طقم'],
            ['name_en' => 'Set', 'is_active' => true]
        );
        $liter = InventoryUnit::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name_ar' => 'لتر'],
            ['name_en' => 'Liter', 'is_active' => true]
        );
        $box = InventoryUnit::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name_ar' => 'كرتون'],
            ['name_en' => 'Box', 'is_active' => true]
        );
        $gallon = InventoryUnit::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name_ar' => 'جالون'],
            ['name_en' => 'Gallon', 'is_active' => true]
        );

        // 3. تصنيفات قطع الغيار
        $categoriesData = [
            'brakes' => ['ar' => 'نظام الفرامل والتعليق', 'en' => 'Brakes & Suspension'],
            'oils' => ['ar' => 'الزيوت والشحوم وسوائل التشغيل', 'en' => 'Oils & Lubricants'],
            'filters' => ['ar' => 'الفلاتر والاستهلاكيات', 'en' => 'Filters & Consumables'],
            'electrical' => ['ar' => 'الكهرباء والحساسات والبواجي', 'en' => 'Electrical & Ignition'],
            'cooling' => ['ar' => 'قطع التكييف والتبريد', 'en' => 'AC & Cooling Parts'],
            'body' => ['ar' => 'أجزاء البودي والإنارة الخارجية', 'en' => 'Body & Lighting'],
        ];

        $categories = [];

        foreach ($categoriesData as $k => $c) {
            $categories[$k] = InventoryCategory::create([
                'tenant_id' => $tenant->id,
                'name_ar' => $c['ar'],
                'name_en' => $c['en'],
                'is_active' => true,
            ]);
        }

        // 4. قائمة قطع الغيار مع الكميات والتكاليف
        $partsData = [
            [
                'name_ar' => 'فحمات فرامل أمامية أصلي لاندكروزر V8',
                'name_en' => 'Front Brake Pads Genuine Toyota Land Cruiser',
                'sku' => '04465-60320',
                'barcode' => '628100100001',
                'unit_id' => $set->id,
                'category_id' => $categories['brakes']->id,
                'cost' => 280.00,
                'sale_price' => 390.00,
                'min_sale_price' => 350.00,
                'min_qty' => 5,
                'qty' => 18,
                'location' => 'A-01-02',
            ],
            [
                'name_ar' => 'فحمات فرامل خلفية سوناتا وسيراتو كوري',
                'name_en' => 'Rear Brake Pads Hyundai Sonata / Kia Cerato',
                'sku' => '58302-C1A00',
                'barcode' => '628100100002',
                'unit_id' => $set->id,
                'category_id' => $categories['brakes']->id,
                'cost' => 110.00,
                'sale_price' => 175.00,
                'min_sale_price' => 150.00,
                'min_qty' => 4,
                'qty' => 14,
                'location' => 'A-01-04',
            ],
            [
                'name_ar' => 'مساعدات أمامية غاز جبريال لاندكروزر',
                'name_en' => 'Front Gas Shock Absorbers Pair Gabriel',
                'sku' => 'G51892',
                'barcode' => '628100100003',
                'unit_id' => $set->id,
                'category_id' => $categories['brakes']->id,
                'cost' => 360.00,
                'sale_price' => 520.00,
                'min_sale_price' => 460.00,
                'min_qty' => 2,
                'qty' => 8,
                'location' => 'A-03-01',
            ],
            [
                'name_ar' => 'زيت محرك تويوتا تخليقي بالكامل 5W-40 SN',
                'name_en' => 'Toyota Fully Synthetic Engine Oil 5W-40 (1L)',
                'sku' => '08880-83321',
                'barcode' => '628100200001',
                'unit_id' => $liter->id,
                'category_id' => $categories['oils']->id,
                'cost' => 26.00,
                'sale_price' => 45.00,
                'min_sale_price' => 38.00,
                'min_qty' => 30,
                'qty' => 140,
                'location' => 'B-01-BULK',
            ],
            [
                'name_ar' => 'زيت محرك كاسترول ماجناتيك 10W-40 نصف تخليقي',
                'name_en' => 'Castrol Magnatec 10W-40 Semi-Synthetic Oil',
                'sku' => 'CAS-10W40-1L',
                'barcode' => '628100200002',
                'unit_id' => $liter->id,
                'category_id' => $categories['oils']->id,
                'cost' => 21.00,
                'sale_price' => 38.00,
                'min_sale_price' => 32.00,
                'min_qty' => 25,
                'qty' => 95,
                'location' => 'B-02-BULK',
            ],
            [
                'name_ar' => 'زيت جير أوتوماتيك تويوتا WS أصلي',
                'name_en' => 'Toyota Genuine ATF WS Transmission Fluid (4L)',
                'sku' => '08886-02305',
                'barcode' => '628100200003',
                'unit_id' => $gallon->id,
                'category_id' => $categories['oils']->id,
                'cost' => 135.00,
                'sale_price' => 195.00,
                'min_sale_price' => 170.00,
                'min_qty' => 6,
                'qty' => 22,
                'location' => 'B-03-02',
            ],
            [
                'name_ar' => 'فلتر زيت تويوتا لاندكروزر وكامري أصلي',
                'name_en' => 'Toyota Genuine Oil Filter Element',
                'sku' => '04152-YZZA1',
                'barcode' => '628100300001',
                'unit_id' => $piece->id,
                'category_id' => $categories['filters']->id,
                'cost' => 22.00,
                'sale_price' => 40.00,
                'min_sale_price' => 35.00,
                'min_qty' => 15,
                'qty' => 50,
                'location' => 'C-01-01',
            ],
            [
                'name_ar' => 'فلتر هواء محرك هيونداي سوناتا 2.5L',
                'name_en' => 'Hyundai Sonata Engine Air Filter Element',
                'sku' => '28113-L1000',
                'barcode' => '628100300002',
                'unit_id' => $piece->id,
                'category_id' => $categories['filters']->id,
                'cost' => 35.00,
                'sale_price' => 65.00,
                'min_sale_price' => 55.00,
                'min_qty' => 8,
                'qty' => 26,
                'location' => 'C-01-03',
            ],
            [
                'name_ar' => 'فلتر مكيف هواء مرسيدس كربوني ضد الروائح',
                'name_en' => 'Mercedes-Benz Activated Carbon Cabin Filter',
                'sku' => 'A2058350147',
                'barcode' => '628100300003',
                'unit_id' => $piece->id,
                'category_id' => $categories['filters']->id,
                'cost' => 135.00,
                'sale_price' => 220.00,
                'min_sale_price' => 190.00,
                'min_qty' => 3,
                'qty' => 9,
                'location' => 'C-02-01',
            ],
            [
                'name_ar' => 'طقم بواجي إيريديوم ليزر NGK ياباني (4 حبات)',
                'name_en' => 'NGK Laser Iridium Spark Plugs Set (4 pcs)',
                'sku' => 'ILZKR7B-11',
                'barcode' => '628100400001',
                'unit_id' => $set->id,
                'category_id' => $categories['electrical']->id,
                'cost' => 125.00,
                'sale_price' => 190.00,
                'min_sale_price' => 170.00,
                'min_qty' => 6,
                'qty' => 28,
                'location' => 'D-01-02',
            ],
            [
                'name_ar' => 'بطارية هانكوك كوري 70 أمبير معتمدة',
                'name_en' => 'Hankook Maintenance-Free Battery 70Ah',
                'sku' => 'BAT-HK-70AH',
                'barcode' => '628100400002',
                'unit_id' => $piece->id,
                'category_id' => $categories['electrical']->id,
                'cost' => 210.00,
                'sale_price' => 320.00,
                'min_sale_price' => 290.00,
                'min_qty' => 5,
                'qty' => 15,
                'location' => 'D-FLOOR-01',
            ],
            [
                'name_ar' => 'كمبروسر مكيف دينسو أصلي ياباني',
                'name_en' => 'Denso Genuine AC Compressor Assembly',
                'sku' => 'DCP50085',
                'barcode' => '628100500001',
                'unit_id' => $piece->id,
                'category_id' => $categories['cooling']->id,
                'cost' => 1150.00,
                'sale_price' => 1650.00,
                'min_sale_price' => 1480.00,
                'min_qty' => 2,
                'qty' => 5,
                'location' => 'E-01-01',
            ],
            [
                'name_ar' => 'أسطوانة غاز فريون R134a أمريكي أصلي (13.6 كجم)',
                'name_en' => 'DuPont / Chemours R134a Refrigerant Cylinder',
                'sku' => 'GAS-R134A-USA',
                'barcode' => '628100500002',
                'unit_id' => $piece->id,
                'category_id' => $categories['cooling']->id,
                'cost' => 390.00,
                'sale_price' => 580.00,
                'min_sale_price' => 520.00,
                'min_qty' => 3,
                'qty' => 10,
                'location' => 'E-CORNER',
            ],
            [
                'name_ar' => 'ماء راديتر تويوتا وردي طويل المدى سائل جاهز (4L)',
                'name_en' => 'Toyota Super Long Life Pink Coolant (4L)',
                'sku' => '08889-80072',
                'barcode' => '628100500003',
                'unit_id' => $gallon->id,
                'category_id' => $categories['cooling']->id,
                'cost' => 45.00,
                'sale_price' => 75.00,
                'min_sale_price' => 65.00,
                'min_qty' => 10,
                'qty' => 32,
                'location' => 'E-02-BULK',
            ],
            [
                'name_ar' => 'شمعة إضاءة أمامية ليد يمين لاندكروزر',
                'name_en' => 'Right LED Headlamp Assembly Toyota Land Cruiser',
                'sku' => '81110-60M50',
                'barcode' => '628100600001',
                'unit_id' => $piece->id,
                'category_id' => $categories['body']->id,
                'cost' => 1850.00,
                'sale_price' => 2450.00,
                'min_sale_price' => 2200.00,
                'min_qty' => 1,
                'qty' => 4,
                'location' => 'F-01-01',
            ],
            [
                'name_ar' => 'كلبسات وتثبيت صدام وبودي منوعة (علبة 100 حبة)',
                'name_en' => 'Bumper & Body Retainer Fastener Clips (Box 100 pcs)',
                'sku' => 'CLP-UNIV-100',
                'barcode' => '628100600002',
                'unit_id' => $box->id,
                'category_id' => $categories['body']->id,
                'cost' => 25.00,
                'sale_price' => 50.00,
                'min_sale_price' => 40.00,
                'min_qty' => 5,
                'qty' => 20,
                'location' => 'F-02-05',
            ],
        ];

        foreach ($partsData as $p) {
            $part = Part::create([
                'tenant_id' => $tenant->id,
                'name_ar' => $p['name_ar'],
                'name_en' => $p['name_en'],
                'sku' => $p['sku'],
                'barcode' => $p['barcode'],
                'unit_id' => $p['unit_id'],
                'category_id' => $p['category_id'],
                'default_sale_price' => $p['sale_price'],
                'min_sale_price' => $p['min_sale_price'],
                'min_qty' => $p['min_qty'],
                'reorder_qty' => $p['min_qty'] * 2,
                'is_active' => true,
            ]);

            // Create live stock balance
            InventoryBalance::create([
                'warehouse_id' => $warehouse->id,
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'part_id' => $part->id,
                'qty_on_hand' => $p['qty'],
                'wac_cost' => $p['cost'],
                'sale_price' => $p['sale_price'],
                'min_sale_price' => $p['min_sale_price'],
                'min_stock' => $p['min_qty'],
                'storage_location' => $p['location'],
                'allow_price_change' => true,
                'is_active' => true,
                'last_move_at' => now(),
            ]);

            // Create initial opening inventory move (Receipt / Opening Balance)
            InventoryMove::create([
                'warehouse_id' => $warehouse->id,
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'part_id' => $part->id,
                'move_type' => InventoryMove::TYPE_RECEIPT,
                'qty' => $p['qty'],
                'unit_cost' => $p['cost'],
                'total_cost' => round($p['qty'] * $p['cost'], 2),
                'balance_after' => $p['qty'],
                'wac_after' => $p['cost'],
                'notes' => 'رصيد مخزني افتتاحي معتمد',
                'posted_at' => now(),
            ]);
        }
    }

    /**
     * Seed realistic employees and staff with corresponding user accounts and granular roles:
     * - Branch Manager (مدير فرع)
     * - Receptionist / Service Advisor (موظف استقبال)
     * - Accountant (محاسب)
     * - Lead Mechanic (فني ميكانيكا)
     * - Auto Electrician (فني كهرباء)
     * - Bodywork Tech (فني سمكرة)
     * - HR Specialist (مسؤول موارد بشرية)
     */
    private function seedEmployeesAndStaff(Tenant $tenant, Center $center): void
    {
        $staffData = [
            [
                'name_ar' => 'م. طارق بن فهد الشهري',
                'name_en' => 'Tariq Al-Shehri',
                'email' => 'manager@active.test',
                'phone' => '0501110001',
                'role' => 'branch_manager',
                'job_title' => 'مدير',
                'salary' => 12000.00,
                'gender' => 'male',
                'commission' => false,
                'rate' => null,
            ],
            [
                'name_ar' => 'ريان بن عبدالله الدوسري',
                'name_en' => 'Rayan Al-Dosari',
                'email' => 'reception@active.test',
                'phone' => '0501110002',
                'role' => 'receptionist',
                'job_title' => 'موظف استقبال',
                'salary' => 6500.00,
                'gender' => 'male',
                'commission' => false,
                'rate' => null,
            ],
            [
                'name_ar' => 'أحمد مصطفى إبراهيم',
                'name_en' => 'Ahmed Mostafa',
                'email' => 'accountant@active.test',
                'phone' => '0501110003',
                'role' => 'accountant',
                'job_title' => 'محاسب',
                'salary' => 8000.00,
                'gender' => 'male',
                'commission' => false,
                'rate' => null,
            ],
            [
                'name_ar' => 'رفيق الإسلام ميرزا',
                'name_en' => 'Rafiqul Islam',
                'email' => 'mechanic@active.test',
                'phone' => '0501110004',
                'role' => 'technician',
                'job_title' => 'ميكانيكي',
                'salary' => 5500.00,
                'gender' => 'male',
                'commission' => true,
                'rate' => 5.00,
            ],
            [
                'name_ar' => 'المهندس بلال خان',
                'name_en' => 'Bilal Khan',
                'email' => 'electrician@active.test',
                'phone' => '0501110005',
                'role' => 'technician',
                'job_title' => 'كهربائي',
                'salary' => 5500.00,
                'gender' => 'male',
                'commission' => true,
                'rate' => 5.00,
            ],
            [
                'name_ar' => 'يوسف كمال الدين',
                'name_en' => 'Youssef Kamal',
                'email' => 'body@active.test',
                'phone' => '0501110006',
                'role' => 'technician',
                'job_title' => 'سمكري',
                'salary' => 5000.00,
                'gender' => 'male',
                'commission' => true,
                'rate' => 4.00,
            ],
            [
                'name_ar' => 'ماجد بن سعود الحربي',
                'name_en' => 'Majed Al-Harbi',
                'email' => 'hr@active.test',
                'phone' => '0501110007',
                'role' => 'hr',
                'job_title' => 'مشرف',
                'salary' => 7500.00,
                'gender' => 'male',
                'commission' => false,
                'rate' => null,
            ],
        ];

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        foreach ($staffData as $idx => $item) {
            // 1. Create User
            $user = User::create([
                'tenant_id' => $tenant->id,
                'current_center_id' => $center->id,
                'name' => $item['name_ar'],
                'email' => $item['email'],
                'password' => Hash::make('11223344'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            // Attach user to center
            $user->centers()->attach($center->id, ['tenant_id' => $tenant->id]);

            // Assign role
            $role = Role::where('name', $item['role'])->where('tenant_id', $tenant->id)->first();

            if ($role) {
                $user->assignRole($role);
            }

            // Find job title
            $jobTitle = JobTitle::where('tenant_id', $tenant->id)
                ->where('name_ar', $item['job_title'])
                ->first();

            // 2. Create Employee Profile
            Employee::create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'user_id' => $user->id,
                'employee_number' => 'EMP-'.str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'name_ar' => $item['name_ar'],
                'name_en' => $item['name_en'],
                'email' => $item['email'],
                'phone' => $item['phone'],
                'gender' => $item['gender'],
                'job_title_id' => $jobTitle?->id,
                'base_salary' => $item['salary'],
                'commission_enabled' => $item['commission'],
                'commission_type' => $item['commission'] ? 'percentage' : null,
                'commission_rate' => $item['rate'],
                'hire_date' => now()->subMonths(12 - $idx),
                'status' => 'active',
                'city' => 'الرياض',
                'district' => 'الملز',
            ]);
        }
    }

    /**
     * Seed work orders with technician assignments for testing Technician Portal.
     */
    private function seedWorkOrdersWithTechnicians(Tenant $tenant, Center $center): void
    {
        $customer = Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
        ]);

        $vehicle = Vehicle::factory()->create([
            'tenant_id' => $tenant->id,
            'center_id' => $center->id,
            'customer_id' => $customer->id,
        ]);

        $services = Service::where('tenant_id', $tenant->id)->where('is_active', true)->orderBy('id')->get();
        $technicians = User::whereHas('roles', function ($q) {
            $q->where('name', 'technician');
        })->where('tenant_id', $tenant->id)->orderBy('id')->get();

        if ($technicians->isEmpty() || $services->isEmpty()) {
            return;
        }

        // Create 5 work orders with items assigned to technicians
        for ($i = 0; $i < 5; $i++) {
            $workOrder = WorkOrder::factory()->create([
                'tenant_id' => $tenant->id,
                'center_id' => $center->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'status' => ['done', 'in_progress', 'on_hold', 'ready_for_qc', 'open'][$i],
            ]);

            // Create 2-3 items per work order
            $itemCount = 2 + ($i % 2);

            for ($j = 0; $j < $itemCount; $j++) {
                $service = $services[($i + $j) % $services->count()];
                $unitPrice = (float) ($service->default_price ?? 100);
                $minPrice = (float) ($service->min_price ?? 0);

                if ($minPrice > 0 && $unitPrice < $minPrice) {
                    $unitPrice = $minPrice;
                }
                $item = WorkOrderItem::factory()->create([
                    'work_order_id' => $workOrder->id,
                    'service_id' => $service->id,
                    'tenant_id' => $tenant->id,
                    'center_id' => $center->id,
                    'title' => $service->name_ar,
                    'qty' => 1,
                    'unit_price' => $unitPrice,
                    'base_price_snapshot' => $unitPrice,
                    'min_price_snapshot' => $minPrice,
                    'discount_type' => 'none',
                    'discount_value' => 0,
                    'discount_amount' => 0,
                    'final_unit_price' => $unitPrice,
                    'line_total' => $unitPrice,
                    'total' => $unitPrice,
                    'price_locked' => false,
                    'status' => 'pending',
                ]);

                // Assign random technician to this item
                $tech = $technicians[($i + $j) % $technicians->count()];
                $item->technicians()->attach($tech->id, [
                    'assigned_at' => now()->subDays($i),
                    'notes' => 'مُسند عبر Seeder للاختبار',
                    'share' => 100,
                ]);
            }

            if ($i === 0) {
                $invoices = app(InvoiceService::class);
                $invoice = $invoices->issueInvoice($invoices->createFromWorkOrder($workOrder, $tech));
                app(PaymentService::class)->recordPayment($invoice, [
                    'amount' => $invoice->balance,
                    'payment_method' => 'cash',
                    'received_by' => $tech->id,
                ]);
            }
        }
    }
}
