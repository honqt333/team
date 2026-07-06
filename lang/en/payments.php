<?php

return [
    'title' => 'Payments',
    'payment' => 'Payment',
    'payments' => 'Payments',
    'add' => 'Add Payment',
    'recorded' => 'Payment recorded successfully',
    'deleted' => 'Payment deleted',
    'amount' => 'Amount',
    'date' => 'Payment Date',
    'method' => 'Payment Method',
    'reference' => 'Reference',
    'notes' => 'Notes',
    'received_by' => 'Received By',
    'pay_full' => 'Pay Full Amount',
    'pay_partial' => 'Partial Payment',
    
    // Payment Methods
    'methods' => [
        'cash' => 'Cash',
        'card' => 'Card',
        'transfer' => 'Bank Transfer',
        'credit' => 'Credit',
        'mada' => 'Mada',
        'visa' => 'Visa',
        'mastercard' => 'Mastercard',
        'apple_pay' => 'Apple Pay',
        'stc_pay' => 'STC Pay',
        'debit_note' => 'Debit Note (Credit Balance)',
        'other' => 'Other',
    ],
    
    // Validation
    'amount_exceeds_balance' => 'The amount cannot exceed the remaining balance',
    'auto_payment_notes' => 'Automatic payment recorded upon receipt of invoice',
    
    // Types
    'types' => [
        'payment' => 'Payment',
        'refund' => 'Refund',
        'bad_debt' => 'Bad Debt',
    ],
];
