<?php
require_once __DIR__ . '/_init.php';
admin_yetki('admin');
$pageTitle = 'Güncelleme';

@set_time_limit(180);

// Yerel manifest
$yerelManifestYol = dirname(__DIR__) . '/manifest.json';
$yerelManifest = file_exists($yerelManifestYol) ? json_decode((string)file_get_contents($yerelManifestYol), true) : null;
$mevcutSurum = $yerelManifest['version'] ?? '0.0.0';

// Uzak manifest URL'i (kaynak repo) - ayar ya da varsayilan
$uzakManifestUrl = ayar('guncelleme_kaynak_url', '');

$mesaj = '';
$mesajTip = 'bilgi';
$uzakManifest = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $islem = $_POST['islem'] ?? '';

    if ($islem === 'kaynak_kaydet') {
        $url = trim($_POST['kaynak_url'] ?? '');
        ayar_kaydet('guncelleme_kaynak_url', $url);
        admin_log('Guncelleme kaynagi degistirildi', $url);
        flash_set('basari', 'Güncelleme kaynağı kaydedildi.');
        yonlendir(admin_url('guncelleme.php'));
    }

    if ($islem === 'kontrol') {
        if (!$uzakManifestUrl) {
            $mesaj = 'Önce güncelleme kaynak URL\'sini ayarlayin.';
            $mesajTip = 'uyari';
        } else {
            $uzakManifest = guncelleme_uzak_manifest($uzakManifestUrl);
            if (!$uzakManifest) {
                $mesaj = 'Uzak manifest okunamadı. URL ve sunucu erişimini kontrol edin.';
                $mesajTip = 'hata';
            } else {
                if (version_compare($uzakManifest['version'], $mevcutSurum, '>')) {
                    $mesaj = 'Yeni sürüm mevcut: <strong>' . htmlspecialchars($uzakManifest['version']) . '</strong> (şu anki: ' . htmlspecialchars($mevcutSurum) . ')';
                    $mesajTip = 'basarili';
                } else {
                    $mesaj = 'Sisteminiz güncel. (Sürüm: ' . htmlspecialchars($mevcutSurum) . ')';
                    $mesajTip = 'bilgi';
                }
            }
        }
    }

    if ($islem === 'guncelle') {
        if (!$uzakManifestUrl) {
            $mesaj = 'Kaynak URL ayarlı değil.';
            $mesajTip = 'hata';
        } else {
            $uzakManifest = guncelleme_uzak_manifest($uzakManifestUrl);
            if (!$uzakManifest || empty($uzakManifest['zip_url'])) {
                $mesaj = 'Manifest veya ZIP URL bulunamadı.';
                $mesajTip = 'hata';
            } else {
                $sonuc = guncelleme_uygula($uzakManifest);
                $mesaj = $sonuc['mesaj'];
                $mesajTip = $sonuc['basari'] ? 'basarili' : 'hata';
                if ($sonuc['basari']) {
                    admin_log('Sistem guncellendi', 'v' . $uzakManifest['version']);
                }
            }
        }
    }
}

function guncelleme_uzak_manifest(string $url): ?array {
    $ctx = stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: ATASU-Updater/1.0\r\n"]]);
    $icerik = @file_get_contents($url, false, $ctx);
    if ($icerik === false) return null;
    $j = json_decode($icerik, true);
    return is_array($j) ? $j : null;
}

function guncelleme_uygula(array $manifest): array {
    $kok = dirname(__DIR__);
    $zipUrl = $manifest['zip_url'];
    $beklenenHash = $manifest['zip_sha256'] ?? null;
    $haricListe = $manifest['exclude_from_zip'] ?? ['config.php', '.htaccess', 'assets/uploads/'];

    // Indir
    $tmp = tempnam(sys_get_temp_dir(), 'atasu_upd_');
    $ctx = stream_context_create(['http' => ['timeout' => 120, 'header' => "User-Agent: ATASU-Updater/1.0\r\n"]]);
    $veri = @file_get_contents($zipUrl, false, $ctx);
    if ($veri === false) {
        @unlink($tmp);
        return ['basari' => false, 'mesaj' => 'ZIP indirilemedi: ' . htmlspecialchars($zipUrl)];
    }
    file_put_contents($tmp, $veri);

    if ($beklenenHash) {
        $gercek = hash_file('sha256', $tmp);
        if (!hash_equals(strtolower($beklenenHash), strtolower($gercek))) {
            @unlink($tmp);
            return ['basari' => false, 'mesaj' => 'ZIP bütünlük doğrulaması başarısız (hash uyusmadi).'];
        }
    }

    if (!class_exists('ZipArchive')) {
        @unlink($tmp);
        return ['basari' => false, 'mesaj' => 'PHP ZipArchive eklentisi gerekli.'];
    }

    $zip = new ZipArchive();
    if ($zip->open($tmp) !== true) {
        @unlink($tmp);
        return ['basari' => false, 'mesaj' => 'ZIP açılamadı.'];
    }

    // Yedek kayit klasoru
    $yedekKlasor = $kok . '/assets/yedekler/' . date('Ymd_His');
    if (!is_dir(dirname($yedekKlasor))) @mkdir(dirname($yedekKlasor), 0755, true);
    @mkdir($yedekKlasor, 0755, true);

    $kopyalanan = 0;
    $atlanan = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $isim = $stat['name'];
        if (substr($isim, -1) === '/') continue;

        // Hariclere ait mi?
        $haric = false;
        foreach ($haricListe as $h) {
            if ($isim === $h || (substr($h, -1) === '/' && strpos($isim, $h) === 0)) {
                $haric = true;
                break;
            }
        }
        if ($haric) { $atlanan++; continue; }

        $hedef = $kok . '/' . $isim;
        $hedefDir = dirname($hedef);
        if (!is_dir($hedefDir)) @mkdir($hedefDir, 0755, true);

        // Yedekle
        if (file_exists($hedef)) {
            $yedekHedef = $yedekKlasor . '/' . $isim;
            @mkdir(dirname($yedekHedef), 0755, true);
            @copy($hedef, $yedekHedef);
        }

        $stream = $zip->getStream($isim);
        if ($stream) {
            $out = fopen($hedef, 'wb');
            if ($out) {
                while (!feof($stream)) fwrite($out, fread($stream, 8192));
                fclose($out);
                $kopyalanan++;
            }
            fclose($stream);
        }
    }
    $zip->close();
    @unlink($tmp);

    return ['basari' => true, 'mesaj' => "Guncelleme tamamlandi. <strong>$kopyalanan</strong> dosya yazıldı, $atlanan dosya atlandi. Yedek: <code>" . htmlspecialchars(str_replace($kok, '', $yedekKlasor)) . "</code>"];
}

