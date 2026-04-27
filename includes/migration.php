<?php
/**
 * Migration Calistirici
 */
defined('ATASU') or exit('403');

class Migration
{
    public static function calistir(): array
    {
        $sonuclar = [];
        $klasor = __DIR__ . '/../migrations';
        if (!is_dir($klasor)) return $sonuclar;

        // Migrations tablosu
        $mTablo = DB::tablo('migrations');
        DB::pdo()->exec("CREATE TABLE IF NOT EXISTS {$mTablo} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            dosya VARCHAR(255) NOT NULL UNIQUE,
            calistirma_tarihi DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $calistirilanlar = array_column(DB::liste("SELECT dosya FROM {$mTablo}"), 'dosya');

        $dosyalar = glob($klasor . '/*.sql');
        sort($dosyalar);

        foreach ($dosyalar as $dosya) {
            $ad = basename($dosya);
            if (in_array($ad, $calistirilanlar, true)) continue;

            $icerik = file_get_contents($dosya);
            // {{prefix}} placeholder'i
            $icerik = str_replace('{{prefix}}', DB_PREFIX, $icerik);

            try {
                DB::pdo()->exec($icerik);
                DB::ekle('migrations', [
                    'dosya' => $ad,
                    'calistirma_tarihi' => date('Y-m-d H:i:s'),
                ]);
                $sonuclar[] = ['dosya' => $ad, 'durum' => 'OK'];
            } catch (Throwable $e) {
                $sonuclar[] = ['dosya' => $ad, 'durum' => 'HATA', 'mesaj' => $e->getMessage()];
                break;
            }
        }
        return $sonuclar;
    }

    public static function bekleyenSayisi(): int
    {
        $klasor = __DIR__ . '/../migrations';
        if (!is_dir($klasor)) return 0;
        $mTablo = DB::tablo('migrations');
        if (!DB::tabloVarMi('migrations')) {
            return count(glob($klasor . '/*.sql'));
        }
        $calistirilanlar = array_column(DB::liste("SELECT dosya FROM {$mTablo}"), 'dosya');
        $tum = array_map('basename', glob($klasor . '/*.sql'));
        return count(array_diff($tum, $calistirilanlar));
    }
}
