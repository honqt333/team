@component('mail::message')
    # ⏰ تذكير بموعد التجديد

    مرحباً **{{ $tenant->trade_name ?? $tenant->name }}**,

    @if ($daysRemaining === 1)
        اشتراكك في باقة **{{ $plan?->name_ar ?? 'الاشتراك' }}** ينتهي **غداً**.
    @else
        اشتراكك في باقة **{{ $plan?->name_ar ?? 'الاشتراك' }}** ينتهي خلال **{{ $daysRemaining }} أيام**.
    @endif

    ---

    ## تفاصيل الاشتراك

    | | |
    |---|---|
    | **الباقة** | {{ $plan?->name_ar ?? 'الاشتراك' }} |
    | **تاريخ الانتهاء** | {{ $subscription->ends_at?->format('Y-m-d') }} |
    | **التجديد التلقائي** | {{ $subscription->auto_renew ? '✅ مفعّل' : '❌ غير مفعّل' }} |

    ---

    @if ($subscription->auto_renew)
        > **ملاحظة:** التجديد التلقائي مفعّل. سيتم تجديد اشتراكك تلقائياً وإرسال فاتورة جديدة.
    @else
        لضمان استمرارية الخدمة، يرجى تجديد اشتراكك قبل انتهاء الصلاحية.

        @component('mail::button', ['url' => config('app.url') . '/billing/renew'])
            تجديد الاشتراك
        @endcomponent
    @endif

    شكراً لاختياركم **Khidma Pro**

    {{ config('app.name') }}
@endcomponent
