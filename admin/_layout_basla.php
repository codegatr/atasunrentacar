<?php defined('ATASU_ADMIN') or exit('403');
$adminAd = $GLOBALS['admin_kullanici']['ad_soyad'] ?? 'Admin';
$adminRol = $GLOBALS['admin_kullanici']['rol'] ?? '';
$mevcut = basename($_SERVER['SCRIPT_NAME']);
$pageTitle = $pageTitle ?? 'Yönetim Paneli';

// Bekleyen sayilari (sidebar rozeti)
try {
  $bekleyenRez = (int)DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE durum='beklemede'")['c'] ?? 0;
  $okunmamisMsj = (int)DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('iletisim_mesajlari') . " WHERE okundu=0")['c'] ?? 0;
  $bekleyenYorum = (int)DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('yorumlar') . " WHERE onayli=0")['c'] ?? 0;
} catch (Throwable $e) {
  $bekleyenRez = $okunmamisMsj = $bekleyenYorum = 0;
}
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> - ATA SU Admin</title>
<link rel="stylesheet" href="<?= url('admin/assets/admin.css') ?>">
</head>
<body class="admin">

<aside class="sidebar">
  <div class="sb-baslik">
    <div class="logo-yazi">
      <span class="logo-ana">ATA SU</span>
      <span class="logo-alt">ADMIN</span>
    </div>
  </div>

  <nav class="sb-menu">
    <div class="sb-grup">Genel</div>
    <a href="<?= admin_url('index.php') ?>" class="<?= $mevcut === 'index.php' ? 'aktif' : '' ?>">
      <span>📊</span> Panel
    </a>

    <div class="sb-grup">Operasyon</div>
    <a href="<?= admin_url('rezervasyonlar.php') ?>" class="<?= str_starts_with($mevcut, 'rezervasyon') ? 'aktif' : '' ?>">
      <span>📅</span> Rezervasyonlar
      <?php if ($bekleyenRez > 0): ?><span class="rozet"><?= $bekleyenRez ?></span><?php endif; ?>
    </a>
    <a href="<?= admin_url('araclar.php') ?>" class="<?= str_starts_with($mevcut, 'arac') ? 'aktif' : '' ?>">
      <span>🚗</span> Araçlar
    </a>
    <a href="<?= admin_url('musteriler.php') ?>" class="<?= str_starts_with($mevcut, 'musteri') ? 'aktif' : '' ?>">
      <span>👥</span> Müşteriler
    </a>
    <a href="<?= admin_url('kategoriler.php') ?>" class="<?= $mevcut === 'kategoriler.php' ? 'aktif' : '' ?>">
      <span>🏷️</span> Kategoriler
    </a>
    <a href="<?= admin_url('ek-hizmetler.php') ?>" class="<?= $mevcut === 'ek-hizmetler.php' ? 'aktif' : '' ?>">
      <span>➕</span> Ek Hizmetler
    </a>

    <div class="sb-grup">Araç Yönetimi</div>
    <a href="<?= admin_url('sigortalar.php') ?>" class="<?= $mevcut === 'sigortalar.php' ? 'aktif' : '' ?>">
      <span>🛡️</span> Sigortalar
    </a>
    <a href="<?= admin_url('muayeneler.php') ?>" class="<?= $mevcut === 'muayeneler.php' ? 'aktif' : '' ?>">
      <span>📋</span> Muayeneler
    </a>
    <a href="<?= admin_url('bakimlar.php') ?>" class="<?= $mevcut === 'bakimlar.php' ? 'aktif' : '' ?>">
      <span>🔧</span> Bakımlar
    </a>
    <a href="<?= admin_url('hasarlar.php') ?>" class="<?= $mevcut === 'hasarlar.php' ? 'aktif' : '' ?>">
      <span>⚠️</span> Hasarlar
    </a>

    <div class="sb-grup">Finans</div>
    <a href="<?= admin_url('gelir-gider.php') ?>" class="<?= $mevcut === 'gelir-gider.php' ? 'aktif' : '' ?>">
      <span>💰</span> Gelir / Gider
    </a>
    <a href="<?= admin_url('raporlar.php') ?>" class="<?= $mevcut === 'raporlar.php' ? 'aktif' : '' ?>">
      <span>📈</span> Raporlar
    </a>

    <div class="sb-grup">İçerik</div>
    <a href="<?= admin_url('bloglar.php') ?>" class="<?= str_starts_with($mevcut, 'blog') ? 'aktif' : '' ?>">
      <span>📰</span> Blog
    </a>
    <a href="<?= admin_url('yorumlar.php') ?>" class="<?= $mevcut === 'yorumlar.php' ? 'aktif' : '' ?>">
      <span>💬</span> Yorumlar
      <?php if ($bekleyenYorum > 0): ?><span class="rozet"><?= $bekleyenYorum ?></span><?php endif; ?>
    </a>
    <a href="<?= admin_url('iletisim-mesajlari.php') ?>" class="<?= $mevcut === 'iletisim-mesajlari.php' ? 'aktif' : '' ?>">
      <span>✉️</span> Mesajlar
      <?php if ($okunmamisMsj > 0): ?><span class="rozet"><?= $okunmamisMsj ?></span><?php endif; ?>
    </a>

    <?php if ($adminRol === 'admin'): ?>
    <div class="sb-grup">Sistem</div>
    <a href="<?= admin_url('ayarlar.php') ?>" class="<?= $mevcut === 'ayarlar.php' ? 'aktif' : '' ?>">
      <span>⚙️</span> Ayarlar
    </a>
    <a href="<?= admin_url('kullanicilar.php') ?>" class="<?= str_starts_with($mevcut, 'kullanici') ? 'aktif' : '' ?>">
      <span>👤</span> Kullanıcılar
    </a>
    <a href="<?= admin_url('guncelleme.php') ?>" class="<?= $mevcut === 'guncelleme.php' ? 'aktif' : '' ?>">
      <span>🔄</span> Güncelleme
    </a>
    <?php endif; ?>
  </nav>
</aside>

<main class="ana-icerik">
  <header class="ust-bar">
    <button class="sb-acan" aria-label="Menu" onclick="document.body.classList.toggle('sb-acik')">☰</button>
    <h1><?= e($pageTitle) ?></h1>
    <div class="ust-sag">
      <a href="<?= url() ?>" target="_blank" class="ust-link">Siteyi Görüntüle ↗</a>
      <div class="ust-kullanici">
        <span><?= e($adminAd) ?></span>
        <a href="<?= admin_url('cikis.php') ?>" class="cikis-btn">Çıkış</a>
      </div>
    </div>
  </header>

  <div class="icerik">
    <?php
    foreach (flash_al() as $f):
      $tip = $f['tip'] === 'basari' ? 'basarili' : $f['tip'];
    ?>
      <div class="alert alert-<?= e($tip) ?>"><?= e($f['mesaj']) ?></div>
    <?php endforeach; ?>
