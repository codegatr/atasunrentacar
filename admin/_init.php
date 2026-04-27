<?php
/**
 * ATA SU Rent A Car - Admin Bootstrap
 * Tum admin sayfalari bu dosyayi require eder.
 */
declare(strict_types=1);

define('ATASU', true);
define('ATASU_ADMIN', true);

require_once __DIR__ . '/../includes/bootstrap.php';

// Giris kontrol (login sayfasi disinda)
$buDosya = basename($_SERVER['SCRIPT_NAME'] ?? '');
if (!in_array($buDosya, ['giris.php', 'cikis.php'], true)) {
    if (empty($_SESSION['admin_id'])) {
        yonlendir(admin_url('giris.php'));
    }

    // Kullanici hala mevcut ve aktif mi?
    $kullanici = DB::tek("SELECT * FROM " . DB::tablo('kullanicilar') . " WHERE id = ? AND aktif = 1", [(int)$_SESSION['admin_id']]);
    if (!$kullanici) {
        session_unset();
        yonlendir(admin_url('giris.php'));
    }
    $GLOBALS['admin_kullanici'] = $kullanici;

    // Son giris guncelle (her 5 dakikada bir)
    if (empty($_SESSION['son_aktivite']) || (time() - (int)$_SESSION['son_aktivite']) > 300) {
        DB::sorgu("UPDATE " . DB::tablo('kullanicilar') . " SET son_giris = NOW() WHERE id = ?", [(int)$kullanici['id']]);
        $_SESSION['son_aktivite'] = time();
    }
}

/**
 * Admin yetki kontrol
 */
function admin_yetki(string ...$roller): void {
    $rol = $GLOBALS['admin_kullanici']['rol'] ?? '';
    if (!in_array($rol, $roller, true)) {
        flash_set('hata', 'Bu işlem için yetkiniz yok.');
        yonlendir(admin_url('index.php'));
    }
}

/**
 * Log kaydi
 */
function admin_log(string $islem, string $aciklama = ''): void {
    try {
        DB::ekle('log', [
            'kullanici_id' => $GLOBALS['admin_kullanici']['id'] ?? null,
            'islem' => $islem,
            'aciklama' => $aciklama,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);
    } catch (Throwable $e) {}
}
