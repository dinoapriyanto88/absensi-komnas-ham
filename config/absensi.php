<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Superadmin
    |--------------------------------------------------------------------------
    */

    'superadmin' => [

        // Username untuk login Superadmin
        'username' => env(
            'SUPERADMIN_USERNAME',
            'superadmin'
        ),

        // Nama Superadmin
        'nama' => env(
            'SUPERADMIN_NAME',
            'Superadmin'
        ),

        // Email Superadmin
        'email' => env(
            'SUPERADMIN_EMAIL',
            env('MAIL_FROM_ADDRESS', '')
        ),

        // Password awal Superadmin
        'password' => env(
            'SUPERADMIN_PASSWORD',
            ''
        ),

    ],

];