require __DIR__ . '/_layout_basla.php';
?>

<div class="ist-grid">
  <div class="ist-kart bilgi">
    <div class="ist-baslik">Mevcut Sürüm</div>
    <div class="ist-deger"><?= e($mevcutSurum) ?></div>
  </div>
  <div class="ist-kart">
    <div class="ist-baslik">PHP</div>
    <div class="ist-deger"><?= e(PHP_VERSION) ?></div>
  </div>
  <div class="ist-kart">
    <div class="ist-baslik">Min. PHP</div>
    <div class="ist-deger"><?= e($yerelManifest['min_php'] ?? '8.3') ?></div>
  </div>
</div>

<?php if ($mesaj): ?>
  <div class="alert alert-<?= e($mesajTip) ?>"><?= $mesaj ?></div>
<?php endif; ?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Guncellemeyi Kontrol Et / Uygula</h2></div>
      <div class="kart-icerik">
        <p>Guncelleme kaynak URL: <?= $uzakManifestUrl ? '<code>' . e($uzakManifestUrl) . '</code>' : '<em>Tanimli degil</em>' ?></p>

        <form method="post" style="display:inline-block;margin-right:8px;">
          <?= csrf_input() ?>
          <input type="hidden" name="islem" value="kontrol">
          <button class="btn btn-cerceve" <?= $uzakManifestUrl ? '' : 'disabled' ?>>Guncelleme Kontrol Et</button>
        </form>

        <?php if ($uzakManifest && version_compare($uzakManifest['version'] ?? '0', $mevcutSurum, '>')): ?>
          <form method="post" style="display:inline-block;margin-top:10px;">
            <?= csrf_input() ?>
            <input type="hidden" name="islem" value="guncelle">
            <button class="btn btn-birincil" data-onay="Guncellemeyi simdi uygulamak istiyor musunuz?">Simdi Guncelle (v<?= e($uzakManifest['version']) ?>)</button>
          </form>
          <?php if (!empty($uzakManifest['changelog'])): ?>
            <div class="kart" style="margin-top:14px;">
              <div class="kart-baslik"><h3>Surum Notlari</h3></div>
              <div class="kart-icerik">
                <pre style="white-space:pre-wrap;font-family:inherit;"><?= e(is_array($uzakManifest['changelog']) ? implode("\n", $uzakManifest['changelog']) : $uzakManifest['changelog']) ?></pre>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Kaynak Ayari</h2></div>
      <div class="kart-icerik">
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="islem" value="kaynak_kaydet">
          <div class="form-grup">
            <label>Manifest URL</label>
            <input type="url" name="kaynak_url" value="<?= e($uzakManifestUrl) ?>" placeholder="https://releases.example.com/atasu/manifest.json">
            <small>JSON manifest dosyasi: version, zip_url, zip_sha256 alanlarini icermeli.</small>
          </div>
          <button class="btn btn-birincil btn-blok">Kaydet</button>
        </form>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Korunan Dosyalar</h2></div>
      <div class="kart-icerik">
        <p>Guncelleme sirasinda <strong>uzerine yazilmaz</strong>:</p>
        <ul>
          <?php foreach (($yerelManifest['exclude_from_zip'] ?? []) as $h): ?>
            <li><code><?= e($h) ?></code></li>
          <?php endforeach; ?>
        </ul>
        <p style="font-size:13px;color:#64748b;">Tum dosyalarin yedegi guncelleme oncesi <code>assets/yedekler/</code> klasorune alinir.</p>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
