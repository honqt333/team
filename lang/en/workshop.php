<?php

declare(strict_types=1);

return [
    'board' => [
        'title' => 'Service Center Board',
        'subtitle' => 'Live snapshot of active service items grouped by execution status',
        'total_active' => '{count} active items',
        'empty_column' => 'No items in this column',
        'open_work_order' => 'Open work order',
        'no_technicians' => 'No assigned staff',
        'started_at' => 'Started at',
        'not_started' => 'Not started yet',
        'due_date' => 'Due',
        'expected_delivery' => 'Expected delivery',
        'overdue_badge' => 'Overdue',
        'hold_reason_label' => 'Hold reason',
        'labor_paused' => 'Paused',
        'labor_running' => 'On clock',
        'departments' => [
            'pending' => 'Pending start',
            'in_progress' => 'In progress',
            'on_hold' => 'On hold',
            'ready_for_qc' => 'Ready for QC',
        ],
    ],
    'today' => [
        'check_in' => 'Check in',
        'checked_in_badge' => 'At the center',
        'empty' => 'No appointments scheduled for today',
        'more_services' => '+{count} more',
        'no_bay' => 'No bay',
        'no_tech' => 'No technician',
        'open_appointment' => 'Open appointment',
        'see_all' => 'See all appointments',
        'subtitle' => 'Bookings scheduled for today in this center',
        'title' => 'Today\'s appointments',
    ],
];
