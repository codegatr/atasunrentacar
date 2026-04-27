<?php
/**
 * ATA SU Rent A Car - Sistem Konfigürasyonu
 * Bu dosya ZIP güncellemelerine dahil DEĞİLDİR.
 * Kurulumdan önce config.sample.php -> config.php olarak kopyalayın.
 */

if (!defined('ATASU')) {
    http_response_code(403);
    exit('Erisim yok.');
}

// ==================== VERITABANI ====================
define('DB_HOST', 'localhost');
define('DB_NAME', 'atasu_db');
define('DB_USER', 'atasu_user');
define('DB_PASS', 'sifre_buraya');
define('DB_PREFIX', 'atasu_');
define('DB_CHARSET', 'utf8mb4');

// ==================== SITE ====================
define('SITE_URL', 'https://atasurentacar.com');
define('SITE_NAME', 'ATA SU Rent A Car');
define('ADMIN_PATH', 'admin');

// ==================== GUVENLIK ====================
// 64 karakterlik rastgele bir secret. Production'da mutlaka degistirin.
define('APP_SECRET', 'CHANGE_THIS_TO_A_LONG_RANDOM_STRING_64_CHARS_RECOMMENDED');
define('SESSION_NAME', 'ATASU_SESSION');
define('SESSION_LIFETIME', 60 * 60 * 8); // 8 saat

// ==================== UPLOAD ====================
define('UPLOAD_MAX_SIZE', 8 * 1024 * 1024); // 8 MB
define('UPLOAD_PATH', __DIR__ . '/assets/uploads');
define('UPLOAD_URL', SITE_URL . '/assets/uploads');

// ==================== HATA RAPORLAMA ====================
define('DEBUG_MODE', false); // Production'da false olmali
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// ==================== ZAMAN DILIMI ====================
date_default_timezone_set('Europe/Istanbul');
mb_internal_encoding('UTF-8');
