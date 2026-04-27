<?php
require_once __DIR__ . '/_init.php';
admin_yetki('admin');
$pageTitle = 'Güncelleme';

@set_time_limit(300);
@ini_set('memory_limit', '256M');

// === Yerel manifest ===
$yerelManifestYol = dirname(__DIR__) . '/manifest.json';
$yerelManifest = file_exists($yerelManifestYol) ? json_decode((string)file_get_contents($yerelManifestYol), true) : null;
$mevcutSurum = $yerelManifest['version'] ?? '0.0.0';

// === GitHub repo bilgisi ===
$repo = trim((string)ayar('guncelleme_github_repo', 'codegatr/atasunrentacar'));
$kanal = (string)ayar('guncelleme_kanali', 'releases'); // releases | tags | branch
$branch = (string)ayar('guncelleme_branch', 'main');
$githubToken = (string)ayar('guncelleme_github_token', ''); // private repo icin opsiyonel

$mesaj = '';
$mesajTip = 'bilgi';
$uzakSurum = null;

// === Form islemleri ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $islem = $_POST['islem'] ?? '';

    if ($islem === 'ayar_kaydet') {
        ayar_kaydet('guncelleme_github_repo', trim($_POST['github_repo'] ?? ''));
        ayar_kaydet('guncelleme_kanali', in_array($_POST['kanal'] ?? '', ['releases', 'branch'], true) ? $_POST['kanal'] : 'releases');
        ayar_kaydet('guncelleme_branch', trim($_POST['branch'] ?? 'main'));
        if (!empty($_POST['github_token'])) {
            ayar_kaydet('guncelleme_github_token', trim($_POST['github_token']));
        } elseif (isset($_POST['token_sil'])) {
            ayar_kaydet('guncelleme_github_token', '');
        }
        admin_log('Guncelleme ayari degistirildi', $repo);
        flash_set('basari', 'Güncelleme ayarları kaydedildi.');
        yonlendir(admin_url('guncelleme.php'));
    }

    if ($islem === 'kontrol') {
        $uzakSurum = guncelleme_son_surum($repo, $kanal, $branch, $githubToken);
        if (!$uzakSurum) {
            $mesaj = 'GitHub\'dan sürüm bilgisi alınamadı. Repo adını ve internet bağlantısını kontrol edin.';
            $mesajTip = 'hata';
        } else {
            if (version_compare($uzakSurum['surum'], $mevcutSurum, '>')) {
                $mesaj = 'Yeni sürüm mevcut: <strong>v' . htmlspecialchars($uzakSurum['surum']) . '</strong> (şu anki: v' . htmlspecialchars($mevcutSurum) . ')';
                $mesajTip = 'basarili';
            } else {
                $mesaj = 'Sisteminiz güncel. (Sürüm: v' . htmlspecialchars($mevcutSurum) . ')';
                $mesajTip = 'bilgi';
            }
        }
    }

    if ($islem === 'guncelle') {
        $uzakSurum = guncelleme_son_surum($repo, $kanal, $branch, $githubToken);
        if (!$uzakSurum) {
            $mesaj = 'GitHub\'dan sürüm bilgisi alınamadı.';
            $mesajTip = 'hata';
        } elseif (!version_compare($uzakSurum['surum'], $mevcutSurum, '>') && empty($_POST['zorla'])) {
            $mesaj = 'Sisteminiz zaten güncel.';
            $mesajTip = 'bilgi';
        } else {
            $sonuc = guncelleme_uygula($uzakSurum, $githubToken, $yerelManifest);
            $mesaj = $sonuc['mesaj'];
            $mesajTip = $sonuc['basari'] ? 'basarili' : 'hata';
            if ($sonuc['basari']) {
                admin_log('Sistem guncellendi', 'v' . $uzakSurum['surum']);
            }
        }
    }
}

// === Fonksiyonlar ===

/**
 * GitHub API uzerinden son surum bilgisini al.
 * Kanallar:
 *  - releases: Son release (tag) ve ZIP asset URL'si
 *  - branch:   Belirtilen branch'in HEAD'i (zipball indirilir)
 */
