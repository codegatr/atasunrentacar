<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Rezervasyonlar';

if (($_GET['islem'] ?? '') === 'durum_degistir' && !empty($_GET['id']) && !empty($_GET['durum'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $yeniDurum = $_GET['durum'];
    if (in_array($yeniDurum, ['beklemede','onaylandi','teslim','iade','iptal'], true)) {
        DB::guncelle('rezervasyonlar', ['durum' => $yeniDurum], 'id = ?', [$id]);
        admin_log('Rezervasyon durumu', 'ID ' . $id . ' -> ' . $yeniDurum);
        flash_set('basari', 'Rezervasyon durumu guncellendi.');
    }
    yonlendir(admin_url('rezervasyonlar.php'));
}

$ara = trim($_GET['ara'] ?? '');
$durum = $_GET['durum'] ?? '';
$where = ['1=1']; $params = [];
if ($ara) {
    $where[] = "(r.rezervasyon_no LIKE ? OR r.misafir_ad LIKE ? OR r.misafir_soyad LIKE ? OR r.misafir_telefon LIKE ?)";
    $params = array_merge($params, ["%$ara%","%$ara%","%$ara%","%$ara%"]);
}
if ($durum) { $where[] = "r.durum = ?"; $params[] = $durum; }

$sayfa = max(1, (int)($_GET['s'] ?? 1));
$boyut = 25;
$ofset = ($sayfa - 1) * $boyut;
$whereSql = implode(' AND ', $where);

$toplam = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " r WHERE $whereSql", $params)['c'] ?? 0);
$rezervasyonlar = DB::liste(
    "SELECT r.*, a.plaka, a.marka, a.model
     FROM " . DB::tablo('rezervasyonlar') . " r
     LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = r.arac_id
     WHERE $whereSql ORDER BY r.olusturma DESC LIMIT $boyut OFFSET $ofset",
    $params
);

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2>Tum Rezervasyonlar (<?= $toplam ?>)</h2>
    <a href="<?= admin_url('rezervasyon-duzenle.php') ?>" class="btn btn-birincil">+ Yeni Rezervasyon</a>
  </div>

  <div class="kart-icerik">
    <form class="filtre" method="get">
      <input type="text" name="ara" value="<?= e($ara) ?>" placeholder="No, ad, soyad, telefon...">
      <select name="durum">
        <option value="">Tüm Durumlar</option>
        <?php foreach (['beklemede','onaylandi','teslim','iade','iptal'] as $d): ?>
        <option value="<?= e($d) ?>" <?= $durum===$d?'selected':'' ?>><?= e(ucfirst($d)) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-cerceve">Filtrele</button>
    </form>

    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead>
          <tr>
            <th>No</th>
            <th>Müşteri</th>
            <th>Araç</th>
            <th>Tarih</th>
            <th>Gun</th>
            <th>Tutar</th>
            <th>Durum</th>
            <th>İşlemler</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($rezervasyonlar): foreach ($rezervasyonlar as $r): ?>
          <tr>
            <td><a href="<?= admin_url('rezervasyon-duzenle.php?id=' . (int)$r['id']) ?>"><strong><?= e($r['rezervasyon_no']) ?></strong></a></td>
            <td>
              <strong><?= e($r['misafir_ad'] . ' ' . $r['misafir_soyad']) ?></strong>
              <small style="display:block; color:var(--renk-yazi-acik);"><?= e($r['misafir_telefon']) ?></small>
            </td>
            <td>
              <?php if ($r['marka']): ?>
                <strong><?= e($r['marka'] . ' ' . $r['model']) ?></strong>
                <small style="display:block; color:var(--renk-yazi-acik);"><?= e($r['plaka']) ?></small>
              <?php else: ?>
                <span style="color:var(--renk-yazi-acik);">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?= tarih_tr($r['alis_tarihi']) ?><br>
              <small style="color:var(--renk-yazi-acik);"><?= tarih_tr($r['iade_tarihi']) ?></small>
            </td>
            <td><?= (int)$r['toplam_gun'] ?></td>
            <td><?= tl((float)$r['toplam_tutar']) ?></td>
            <td><span class="rozet-tip rozet-<?= e($r['durum']) ?>"><?= e(ucfirst($r['durum'])) ?></span></td>
            <td>
              <div class="islemler">
                <a href="<?= admin_url('rezervasyon-duzenle.php?id=' . (int)$r['id']) ?>" class="duzenle">Düzenle</a>
                <?php if ($r['durum'] === 'beklemede'): ?>
                <a href="<?= admin_url('rezervasyonlar.php?islem=durum_degistir&id=' . (int)$r['id'] . '&durum=onaylandi&_csrf=' . csrf_token()) ?>" class="duzenle" data-onay="Onaylamak istiyor musunuz?" style="color:var(--renk-basari);">Onayla</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="8" class="bos-durum">Rezervasyon bulunamadi.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?= sayfalama($toplam, $sayfa, $boyut, admin_url('rezervasyonlar.php?ara=' . urlencode($ara) . '&durum=' . urlencode($durum) . '&s={p}')) ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
