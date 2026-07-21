@component('mail::message')
    # تم تفعيل اشتراكك بنجاح ✓

    مرحباً **{{ $recipientName }}**,

    شكراً لاشتراكك في **{{ $planName }}**. تم استلام دفعتك بنجاح واشتراكك أصبح نشطاً.

    ---

    ## تفاصيل الاشتراك

    | | |
    |---|---|
    | **رقم الدفع** | #{{ $payment->id }} |
    | **الباقة** | {{ $planName }} |
    | **المبلغ المدفوع** | **{{ $amount }} {{ $currency }}** |
    | **تاريخ التفعيل** | {{ $subscription->starts_at?->format('Y-m-d') ?? now()->format('Y-m-d') }} |
    | **تاريخ الانتهاء** | {{ $endsAt ?? '-' }} |
    | **طريقة الدفع** | {{ $payment->payment_method ?? $payment->gateway }} |
    | **الحالة** | ✅ نشط |

    ---

    @component('mail::button', ['url' => $dashboardUrl])
        الذهاب إلى لوحة التحكم
    @endcomponent

    إذا كان لديك أي استفسار، لا تتردد في التواصل مع فريق الدعم.

    مع أطيب التحيات,
    {{ config('app.name', 'Carag') }}
@endcomponent
