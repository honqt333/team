<?php

declare(strict_types=1);

return [
    'errors' => [
        'too_many_attempts' => 'You have made too many requests. Please try again in a few minutes.',
        'unauthenticated' => 'Please sign in to continue.',
        'malformed' => 'The phone number is not valid.',
    ],
    'auth' => [
        'code_sent' => 'If your number is registered, we sent you a verification code. Please check your phone.',
        'code_invalid' => 'The verification code is not valid or has expired.',
    ],
    'login' => [
        'title' => 'Customer portal',
        'subtitle' => 'Sign in with your phone number to access your vehicles and appointments.',
        'phone_label' => 'Phone number',
        'phone_placeholder' => '+966 5x xxx xxxx',
        'phone_hint' => 'We will send a 6-digit verification code to your phone.',
        'submit' => 'Send code',
        'back_to_site' => 'Back to the public site',
    ],
    'verify' => [
        'title' => 'Enter verification code',
        'subtitle' => 'We sent a 6-digit code to your phone.',
        'sent_to' => 'Code sent to',
        'code_label' => 'Verification code',
        'code_placeholder' => '••••••',
        'submit' => 'Verify',
        'change_number' => 'Use a different number',
        'expiry_notice' => 'The code expires in 10 minutes.',
    ],
    'dashboard' => [
        'title' => 'My account',
        'greeting' => 'Hi, :name',
        'back_to_site' => 'Public site',
        'logout' => 'Sign out',
        'vehicles' => 'My vehicles',
        'no_vehicles' => 'No vehicles are linked to your account yet.',
        'upcoming' => 'Upcoming appointments',
        'no_upcoming' => 'You have no upcoming appointments.',
        'until' => 'Until',
        'recent' => 'Recent activity',
        'no_recent' => 'No recent activity on your account.',
        'opened' => 'Opened',
        'expected_end' => 'Expected completion',
        'status_scheduled' => 'Scheduled',
        'status_confirmed' => 'Confirmed',
        'status_checked_in' => 'Checked in',
    ],
];
