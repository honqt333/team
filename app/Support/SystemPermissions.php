<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The system panel's permission registry.
 *
 * ## What it is
 *
 * The /system panel is authorised by ROLE, not by the tenant-side
 * Spatie permissions. Every admin account carries a `role` column
 * (`super_admin` / `admin` / `support`) and an optional JSON list of
 * explicit permissions.
 *
 * This class is the single place that answers two questions:
 *
 *   1. Which abilities EXIST, and how are they grouped and labelled?
 *      → `all()`, `groups()`
 *   2. Which abilities does a given account hold?
 *      → `forRole()`, `resolveFor()`
 *
 * ## Why it exists separately from the model
 *
 * `AdminUser::getAvailablePermissions()` used to carry this data, which
 * meant every new ability meant editing the model. The registry is
 * separate so the ability list can grow without touching the entity,
 * and so the same list can be handed to the UI (the admin-user form
 * renders one checkbox per ability) and to the server (the middleware
 * checks against it) from ONE source.
 *
 * ## Extending it
 *
 * Adding an ability is three steps:
 *
 *   1. Add the constant to App\Support\Permissions (the constitution's
 *      single source of truth for permission names).
 *   2. Add the ability to a group in `GROUPS` below, with its label.
 *   3. Include it in the role defaults it should belong to
 *      (`ROLE_DEFAULTS`).
 *
 * Step 2 is the only one this file needs: `GROUPS` is the master list,
 * and `all()` is derived from it. A group added there shows up in the
 * admin-user form automatically, and the role defaults decide who gets
 * it.
 *
 * An ability that exists in `GROUPS` but in no role default belongs to
 * `super_admin` only — which is the right place for anything
 * destructive.
 */
class SystemPermissions
{
    /**
     * The roles the system panel knows about.
     */
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_SUPPORT = 'support';

    /**
     * The master list of abilities, grouped for display.
     *
     * Each entry is `ability => label`. The label is what the
     * admin-user form shows next to the checkbox.
     *
     * The GROUP KEYS are also the permission GROUP identifiers used by
     * the Vue form's `groupLabels` map, so adding a group here means
     * adding its label to the front end as well.
     */
    public const GROUPS = [
        'tenants' => [
            Permissions::SYSTEM_TENANTS_VIEW => 'عرض المستأجرين',
            Permissions::SYSTEM_TENANTS_MANAGE => 'إدارة المستأجرين (تعليق/تفعيل/تمديد)',
            Permissions::SYSTEM_TENANTS_DELETE => 'حذف المستأجرين',
            Permissions::SYSTEM_TENANTS_IMPERSONATE => 'الدخول كمستأجر',
        ],
        'billing' => [
            Permissions::SYSTEM_SUBSCRIPTIONS_VIEW => 'عرض الاشتراكات',
            Permissions::SYSTEM_SUBSCRIPTIONS_MANAGE => 'إدارة الاشتراكات',
            Permissions::SYSTEM_INVOICES_VIEW => 'عرض الفواتير',
            Permissions::SYSTEM_INVOICES_MANAGE => 'إدارة الفواتير (إلغاء/تأكيد دفع)',
            Permissions::SYSTEM_PLANS_MANAGE => 'إدارة الباقات',
            Permissions::SYSTEM_PROMOCODES_MANAGE => 'إدارة الرموز الترويجية',
            Permissions::SYSTEM_PAYMENT_SETTINGS => 'إعدادات بوابات الدفع',
            Permissions::SYSTEM_PAYMENT_RETRY => 'إعادة محاولة الدفعات',
        ],
        'settings' => [
            Permissions::SYSTEM_SETTINGS_MANAGE => 'إعدادات المنصة',
            Permissions::SYSTEM_INTEGRATIONS_MANAGE => 'إدارة التكاملات',
            Permissions::SYSTEM_ANNOUNCEMENTS_MANAGE => 'إدارة الإعلانات',
            Permissions::SYSTEM_TEMPLATES_MANAGE => 'إدارة قوالب التواصل',
        ],
        'support' => [
            Permissions::SYSTEM_FEEDBACKS_MANAGE => 'إدارة بلاغات المستأجرين',
            Permissions::SYSTEM_CONTACT_MESSAGES_MANAGE => 'إدارة رسائل الزوار',
        ],
        'credits' => [
            Permissions::SYSTEM_CREDITS_MANAGE => 'إدارة أرصدة SMS و WhatsApp',
        ],
        'panel' => [
            Permissions::SYSTEM_NOTIFICATIONS_MANAGE => 'إدارة إشعارات اللوحة',
            Permissions::SYSTEM_DEVELOPER_ACCESS => 'مركز التطوير',
            Permissions::SYSTEM_ADMIN_USERS_VIEW => 'عرض حسابات المسؤولين',
            Permissions::SYSTEM_ADMIN_USERS_MANAGE => 'إدارة حسابات المسؤولين',
            Permissions::SYSTEM_ACTIVITY_LOG_VIEW => 'عرض سجل النشاط',
        ],
        'self' => [
            Permissions::SYSTEM_PROFILE_MANAGE => 'إدارة الملف الشخصي',
            Permissions::SYSTEM_SECURITY_2FA_MANAGE => 'إدارة المصادقة الثنائية',
        ],
    ];