function guncelleme_son_surum(string $repo, string $kanal, string $branch, string $token = ''): ?array
{
    if (!$repo || strpos($repo, '/') === false) return null;

    if ($kanal === 'releases') {
        $api = "https://api.github.com/repos/$repo/releases/latest";
        $r = guncelleme_http_get($api, $token, true);
        if (!$r || empty($r['data'])) return null;
        $j = $r['data'];
        if (empty($j['tag_name'])) return null;
        $surum = ltrim($j['tag_name'], 'vV');

        // Asset varsa onu kullan, yoksa zipball
        $zipUrl = '';
        $assetIsim = '';
        if (!empty($j['assets']) && is_array($j['assets'])) {
            foreach ($j['assets'] as $asset) {
                if (preg_match('/\.zip$/i', $asset['name'] ?? '')) {
                    $zipUrl = $asset['browser_download_url'] ?? ($asset['url'] ?? '');
                    $assetIsim = $asset['name'];
                    break;
                }
            }
        }
        if (!$zipUrl) {
            $zipUrl = $j['zipball_url'] ?? '';
            $assetIsim = 'zipball';
        }
        return [
            'surum' => $surum,
            'tag' => $j['tag_name'],
            'baslik' => $j['name'] ?? $j['tag_name'],
            'aciklama' => $j['body'] ?? '',
            'tarih' => $j['published_at'] ?? '',
            'zip_url' => $zipUrl,
            'zip_isim' => $assetIsim,
            'kanal' => 'releases',
        ];
    }

    // Branch kanali
    $api = "https://api.github.com/repos/$repo/branches/" . urlencode($branch);
    $r = guncelleme_http_get($api, $token, true);
    if (!$r || empty($r['data'])) return null;
    $j = $r['data'];
    $sha = $j['commit']['sha'] ?? '';
    if (!$sha) return null;
    return [
        'surum' => substr($sha, 0, 7),
        'tag' => $branch . '@' . substr($sha, 0, 7),
        'baslik' => 'Branch: ' . $branch,
        'aciklama' => $j['commit']['commit']['message'] ?? '',
        'tarih' => $j['commit']['commit']['author']['date'] ?? '',
        'zip_url' => "https://api.github.com/repos/$repo/zipball/" . $sha,
        'zip_isim' => "$branch-$sha.zip",
        'kanal' => 'branch',
    ];
}

/**
 * GitHub'dan veya genel URL'den HTTP GET.
 * Token verilirse Authorization header eklenir.
 */
function guncelleme_http_get(string $url, string $token = '', bool $jsonBekle = false): ?array
{
    $headers = [
        'User-Agent: ATASU-Updater/1.0',
        'Accept: ' . ($jsonBekle ? 'application/vnd.github+json' : 'application/octet-stream'),
        'X-GitHub-Api-Version: 2022-11-28',
    ];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) return null;
        return ['data' => $jsonBekle ? json_decode($body, true) : null, 'body' => $body, 'code' => $code];
    }

    // cURL yoksa file_get_contents fallback
    $ctx = stream_context_create([
        'http' => ['timeout' => 60, 'header' => implode("\r\n", $headers), 'follow_location' => 1, 'max_redirects' => 5],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) return null;
    return ['data' => $jsonBekle ? json_decode($body, true) : null, 'body' => $body, 'code' => 200];
}

/**
 * Belirli bir URL'den dosya indirir.
 */
function guncelleme_dosya_indir(string $url, string $hedefDosya, string $token = ''): bool
{
    $headers = [
        'User-Agent: ATASU-Updater/1.0',
        'Accept: application/octet-stream',
    ];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;

    if (function_exists('curl_init')) {
        $fp = fopen($hedefDosya, 'wb');
        if (!$fp) return false;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $ok = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        return $ok !== false && $code < 400 && filesize($hedefDosya) > 0;
    }

    $r = guncelleme_http_get($url, $token);
    if (!$r || empty($r['body'])) return false;
    return (bool)file_put_contents($hedefDosya, $r['body']);
}

/**
 * Asil guncelleme uygulama mantigi.
 * Indirir, dogrular, korunmus dosyalar haric uzerine yazar, yedek alir.
 */
