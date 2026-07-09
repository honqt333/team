<?php

use App\Models\Billing\Plan;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Standalone Trial Plan
        Plan::firstOrCreate(
            ['slug' => 'trial'],
            [
                'name' => 'الباقة التجريبية',
                'name_ar' => 'الباقة التجريبية',
                'name_en' => 'Trial Plan',
                'description' => 'باقة تجريبية مجانية لتجربة كافة خصائص النظام الأساسية.',
                'description_ar' => 'باقة تجريبية مجانية لتجربة كافة خصائص النظام الأساسية.',
                'description_en' => 'Free trial package to experience all core system features.',
                'price_monthly' => 0.00,
                'price_yearly' => 0.00,
                'trial_days' => 14,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
                'features' => [
                    'ar' => [
                        'إدارة فرع واحد',
                        'إدارة الموظفين (حتى 3 مستخدمين)',
                        'إدارة أوامر العمل والعملاء والسيارات',
                        'الفواتير والمدفوعات المتوافقة مع زكاة وضريبة',
                    ],
                    'en' => [
                        'Manage 1 Branch',
                        'Manage employees (up to 3 users)',
                        'Manage Work Orders, Customers & Vehicles',
                        'ZATCA compliant Invoices & Payments',
                    ],
                ],
                'limits' => [
                    'max_centers' => 1,
                    'max_users' => 3,
                    'storage_gb' => 2,
                ],
            ]
        );

        // 2. Basic Plan
        Plan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name' => 'الباقة الأساسية',
                'name_ar' => 'الباقة الأساسية',
                'name_en' => 'Basic Plan',
                'description' => 'مثالية للمراكز والورش الصغيرة والمتوسطة بفرع واحد.',
                'description_ar' => 'مثالية للمراكز والورش الصغيرة والمتوسطة بفرع واحد.',
                'description_en' => 'Perfect for small to medium service centers with one branch.',
                'price_monthly' => 199.00,
                'price_yearly' => 1990.00,
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 2,
                'features' => [
                    'ar' => [
                        'إدارة فرع واحد',
                        'حتى 3 مستخدمين',
                        'إدارة العملاء والمركبات',
                        'إدارة أوامر العمل والخدمات والقطع',
                        'الفواتير الضريبية المبسطة والباركود المتوافق مع ZATCA',
                        'دعم فني عبر البريد الإلكتروني',
                    ],
                    'en' => [
                        'Manage 1 Branch',
                        'Up to 3 users',
                        'Customer & Vehicle management',
                        'Work Order, Service & Parts management',
                        'Simplified Tax Invoices & ZATCA QR codes',
                        'Email support',
                    ],
                ],
                'limits' => [
                    'max_centers' => 1,
                    'max_users' => 3,
                    'storage_gb' => 5,
                ],
            ]
        );

        // 3. Pro Plan
        Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'الباقة الاحترافية',
                'name_ar' => 'الباقة الاحترافية',
                'name_en' => 'Pro Plan',
                'description' => 'مثالية للمراكز المتعددة الفروع والنمو السريع.',
                'description_ar' => 'مثالية للمراكز المتعددة الفروع والنمو السريع.',
                'description_en' => 'Best for multi-branch centers and growing businesses.',
                'price_monthly' => 399.00,
                'price_yearly' => 3990.00,
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 3,
                'features' => [
                    'ar' => [
                        'إدارة حتى 5 فروع',
                        'حتى 25 مستخدم',
                        'إدارة المشتريات والمخزون وحركة القطع',
                        'إدارة الموارد البشرية والرواتب والحضور والغياب',
                        'التقارير المالية المتقدمة والإيرادات والمصروفات',
                        'دعم فني ذو أولوية 24/7',
                    ],
                    'en' => [
                        'Manage up to 5 Branches',
                        'Up to 25 users',
                        'Purchasing, Inventory & Parts flow management',
                        'HR, Payroll, Attendance & Absences',
                        'Advanced financial reports (Revenue & Expenses)',
                        'Priority 24/7 support',
                    ],
                ],
                'limits' => [
                    'max_centers' => 5,
                    'max_users' => 25,
                    'storage_gb' => 20,
                ],
            ]
        );

        // 4. Elite Plan
        Plan::firstOrCreate(
            ['slug' => 'elite'],
            [
                'name' => 'الباقة اللامحدودة (النخبة)',
                'name_ar' => 'الباقة اللامحدودة (النخبة)',
                'name_en' => 'Elite Plan (Unlimited)',
                'description' => 'الحل الشامل والكامل للشركات الكبرى وسلاسل الفروع اللامحدودة.',
                'description_ar' => 'الحل الشامل والكامل للشركات الكبرى وسلاسل الفروع اللامحدودة.',
                'description_en' => 'Comprehensive solution for large corporations and unlimited branch chains.',
                'price_monthly' => 799.00,
                'price_yearly' => 7990.00,
                'trial_days' => 0,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
                'features' => [
                    'ar' => [
                        'فروع ومراكز لا محدودة',
                        'مستخدمين وموظفين لا محدودين',
                        'مساحة تخزين سحابية ضخمة للصور والمستندات',
                        'ربط ZATCA المتقدم للمبيعات والمشتريات',
                        'تخصيص كامل للهوية البصرية والترقيم والرسائل والتنبيهات',
                        'مدير حساب مخصص ودعم عبر الهاتف والواتساب',
                    ],
                    'en' => [
                        'Unlimited branches & centers',
                        'Unlimited users & employees',
                        'Large cloud storage for photos & documents',
                        'Advanced ZATCA integration (Sales & Purchases)',
                        'Visual identity, numbering, templates & alert customizations',
                        'Dedicated account manager via Phone & WhatsApp',
                    ],
                ],
                'limits' => [
                    'max_centers' => 999,
                    'max_users' => 999,
                    'storage_gb' => 100,
                ],
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Plan::whereIn('slug', ['trial', 'basic', 'pro', 'elite'])->delete();
    }
};
