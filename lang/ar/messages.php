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
    'cannot_complete_items_pending' => 'لا يمكن إكمال كرت العمل - هناك خدمات غير مكتملة',
    
    // Work Order Items
    'service_added' => 'تمت إضافة الخدمة بنجاح',
    'service_updated' => 'تم تحديث الخدمة بنجاح',
    'service_deleted' => 'تم حذف الخدمة بنجاح',
    'item_status_updated' => 'تم تحديث حالة الخدمة',
    'cannot_change_item_status' => 'لا يمكن تغيير حالة الخدمة - يوجد فنيين أو قطع غيار مرتبطة',
    
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


];