function guncelleme_uygula(array $surum, string $token, ?array $yerelManifest): array
{
    $kok = dirname(__DIR__);
    $zipUrl = $surum['zip_url'];

    $haricListe = ['config.php', 'manifest.local.json', 'config.local.php'];
    if (!empty($yerelManifest['exclude_from_zip']) && is_array($yerelManifest['exclude_from_zip'])) {
        $haricListe = array_unique(array_merge($haricListe, $yerelManifest['exclude_from_zip']));
    }

    // Indir
    $tmp = tempnam(sys_get_temp_dir(), 'atasu_upd_');
    if (!guncelleme_dosya_indir($zipUrl, $tmp, $token)) {
        @unlink($tmp);
        return ['basari' => false, 'mesaj' => 'ZIP indirilemedi: ' . htmlspecialchars($zipUrl)];
    }

    if (!class_exists('ZipArchive')) {
        @unlink($tmp);
        return ['basari' => false, 'mesaj' => 'PHP ZipArchive eklentisi gerekli (sunucunuzda etkinleştirin).'];
    }

    $zip = new ZipArchive();
    $r = $zip->open($tmp);
    if ($r !== true) {
        @unlink($tmp);
        return ['basari' => false, 'mesaj' => 'ZIP açılamadı (kod: ' . $r . '). İndirme bozulmuş olabilir.'];
    }

    // GitHub zipball'lari "owner-repo-sha/" gibi bir kok klasor icerir.
    // Bunu otomatik tespit edip kaldirmamiz gerekir.
    $kokOnEk = '';
    if ($zip->numFiles > 0) {
        $ilk = $zip->statIndex(0)['name'] ?? '';
        if (substr_count($ilk, '/') >= 1) {
            $parts = explode('/', $ilk);
            $aday = $parts[0] . '/';
            // Tum dosyalar bu prefix ile basliyorsa kok klasor demektir
            $tumuPrefix = true;
            for ($i = 0; $i < min($zip->numFiles, 10); $i++) {
                $isim = $zip->statIndex($i)['name'] ?? '';
                if ($isim && strpos($isim, $aday) !== 0) {
                    $tumuPrefix = false;
                    break;
                }
            }
            if ($tumuPrefix) $kokOnEk = $aday;
        }
    }

    // Yedek klasoru
    $yedekKlasor = $kok . '/assets/yedekler/' . date('Ymd_His');
    if (!is_dir(dirname($yedekKlasor))) @mkdir(dirname($yedekKlasor), 0755, true);
    @mkdir($yedekKlasor, 0755, true);
    if (!is_writable($yedekKlasor)) {
        $zip->close();
        @unlink($tmp);
        return ['basari' => false, 'mesaj' => 'Yedek klasörüne yazma izni yok: ' . htmlspecialchars($yedekKlasor)];
    }

    $kopyalanan = 0;
    $atlanan = 0;
    $hatalar = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $isim = $stat['name'];
        if (substr($isim, -1) === '/') continue;
        if ($kokOnEk && strpos($isim, $kokOnEk) === 0) {
            $isim = substr($isim, strlen($kokOnEk));
        }
        if ($isim === '' || strpos($isim, '..') !== false) continue;

        // Hariclere ait mi?
        $haric = false;
        foreach ($haricListe as $h) {
            $h = ltrim($h, './');
            if ($isim === $h) { $haric = true; break; }
            if (substr($h, -1) === '/' && strpos($isim, $h) === 0) { $haric = true; break; }
        }
        if ($haric) { $atlanan++; continue; }

        $hedef = $kok . '/' . $isim;
        $hedefDir = dirname($hedef);
        if (!is_dir($hedefDir)) {
            if (!@mkdir($hedefDir, 0755, true) && !is_dir($hedefDir)) {
                $hatalar++;
                continue;
            }
        }

        // Yedekle
        if (file_exists($hedef)) {
            $yedekHedef = $yedekKlasor . '/' . $isim;
            if (!is_dir(dirname($yedekHedef))) @mkdir(dirname($yedekHedef), 0755, true);
            @copy($hedef, $yedekHedef);
        }

        // Yaz
        $stream = $zip->getStream($stat['name']);
        if ($stream) {
            $out = @fopen($hedef, 'wb');
            if ($out) {
                while (!feof($stream)) {
                    $chunk = fread($stream, 8192);
                    if ($chunk === false) break;
                    fwrite($out, $chunk);
                }
                fclose($out);
                $kopyalanan++;
            } else {
                $hatalar++;
            }
            fclose($stream);
        } else {
            $hatalar++;
        }
    }

    $zip->close();
    @unlink($tmp);

    if ($hatalar > 0 && $kopyalanan === 0) {
        return ['basari' => false, 'mesaj' => "Hiçbir dosya yazılamadı ($hatalar hata). Klasör izinlerini kontrol edin."];
    }

    $mesaj = "Güncelleme tamamlandı: <strong>$kopyalanan</strong> dosya yazıldı";
    if ($atlanan > 0) $mesaj .= ", $atlanan dosya korundu";
    if ($hatalar > 0) $mesaj .= ", <strong style=\"color:#dc2626\">$hatalar dosya hatası</strong>";
    $mesaj .= ".<br>Yedek: <code>" . htmlspecialchars(str_replace($kok, '', $yedekKlasor)) . "</code>";
    return ['basari' => true, 'mesaj' => $mesaj];
}

