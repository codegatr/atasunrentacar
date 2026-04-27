<?php
require_once __DIR__ . '/_init.php';

if (!empty($_SESSION['admin_id'])) {
    try {
        DB::ekle('log', [
            'kullanici_id' => (int)$_SESSION['admin_id'],
            'islem' => 'Cikis',
            'aciklama' => 'Admin paneli cikis',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (Throwable $e) {}
}

session_unset();
session_destroy();
yonlendir(admin_url('giris.php'));
