<?php
require_once __DIR__ . '/_init.php';
admin_yetki('admin');
$pageTitle = 'Kullanıcılar';

if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    if ($id === (int)($GLOBALS['admin_kullanici']['id'] ?? 0)) {
        flash_set('hata', 'Kendi hesabınızı silemezsiniz.');
    } else {
        DB::sil('kullanicilar', 'id = ?', [$id]);
        admin_log('Kullanici sil', 'ID ' . $id);
        flash_set('basari', 'Kullanıcı silindi.');
    }
    yonlendir(admin_url('kullanicilar.php'));
}

if (($_GET['islem'] ?? '') === 'durum' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    if ($id === (int)($GLOBALS['admin_kullanici']['id'] ?? 0)) {
        flash_set('hata', 'Kendi hesabınızı pasifleştiremezsiniz.');
    } else {
        $u = DB::tek("SELECT aktif FROM " . DB::tablo('kullanicilar') . " WHERE id = ?", [$id]);
        if ($u) {
            DB::guncelle('kullanicilar', ['aktif' => $u['aktif'] ? 0 : 1], 'id = ?', [$id]);
            admin_log('Kullanici durum', 'ID ' . $id);
            flash_set('basari', 'Durum güncellendi.');
        }
    }
    yonlendir(admin_url('kullanicilar.php'));
}

$kullanicilar = DB::liste("SELECT id, kullanici_adi, ad_soyad, email, rol, aktif, son_giris, olusturma FROM " . DB::tablo('kullanicilar') . " ORDER BY id ASC");

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2>Kullanıcılar (<?= count($kullanicilar) ?>)</h2>
    <a href="<?= admin_url('kullanici-duzenle.php') ?>" class="btn btn-birincil">+ Yeni Kullanici</a>
  </div>
  <div class="kart-icerik">
    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead>
          <tr><th>#</th><th>Kullanıcı Adı</th><th>Ad Soyad</th><th>E-posta</th><th>Rol</th><th>Durum</th><th>Son Giriş</th><th>İşlemler</th></tr>
        </thead>
        <tbody>
          <?php foreach ($kullanicilar as $k): ?>
            <?php $kendisi = (int)$k['id'] === (int)($GLOBALS['admin_kullanici']['id'] ?? 0); ?>
          <tr>
            <td><?= (int)$k['id'] ?></td>
            <td><strong><?= e($k['kullanici_adi']) ?></strong> <?= $kendisi ? '<span style="color:#3b82f6;font-size:11px;">(siz)</span>' : '' ?></td>
            <td><?= e($k['ad_soyad']) ?></td>
            <td><?= e($k['email'] ?: '-') ?></td>
            <td><span class="rozet-tip rozet-<?= $k['rol'] === 'admin' ? 'aktif' : 'beklemede' ?>"><?= ucfirst($k['rol']) ?></span></td>
            <td><span class="rozet-tip rozet-<?= $k['aktif'] ? 'aktif' : 'pasif' ?>"><?= $k['aktif'] ? 'Aktif' : 'Pasif' ?></span></td>
            <td><?= $k['son_giris'] ? tarih_tr($k['son_giris'], true) : '-' ?></td>
            <td>
              <div class="islemler">
                <a href="<?= admin_url('kullanici-duzenle.php?id=' . (int)$k['id']) ?>" class="duzenle">Düzenle</a>
                <?php if (!$kendisi): ?>
                  <a href="<?= admin_url('kullanicilar.php?islem=durum&id=' . (int)$k['id'] . '&_csrf=' . csrf_token()) ?>" class="duzenle"><?= $k['aktif'] ? 'Pasifleştir' : 'Aktiflestir' ?></a>
                  <a href="<?= admin_url('kullanicilar.php?islem=sil&id=' . (int)$k['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Kullanıcı silinsin mi?">Sil</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
