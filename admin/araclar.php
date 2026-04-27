<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Araçlar';

// Silme
if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $arac = DB::tek("SELECT * FROM " . DB::tablo('araclar') . " WHERE id = ?", [$id]);
    if ($arac) {
        // Aktif rezervasyon var mi?
        $aktifRez = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE arac_id = ? AND durum IN ('beklemede','onaylandi','teslim')", [$id])['c'] ?? 0);
        if ($aktifRez > 0) {
            flash_set('hata', 'Aktif rezervasyonu olan araç silinemez. Önce pasif yapın.');
        } else {
            // Resimleri sil
            $resimler = DB::liste("SELECT dosya FROM " . DB::tablo('arac_resimler') . " WHERE arac_id = ?", [$id]);
            foreach ($resimler as $r) dosya_sil('araclar/' . $r['dosya']);
            DB::sil('araclar', 'id = ?', [$id]);
            admin_log('Arac silindi', $arac['plaka'] . ' ' . $arac['marka'] . ' ' . $arac['model']);
            flash_set('basari', 'Araç silindi.');
        }
    }
    yonlendir(admin_url('araclar.php'));
}

// Filtre
$ara = trim($_GET['ara'] ?? '');
$durum = $_GET['durum'] ?? '';
$kategoriId = (int)($_GET['kategori'] ?? 0);

$where = ['1=1'];
$params = [];
if ($ara) {
    $where[] = "(plaka LIKE ? OR marka LIKE ? OR model LIKE ?)";
    $params[] = "%$ara%"; $params[] = "%$ara%"; $params[] = "%$ara%";
}
if ($durum) { $where[] = "durum = ?"; $params[] = $durum; }
if ($kategoriId) { $where[] = "kategori_id = ?"; $params[] = $kategoriId; }
$whereSql = implode(' AND ', $where);

$sayfa = max(1, (int)($_GET['s'] ?? 1));
$boyut = 20;
$ofset = ($sayfa - 1) * $boyut;

$toplam = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE $whereSql", $params)['c'] ?? 0);
$araclar = DB::liste(
    "SELECT a.*, k.ad kategori_ad, (SELECT dosya FROM " . DB::tablo('arac_resimler') . " WHERE arac_id=a.id AND ana_resim=1 LIMIT 1) ana_resim
     FROM " . DB::tablo('araclar') . " a
     LEFT JOIN " . DB::tablo('kategoriler') . " k ON k.id=a.kategori_id
     WHERE $whereSql ORDER BY a.olusturma DESC LIMIT $boyut OFFSET $ofset",
    $params
);

$kategoriler = DB::liste("SELECT id, ad FROM " . DB::tablo('kategoriler') . " WHERE aktif=1 ORDER BY sira ASC");

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2>Tum Araclar (<?= $toplam ?>)</h2>
    <a href="<?= admin_url('arac-duzenle.php') ?>" class="btn btn-birincil">+ Yeni Arac</a>
  </div>

  <div class="kart-icerik">
    <form class="filtre" method="get">
      <input type="text" name="ara" value="<?= e($ara) ?>" placeholder="Plaka, marka, model...">
      <select name="durum">
        <option value="">Tüm Durumlar</option>
        <?php foreach (['musait','kirada','rezerve','bakimda','satildi'] as $d): ?>
        <option value="<?= e($d) ?>" <?= $durum===$d?'selected':'' ?>><?= e(ucfirst($d)) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="kategori">
        <option value="0">Tüm Kategoriler</option>
        <?php foreach ($kategoriler as $k): ?>
        <option value="<?= (int)$k['id'] ?>" <?= $kategoriId===(int)$k['id']?'selected':'' ?>><?= e($k['ad']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-cerceve">Filtrele</button>
      <?php if ($ara || $durum || $kategoriId): ?>
      <a href="<?= admin_url('araclar.php') ?>" class="btn btn-cerceve">Temizle</a>
      <?php endif; ?>
    </form>

    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead>
          <tr>
            <th></th>
            <th>Plaka</th>
            <th>Araç</th>
            <th>Kategori</th>
            <th>Yıl</th>
            <th>Gunluk</th>
            <th>Durum</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($araclar): foreach ($araclar as $a): ?>
          <tr>
            <td>
              <?php if ($a['ana_resim']): ?>
                <img src="<?= e(upload_url('araclar/' . $a['ana_resim'])) ?>" style="width:50px; height:50px; object-fit:cover; border-radius:6px;">
              <?php else: ?>
                <div style="width:50px; height:50px; background:var(--renk-arka); border-radius:6px; display:flex; align-items:center; justify-content:center;">🚗</div>
              <?php endif; ?>
            </td>
            <td><strong><?= e($a['plaka']) ?></strong></td>
            <td>
              <strong><?= e($a['marka'] . ' ' . $a['model']) ?></strong>
              <small style="display:block; color:var(--renk-yazi-acik);"><?= e($a['vites']) ?> · <?= e($a['yakit']) ?></small>
            </td>
            <td><?= e($a['kategori_ad'] ?? '-') ?></td>
            <td><?= (int)$a['yil'] ?></td>
            <td><?= tl((float)$a['gunluk_fiyat']) ?></td>
            <td><span class="rozet-tip rozet-<?= e($a['durum']) ?>"><?= e(ucfirst($a['durum'])) ?></span></td>
            <td>
              <div class="islemler">
                <a href="<?= admin_url('arac-duzenle.php?id=' . (int)$a['id']) ?>" class="duzenle">Düzenle</a>
                <a href="<?= url('arac/' . $a['slug']) ?>" target="_blank" class="duzenle">↗</a>
                <a href="<?= admin_url('araclar.php?islem=sil&id=' . (int)$a['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Bu araci silmek istediginize emin misiniz?">Sil</a>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="8" class="bos-durum">Arac bulunamadi.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php
    $linkBase = admin_url('araclar.php?ara=' . urlencode($ara) . '&durum=' . urlencode($durum) . '&kategori=' . $kategoriId . '&s={p}');
    echo sayfalama($toplam, $sayfa, $boyut, $linkBase);
    ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
