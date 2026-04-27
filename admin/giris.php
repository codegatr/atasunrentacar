<?php
require_once __DIR__ . '/_init.php';

if (!empty($_SESSION['admin_id'])) {
    yonlendir(admin_url('index.php'));
}

$hata = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $kAdi = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';

    if (!$kAdi || !$sifre) {
        $hata = 'Lutfen tum alanlari doldurun.';
    } else {
        $u = DB::tek("SELECT * FROM " . DB::tablo('kullanicilar') . " WHERE (kullanici_adi = ? OR email = ?) AND aktif = 1 LIMIT 1", [$kAdi, $kAdi]);
        if ($u && password_verify($sifre, $u['sifre_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$u['id'];
            $_SESSION['admin_rol'] = $u['rol'];
            $_SESSION['admin_ad'] = $u['ad_soyad'];
            DB::sorgu("UPDATE " . DB::tablo('kullanicilar') . " SET son_giris = NOW() WHERE id = ?", [(int)$u['id']]);
            try {
                DB::ekle('log', ['kullanici_id' => (int)$u['id'], 'islem' => 'Giris', 'aciklama' => 'Admin paneline giris yapti', 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
            } catch (Throwable $e) {}
            yonlendir(admin_url('index.php'));
        }
        $hata = 'Kullanıcı adı veya şifre hatalı.';
    }
}
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Giris - ATA SU Rent A Car</title>
<link rel="stylesheet" href="<?= url('admin/assets/admin.css') ?>">
</head>
<body class="giris-sayfa">

<div class="giris-kutu">
  <div class="giris-baslik">
    <div class="logo-yazi">
      <span class="logo-ana">ATA SU</span>
      <span class="logo-alt">RENT A CAR</span>
    </div>
    <h1>Yönetim Paneli</h1>
  </div>

  <form method="post" class="giris-form">
    <?= csrf_input() ?>
    <?php if ($hata): ?><div class="alert alert-hata"><?= e($hata) ?></div><?php endif; ?>

    <div class="form-grup">
      <label>Kullanıcı Adı veya E-posta</label>
      <input type="text" name="kullanici_adi" required autofocus>
    </div>

    <div class="form-grup">
      <label>Şifre</label>
      <input type="password" name="sifre" required>
    </div>

    <button type="submit" class="btn btn-birincil btn-blok">Giriş Yap</button>
  </form>

  <div class="giris-alt">
    <a href="<?= url() ?>">&larr; Siteye Don</a>
  </div>
</div>

</body>
</html>
