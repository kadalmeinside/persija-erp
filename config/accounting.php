<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GL Account Mapping
    |--------------------------------------------------------------------------
    |
    | Memetakan akun sistem ke ID Akun GL yang sesuai.
    | Set melalui .env agar tidak hardcode ID di kode.
    |
    | Wajib diisi untuk fitur terkait:
    |   ACC_AR_ID              → Invoice (Piutang Usaha)
    |   ACC_SALARIES_PAYABLE_ID → Payroll (Hutang Gaji)
    |   ACC_TAX_PAYABLE_ID     → Payroll (Hutang PPh 21)
    |
    */

    'accounts' => [
        // Revenue & Receivables
        'ar'              => env('ACC_AR_ID', null),             // Piutang Usaha
        'revenue_default' => env('ACC_REVENUE_ID', null),

        // Taxes
        'vat_in'          => env('ACC_VAT_IN_ID', null),        // PPN Masukan
        'vat_out'         => env('ACC_VAT_OUT_ID', null),       // PPN Keluaran
        'pph_payable'     => env('ACC_PPH_PAYABLE_ID', null),   // Hutang PPh

        // Payables
        'ap'              => env('ACC_AP_ID', null),             // Hutang Usaha

        // Payroll — akses: config('accounting.accounts.salaries_payable')
        'salary_expense'     => env('ACC_SALARY_EXPENSE_ID', null),    // Beban Gaji (Dr)
        'salaries_payable'   => env('ACC_SALARIES_PAYABLE_ID', null),  // Hutang Gaji (Cr) ★ WAJIB
        'tax_payable'        => env('ACC_TAX_PAYABLE_ID', null),       // Hutang PPh 21 (Cr)
        'bpjs_payable'       => env('ACC_BPJS_PAYABLE_ID', null),      // Hutang BPJS (Cr)
        'other_payable'      => env('ACC_OTHER_PAYABLE_ID', null),     // Hutang Lain-lain
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'currency' => 'IDR',
    ],
];
