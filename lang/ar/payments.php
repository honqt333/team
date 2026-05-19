<?php

return [
    'title' => 'المدفوعات',
    'payment' => 'دفعة',
    'payments' => 'المدفوعات',
    'add' => 'إضافة دفعة',
    'recorded' => 'تم تسجيل الدفعة بنجاح',
    'deleted' => 'تم حذف الدفعة',
    'amount' => 'المبلغ',
    'date' => 'تاريخ الدفع',
    'method' => 'طريقة الدفع',
    'reference' => 'رقم المرجع',
    'notes' => 'ملاحظات',
    'received_by' => 'المستلم',
    'pay_full' => 'دفع كامل المبلغ',
    'pay_partial' => 'دفع جزئي',
    
    // Payment Methods
    'methods' => [
        'cash' => 'نقداً',
        'card' => 'بطاقة',
        'transfer' => 'تحويل بنكي',
        'credit' => 'آجل',
        'mada' => 'مدى',
        'visa' => 'فيزا',
        'mastercard' => 'ماستركارد',
        'apple_pay' => 'أبل باي',
        'stc_pay' => 'إس تي سي باي',
        'debit_note' => 'فاتورة مدينة (رصيد دائن)',
        'other' => 'أخرى',
    ],
    
    // Validation
    'amount_exceeds_balance' => 'المبلغ لا يمكن أن يتجاوز المتبقي',
    'auto_payment_notes' => 'تسجيل دفعة تلقائية عند استلام الفاتورة',
];
