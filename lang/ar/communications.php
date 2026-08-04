<?php

declare(strict_types=1);

return [
    'appointment_messages' => [
        'title' => 'سجل رسائل المواعيد',
        'subtitle' => 'متابعة رسائل SMS للمواعيد وإعادة إرسال الرسائل الفاشلة',

        'filter_status' => 'الحالة',
        'filter_message_type' => 'نوع الرسالة',
        'filter_date_from' => 'من تاريخ',
        'filter_date_to' => 'إلى تاريخ',
        'filter_search' => 'بحث برقم الموعد أو اسم العميل',
        'clear_filters' => 'مسح الفلاتر',
        'results_count' => '{count} سجل',
        'empty' => 'لا توجد سجلات بهذه الفلاتر',

        'table' => [
            'queued_at' => 'وقت الإدراج',
            'attempted_at' => 'آخر محاولة',
            'message_type' => 'نوع الرسالة',
            'status' => 'الحالة',
            'customer' => 'العميل',
            'appointment' => 'الموعد',
            'branch' => 'الفرع',
            'scheduled_at' => 'وقت الموعد',
            'provider' => 'المزود',
            'provider_message_id' => 'معرّف رسالة المزود',
            'error_code' => 'رمز الخطأ',
            'actions' => 'إجراءات',
        ],

        'message_types' => [
            'booking_confirmation' => 'تأكيد الحجز',
            'booking_rescheduled' => 'تعديل الموعد',
            'booking_cancelled' => 'إلغاء الموعد',
            'reminder_24h' => 'تذكير قبل 24 ساعة',
            'reminder_2h' => 'تذكير قبل ساعتين',
        ],

        'statuses' => [
            'queued' => 'بانتظار الإرسال',
            'sending' => 'قيد الإرسال',
            'sent' => 'مُرسلة',
            'failed' => 'فشلت',
            'skipped' => 'تم تخطيها',
        ],

        'actions' => [
            'retry' => 'إعادة إرسال',
            'retry_aria' => 'إعادة إرسال الرسالة رقم :id',
        ],

        'confirm' => [
            'title' => 'تأكيد إعادة الإرسال',
            'body' => 'هل تريد إعادة إرسال هذه الرسالة للموعد؟',
            'appointment_label' => 'الموعد',
            'message_type_label' => 'نوع الرسالة',
            'scheduled_at_label' => 'وقت الموعد',
            'confirm_button' => 'نعم، أعد الإرسال',
            'cancel_button' => 'إلغاء',
        ],

        'retry' => [
            'success' => 'تمت إعادة إرسال الرسالة إلى الطابور.',
            'rejected' => 'لا يمكن إعادة إرسال هذه الرسالة. قد تكون الحالة قد تغيّرت أو الموعد أُلغي أو نُقل.',
        ],

        'audit' => [
            'retried' => 'أعاد المستخدم رقم :user_id إرسال الرسالة رقم :delivery_id من نوع :message_type.',
        ],

        'error_codes' => [
            'appointment_schedule_changed' => 'تغيّر وقت الموعد',
            'not_applicable_anymore' => 'لم تعد الرسالة مناسبة',
            'appointment_missing' => 'الموعد محذوف',
            'customer_missing' => 'العميل محذوف',
            'no_phone' => 'لا يوجد رقم هاتف',
            'customer_refused' => 'العميل رفض',
            'unknown_number' => 'رقم غير معروف لدى المزود',
            'rate_limited' => 'تجاوز حد الإرسال',
            'dispatch_failed' => 'فشل إرسال المهمة إلى الطابور',
        ],
    ],
];
