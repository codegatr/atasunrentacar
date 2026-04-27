<?php
/**
 * Bootstrap - Tum giris noktalari bunu cagirir
 */
defined('ATASU') or exit('403');

require __DIR__ . '/../config.php';
require __DIR__ . '/baglanti.php';
require __DIR__ . '/fonksiyonlar.php';
require __DIR__ . '/migration.php';
require __DIR__ . '/mail.php';

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Otomatik migration kontrolu (her istek degil, gerekirse)
if (!isset($_SESSION['_migration_kontrol']) || (time() - $_SESSION['_migration_kontrol'] > 300)) {
    if (Migration::bekleyenSayisi() > 0) {
        Migration::calistir();
    }
    $_SESSION['_migration_kontrol'] = time();
}
