<?php

return [
    // Work Order Status
    'work_order_created' => 'تم إنشاء كرت العمل بنجاح',
    'work_order_updated' => 'تم تحديث كرت العمل بنجاح',
    'work_order_deleted' => 'تم حذف كرت العمل بنجاح',
    'work_order_on_hold' => 'تم تعليق كرت العمل',
    'work_order_resumed' => 'تم استئناف كرت العمل',
    'work_order_cancelled' => 'تم إلغاء كرت العمل',
    'work_order_completed' => 'تم إكمال كرت العمل - خروج المركبة',
    
    // Work Order Status Errors
    'cannot_put_on_hold' => 'لا يمكن تعليق كرت العمل في هذه الحالة',
    'cannot_resume' => 'كرت العمل ليس معلقاً',
    'cannot_cancel_has_technicians_or_parts' => 'لا يمكن إلغاء كرت العمل - يوجد فنيين أو قطع غيار مرتبطة',
    'cannot_cancel_has_relations' => 'لا يمكن إلغاء كرت العمل - يرجى حذف جميع الخدمات، القطع، الفنيين، وإعادة أي مدفوعات أولاً',
    'cannot_complete_items_pending' => 'لا يمكن إكمال كرت العمل - هناك خدمات غير مكتملة',
    
    // Work Order Items
    'service_added' => 'تمت إضافة الخدمة بنجاح',
    'service_updated' => 'تم تحديث الخدمة بنجاح',
    'service_deleted' => 'تم حذف الخدمة بنجاح',
    'item_status_updated' => 'تم تحديث حالة الخدمة',
    'cannot_change_item_status' => 'لا يمكن تغيير حالة الخدمة - يوجد فنيين أو قطع غيار مرتبطة',
    'cannot_delete_item_has_parts_or_technicians' => 'لا يمكن إلغاء الخدمة حتى يتم إزالة جميع قطع الغيار والفنيين!',
    
    // Departments
    'department_added' => 'تمت إضافة القسم بنجاح',
    'department_removed' => 'تم إزالة القسم بنجاح',
    'cannot_remove_department_has_items' => 'لا يمكن إزالة القسم - يوجد خدمات مرتبطة به',
    
    // Technicians
    'technician_assigned' => 'تم تعيين الفني بنجاح',
    'technician_removed' => 'تم إزالة الفني بنجاح',
    'technician_not_belong_to_center' => 'هذا الفني لا ينتمي لهذا المركز',
    
    // Parts
    'part_added' => 'تمت إضافة قطعة الغيار بنجاح',
    'part_updated' => 'تم تحديث قطعة الغيار بنجاح',
    'part_deleted' => 'تم حذف قطعة الغيار بنجاح',
    
    // Notes
    'note_added' => 'تمت إضافة الملاحظة بنجاح',
    'note_deleted' => 'تم حذف الملاحظة بنجاح',

    // Service Deletion
    'service_has_quote_lines' => 'لا يمكن حذف الخدمة - يوجد :count تقييم يستخدم هذه الخدمة',
    'service_has_work_order_items' => 'لا يمكن حذف الخدمة - يوجد :count كرت عمل يستخدم هذه الخدمة',
    'service_has_quote_lines_with_codes' => 'لا يمكن حذف الخدمة - مستخدمة في التقييمات: :codes',
    'service_has_work_order_items_with_codes' => 'لا يمكن حذف الخدمة - مستخدمة في كروت العمل: :codes',

    // Photos & Attachments
    'photo_deleted' => 'تم حذف الصورة بنجاح',
    'photos_uploaded_successfully' => 'تم رفع الصور بنجاح',
    'attachments_uploaded_successfully' => 'تم رفع المرفقات بنجاح',
    'attachment_deleted' => 'تم حذف المرفق بنجاح',
];
