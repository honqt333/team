<?php

return [
    // Work Order Status
    'work_order_created' => 'Work order created successfully',
    'work_order_updated' => 'Work order updated successfully',
    'work_order_deleted' => 'Work order deleted successfully',
    'work_order_on_hold' => 'Work order put on hold',
    'work_order_resumed' => 'Work order resumed',
    'work_order_cancelled' => 'Work order cancelled',
    'work_order_completed' => 'Work order completed - Vehicle exit',
    
    // Work Order Status Errors
    'cannot_put_on_hold' => 'Cannot put work order on hold in this status',
    'cannot_resume' => 'Work order is not on hold',
    'cannot_cancel_has_technicians_or_parts' => 'Cannot cancel work order - has assigned technicians or parts',
    'cannot_complete_items_pending' => 'Cannot complete work order - pending items exist',
    
    // Work Order Items
    'service_added' => 'Service added successfully',
    'service_updated' => 'Service updated successfully',
    'service_deleted' => 'Service deleted successfully',
    'item_status_updated' => 'Item status updated',
    'cannot_change_item_status' => 'Cannot change item status - has technicians or parts',
    
    // Departments
    'department_added' => 'Department added successfully',
    'department_removed' => 'Department removed successfully',
    'cannot_remove_department_has_items' => 'Cannot remove department - has linked items',
    
    // Technicians
    'technician_assigned' => 'Technician assigned successfully',
    'technician_removed' => 'Technician removed successfully',
    'technician_not_belong_to_center' => 'This technician does not belong to this center',
    
    // Parts
    'part_added' => 'Part added successfully',
    'part_updated' => 'Part updated successfully',
    'part_deleted' => 'Part deleted successfully',
    
    // Notes
    'note_added' => 'Note added successfully',
    'note_deleted' => 'Note deleted successfully',


];
