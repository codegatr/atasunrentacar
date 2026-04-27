<?php
/**
 * ATA SU Rent A Car - Kurulum Sihirbazi
 * Bu dosya kurulumdan sonra silinmelidir!
 */
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

// Config'i guvenli sekilde yuklemek icin
if (!defined('ATASU')) define('ATASU', true);

$adim = $_GET['adim'] ?? '1';
$hata = '';
$basari = '';

// Kurulum tamamlandiysa engelle (config var ve admin var)
if (file_exists(__DIR__ . '/config.php') && !isset($_GET['force'])) {
    require __DIR__ . '/config.php';
    try {
        $kontrol = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $stmt = $kontrol->query("SELECT COUNT(*) FROM " . DB_PREFIX . "kullanicilar WHERE rol='admin'");
        if ((int)$stmt->fetchColumn() > 0) {
            // Kurulum tamamlanmis
            $kuruluTamam = true;
        }
    } catch (Throwable $e) {}
}

// === ADIM ISLEMLERI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($adim === '2') {
            // Veritabani test ve config olustur
            $host = trim($_POST['host'] ?? '');
            $kul = trim($_POST['kullanici'] ?? '');
            $sif = $_POST['sifre'] ?? '';
            $vt = trim($_POST['veritabani'] ?? '');
            $prefix = trim($_POST['prefix'] ?? 'atasu_');
            $siteUrl = trim($_POST['site_url'] ?? '');

            if (!$host || !$kul || !$vt) throw new Exception('Lutfen tum alanlari doldurun.');

            $pdo = new PDO("mysql:host=$host;dbname=$vt;charset=utf8mb4", $kul, $sif, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // config.sample.php icerigini al ve degerleri yerlestir
            $tmpl = file_get_contents(__DIR__ . '/config.sample.php');
            $tmpl = preg_replace("/define\\('DB_HOST',\\s*'[^']*'\\)/", "define('DB_HOST', " . var_export($host, true) . ")", $tmpl);
            $tmpl = preg_replace("/define\\('DB_NAME',\\s*'[^']*'\\)/", "define('DB_NAME', " . var_export($vt, true) . ")", $tmpl);
            $tmpl = preg_replace("/define\\('DB_USER',\\s*'[^']*'\\)/", "define('DB_USER', " . var_export($kul, true) . ")", $tmpl);
            $tmpl = preg_replace("/define\\('DB_PASS',\\s*'[^']*'\\)/", "define('DB_PASS', " . var_export($sif, true) . ")", $tmpl);
            $tmpl = preg_replace("/define\\('DB_PREFIX',\\s*'[^']*'\\)/", "define('DB_PREFIX', " . var_export($prefix, true) . ")", $tmpl);
            $tmpl = preg_replace("/define\\('SITE_URL',\\s*'[^']*'\\)/", "define('SITE_URL', " . var_export(rtrim($siteUrl, '/'), true) . ")", $tmpl);
            $tmpl = preg_replace("/define\\('APP_SECRET',\\s*'[^']*'\\)/", "define('APP_SECRET', " . var_export(bin2hex(random_bytes(32)), true) . ")", $tmpl);

            if (file_put_contents(__DIR__ . '/config.php', $tmpl) === false) {
                throw new Exception('config.php olusturulamadi. Klasor yazma izni var mi?');
            }

            $_SESSION['kurulum_db_ok'] = true;
            header('Location: install.php?adim=3');
            exit;
        }

        if ($adim === '3') {
            // Migration calistir
            require_once __DIR__ . '/config.php';
            require_once __DIR__ . '/includes/baglanti.php';
            require_once __DIR__ . '/includes/migration.php';

            $sayi = Migration::calistir(__DIR__ . '/migrations');
            $_SESSION['kurulum_migrate_ok'] = true;
            header('Location: install.php?adim=4');
            exit;
        }

        if ($adim === '4') {
            // Admin kullanici olustur
            $kAdi = trim($_POST['k_adi'] ?? '');
            $adSoyad = trim($_POST['ad_soyad'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $sifre = $_POST['sifre'] ?? '';
            $sifre2 = $_POST['sifre2'] ?? '';

            if (!$kAdi || !$email || !$sifre) throw new Exception('Tüm alanları doldurun.');
            if (strlen($sifre) < 6) throw new Exception('Şifre en az 6 karakter olmalıdır.');
            if ($sifre !== $sifre2) throw new Exception('Şifreler eşleşmiyor.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Geçerli bir e-posta girin.');

            require_once __DIR__ . '/config.php';
            require_once __DIR__ . '/includes/baglanti.php';

            DB::sorgu("DELETE FROM " . DB::tablo('kullanicilar') . " WHERE kullanici_adi = ? OR email = ?", [$kAdi, $email]);
            DB::ekle('kullanicilar', [
                'kullanici_adi' => $kAdi,
                'sifre_hash' => password_hash($sifre, PASSWORD_BCRYPT),
                'ad_soyad' => $adSoyad ?: $kAdi,
                'email' => $email,
                'rol' => 'admin',
                'aktif' => 1,
            ]);

            $_SESSION['kurulum_admin_ok'] = true;
            header('Location: install.php?adim=5');
            exit;
        }
    } catch (Throwable $e) {
        $hata = $e->getMessage();
    }
}