require __DIR__ . '/_layout_basla.php';
?>

<div class="ist-grid">
  <div class="ist-kart bilgi">
    <div class="ist-baslik">Mevcut Sürüm</div>
    <div class="ist-deger">v<?= e($mevcutSurum) ?></div>
  </div>
  <div class="ist-kart">
    <div class="ist-baslik">PHP</div>
    <div class="ist-deger"><?= e(PHP_VERSION) ?></div>
  </div>
  <div class="ist-kart">
    <div class="ist-baslik">Min. PHP</div>
    <div class="ist-deger"><?= e($yerelManifest['min_php'] ?? '8.3') ?></div>
  </div>
  <div class="ist-kart">
    <div class="ist-baslik">Repo</div>
    <div class="ist-deger" style="font-size:14px;"><?= e($repo ?: '-') ?></div>
  </div>
</div>

<?php if ($mesaj): ?>
  <div class="alert alert-<?= e($mesajTip) ?>"><?= $mesaj ?></div>
<?php endif; ?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Güncelleme</h2></div>
      <div class="kart-icerik">
        <p>
          <strong>Kanal:</strong>
          <?= $kanal === 'branch' ? 'Branch (' . e($branch) . ')' : 'Releases' ?>
          ·
          <a href="https://github.com/<?= e($repo) ?>/releases" target="_blank" rel="noopener">GitHub Releases</a>
        </p>

        <form method="post" style="display:inline-block;margin-right:8px;margin-top:10px;">
          <?= csrf_input() ?>
          <input type="hidden" name="islem" value="kontrol">
          <button class="btn btn-cerceve" <?= $repo ? '' : 'disabled' ?>>🔄 Güncelleme Kontrol Et</button>
        </form>

        <?php if ($uzakSurum): ?>
          <div class="kart" style="margin-top:14px;background:#f8fafc;">
            <div class="kart-icerik">
              <h3 style="margin:0 0 8px;color:#1e3a5f;">
                <?= e($uzakSurum['baslik']) ?>
                <span style="font-size:14px;color:#64748b;font-weight:normal;">(<?= e($uzakSurum['tag']) ?>)</span>
              </h3>
              <?php if (!empty($uzakSurum['tarih'])): ?>
                <small style="color:#64748b;">Yayın: <?= e(tarih_tr($uzakSurum['tarih'])) ?></small>
              <?php endif; ?>
              <?php if (!empty($uzakSurum['aciklama'])): ?>
                <details style="margin-top:10px;">
                  <summary style="cursor:pointer;font-weight:600;color:#1e3a5f;">📋 Sürüm Notları</summary>
                  <pre style="white-space:pre-wrap;font-family:inherit;font-size:13px;color:#475569;margin-top:8px;background:#fff;padding:12px;border-radius:6px;border:1px solid #e2e8f0;max-height:300px;overflow:auto;"><?= e($uzakSurum['aciklama']) ?></pre>
                </details>
              <?php endif; ?>

              <?php $yeniMi = version_compare($uzakSurum['surum'], $mevcutSurum, '>'); ?>
              <form method="post" style="margin-top:14px;">
                <?= csrf_input() ?>
                <input type="hidden" name="islem" value="guncelle">
                <?php if (!$yeniMi): ?>
                  <input type="hidden" name="zorla" value="1">
                  <button class="btn btn-cerceve" data-onay="Sistem zaten güncel. Yine de yüklemek istediğinizden emin misiniz?">⚠️ Bu Sürümü Yeniden Yükle</button>
                <?php else: ?>
                  <button class="btn btn-birincil" data-onay="Güncellemeyi şimdi uygulamak istiyor musunuz? Mevcut dosyalar yedeklenecektir.">⬇️ Şimdi Güncelle (v<?= e($uzakSurum['surum']) ?>)</button>
                <?php endif; ?>
              </form>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Korunan Dosyalar</h2></div>
      <div class="kart-icerik">
        <p>Güncelleme sırasında <strong>üzerine yazılmaz</strong>:</p>
        <ul>
          <li><code>config.php</code></li>
          <li><code>config.local.php</code></li>
          <?php foreach (($yerelManifest['exclude_from_zip'] ?? []) as $h):
            if (in_array($h, ['config.php', 'config.local.php'])) continue; ?>
            <li><code><?= e($h) ?></code></li>
          <?php endforeach; ?>
        </ul>
        <p style="font-size:13px;color:#64748b;margin-top:10px;">📦 Tüm dosyaların yedeği güncelleme öncesi <code>assets/yedekler/</code> klasörüne alınır.</p>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>GitHub Ayarları</h2></div>
      <div class="kart-icerik">
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="islem" value="ayar_kaydet">

          <div class="form-grup">
            <label>GitHub Repo (kullanıcı/repo)</label>
            <input type="text" name="github_repo" value="<?= e($repo) ?>" placeholder="codegatr/atasunrentacar" required>
            <small>Örn: <code>codegatr/atasunrentacar</code></small>
          </div>

          <div class="form-grup">
            <label>Güncelleme Kanalı</label>
            <select name="kanal">
              <option value="releases" <?= $kanal === 'releases' ? 'selected' : '' ?>>Releases (önerilen)</option>
              <option value="branch" <?= $kanal === 'branch' ? 'selected' : '' ?>>Branch (geliştirici)</option>
            </select>
            <small>
              <strong>Releases:</strong> Sadece yayınlanmış sürüm tag'lerini takip eder.<br>
              <strong>Branch:</strong> Belirtilen branch'in son commit'ini alır (test ortamları için).
            </small>
          </div>

          <div class="form-grup">
            <label>Branch (sadece branch kanalı için)</label>
            <input type="text" name="branch" value="<?= e($branch) ?>" placeholder="main">
          </div>

          <div class="form-grup">
            <label>GitHub Personal Access Token (özel repo için)</label>
            <input type="password" name="github_token" value="" placeholder="<?= $githubToken ? '•••••••• (kayıtlı)' : 'ghp_...' ?>" autocomplete="new-password">
            <small>
              Sadece <strong>özel repo</strong> ise gereklidir.
              <a href="https://github.com/settings/tokens?type=beta" target="_blank" rel="noopener">Token oluştur</a>
              · İzin: sadece <code>Contents: Read</code>
            </small>
            <?php if ($githubToken): ?>
              <label style="display:flex;align-items:center;gap:6px;margin-top:6px;font-weight:normal;font-size:13px;color:#dc2626;">
                <input type="checkbox" name="token_sil" value="1"> Mevcut token'ı sil
              </label>
            <?php endif; ?>
          </div>

          <button class="btn btn-birincil btn-blok">💾 Ayarları Kaydet</button>
        </form>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Nasıl Yeni Sürüm Yayınlanır?</h2></div>
      <div class="kart-icerik" style="font-size:14px;">
        <ol style="padding-left:20px;line-height:1.7;">
          <li>Yeni sürüm dosyalarını GitHub repo'ya push edin</li>
          <li>GitHub repo sayfasında <strong>Releases → Draft a new release</strong></li>
          <li>Tag adı: <code>v1.0.5</code> (semver)</li>
          <li>Asset olarak <code>.zip</code> dosyasını yükleyin (opsiyonel; yoksa otomatik zipball indirilir)</li>
          <li><strong>Publish release</strong> tıklayın</li>
          <li>Burada "Güncelleme Kontrol Et" → "Şimdi Güncelle"</li>
        </ol>
        <p style="margin-top:10px;color:#64748b;">
          💡 <a href="https://github.com/<?= e($repo) ?>/releases/new" target="_blank" rel="noopener">Yeni release oluştur</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
