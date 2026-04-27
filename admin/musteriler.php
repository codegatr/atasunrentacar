<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Müşteriler';

if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $rezSayi = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE musteri_id = ?", [$id])['c'] ?? 0);
    if ($rezSayi > 0) {
        flash_set('hata', 'Bu musteriye ait rezervasyon var, silinemez.');
    } else {
        DB::sil('musteriler', 'id = ?', [$id]);
        admin_log('Musteri sil', 'ID ' . $id);
        flash_set('basari', 'Musteri silindi.');
    }
    yonlendir(admin_url('musteriler.php'));
}

if (($_GET['islem'] ?? '') === 'kara_liste' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $m = DB::tek("SELECT kara_liste FROM " . DB::tablo('musteriler') . " WHERE id = ?", [$id]);
    if ($m) {
        DB::guncelle('musteriler', ['kara_liste' => $m['kara_liste'] ? 0 : 1], 'id = ?', [$id]);
        admin_log('Musteri kara liste', 'ID ' . $id);
        flash_set('basari', 'Kara liste durumu degistirildi.');
    }
    yonlendir(admin_url('musteriler.php'));
}

$ara = trim($_GET['ara'] ?? '');
$where = ['1=1']; $params = [];
if ($ara) {
    $where[] = "(ad LIKE ? OR soyad LIKE ? OR telefon LIKE ? OR email LIKE ? OR tc_no LIKE ?)";
    $params = array_merge($params, ["%$ara%","%$ara%","%$ara%","%$ara%","%$ara%"]);
}

$sayfa = max(1, (int)($_GET['s'] ?? 1));
$boyut = 25;
$ofset = ($sayfa - 1) * $boyut;
$whereSql = implode(' AND ', $where);

$toplam = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('musteriler') . " WHERE $whereSql", $params)['c'] ?? 0);
$musteriler = DB::liste(
    "SELECT * FROM " . DB::tablo('musteriler') . " WHERE $whereSql ORDER BY ad, soyad LIMIT $boyut OFFSET $ofset",
    $params
);

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2>Müşteriler (<?= $toplam ?>)</h2>
    <a href="<?= admin_url('musteri-duzenle.php') ?>" class="btn btn-birincil">+ Yeni Musteri</a>
  </div>

  <div class="kart-icerik">
    <form class="filtre" method="get">
      <input type="text" name="ara" value="<?= e($ara) ?>" placeholder="Ad, soyad, telefon, e-posta, TC...">
      <button class="btn btn-cerceve">Ara</button>
      <?php if ($ara): ?><a href="<?= admin_url('musteriler.php') ?>" class="btn btn-cerceve">Temizle</a><?php endif; ?>
    </form>

    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead>
          <tr>
            <th>Ad Soyad</th>
            <th>Telefon</th>
            <th>E-posta</th>
            <th>Sehir</th>
            <th>Ehliyet</th>
            <th>Durum</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($musteriler): foreach ($musteriler as $m): ?>
          <tr>
            <td><strong><?= e($m['ad'] . ' ' . $m['soyad']) ?></strong>
              <?php if ($m['tc_no']): ?><small style="display:block; color:var(--renk-yazi-acik);">TC: <?= e($m['tc_no']) ?></small><?php endif; ?>
            </td>
            <td><?= e($m['telefon']) ?></td>
            <td><?= e($m['email']) ?></td>
            <td><?= e($m['sehir']) ?></td>
            <td><?= e($m['ehliyet_no']) ?></td>
            <td>
              <?php if ($m['kara_liste']): ?>
                <span class="rozet-tip rozet-iptal">Kara Liste</span>
              <?php else: ?>
                <span class="rozet-tip rozet-aktif">Aktif</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="islemler">
                <a href="<?= admin_url('musteri-duzenle.php?id=' . (int)$m['id']) ?>" class="duzenle">Düzenle</a>
                <a href="<?= admin_url('musteriler.php?islem=kara_liste&id=' . (int)$m['id'] . '&_csrf=' . csrf_token()) ?>" class="duzenle" data-onay="Emin misiniz?">
                  <?= $m['kara_liste'] ? 'Kara liste cikar' : 'Kara liste' ?>
                </a>
                <a href="<?= admin_url('musteriler.php?islem=sil&id=' . (int)$m['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silmek istediginize emin misiniz?">Sil</a>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" class="bos-durum">Musteri bulunamadi.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?= sayfalama($toplam, $sayfa, $boyut, admin_url('musteriler.php?ara=' . urlencode($ara) . '&s={p}')) ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
