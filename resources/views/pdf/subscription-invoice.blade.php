@php
    $pdfLocale = $locale ?? app()->getLocale();
    $isRtl = $pdfLocale === 'ar';
    $billingCycle = strtolower((string) ($subscription?->billing_cycle ?? ''));
    if (!in_array($billingCycle, ['monthly', 'yearly', 'annual'], true) && $subscription?->starts_at && $subscription?->ends_at) {
        $days = $subscription->starts_at->diffInDays($subscription->ends_at);
        $billingCycle = $days <= 45 ? 'monthly' : 'yearly';
    }
    $isMonthly = $billingCycle === 'monthly';
    $cycleLabel = $isRtl
        ? ($isMonthly ? 'اشتراك شهري' : 'اشتراك سنوي')
        : ($isMonthly ? 'Monthly Subscription' : 'Annual Subscription');
@endphp
<!DOCTYPE html>
<html lang="{{ $pdfLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $isRtl ? 'فاتورة اشتراك' : 'Subscription Invoice' }} - {{ $invoice->invoice_number }}</title>
    <style>
        /* Register IBM Plex Sans Arabic via @font-face so DomPDF picks it up
           when CSS references the family name. The file is
           committed at resources/fonts/ and registered as a
           fontDir in AppServiceProvider. */
        @font-face {
            font-family: 'IBM Plex Sans Arabic';
            font-weight: normal;
            font-style: normal;
            src: url('file://{{ base_path("resources/fonts/IBMPlexSansArabic-Regular.ttf") }}') format('truetype');
        }
        @font-face {
            font-family: 'IBM Plex Sans Arabic';
            font-weight: bold;
            font-style: normal;
            src: url('file://{{ base_path("resources/fonts/IBMPlexSansArabic-Bold.ttf") }}') format('truetype');
        }

        @page {
            size: A4 portrait;
            margin: 11mm 12mm 11mm 12mm;
        }

        * {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 11.5px;
            line-height: 1.35;
            color: #1f2937;
            direction: {{ $isRtl ? 'rtl' : 'ltr' }};
        }

        /* DomPDF renders HTML <table> columns physically left-to-right regardless of dir="rtl",
           so two-column and multi-column sections place left-hand cells first in markup. */
        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table {
            margin-bottom: 14px;
            border-bottom: 2px solid #e5e7eb;
        }

        .header-table td {
            padding-bottom: 10px;
            vertical-align: top;
        }

        .logo {
            max-height: 60px;
            max-width: 150px;
            margin-bottom: 6px;
        }

        .company-name {
            font-size: 19px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        .company-meta {
            color: #6b7280;
            font-size: 11px;
            line-height: 1.6;
        }

        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 4px;
        }

        .invoice-number {
            font-size: 13px;
            font-weight: bold;
            color: #4b5563;
            direction: ltr;
        }

        .box-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: middle;
        }

        .info-label {
            width: 130px;
            color: #6b7280;
            text-align: {{ $isRtl ? 'right' : 'left' }};
            font-size: 11px;
        }

        .info-value {
            color: #111827;
            font-weight: bold;
            text-align: right;
            font-size: 12px;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
            text-align: right;
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid #e5e7eb;
        }

        .details-section {
            margin-bottom: 16px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        table.items th {
            background: #4f46e5;
            color: #ffffff;
            padding: 10px 12px;
            font-size: 11px;
            font-weight: bold;
        }

        table.items td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        table.items tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .totals-wrap {
            width: 100%;
            margin-bottom: 12px;
        }

        table.totals-table {
            width: 310px;
            border-collapse: collapse;
            margin-left: 0;
            margin-right: auto;
        }

        table.totals-table td {
            padding: 7px 12px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11.5px;
        }

        table.totals-table tr.total-row td {
            border-bottom: none;
            background: #4f46e5;
            color: #ffffff;
            font-size: 13.5px;
            font-weight: bold;
            padding: 9px 12px;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 14px;
            font-size: 11px;
            font-weight: bold;
        }

        .status-paid {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background: #f3f4f6;
            color: #4b5563;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }

        .bank-info {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            padding: 14px 16px;
            border-radius: 8px;
            margin-top: 16px;
            text-align: right;
        }

        .bank-info-title {
            font-weight: bold;
            color: #166534;
            margin-bottom: 6px;
            font-size: 12px;
        }

        .footer {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
            line-height: 1.4;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <table class="layout-table header-table">
        <tr>
            @if ($isRtl)
                <td style="width: 45%; text-align: left;">
                    <div class="invoice-title">فاتورة اشتراك</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                </td>
                <td style="width: 55%; text-align: right;">
                    @if (!empty($company['logo_url']))
                        <img src="{{ $company['logo_url'] }}" class="logo" alt="logo" />
                    @endif
                    <div class="company-name">{{ $company['name'] ?? config('app.name_ar', 'خدمة برو') }}</div>
                    <div class="company-meta">
                        @if (!empty($company['cr_number']))
                            السجل التجاري: <span style="direction: ltr; display: inline-block;">{{ $company['cr_number'] }}</span><br>
                        @endif
                        @if (!empty($company['vat_number']))
                            الرقم الضريبي: <span style="direction: ltr; display: inline-block;">{{ $company['vat_number'] }}</span><br>
                        @endif
                        @if (!empty($company['phone']))
                            <span style="direction: ltr; display: inline-block;">{{ $company['phone'] }}</span><br>
                        @endif
                        @if (!empty($company['email']))
                            {{ $company['email'] }}<br>
                        @endif
                        @if (!empty($company['website']))
                            {{ $company['website'] }}
                        @endif
                    </div>
                </td>
            @else
                <td style="width: 55%; text-align: left;">
                    @if (!empty($company['logo_url']))
                        <img src="{{ $company['logo_url'] }}" class="logo" alt="logo" />
                    @endif
                    <div class="company-name">{{ $company['name_en'] ?? $company['name'] ?? config('app.name', 'Khidmah Pro') }}</div>
                    <div class="company-meta">
                        @if (!empty($company['cr_number']))
                            CR No: <span style="direction: ltr; display: inline-block;">{{ $company['cr_number'] }}</span><br>
                        @endif
                        @if (!empty($company['vat_number']))
                            VAT No: <span style="direction: ltr; display: inline-block;">{{ $company['vat_number'] }}</span><br>
                        @endif
                        @if (!empty($company['phone']))
                            <span style="direction: ltr; display: inline-block;">{{ $company['phone'] }}</span><br>
                        @endif
                        @if (!empty($company['email']))
                            {{ $company['email'] }}<br>
                        @endif
                        @if (!empty($company['website']))
                            {{ $company['website'] }}
                        @endif
                    </div>
                </td>
                <td style="width: 45%; text-align: right;">
                    <div class="invoice-title">Subscription Invoice</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                </td>
            @endif
        </tr>
    </table>

    <!-- Invoice Meta -->
    <div class="box-card">
        <table class="info-table">
            @php
                $statusMeta = match ($invoice->status) {
                    'paid' => ['class' => 'status-paid', 'label' => $isRtl ? 'مدفوعة' : 'Paid'],
                    'cancelled' => ['class' => 'status-cancelled', 'label' => $isRtl ? 'ملغية' : 'Cancelled'],
                    'failed' => ['class' => 'status-failed', 'label' => $isRtl ? 'فشلت' : 'Failed'],
                    'refunded' => ['class' => 'status-cancelled', 'label' => $isRtl ? 'مستردة' : 'Refunded'],
                    default => ['class' => 'status-pending', 'label' => $isRtl ? 'معلقة' : 'Pending'],
                };
            @endphp
            @if ($isRtl)
                <tr>
                    <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ ($invoice->issued_at ?? $invoice->created_at)?->format('Y-m-d') ?? '—' }}</span></td>
                    <td class="info-label">تاريخ الإصدار:</td>
                </tr>
                <tr>
                    <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $invoice->due_at?->format('Y-m-d') ?? '—' }}</span></td>
                    <td class="info-label">تاريخ الاستحقاق:</td>
                </tr>
                @if ($invoice->paid_at)
                    <tr>
                        <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $invoice->paid_at->format('Y-m-d') }}</span></td>
                        <td class="info-label">تاريخ السداد:</td>
                    </tr>
                @endif
                <tr>
                    <td class="info-value"><span class="status-badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></td>
                    <td class="info-label">الحالة:</td>
                </tr>
            @else
                <tr>
                    <td class="info-label">Issue Date:</td>
                    <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ ($invoice->issued_at ?? $invoice->created_at)?->format('Y-m-d') ?? '—' }}</span></td>
                </tr>
                <tr>
                    <td class="info-label">Due Date:</td>
                    <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $invoice->due_at?->format('Y-m-d') ?? '—' }}</span></td>
                </tr>
                @if ($invoice->paid_at)
                    <tr>
                        <td class="info-label">Paid Date:</td>
                        <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $invoice->paid_at->format('Y-m-d') }}</span></td>
                    </tr>
                @endif
                <tr>
                    <td class="info-label">Status:</td>
                    <td class="info-value"><span class="status-badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Customer Info -->
    <div class="details-section">
        <div class="section-title">{{ $isRtl ? 'بيانات العميل' : 'Customer Information' }}</div>
        <table class="info-table">
            @if ($isRtl)
                <tr>
                    <td class="info-value">{{ $tenant?->trade_name ?? $tenant?->name ?? '—' }}</td>
                    <td class="info-label">الاسم التجاري:</td>
                </tr>
                @if ($tenant?->cr_number)
                    <tr>
                        <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $tenant->cr_number }}</span></td>
                        <td class="info-label">السجل التجاري:</td>
                    </tr>
                @endif
                @if ($tenant?->vat_number)
                    <tr>
                        <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $tenant->vat_number }}</span></td>
                        <td class="info-label">الرقم الضريبي:</td>
                    </tr>
                @endif
                @if ($tenant?->email)
                    <tr>
                        <td class="info-value">{{ $tenant->email }}</td>
                        <td class="info-label">البريد الإلكتروني:</td>
                    </tr>
                @endif
            @else
                <tr>
                    <td class="info-label">Trade Name:</td>
                    <td class="info-value">{{ $tenant?->trade_name ?? $tenant?->name ?? '—' }}</td>
                </tr>
                @if ($tenant?->cr_number)
                    <tr>
                        <td class="info-label">CR No:</td>
                        <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $tenant->cr_number }}</span></td>
                    </tr>
                @endif
                @if ($tenant?->vat_number)
                    <tr>
                        <td class="info-label">VAT No:</td>
                        <td class="info-value"><span style="direction: ltr; display: inline-block;">{{ $tenant->vat_number }}</span></td>
                    </tr>
                @endif
                @if ($tenant?->email)
                    <tr>
                        <td class="info-label">Email:</td>
                        <td class="info-value">{{ $tenant->email }}</td>
                    </tr>
                @endif
            @endif
        </table>
    </div>

    <!-- Items Table -->
    @php
        $subCurrency = $invoice->currency ?? config('app.default_currency', 'SAR');
        $subCurrencyLabel = \App\Support\CountryCurrencyRegistry::currencyLabel($subCurrency, $pdfLocale);
        $planDisplayName = $isRtl
            ? ($plan?->name_ar ?? $plan?->name_en ?? 'باقة الاشتراك')
            : ($plan?->name_en ?? $plan?->name_ar ?? 'Subscription Plan');
    @endphp
    <table class="items">
        <thead>
            @if ($isRtl)
                <tr>
                    <th style="width: 18%; text-align: left;">المجموع</th>
                    <th style="width: 18%; text-align: left;">السعر</th>
                    <th style="width: 24%; text-align: center;">الفترة</th>
                    <th style="width: 40%; text-align: right;">الوصف</th>
                </tr>
            @else
                <tr>
                    <th style="width: 40%; text-align: left;">Description</th>
                    <th style="width: 24%; text-align: center;">Period</th>
                    <th style="width: 18%; text-align: right;">Price</th>
                    <th style="width: 18%; text-align: right;">Total</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @if ($isRtl)
                <tr>
                    <td style="text-align: left; font-weight: bold; white-space: nowrap;">
                        <span style="direction: ltr; display: inline-block;">{{ number_format($invoice->subtotal, 2) }}</span> {{ $subCurrencyLabel }}
                    </td>
                    <td style="text-align: left; white-space: nowrap;">
                        <span style="direction: ltr; display: inline-block;">{{ number_format($invoice->subtotal, 2) }}</span> {{ $subCurrencyLabel }}
                    </td>
                    <td style="text-align: center; font-size: 11px;">
                        <span style="direction: ltr; display: inline-block;">{{ $subscription?->starts_at?->format('Y-m-d') ?? '-' }}</span><br>
                        إلى <span style="direction: ltr; display: inline-block; margin-right: 4px;">{{ $subscription?->ends_at?->format('Y-m-d') ?? '-' }}</span>
                    </td>
                    <td style="text-align: right;">
                        <strong>{{ $planDisplayName }}</strong><br>
                        <span style="color: #6b7280; font-size: 10px;">{{ $cycleLabel }}</span>
                    </td>
                </tr>
            @else
                <tr>
                    <td style="text-align: left;">
                        <strong>{{ $planDisplayName }}</strong><br>
                        <span style="color: #6b7280; font-size: 10px;">{{ $cycleLabel }}</span>
                    </td>
                    <td style="text-align: center; font-size: 11px;">
                        <span style="direction: ltr; display: inline-block;">{{ $subscription?->starts_at?->format('Y-m-d') ?? '-' }}</span><br>
                        to <span style="direction: ltr; display: inline-block;">{{ $subscription?->ends_at?->format('Y-m-d') ?? '-' }}</span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        {{ number_format($invoice->subtotal, 2) }} {{ $subCurrencyLabel }}
                    </td>
                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">
                        {{ number_format($invoice->subtotal, 2) }} {{ $subCurrencyLabel }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-wrap">
        <table class="totals-table">
            @if ($isRtl)
                <tr>
                    <td style="text-align: left; font-weight: bold; white-space: nowrap;">
                        {{ number_format($invoice->subtotal, 2) }} {{ $subCurrencyLabel }}
                    </td>
                    <td style="text-align: right; color: #6b7280;">المجموع الفرعي</td>
                </tr>
                @if ($invoice->discount > 0)
                    <tr>
                        <td style="text-align: left; font-weight: bold; color: #16a34a; white-space: nowrap;">
                            - {{ number_format($invoice->discount, 2) }} {{ $subCurrencyLabel }}
                        </td>
                        <td style="text-align: right; color: #6b7280;">الخصم</td>
                    </tr>
                @endif
                <tr>
                    <td style="text-align: left; font-weight: bold; white-space: nowrap;">
                        {{ number_format($invoice->vat, 2) }} {{ $subCurrencyLabel }}
                    </td>
                    <td style="text-align: right; color: #6b7280;">ضريبة القيمة المضافة ({{ $vat_rate }}%)</td>
                </tr>
                <tr class="total-row">
                    <td style="text-align: left; white-space: nowrap;">
                        {{ number_format($invoice->total, 2) }} {{ $subCurrencyLabel }}
                    </td>
                    <td style="text-align: right;">الإجمالي</td>
                </tr>
            @else
                <tr>
                    <td style="text-align: left; color: #6b7280;">Subtotal</td>
                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">
                        {{ number_format($invoice->subtotal, 2) }} {{ $subCurrencyLabel }}
                    </td>
                </tr>
                @if ($invoice->discount > 0)
                    <tr>
                        <td style="text-align: left; color: #6b7280;">Discount</td>
                        <td style="text-align: right; font-weight: bold; color: #16a34a; white-space: nowrap;">
                            - {{ number_format($invoice->discount, 2) }} {{ $subCurrencyLabel }}
                        </td>
                    </tr>
                @endif
                <tr>
                    <td style="text-align: left; color: #6b7280;">VAT ({{ $vat_rate }}%)</td>
                    <td style="text-align: right; font-weight: bold; white-space: nowrap;">
                        {{ number_format($invoice->vat, 2) }} {{ $subCurrencyLabel }}
                    </td>
                </tr>
                <tr class="total-row">
                    <td style="text-align: left;">Total</td>
                    <td style="text-align: right; white-space: nowrap;">
                        {{ number_format($invoice->total, 2) }} {{ $subCurrencyLabel }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

    @if ($invoice->status === 'pending')
        <!-- Bank Info -->
        <div class="bank-info">
            <div class="bank-info-title">{{ $isRtl ? 'معلومات الدفع' : 'Payment Information' }}</div>
            <p style="margin: 0; font-size: 11px;">
                @if ($isRtl)
                    يمكنك الدفع عبر البوابة الإلكترونية أو التحويل البنكي.<br>
                    يرجى ذكر رقم الفاتورة (<span style="direction: ltr; display: inline-block;">{{ $invoice->invoice_number }}</span>) في وصف التحويل.
                @else
                    You may pay via the online portal or bank transfer.<br>
                    Please reference invoice number (<strong>{{ $invoice->invoice_number }}</strong>) in the transfer description.
                @endif
            </p>
        </div>
    @endif

    <!-- Footer + optional company stamp -->
    @if (!empty($company['stamp_url']))
        <div style="position: fixed; bottom: 60px; right: 40px; opacity: 0.92;">
            <img src="{{ $company['stamp_url'] }}" style="height: 110px; max-width: 180px; object-fit: contain;" alt="Stamp" />
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        @if ($isRtl)
            <p style="margin: 2px 0;">شكراً لاختياركم {{ $company['name'] ?? config('app.name_ar', 'خدمة برو') }}</p>
            @if (!empty($company['name_en']))
                <p style="margin: 2px 0; font-size: 9px; color: #9ca3af;">{{ $company['name_en'] }}</p>
            @endif
            <p style="margin: 2px 0;">هذه فاتورة إلكترونية صادرة آلياً ولا تحتاج إلى توقيع</p>
        @else
            <p style="margin: 2px 0;">Thank you for choosing {{ $company['name_en'] ?? $company['name'] ?? config('app.name', 'Khidmah Pro') }}</p>
            <p style="margin: 2px 0;">This is an electronically generated invoice and does not require a signature.</p>
        @endif
    </div>
</body>

</html>
