<?php

return [
    'activities' => [
        'actions' => [
            'created' => 'Work Order Created',
            'updated' => 'Work Order Updated',
            'status_changed' => 'Status changed to :status',
            'item_added' => 'Service added: :title',
            'item_updated' => 'Service title updated: :title',
            'item_deleted' => 'Service deleted: :title',
            'part_added' => 'Part added: :name',
            'part_updated' => 'Part updated: :name',
            'part_deleted' => 'Part deleted: :name',
            'payment_added' => 'Payment added: :amount',
            'photos_uploaded' => 'New photos uploaded',
            'attachments_uploaded' => 'New attachments uploaded',
            'technician_assigned' => 'Technician :name assigned to :service',
            'technician_removed' => 'Technician removed from :service',
            'condition_updated' => 'Vehicle condition report updated',
        ],
    ],
    'status' => [
        'draft' => 'Draft',
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'on_hold' => 'On Hold',
        'done' => 'Done',
        'cancelled' => 'Cancelled',
        'closed' => 'Closed',
    ],
];
