<?php

return [
    'name' => env('APOTEK_NAME', 'Apotek Billing'),

    /*
    | Jumlah hari sebelum jatuh tempo yang dianggap "segera jatuh tempo".
    */
    'due_soon_days' => 7,

    /*
    | Prefix nomor transaksi pembayaran: BYR-20261102-0001
    */
    'payment_prefix' => 'BYR',

    'pagination' => 10,

    'upload' => [
        'max_kb' => 5120,
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
        'disk' => 'local',
        'directory' => 'apotek',
    ],
];
