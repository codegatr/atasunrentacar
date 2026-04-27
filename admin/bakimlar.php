<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Bakımlar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    if (($_POST['islem'] ?? '') === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $aracId = (int)($_POST['arac_id'] ?? 0);
        if (!$aracId) { flash_set('hata', 'Araç seçilmedi.'); }
        else {
            $data = [
                'arac_id' => $aracId,
                'tarih' => $_POST['tarih'] ?: null,
                'tip' => trim($_POST['tip'] ?? ''),
                'km' => $_POST['km'] !== '' ? (int)$_POST['km'] : null,
                'sonraki_bakim_km' => $_POST['sonraki_bakim_km'] !== '' ? (int)$_POST['sonraki_bakim_km'] : null,
                'sonraki_bakim_tarihi' => $_POST['sonraki_bakim_tarihi'] ?: null,
                'yer' => trim($_POST['yer'] ?? ''),
                'tutar' => (float)($_POST['tutar'] ?? 0),
                'notlar' => trim($_POST['notlar'] ?? ''),
            ];
            if ($id) { DB::guncelle('bakimlar', $data, 'id = ?', [$id]); admin_log('Bakim guncelle', 'ID ' . $id); }
            else { DB::ekle('bakimlar', $data); admin_log('Bakim olustur', 'Arac ' . $aracId); }
            flash_set('basari', 'Kaydedildi.');
        }
        yonlendir(admin_url('bakimlar.php'));
    }
}
if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    DB::sil('bakimlar', 'id = ?', [(int)$_GET['id']]);
    admin_log('Bakim sil', 'ID ' . (int)$_GET['id']);
    flash_set('basari', 'Silindi.');
    yonlendir(admin_url('bakimlar.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) $duzenle = DB::tek("SELECT * FROM " . DB::tablo('bakimlar') . " WHERE id = ?", [(int)$_GET['duzenle']]);

$liste = DB::liste(
    "SELECT b.*, a.plaka, a.marka, a.model FROM " . DB::tablo('bakimlar') . " b
     LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = b.arac_id
     ORDER BY b.tarih DESC LIMIT 100"
);
$araclar = DB::liste("SELECT id, plaka, marka, model FROM " . DB::tablo('araclar') . " ORDER BY plaka");
require __DIR__ . '/_layout_basla.php';
?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Bakımlar (<?= count($liste) ?>)</h2></div>
      <div class="kart-icerik">
        <div class="tablo-kapsayici">
          <table class="tablo">
            <thead><tr><th>Araç</th><th>Tarih</th><th>Tip</th><th>KM</th><th>Sonraki</th><th>Tutar</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($liste as $b): ?>
              <tr>
                <td><strong><?= e($b['plaka']) ?></strong><br><small><?= e($b['marka'] . ' ' . $b['model']) ?></small></td>
                <td><?= tarih_tr($b['tarih']) ?></td>
                <td><?= e($b['tip']) ?></td>
                <td><?= number_format((int)$b['km'], 0, ',', '.') ?></td>
                <td>
                  <?php if ($b['sonraki_bakim_km']): ?><?= number_format((int)$b['sonraki_bakim_km'], 0, ',', '.') ?> km<?php endif; ?>
                  <?php if ($b['sonraki_bakim_tarihi']): ?><br><small><?= tarih_tr($b['sonraki_bakim_tarihi']) ?></small><?php endif; ?>
                </td>
                <td><?= tl((float)$b['tutar']) ?></td>
                <td>
                  <div class="islemler">
                    <a href="<?= admin_url('bakimlar.php?duzenle=' . (int)$b['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('bakimlar.php?islem=sil&id=' . (int)$b['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silmek istiyor musunuz?">Sil</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$liste): ?><tr><td colspan="7" class="bos-durum">Bakim kaydi yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Duzenle' : 'Yeni Bakim' ?></h2></div>
      <form method="post" class="kart-icerik">
        <?= csrf_input() ?>
        <input type="hidden" name="islem" value="kaydet">
        <input type="hidden" name="id" value="<?= (int)($duzenle['id'] ?? 0) ?>">

        <div class="form-grup">
          <label>Arac *</label>
          <select name="arac_id" required>
            <option value="">Sec...</option>
            <?php foreach ($araclar as $a): ?>
            <option value="<?= $a['id'] ?>" <?= ($duzenle['arac_id'] ?? 0) == $a['id'] ? 'selected' : '' ?>><?= e($a['plaka'] . ' - ' . $a['marka'] . ' ' . $a['model']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grup"><label>Tarih</label><input type="date" name="tarih" value="<?= e($duzenle['tarih'] ?? date('Y-m-d')) ?>"></div>
        <div class="form-grup"><label>Tip (yag, lastik, fren vb.)</label><input type="text" name="tip" value="<?= e($duzenle['tip'] ?? '') ?>"></div>
        <div class="form-satir">
          <div class="form-grup"><label>KM</label><input type="number" name="km" value="<?= e($duzenle['km'] ?? '') ?>"></div>
          <div class="form-grup"><label>Sonraki Bakim KM</label><input type="number" name="sonraki_bakim_km" value="<?= e($duzenle['sonraki_bakim_km'] ?? '') ?>"></div>
        </div>
        <div class="form-grup"><label>Sonraki Bakim Tarihi</label><input type="date" name="sonraki_bakim_tarihi" value="<?= e($duzenle['sonraki_bakim_tarihi'] ?? '') ?>"></div>
        <div class="form-grup"><label>Yer / Servis</label><input type="text" name="yer" value="<?= e($duzenle['yer'] ?? '') ?>"></div>
        <div class="form-grup"><label>Tutar</label><input type="number" step="0.01" name="tutar" value="<?= e($duzenle['tutar'] ?? '0') ?>"></div>
        <div class="form-grup"><label>Notlar</label><textarea name="notlar" rows="3"><?= e($duzenle['notlar'] ?? '') ?></textarea></div>
        <div style="margin-top:16px;"><button class="btn btn-birincil">Kaydet</button>
          <?php if ($duzenle): ?><a href="<?= admin_url('bakimlar.php') ?>" class="btn btn-cerceve">İptal</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
