@component('mail::message')
    # فاتورة اشتراك {{ $invoice->status === 'paid' ? '- مدفوعة ✓' : '' }}

    مرحباً **{{ $tenant->trade_name ?? $tenant->name }}**,

    @if ($invoice->status === 'paid')
        تم استلام دفعتكم بنجاح. شكراً لثقتكم بنا!
    @else
        مرفق فاتورة اشتراككم في باقة **{{ $plan?->name_ar ?? 'الاشتراك' }}**.
    @endif

    ---

    ## تفاصيل الفاتورة

    | | |
    |---|---|
    | **رقم الفاتورة** | {{ $invoice->invoice_number }} |
    | **تاريخ الإصدار** | {{ $invoice->created_at->format('Y-m-d') }} |
    | **تاريخ الاستحقاق** | {{ $invoice->due_date->format('Y-m-d') }} |
    | **المجموع** | **{{ number_format($invoice->total, 2) }} ر.س** |
    | **الحالة** | {{ $invoice->status === 'paid' ? '✅ مدفوعة' : '⏳ معلقة' }} |

    ---

    @if ($invoice->status !== 'paid')
        @component('mail::button', ['url' => config('app.url') . '/system/invoices/' . $invoice->id . '/pay'])
            ادفع الآن
        @endcomponent
    @endif

    إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.

    شكراً لاختياركم **Khidma Pro**

    {{ config('app.name') }}
@endcomponent
