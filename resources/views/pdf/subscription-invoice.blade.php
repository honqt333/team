<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>فاتورة اشتراك - {{ $invoice->invoice_number }}</title>
    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 40px;
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
        .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
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
            <div class="company-name">Khidma Pro</div>
            <div style="color: #666; font-size: 11px;">
                منصة إدارة مراكز الصيانة<br>
                المملكة العربية السعودية
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">فاتورة اشتراك</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
        </div>
    </div>

    <!-- Invoice Meta -->
    <div class="invoice-meta">
        <div class="meta-row">
            <span class="meta-label">تاريخ الإصدار:</span>
            <span class="meta-value">{{ $invoice->created_at->format('Y-m-d') }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">تاريخ الاستحقاق:</span>
            <span class="meta-value">{{ $invoice->due_date->format('Y-m-d') }}</span>
        </div>
        <div class="meta-row">
            <span class="meta-label">الحالة:</span>
            <span class="meta-value">
                <span class="status-badge {{ $invoice->status === 'paid' ? 'status-paid' : 'status-pending' }}">
                    {{ $invoice->status === 'paid' ? 'مدفوعة' : 'معلقة' }}
                </span>
            </span>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="details-section">
        <div class="section-title">بيانات العميل</div>
        <div class="meta-row">
            <span class="meta-label">الاسم التجاري:</span>
            <span class="meta-value">{{ $tenant->trade_name ?? $tenant->name }}</span>
        </div>
        @if ($tenant->cr_number)
            <div class="meta-row">
                <span class="meta-label">السجل التجاري:</span>
                <span class="meta-value">{{ $tenant->cr_number }}</span>
            </div>
        @endif
        @if ($tenant->vat_number)
            <div class="meta-row">
                <span class="meta-label">الرقم الضريبي:</span>
                <span class="meta-value">{{ $tenant->vat_number }}</span>
            </div>
        @endif
        @if ($tenant->email)
            <div class="meta-row">
                <span class="meta-label">البريد الإلكتروني:</span>
                <span class="meta-value">{{ $tenant->email }}</span>
            </div>
        @endif
    </div>

    <!-- Items Table -->
    <table class="items">
        <thead>
            <tr>
                <th style="width: 50%">الوصف</th>
                <th style="width: 20%">الفترة</th>
                <th style="width: 15%">السعر</th>
                <th style="width: 15%">المجموع</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>{{ $plan->name_ar }}</strong><br>
                    <span style="color: #666; font-size: 10px;">اشتراك
                        {{ $subscription->billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}</span>
                </td>
                <td>
                    {{ $subscription->starts_at?->format('Y-m-d') ?? '-' }}<br>
                    إلى {{ $subscription->ends_at?->format('Y-m-d') ?? '-' }}
                </td>
                <td>{{ number_format($invoice->subtotal, 2) }} ر.س</td>
                <td>{{ number_format($invoice->subtotal, 2) }} ر.س</td>
            </tr>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">المجموع الفرعي</span>
            <span class="totals-value">{{ number_format($invoice->subtotal, 2) }} ر.س</span>
        </div>
        @if ($invoice->discount > 0)
            <div class="totals-row">
                <span class="totals-label">الخصم</span>
                <span class="totals-value" style="color: #16a34a;">- {{ number_format($invoice->discount, 2) }}
                    ر.س</span>
            </div>
        @endif
        <div class="totals-row">
            <span class="totals-label">ضريبة القيمة المضافة ({{ $invoice->vat_rate }}%)</span>
            <span class="totals-value">{{ number_format($invoice->vat_amount, 2) }} ر.س</span>
        </div>
        <div class="totals-row total">
            <span class="totals-label">الإجمالي</span>
            <span class="totals-value">{{ number_format($invoice->total, 2) }} ر.س</span>
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

    <!-- Footer -->
    <div class="footer">
        <p>شكراً لاختياركم Khidma Pro</p>
        <p>هذه فاتورة إلكترونية صادرة آلياً ولا تحتاج إلى توقيع</p>
    </div>
</body>

</html>
