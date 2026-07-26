@component('mail::message')
{!! nl2br(e($bodyText)) !!}

@component('mail::button', ['url' => url('/app/billing')])
{{ app()->getLocale() === 'ar' ? 'عرض الفاتورة وسدادها' : 'View invoice and pay' }}
@endcomponent

@endcomponent
