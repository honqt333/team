<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;

class CustomMailChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (method_exists($notification, 'toCustomMail')) {
            $notification->toCustomMail($notifiable);
        }
    }
}
