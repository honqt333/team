<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>فاتورة - {{ $invoice->invoice_number }}</title>
    <style>
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
            margin: 15mm 12mm 12mm 12mm;
        }

        * {
            font-family: 'IBM Plex Sans Arabic', sans-serif;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 12px;
            color: #333;
            direction: rtl;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .header-left,
        .header-center,
        .header-right {
            display: table-cell;
            vertical-align: top;
            width: 33.33%;
        }

        .header-center {
            text-align: center;
        }

        .header-right {
            text-align: left;
        }

        .logo {
            max-height: 60px;
            max-width: 150px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 14px;
            color: #666;
        }

        .invoice-meta {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .meta-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }

        .meta-row:last-child {
            margin-bottom: 0;
        }

        .meta-label {
            display: table-cell;
            width: 120px;
            color: #666;
        }

        .meta-value {
            display: table-cell;
            font-weight: bold;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .details-section {
            margin-bottom: 25px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.items th {
            background: #4f46e5;
            color: white;
            padding: 12px 10px;
            text-align: right;
            font-size: 11px;
        }

        table.items td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.items tr:nth-child(even) {
            background: #f9fafb;
        }

        .totals {
            width: 300px;
            margin-right: auto;
            margin-left: 0;
        }

        .totals-row {
            display: table;
            width: 100%;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals-label {
            display: table-cell;
            color: #666;
        }

        .totals-value {
            display: table-cell;
            text-align: left;
            font-weight: bold;
        }

        .totals-row.total {
            border-bottom: none;
            background: #4f46e5;
            color: white;
            padding: 12px 10px;
            border-radius: 6px;
            font-size: 16px;
        }

        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            color: white;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
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

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #999;
            font-size: 10px;
        }

        .bank-info {
            background: #f0fdf4;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .bank-info-title {
            font-weight: bold;
            color: #166534;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            @if (!empty($company['logo_url']))
                <img src="{{ $company['logo_url'] }}" class="logo" alt="logo" />
            @endif
            <div class="company-name">{{ $company['name'] ?? 'Khidma Pro' }}</div>
            <div style="color: #666; font-size: 11px;">
                @if ($company['center_name'])
                    {{ $company['center_name'] }}<br>
                @endif
                @if ($company['cr_number'])
                    السجل التجاري: {{ $company['cr_number'] }}<br>
                @endif
                @if ($company['vat_number'])
                    الرقم الضريبي: {{ $company['vat_number'] }}<br>
                @endif
                @if ($company['phone'])
                    {{ $company['phone'] }}<br>
                @endif
                @if ($company['email'])
                    {{ $company['email'] }}<br>
                @endif
                @if ($company['website'])
                    {{ $company['website'] }}
                @endif
            </div>
        </div>

        <div class="header-center">
            <div class="invoice-title">{{ $invoice_title ?? 'فاتورة' }}</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
        </div>

        <div class="header-right">
            <div style="text-align: left;">
                <div class="meta-row">
                    <span class="meta-label">تاريخ الإصدار:</span>
                    <span class="meta-value">{{ $invoice->issue_date?->format('Y-m-d') ?? $invoice->created_at?->format('Y-m-d') }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">تاريخ الاستحقاق:</span>
                    <span class="meta-value">{{ $invoice->due_date?->format('Y-m-d') ?? $invoice->due_at?->format('Y-m-d') ?? '—' }}</span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">الحالة:</span>
                    <span class="meta-value">
                        <span class="status-badge {{ $invoice->payment_status === 'paid' ? 'status-paid' : 'status-pending' }}">
                            {{ $invoice->payment_status === 'paid' ? 'مدفوعة' : 'معلقة' }}
                        </span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="details-section">
        <div class="section-title">بيانات العميل</div>
        <div class="meta-row">
            <span class="meta-label">الاسم التجاري:</span>
            <span class="meta-value">{{ $invoice->customer->trade_name ?? $invoice->customer->name }}</span>
        </div>
        @if ($invoice->customer->cr_number)
            <div class="meta-row">
                <span class="meta-label">السجل التجاري:</span>
                <span class="meta-value">{{ $invoice->customer->cr_number }}</span>
            </div>
        @endif
        @if ($invoice->customer->vat_number)
            <div class="meta-row">
                <span class="meta-label">الرقم الضريبي:</span>
                <span class="meta-value">{{ $invoice->customer->vat_number }}</span>
            </div>
        @endif
        @if ($invoice->customer->phone)
            <div class="meta-row">
                <span class="meta-label">الهاتف:</span>
                <span class="meta-value" dir="ltr">{{ $invoice->customer->phone }}</span>
            </div>
        @endif
        @if ($invoice->customer->email)
            <div class="meta-row">
                <span class="meta-label">البريد الإلكتروني:</span>
                <span class="meta-value">{{ $invoice->customer->email }}</span>
            </div>
        @endif
    </div>

    <!-- Items Table -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 50%">الوصف</th>
                <th style="width: 15%">السعر</th>
                <th style="width: 15%">الكمية</th>
                <th style="width: 10%">الخصم</th>
                <th style="width: 10%">ضريبة</th>
                <th style="width: 15%">المجموع</th>
            </tr>
        </thead>
        @php
            $currencyCode = $invoice->currency_code ?? \App\Support\CountryCurrencyRegistry::tenantCurrency($tenant ?? $invoice->tenant);
            $currencyDecimals = \App\Support\CountryCurrencyRegistry::decimalsFor($currencyCode);
            $visualSettings = ($tenant ?? $invoice->tenant)?->print_settings['visual'] ?? [];
            $currencySuffixHtml = \App\Support\CountryCurrencyRegistry::renderCurrencyHtml($currencyCode, $visualSettings, 'ar');
        @endphp
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>
                        <strong>{{ $line->service_name ?? $line->description }}</strong>
                        @if ($line->description && $line->service_name)
                            <br><span style="color: #666; font-size: 10px;">{{ $line->description }}</span>
                        @endif
                    </td>
                    <td>{{ number_format($line->unit_price, $currencyDecimals) }} {!! $currencySuffixHtml !!}</td>
                    <td>{{ $line->qty ?? 1 }}</td>
                    <td>{{ number_format($line->discount_amount ?? $line->discount ?? 0, $currencyDecimals) }} {!! $currencySuffixHtml !!}</td>
                    <td>{{ number_format($line->tax_amount ?? 0, $currencyDecimals) }} {!! $currencySuffixHtml !!}</td>
                    <td>{{ number_format($line->line_total_incl_tax ?? ($line->unit_price * ($line->qty ?? 1) - ($line->discount ?? 0)), $currencyDecimals) }} {!! $currencySuffixHtml !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">المجموع الفرعي&nbsp;&nbsp;&nbsp;</span>
            <span class="totals-value">{{ number_format($invoice->total_excl_tax ?? $invoice->subtotal ?? 0, $currencyDecimals) }} {!! $currencySuffixHtml !!}</span>
        </div>
        @if ($invoice->discount > 0)
            <div class="totals-row">
                <span class="totals-label">الخصم&nbsp;&nbsp;&nbsp;</span>
                <span class="totals-value" style="color: #16a34a;">- {{ number_format($invoice->discount, $currencyDecimals) }} {!! $currencySuffixHtml !!}</span>
            </div>
        @endif
        <div class="totals-row">
            <span class="totals-label">ضريبة القيمة المضافة&nbsp;&nbsp;&nbsp;</span>
            <span class="totals-value">{{ number_format($invoice->total_tax ?? $invoice->vat ?? 0, $currencyDecimals) }} {!! $currencySuffixHtml !!}</span>
        </div>
        <div class="totals-row total">
            <span class="totals-label">الإجمالي&nbsp;&nbsp;&nbsp;</span>
            <span class="totals-value">{{ number_format($invoice->total_incl_tax ?? $invoice->total ?? 0, $currencyDecimals) }} {!! $currencySuffixHtml !!}</span>
        </div>
    </div>

    @if ($invoice->status !== 'paid')
        <!-- Bank Info -->
        <div class="bank-info">
            <div class="bank-info-title">معلومات الدفع</div>
            <p style="margin: 0; font-size: 11px;">
                يمكنك الدفع عبر البوابة الإلكترونية أو التحويل البنكي.<br>
                يرجى ذكر رقم الفاتورة ({{ $invoice->invoice_number }}) في وصف التحويل.
            </p>
        </div>
    @endif

    @if (!empty($company['stamp_url']))
        <div style="position: fixed; bottom: 60px; right: 40px; opacity: 0.92;">
            <img src="{{ $company['stamp_url'] }}" style="height: 110px; max-width: 180px; object-fit: contain;" alt="Stamp" />
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>شكراً لاختياركم {{ $company['name'] ?? 'Khidma Pro' }}</p>
        <p style="font-size: 9px; color: #aaa;">{{ $company['name_en'] ?? '' }}</p>
        <p>هذه فاتورة إلكترونية صادرة آلياً ولا تحتاج إلى توقيع</p>
    </div>
</body>
</html>