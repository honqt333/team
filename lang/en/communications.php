<?php

declare(strict_types=1);

return [
    'appointment_messages' => [
        'title' => 'Appointment message log',
        'subtitle' => 'Track appointment SMS deliveries and resend failed messages',

        'filter_status' => 'Status',
        'filter_message_type' => 'Message type',
        'filter_date_from' => 'From date',
        'filter_date_to' => 'To date',
        'filter_search' => 'Search by appointment id or customer name',
        'clear_filters' => 'Clear filters',
        'results_count' => '{count} entries',
        'empty' => 'No deliveries match these filters',

        'table' => [
            'queued_at' => 'Queued at',
            'attempted_at' => 'Last attempt',
            'message_type' => 'Message type',
            'status' => 'Status',
            'customer' => 'Customer',
            'appointment' => 'Appointment',
            'branch' => 'Branch',
            'scheduled_at' => 'Scheduled at',
            'provider' => 'Provider',
            'provider_message_id' => 'Provider message id',
            'error_code' => 'Error code',
            'actions' => 'Actions',
        ],

        'message_types' => [
            'booking_confirmation' => 'Booking confirmation',
            'booking_rescheduled' => 'Reschedule',
            'booking_cancelled' => 'Cancellation',
            'reminder_24h' => 'Reminder (24 hours)',
            'reminder_2h' => 'Reminder (2 hours)',
        ],

        'statuses' => [
            'queued' => 'Queued',
            'sending' => 'Sending',
            'sent' => 'Sent',
            'failed' => 'Failed',
            'skipped' => 'Skipped',
        ],

        'actions' => [
            'retry' => 'Resend',
            'retry_aria' => 'Resend delivery row :id',
        ],

        'confirm' => [
            'title' => 'Confirm resend',
            'body' => 'Do you want to resend this message for the appointment?',
            'appointment_label' => 'Appointment',
            'message_type_label' => 'Message type',
            'scheduled_at_label' => 'Scheduled at',
            'confirm_button' => 'Yes, resend',
            'cancel_button' => 'Cancel',
        ],

        'retry' => [
            'success' => 'The message has been queued for resend.',
            'rejected' => 'This message can no longer be resent. The status may have changed, or the appointment may have been cancelled or moved.',
        ],

        'audit' => [
            'retried' => 'User :user_id resent delivery :delivery_id of type :message_type.',
        ],

        'error_codes' => [
            'appointment_schedule_changed' => 'Appointment time changed',
            'not_applicable_anymore' => 'No longer applicable',
            'appointment_missing' => 'Appointment deleted',
            'customer_missing' => 'Customer deleted',
            'no_phone' => 'No phone number',
            'customer_refused' => 'Customer refused',
            'unknown_number' => 'Unknown number',
            'rate_limited' => 'Rate limited',
            'dispatch_failed' => 'Dispatch failed',
        ],
    ],
];
