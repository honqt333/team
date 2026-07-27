@component('mail::message')
{!! nl2br(e($bodyText)) !!}

@component('mail::button', ['url' => url('/app/billing')])
{{ app()->getLocale() === 'ar' ? 'عرض الفاتورة وسدادها' : 'View invoice and pay' }}
@endcomponent

@if (! empty($attachmentPath))
    @php
        $attachmentName = basename($attachmentPath);
    @endphp
    <p style="margin-top: 20px; font-size: 13px; color: #666;">
        {{ app()->getLocale() === 'ar'
            ? '📎 مرفق نسخة من الفاتورة للاطلاع'
            : '📎 A copy of the invoice is attached for your reference' }}
        ({{ $attachmentName }})
    </p>
@endif

@endcomponent