    /**
     * Which abilities each role holds by default.
     *
     * The `permissions` column on an admin account is an OPTIONAL
     * OVERRIDE: when it is empty the role decides, and when it is
     * filled it replaces the role's list entirely.
     *
     * That two-step is deliberate. An empty list must not mean "no
     * permissions" — the accounts in the field today were created
     * before this registry existed and carry an empty list, so
     * treating empty as "nothing" would lock every one of them out.
     */
    public const ROLE_DEFAULTS = [
        self::ROLE_ADMIN => [
            // Tenants — everything except destroying one.
            Permissions::SYSTEM_TENANTS_VIEW,
            Permissions::SYSTEM_TENANTS_MANAGE,

            // Billing — the full operational surface.
            Permissions::SYSTEM_SUBSCRIPTIONS_VIEW,
            Permissions::SYSTEM_SUBSCRIPTIONS_MANAGE,
            Permissions::SYSTEM_INVOICES_VIEW,
            Permissions::SYSTEM_INVOICES_MANAGE,
            Permissions::SYSTEM_PLANS_MANAGE,
            Permissions::SYSTEM_PROMOCODES_MANAGE,
            Permissions::SYSTEM_PAYMENT_SETTINGS,
            Permissions::SYSTEM_PAYMENT_RETRY,

            // Settings.
            Permissions::SYSTEM_SETTINGS_MANAGE,
            Permissions::SYSTEM_INTEGRATIONS_MANAGE,
            Permissions::SYSTEM_ANNOUNCEMENTS_MANAGE,
            Permissions::SYSTEM_TEMPLATES_MANAGE,

            // Credits.
            Permissions::SYSTEM_CREDITS_MANAGE,

            // Panel.
            Permissions::SYSTEM_NOTIFICATIONS_MANAGE,
            Permissions::SYSTEM_DEVELOPER_ACCESS,
            Permissions::SYSTEM_ADMIN_USERS_VIEW,

            // Self.
            Permissions::SYSTEM_PROFILE_MANAGE,
            Permissions::SYSTEM_SECURITY_2FA_MANAGE,
        ],

        self::ROLE_SUPPORT => [
            // Support staff read the platform and handle its inbound
            // requests, but never move money or change configuration.
            Permissions::SYSTEM_TENANTS_VIEW,
            Permissions::SYSTEM_SUBSCRIPTIONS_VIEW,
            Permissions::SYSTEM_INVOICES_VIEW,
            Permissions::SYSTEM_FEEDBACKS_MANAGE,
            Permissions::SYSTEM_CONTACT_MESSAGES_MANAGE,
            Permissions::SYSTEM_NOTIFICATIONS_MANAGE,
            // The admin roster is a diagnostic surface — support staff
            // need to see who the admins are, but never to change one.
            Permissions::SYSTEM_ADMIN_USERS_VIEW,
            Permissions::SYSTEM_PROFILE_MANAGE,
            Permissions::SYSTEM_SECURITY_2FA_MANAGE,
        ],
    ];

    /**
     * Every ability, flattened.
     *
     * @return array<string, string> ability => label
     */
    public static function all(): array
    {
        return array_merge(...array_values(self::GROUPS));
    }

    /**
     * The grouped ability list — the shape the admin-user form needs.
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return self::GROUPS;
    }

    /**
     * The ability names only.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    /**
     * The abilities a role holds when it carries no explicit list.
     *
     * `super_admin` is not listed here on purpose: it bypasses the
     * permission list entirely (see AdminUser::isSuperAdmin), so there
     * is nothing to default.
     *
     * @return array<int, string>
     */
    public static function forRole(string $role): array
    {
        return self::ROLE_DEFAULTS[$role] ?? [];
    }

    /**
     * The effective permissions of an account.
     *
     * The account's own list wins when it carries one; otherwise the
     * role's defaults apply.
     *
     * @param array<int, string>|null $explicit
     * @return array<int, string>
     */
    public static function resolveFor(string $role, ?array $explicit = null): array
    {
        if ($explicit !== null && $explicit !== []) {
            return array_values(array_unique($explicit));
        }

        return self::forRole($role);
    }

    /**
     * Whether an ability exists in the registry.
     */
    public static function exists(string $ability): bool
    {
        return in_array($ability, self::names(), true);
    }

    /**
     * Keep only the abilities that exist in the registry.
     *
     * Used when an admin account's permission list is saved, so a
     * stale or hand-crafted value cannot introduce an ability the
     * panel does not know about.
     *
     * @param array<int, string> $abilities
     * @return array<int, string>
     */
    public static function filterValid(array $abilities): array
    {
        $known = self::names();

        return array_values(array_unique(array_filter(
            $abilities,
            fn ($ability) => is_string($ability) && in_array($ability, $known, true)
        )));
    }
}
