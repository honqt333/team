<?php

return [
    'activities' => [
        'actions' => [
            'created' => 'تم إنشاء كرت العمل',
            'updated' => 'تم تحديث بيانات كرت العمل',
            'status_changed' => 'تم تغيير حالة كرت العمل إلى :status',
            'item_added' => 'تمت إضافة خدمة: :title',
            'item_updated' => 'تعديل مسمى الخدمة: :title',
            'item_deleted' => 'تم حذف خدمة: :title',
            'part_added' => 'تمت إضافة قطعة: :name',
            'part_updated' => 'تم تحديث قطعة: :name',
            'part_deleted' => 'تم حذف قطعة: :name',
            'payment_added' => 'تمت إضافة دفعة بقيمة :amount',
            'photos_uploaded' => 'تم رفع صور جديدة',
            'attachments_uploaded' => 'تم رفع مرفقات جديدة',
            'technician_assigned' => 'تم تعيين فني: :name للخدمة :service',
            'technician_removed' => 'تم إزالة فني من الخدمة :service',
            'condition_updated' => 'تم تحديث تقرير حالة المركبة',
        ],
    ],
    'status' => [
        'draft' => 'مسودة',
        'open' => 'مفتوح',
        'in_progress' => 'قيد العمل',
        'on_hold' => 'معلق',
        'done' => 'مكتمل',
        'cancelled' => 'ملغي',
        'closed' => 'مغلق',
    ],
];
