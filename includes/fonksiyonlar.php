<?php
/**
 * Yardimci Fonksiyonlar
 */
defined('ATASU') or exit('403');

// ==================== HTML / GUVENLIK ====================

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_input(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}

function csrf_dogrula(): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return true;
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['_csrf'] ?? '', $sent);
}

function csrf_zorunlu(): void
{
    if (!csrf_dogrula()) {
        http_response_code(403);
        exit('CSRF dogrulamasi basarisiz.');
    }
}

// ==================== SLUG / METIN ====================

function slug_olustur(string $metin): string
{
    $tr = ['ç','Ç','ğ','Ğ','ı','İ','ö','Ö','ş','Ş','ü','Ü'];
    $en = ['c','c','g','g','i','i','o','o','s','s','u','u'];
    $metin = str_replace($tr, $en, $metin);
    $metin = mb_strtolower($metin, 'UTF-8');
    $metin = preg_replace('/[^a-z0-9\s\-]/', '', $metin);
    $metin = preg_replace('/[\s\-]+/', '-', $metin);
    return trim($metin, '-');
}

function kisalt(string $metin, int $uzunluk = 150, string $son = '...'): string
{
    $metin = strip_tags($metin);
    if (mb_strlen($metin) <= $uzunluk) return $metin;
    return mb_substr($metin, 0, $uzunluk) . $son;
}

// ==================== PARA / TARIH ====================

function tl(float|int|string|null $tutar): string
{
    return number_format((float)$tutar, 2, ',', '.') . ' ₺';
}

function tarih_tr(?string $tarih, bool $saat = false): string
{
    if (!$tarih || $tarih === '0000-00-00' || $tarih === '0000-00-00 00:00:00') return '-';
    $ts = strtotime($tarih);
    if (!$ts) return '-';
    return $saat ? date('d.m.Y H:i', $ts) : date('d.m.Y', $ts);
}

function gun_farki(string $baslangic, string $bitis): int
{
    $b = new DateTime($baslangic);
    $s = new DateTime($bitis);
    $diff = $s->diff($b)->days;
    return max(1, $diff);
}

// ==================== AYAR ====================

function ayar(string $anahtar, mixed $varsayilan = null): mixed
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        if (DB::tabloVarMi('ayarlar')) {
            foreach (DB::liste('SELECT anahtar, deger FROM ' . DB::tablo('ayarlar')) as $r) {
                $cache[$r['anahtar']] = $r['deger'];
            }
        }
    }
    return $cache[$anahtar] ?? $varsayilan;
}

function ayar_kaydet(string $anahtar, string $deger): void
{
    $tablo = DB::tablo('ayarlar');
    DB::sorgu(
        "INSERT INTO {$tablo} (anahtar, deger) VALUES (:a, :d)
         ON DUPLICATE KEY UPDATE deger = VALUES(deger)",
        ['a' => $anahtar, 'd' => $deger]
    );
}

// ==================== FLASH MESAJ ====================

function flash_set(string $tip, string $mesaj): void
{
    $_SESSION['_flash'][] = ['tip' => $tip, 'mesaj' => $mesaj];
}

function flash_al(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function flash_render(): string
{
    $out = '';
    foreach (flash_al() as $f) {
        $out .= '<div class="alert alert-' . e($f['tip']) . '">' . e($f['mesaj']) . '</div>';
    }
    return $out;
}

// ==================== YONLENDIRME ====================

function yonlendir(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function url(string $yol = ''): string
{
    return rtrim(SITE_URL, '/') . '/' . ltrim($yol, '/');
}

function admin_url(string $yol = ''): string
{
    return url(ADMIN_PATH . '/' . ltrim($yol, '/'));
}

// ==================== UPLOAD ====================

function dosya_yukle(array $dosya, string $altKlasor = ''): ?string
{
    if (!isset($dosya['tmp_name']) || !is_uploaded_file($dosya['tmp_name'])) return null;
    if ($dosya['error'] !== UPLOAD_ERR_OK) return null;
    if ($dosya['size'] > UPLOAD_MAX_SIZE) return null;

    $izinli = ['jpg','jpeg','png','webp','pdf'];
    $uzanti = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
    if (!in_array($uzanti, $izinli, true)) return null;

    // MIME kontrolu
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($dosya['tmp_name']);
    $mimeIzinli = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (!in_array($mime, $mimeIzinli, true)) return null;

    $klasor = rtrim(UPLOAD_PATH, '/') . ($altKlasor ? '/' . trim($altKlasor, '/') : '');
    if (!is_dir($klasor)) {
        @mkdir($klasor, 0755, true);
    }

    $ad = 'img_' . uniqid('', true) . '.' . $uzanti;
    $hedef = $klasor . '/' . $ad;
    if (!move_uploaded_file($dosya['tmp_name'], $hedef)) return null;

    return ($altKlasor ? trim($altKlasor, '/') . '/' : '') . $ad;
}

function dosya_sil(string $rotaUploads): bool
{
    $tam = rtrim(UPLOAD_PATH, '/') . '/' . ltrim($rotaUploads, '/');
    if (is_file($tam)) return @unlink($tam);
    return false;
}

function upload_url(string $rota): string
{
    return rtrim(UPLOAD_URL, '/') . '/' . ltrim($rota, '/');
}

// ==================== SAYFALAMA ====================

function sayfalama(int $toplam, int $sayfa, int $sayfaBoyut, string $linkTemplate): string
{
    $toplamSayfa = (int)ceil($toplam / max(1, $sayfaBoyut));
    if ($toplamSayfa <= 1) return '';
    $sayfa = max(1, min($sayfa, $toplamSayfa));

    $out = '<nav class="sayfalama"><ul>';
    if ($sayfa > 1) {
        $out .= '<li><a href="' . str_replace('{p}', (string)($sayfa - 1), $linkTemplate) . '">&laquo;</a></li>';
    }
    $start = max(1, $sayfa - 2);
    $end = min($toplamSayfa, $sayfa + 2);
    for ($i = $start; $i <= $end; $i++) {
        $aktif = $i === $sayfa ? ' class="aktif"' : '';
        $out .= '<li' . $aktif . '><a href="' . str_replace('{p}', (string)$i, $linkTemplate) . '">' . $i . '</a></li>';
    }
    if ($sayfa < $toplamSayfa) {
        $out .= '<li><a href="' . str_replace('{p}', (string)($sayfa + 1), $linkTemplate) . '">&raquo;</a></li>';
    }
    $out .= '</ul></nav>';
    return $out;
}

// ==================== LOG ====================

function log_yaz(string $islem, string $aciklama = '', ?int $kullaniciId = null): void
{
    if (!DB::tabloVarMi('log')) return;
    DB::ekle('log', [
        'kullanici_id' => $kullaniciId ?? ($_SESSION['admin_id'] ?? null),
        'islem' => $islem,
        'aciklama' => $aciklama,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'olusturma' => date('Y-m-d H:i:s'),
    ]);
}