// Default site URL tahmini
$tahminSiteUrl = ($_SERVER['HTTPS'] ?? 'off') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
    ? 'https://' : 'http://';
$tahminSiteUrl .= $_SERVER['HTTP_HOST'] ?? 'localhost';
$tahminSiteUrl .= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');

?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ATA SU Rent A Car - Kurulum</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1e3a5f, #0f1e36); min-height: 100vh; padding: 30px 20px; color: #1e293b; }
.kutu { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
.kutu-baslik { background: #1e3a5f; color: #fff; padding: 30px; text-align: center; }
.kutu-baslik h1 { font-size: 1.8rem; margin-bottom: 6px; }
.kutu-baslik p { color: rgba(255,255,255,0.8); font-size: 0.95rem; }
.adimlar { display: flex; padding: 20px 30px 0; gap: 8px; }
.adim-rozet { flex: 1; height: 4px; background: #e2e8f0; border-radius: 2px; }
.adim-rozet.aktif { background: #3b82f6; }
.kutu-icerik { padding: 30px; }
.kutu-icerik h2 { font-size: 1.3rem; margin-bottom: 8px; color: #1e3a5f; }
.kutu-icerik > p { color: #64748b; margin-bottom: 24px; font-size: 0.95rem; }
.form-grup { margin-bottom: 16px; }
.form-grup label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; }
.form-grup input { width: 100%; padding: 12px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem; }
.form-grup input:focus { outline: none; border-color: #3b82f6; }
.btn { display: inline-block; padding: 12px 28px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn:hover { background: #2563eb; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 0.92rem; }
.alert-hata { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
.alert-basari { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
.kontrol { background: #f8fafc; padding: 14px 18px; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; }
.kontrol.basari { color: #065f46; }
.kontrol.hata { color: #991b1b; }
.uyari { background: #fffbeb; color: #92400e; padding: 14px 18px; border-radius: 8px; margin-top: 20px; border: 1px solid #fde68a; font-size: 0.9rem; }
code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.85rem; }
.satir2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
@media (max-width: 600px) { .satir2 { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<?php if (!empty($kuruluTamam)): ?>
<div class="kutu">
  <div class="kutu-baslik">
    <h1>Kurulum Tamamlandi</h1>
    <p>Sistem zaten kurulu</p>
  </div>
  <div class="kutu-icerik">
    <div class="alert alert-basari">Sistem daha once kurulmus. Bu dosyayi sunucudan silmelisiniz!</div>
    <p style="margin-bottom:16px;">Devam etmek icin admin paneline gidin:</p>
    <a href="admin/giris.php" class="btn">Admin Girisi</a>
    <div class="uyari">
      <strong>GUVENLIK UYARISI:</strong> Lutfen <code>install.php</code> dosyasini sunucudan silin.
      Tekrar kurmak isterseniz <code>install.php?force=1</code> ile zorlayabilirsiniz.
    </div>
  </div>
</div>
</body></html>
<?php exit; endif; ?>

<div class="kutu">
  <div class="kutu-baslik">
    <h1>ATA SU Rent A Car</h1>
    <p>Kurulum Sihirbazi</p>
  </div>

  <div class="adimlar">
    <div class="adim-rozet <?= (int)$adim >= 1 ? 'aktif' : '' ?>"></div>
    <div class="adim-rozet <?= (int)$adim >= 2 ? 'aktif' : '' ?>"></div>
    <div class="adim-rozet <?= (int)$adim >= 3 ? 'aktif' : '' ?>"></div>
    <div class="adim-rozet <?= (int)$adim >= 4 ? 'aktif' : '' ?>"></div>
    <div class="adim-rozet <?= (int)$adim >= 5 ? 'aktif' : '' ?>"></div>
  </div>

  <div class="kutu-icerik">
    <?php if ($hata): ?><div class="alert alert-hata"><?= htmlspecialchars($hata, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <?php if ($adim === '1'): ?>
      <h2>1. Sistem Gereksinimleri</h2>
      <p>Devam etmeden once gereksinimleri kontrol ediyoruz.</p>
      <?php
        $kontroller = [
          ['PHP 8.3+', version_compare(PHP_VERSION, '8.3', '>=')],
          ['PDO + MySQL Surucusu', extension_loaded('pdo') && extension_loaded('pdo_mysql')],
          ['mbstring', extension_loaded('mbstring')],
          ['fileinfo', extension_loaded('fileinfo')],
          ['JSON', extension_loaded('json')],
          ['cURL', extension_loaded('curl')],
          ['ZIP', extension_loaded('zip')],
          ['Yazma izni: ' . __DIR__, is_writable(__DIR__)],
          ['Yazma izni: assets/uploads/', is_writable(__DIR__ . '/assets/uploads')],
        ];
        $hep = true;
        foreach ($kontroller as $k):
          if (!$k[1]) $hep = false;
      ?>
        <div class="kontrol <?= $k[1] ? 'basari' : 'hata' ?>">
          <span><?= htmlspecialchars($k[0], ENT_QUOTES, 'UTF-8') ?></span>
          <strong><?= $k[1] ? '✓ Tamam' : '✗ Eksik' ?></strong>
        </div>
      <?php endforeach; ?>
      <?php if ($hep): ?>
        <a href="install.php?adim=2" class="btn" style="margin-top:16px;">Devam Et</a>
      <?php else: ?>
        <div class="uyari">Lutfen eksik gereksinimleri tamamlayip sayfayi yenileyin.</div>
      <?php endif; ?>

    <?php elseif ($adim === '2'): ?>
      <h2>2. Veritabani Bilgileri</h2>
      <p>DirectAdmin panelinden olusturdugunuz veritabani bilgilerini girin.</p>
      <form method="post">
        <div class="satir2">
          <div class="form-grup">
            <label>Sunucu</label>
            <input type="text" name="host" value="localhost" required>
          </div>
          <div class="form-grup">
            <label>Tablo Onek</label>
            <input type="text" name="prefix" value="atasu_" required>
          </div>
        </div>
        <div class="form-grup">
          <label>Veritabani Adi</label>
          <input type="text" name="veritabani" required>
        </div>
        <div class="satir2">
          <div class="form-grup">
            <label>Kullanıcı Adı</label>
            <input type="text" name="kullanici" required>
          </div>
          <div class="form-grup">
            <label>Şifre</label>
            <input type="password" name="sifre">
          </div>
        </div>
        <div class="form-grup">
          <label>Site URL</label>
          <input type="url" name="site_url" value="<?= htmlspecialchars($tahminSiteUrl, ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <button type="submit" class="btn">Baglantiyi Test Et ve Kaydet</button>
      </form>

    <?php elseif ($adim === '3'): ?>
      <h2>3. Veritabani Tablolari</h2>
      <p>Migration dosyalari calistirilacak ve gerekli tablolar olusturulacaktir.</p>
      <form method="post">
        <button type="submit" class="btn">Tablolari Olustur</button>
      </form>

    <?php elseif ($adim === '4'): ?>
      <h2>4. Yonetici Hesabi</h2>
      <p>Admin paneline giris yapacak yonetici hesabini olusturun.</p>
      <form method="post">
        <div class="satir2">
          <div class="form-grup">
            <label>Kullanıcı Adı</label>
            <input type="text" name="k_adi" value="<?= htmlspecialchars($_POST['k_adi'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="form-grup">
            <label>Ad Soyad</label>
            <input type="text" name="ad_soyad" value="<?= htmlspecialchars($_POST['ad_soyad'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </div>
        <div class="form-grup">
          <label>E-posta</label>
          <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
        </div>
        <div class="satir2">
          <div class="form-grup">
            <label>Şifre (en az 6 karakter)</label>
            <input type="password" name="sifre" required>
          </div>
          <div class="form-grup">
            <label>Şifre (Tekrar)</label>
            <input type="password" name="sifre2" required>
          </div>
        </div>
        <button type="submit" class="btn">Yonetici Olustur</button>
      </form>

    <?php elseif ($adim === '5'): ?>
      <h2>Kurulum Tamamlandi</h2>
      <p>Sisteminiz kullanima hazir!</p>
      <div class="alert alert-basari">
        <strong>✓ Tum islemler basariyla tamamlandi.</strong>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:20px;">
        <a href="<?= htmlspecialchars($tahminSiteUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn">Anasayfa</a>
        <a href="admin/giris.php" class="btn" style="background:#1e3a5f;">Admin Girisi</a>
      </div>
      <div class="uyari">
        <strong>GUVENLIK ICIN:</strong> Lutfen <code>install.php</code> dosyasini sunucudan silin.
        Bu dosya kurulumun tekrar baslamasini onler ama silinmesi en guvenli secenektir.
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
