<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>فاتورة اشتراك - {{ $invoice->invoice_number }}</title>
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
            direction: rtl;
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
            text-align: right;
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
    <!-- Header (left column = Invoice Title, right column = Platform Identity) -->
    <table class="layout-table header-table">
        <tr>
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
                        السجل التجاري: {{ $company['cr_number'] }}<br>
                    @endif
                    @if (!empty($company['vat_number']))
                        الرقم الضريبي: {{ $company['vat_number'] }}<br>
                    @endif
                    @if (!empty($company['phone']))
                        {{ $company['phone'] }}<br>
                    @endif
                    @if (!empty($company['email']))
                        {{ $company['email'] }}<br>
                    @endif
                    @if (!empty($company['website']))
                        {{ $company['website'] }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Invoice Meta -->
    <div class="box-card">
        <table class="info-table">
            <tr>
                <td class="info-value">{{ ($invoice->issued_at ?? $invoice->created_at)?->format('Y-m-d') ?? '—' }}</td>
                <td class="info-label">تاريخ الإصدار:</td>
            </tr>
            <tr>
                <td class="info-value">{{ $invoice->due_at?->format('Y-m-d') ?? '—' }}</td>
                <td class="info-label">تاريخ الاستحقاق:</td>
            </tr>
            @if ($invoice->paid_at)
                <tr>
                    <td class="info-value">{{ $invoice->paid_at->format('Y-m-d') }}</td>
                    <td class="info-label">تاريخ السداد:</td>
                </tr>
            @endif
            <tr>
                <td class="info-value">
                    @php
                        $statusMeta = match ($invoice->status) {
                            'paid' => ['class' => 'status-paid', 'label' => 'مدفوعة'],
                            'cancelled' => ['class' => 'status-cancelled', 'label' => 'ملغية'],
                            'failed' => ['class' => 'status-failed', 'label' => 'فشلت'],
                            'refunded' => ['class' => 'status-cancelled', 'label' => 'مستردة'],
                            default => ['class' => 'status-pending', 'label' => 'معلقة'],
                        };
                    @endphp
                    <span class="status-badge {{ $statusMeta['class'] }}">
                        {{ $statusMeta['label'] }}
                    </span>
                </td>
                <td class="info-label">الحالة:</td>
            </tr>
        </table>
    </div>

    <!-- Customer Info -->
    <div class="details-section">
        <div class="section-title">بيانات العميل</div>
        <table class="info-table">
            <tr>
                <td class="info-value">{{ $tenant?->trade_name ?? $tenant?->name ?? '—' }}</td>
                <td class="info-label">الاسم التجاري:</td>
            </tr>
            @if ($tenant?->cr_number)
                <tr>
                    <td class="info-value">{{ $tenant->cr_number }}</td>
                    <td class="info-label">السجل التجاري:</td>
                </tr>
            @endif
            @if ($tenant?->vat_number)
                <tr>
                    <td class="info-value">{{ $tenant->vat_number }}</td>
                    <td class="info-label">الرقم الضريبي:</td>
                </tr>
            @endif
            @if ($tenant?->email)
                <tr>
                    <td class="info-value">{{ $tenant->email }}</td>
                    <td class="info-label">البريد الإلكتروني:</td>
                </tr>
            @endif
        </table>
    </div>

    <!-- Items Table (physical L->R order so RTL reads R->L: الوصف | الفترة | السعر | المجموع) -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 18%; text-align: left;">المجموع</th>
                <th style="width: 18%; text-align: left;">السعر</th>
                <th style="width: 24%; text-align: center;">الفترة</th>
                <th style="width: 40%; text-align: right;">الوصف</th>
            </tr>
        </thead>
        @php
            $subCurrency = $invoice->currency ?? config('app.default_currency', 'SAR');
            $subCurrencyLabel = \App\Support\CountryCurrencyRegistry::currencyLabel($subCurrency, 'ar');
        @endphp
        <tbody>
            <tr>
                <td style="text-align: left; font-weight: bold; white-space: nowrap;">
                    {{ number_format($invoice->subtotal, 2) }} {{ $subCurrencyLabel }}
                </td>
                <td style="text-align: left; white-space: nowrap;">
                    {{ number_format($invoice->subtotal, 2) }} {{ $subCurrencyLabel }}
                </td>
                <td style="text-align: center; font-size: 11px;">
                    <span style="direction: ltr; display: inline-block;">{{ $subscription?->starts_at?->format('Y-m-d') ?? '-' }}</span><br>
                    إلى <span style="direction: ltr; display: inline-block; margin-right: 4px;">{{ $subscription?->ends_at?->format('Y-m-d') ?? '-' }}</span>
                </td>
                <td style="text-align: right;">
                    <strong>{{ $plan?->name_ar ?? $plan?->name_en ?? 'باقة الاشتراك' }}</strong><br>
                    <span style="color: #6b7280; font-size: 10px;">اشتراك سنوي</span>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Totals (left-aligned under المجموع column) -->
    <div class="totals-wrap">
        <table class="totals-table">
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
        </table>
    </div>

    @if ($invoice->status === 'pending')
        <!-- Bank Info -->
        <div class="bank-info">
            <div class="bank-info-title">معلومات الدفع</div>
            <p style="margin: 0; font-size: 11px;">
                يمكنك الدفع عبر البوابة الإلكترونية أو التحويل البنكي.<br>
                يرجى ذكر رقم الفاتورة ({{ $invoice->invoice_number }}) في وصف التحويل.
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
        <p style="margin: 2px 0;">شكراً لاختياركم {{ $company['name'] ?? config('app.name_ar', 'خدمة برو') }}</p>
        @if (!empty($company['name_en']))
            <p style="margin: 2px 0; font-size: 9px; color: #9ca3af;">{{ $company['name_en'] }}</p>
        @endif
        <p style="margin: 2px 0;">هذه فاتورة إلكترونية صادرة آلياً ولا تحتاج إلى توقيع</p>
    </div>
</body>

</html>
