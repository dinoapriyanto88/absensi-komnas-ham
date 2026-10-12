<?php

/*
 * Penyambungan ke database kepegawaian (SIMPEG, database `dbsimpeg`) untuk peserta Internal:
 * peserta cukup mengetik NIP, lalu nama, unit kerja, dan jabatan terisi otomatis.
 *
 * Koneksi ini HANYA-BACA: aplikasi tidak pernah menulis ke database kepegawaian.
 * Sebaiknya buat user MySQL khusus dengan hak SELECT saja pada tb_pegawai, tb_masterjab, tb_unit.
 */
return [
    // Nyalakan setelah koneksi di bawah diisi. Jika false, peserta Internal mengetik datanya manual.
    'enabled' => (bool) env('SIMPEG_ENABLED', false),

    // Nama koneksi yang dipakai (didaftarkan otomatis dari blok 'database' di bawah).
    'connection' => env('SIMPEG_CONNECTION', 'simpeg'),

    'database' => [
        'driver' => 'mysql',
        'host' => env('SIMPEG_DB_HOST', '127.0.0.1'),
        'port' => env('SIMPEG_DB_PORT', '3306'),
        'database' => env('SIMPEG_DB_DATABASE', 'dbsimpeg'),
        'username' => env('SIMPEG_DB_USERNAME', ''),
        'password' => env('SIMPEG_DB_PASSWORD', ''),
        'unix_socket' => '',
        // Tabel sumber berkarakter latin1; utf8mb4 di sisi koneksi mengonversinya dengan benar.
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
        'options' => [],
    ],
];