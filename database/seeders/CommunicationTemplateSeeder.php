<?php

namespace Database\Seeders;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Seeder;

class CommunicationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // 2FA Verification Code
            [
                'code' => '2fa_verification',
                'name' => 'رمز التحقق الثنائي',
                'type' => 'email',
                'subject' => 'رمز التحقق الثنائي - {app_name}',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>رمز التحقق الثنائي</h2>
    <p>مرحباً،</p>
    <p>يرجى استخدام الرمز التالي لإكمال عملية التحقق:</p>
    <div style="font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #4f46e5; text-align: center; margin: 30px 0; padding: 20px; background-color: #f3f4f6; border-radius: 8px;">
        {code}
    </div>
    <p>هذا الرمز صالح لمدة 10 دقائق. لا تشارك هذا الرمز مع أي شخص.</p>
</div>',
                'variables' => ['{code}', '{app_name}'],
            ],
            
            // Email Verification
            [
                'code' => 'email_verification',
                'name' => 'تفعيل البريد الإلكتروني',
                'type' => 'email',
                'subject' => 'تفعيل حسابك - {app_name}',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>🔐 تفعيل حسابك</h2>
    <p>مرحباً {name}،</p>
    <p>شكراً لتسجيلك في {app_name}!</p>
    <p>يرجى الضغط على الزر أدناه لتفعيل حسابك:</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{verification_url}" style="background-color: #4f46e5; color: white; padding: 14px 35px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold;">تفعيل الحساب</a>
    </div>
    <p style="color: #6b7280; font-size: 14px;">إذا لم تقم بإنشاء حساب، يمكنك تجاهل هذه الرسالة.</p>
    <p>فريق {app_name}</p>
</div>',
                'variables' => ['{name}', '{app_name}', '{verification_url}'],
            ],
            
            // Trial Activation
            [
                'code' => 'trial_activation',
                'name' => 'تفعيل الاشتراك التجريبي',
                'type' => 'email',
                'subject' => 'تم تفعيل اشتراكك التجريبي - {app_name}',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>🎉 مرحباً بك في {app_name}!</h2>
    <p>مرحباً {name}،</p>
    <p>تم تفعيل اشتراكك التجريبي بنجاح!</p>
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p><strong>تاريخ البدء:</strong> {start_date}</p>
        <p><strong>تاريخ الانتهاء:</strong> {end_date}</p>
        <p><strong>الباقة:</strong> {plan_name}</p>
    </div>
    <p>استمتع بجميع الميزات خلال فترة التجربة المجانية!</p>
    <p>فريق {app_name}</p>
</div>',
                'variables' => ['{name}', '{app_name}', '{start_date}', '{end_date}', '{plan_name}'],
            ],
            
            // Welcome User
            [
                'code' => 'welcome_user',
                'name' => 'ترحيب بمستخدم جديد',
                'type' => 'email',
                'subject' => 'مرحباً بك في {app_name}!',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>🎉 أهلاً وسهلاً!</h2>
    <p>مرحباً {name}،</p>
    <p>نحن سعداء بانضمامك إلينا في {app_name}!</p>
    <p>يمكنك الآن تسجيل الدخول والبدء في استخدام النظام:</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{login_url}" style="background-color: #4f46e5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">تسجيل الدخول</a>
    </div>
    <p>فريق {app_name}</p>
</div>',
                'variables' => ['{name}', '{app_name}', '{login_url}'],
            ],
            
            // New Invoice
            [
                'code' => 'new_invoice',
                'name' => 'إشعار فاتورة جديدة',
                'type' => 'email',
                'subject' => 'فاتورة جديدة #{invoice_number} - {app_name}',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>📄 فاتورة جديدة</h2>
    <p>مرحباً {name}،</p>
    <p>تم إنشاء فاتورة جديدة لحسابك:</p>
    <div style="background-color: #f3f4f6; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p><strong>رقم الفاتورة:</strong> {invoice_number}</p>
        <p><strong>المبلغ:</strong> {amount} ر.س</p>
        <p><strong>تاريخ الاستحقاق:</strong> {due_date}</p>
    </div>
    <p>فريق {app_name}</p>
</div>',
                'variables' => ['{name}', '{app_name}', '{invoice_number}', '{amount}', '{due_date}'],
            ],
            
            // Payment Confirmation
            [
                'code' => 'payment_confirmation',
                'name' => 'تأكيد الدفع',
                'type' => 'email',
                'subject' => 'تأكيد الدفع - {app_name}',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>✅ تم استلام الدفعة</h2>
    <p>مرحباً {name}،</p>
    <p>شكراً لك! تم استلام دفعتك بنجاح.</p>
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p><strong>المبلغ:</strong> {amount} ر.س</p>
        <p><strong>طريقة الدفع:</strong> {payment_method}</p>
        <p><strong>رقم المرجع:</strong> {reference}</p>
        <p><strong>التاريخ:</strong> {date}</p>
    </div>
    <p>فريق {app_name}</p>
</div>',
                'variables' => ['{name}', '{app_name}', '{amount}', '{payment_method}', '{reference}', '{date}'],
            ],
            
            // Subscription Expiry Warning
            [
                'code' => 'subscription_expiry',
                'name' => 'تنبيه انتهاء الاشتراك',
                'type' => 'email',
                'subject' => '⚠️ اشتراكك سينتهي قريباً - {app_name}',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>⚠️ تنبيه: اشتراكك سينتهي قريباً</h2>
    <p>مرحباً {name}،</p>
    <p>نود تذكيرك بأن اشتراكك سينتهي في <strong>{expiry_date}</strong>.</p>
    <p>لتجنب انقطاع الخدمة، يرجى تجديد اشتراكك في أقرب وقت.</p>
    <div style="text-align: center; margin: 30px 0;">
        <a href="{renew_url}" style="background-color: #4f46e5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; display: inline-block;">تجديد الاشتراك</a>
    </div>
    <p>فريق {app_name}</p>
</div>',
                'variables' => ['{name}', '{app_name}', '{expiry_date}', '{renew_url}'],
            ],
            
            // Appointment Reminder
            [
                'code' => 'appointment_reminder',
                'name' => 'تذكير بموعد',
                'type' => 'email',
                'subject' => '🔔 تذكير بموعدك - {app_name}',
                'content' => '<div style="font-family: sans-serif; text-align: right; direction: rtl;">
    <h2>🔔 تذكير بموعدك</h2>
    <p>مرحباً {customer_name}،</p>
    <p>نود تذكيرك بموعدك القادم:</p>
    <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p><strong>التاريخ:</strong> {date}</p>
        <p><strong>الوقت:</strong> {time}</p>
        <p><strong>الخدمة:</strong> {service}</p>
        <p><strong>المركبة:</strong> {vehicle}</p>
    </div>
    <p>نتطلع لخدمتكم!</p>
    <p>فريق {center_name}</p>
</div>',
                'variables' => ['{customer_name}', '{date}', '{time}', '{service}', '{vehicle}', '{center_name}', '{app_name}'],
            ],
        ];

        foreach ($templates as $template) {
            CommunicationTemplate::firstOrCreate(
                ['code' => $template['code']],
                array_merge($template, ['is_active' => true])
            );
        }
    }
}
