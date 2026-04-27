<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Muayeneler';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    if (($_POST['islem'] ?? '') === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $aracId = (int)($_POST['arac_id'] ?? 0);
        if (!$aracId) { flash_set('hata', 'Araç seçilmedi.'); }
        else {
            $data = [
                'arac_id' => $aracId,
                'muayene_tarihi' => $_POST['muayene_tarihi'] ?: null,
                'sonraki_muayene' => $_POST['sonraki_muayene'] ?: null,
                'km' => $_POST['km'] !== '' ? (int)$_POST['km'] : null,
                'sonuc' => in_array($_POST['sonuc'] ?? '', ['gecti','agir_kusur','hafif_kusur','ret'], true) ? $_POST['sonuc'] : 'gecti',
                'istasyon' => trim($_POST['istasyon'] ?? ''),
                'tutar' => (float)($_POST['tutar'] ?? 0),
                'notlar' => trim($_POST['notlar'] ?? ''),
            ];
            if (!empty($_FILES['dosya']['name'])) {
                $r = dosya_yukle($_FILES['dosya'], 'muayene');
                if ($r) $data['dosya'] = $r;
            }
            if ($id) { DB::guncelle('muayeneler', $data, 'id = ?', [$id]); admin_log('Muayene guncelle', 'ID ' . $id); }
            else { DB::ekle('muayeneler', $data); admin_log('Muayene olustur', 'Arac ' . $aracId); }
            flash_set('basari', 'Kaydedildi.');
        }
        yonlendir(admin_url('muayeneler.php'));
    }
}
if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $m = DB::tek("SELECT dosya FROM " . DB::tablo('muayeneler') . " WHERE id = ?", [(int)$_GET['id']]);
    if ($m && $m['dosya']) dosya_sil($m['dosya']);
    DB::sil('muayeneler', 'id = ?', [(int)$_GET['id']]);
    admin_log('Muayene sil', 'ID ' . (int)$_GET['id']);
    flash_set('basari', 'Silindi.');
    yonlendir(admin_url('muayeneler.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) $duzenle = DB::tek("SELECT * FROM " . DB::tablo('muayeneler') . " WHERE id = ?", [(int)$_GET['duzenle']]);

$liste = DB::liste(
    "SELECT m.*, a.plaka, a.marka, a.model FROM " . DB::tablo('muayeneler') . " m
     LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = m.arac_id
     ORDER BY m.muayene_tarihi DESC"
);
$araclar = DB::liste("SELECT id, plaka, marka, model FROM " . DB::tablo('araclar') . " ORDER BY plaka");
require __DIR__ . '/_layout_basla.php';
?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Muayeneler (<?= count($liste) ?>)</h2></div>
      <div class="kart-icerik">
        <div class="tablo-kapsayici">
          <table class="tablo">
            <thead><tr><th>Araç</th><th>Tarih</th><th>Sonraki</th><th>KM</th><th>Sonuc</th><th>Tutar</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($liste as $m):
              $kalan = $m['sonraki_muayene'] ? (strtotime($m['sonraki_muayene']) - time()) / 86400 : null;
            ?>
              <tr>
                <td><strong><?= e($m['plaka']) ?></strong><br><small><?= e($m['marka'] . ' ' . $m['model']) ?></small></td>
                <td><?= tarih_tr($m['muayene_tarihi']) ?></td>
                <td>
                  <?= tarih_tr($m['sonraki_muayene']) ?>
                  <?php if ($kalan !== null && $kalan < 30 && $kalan > 0): ?>
                    <small style="display:block; color:var(--renk-uyari);"><?= (int)$kalan ?> gun</small>
                  <?php elseif ($kalan !== null && $kalan <= 0): ?>
                    <small style="display:block; color:var(--renk-hata);">Sureç doldu</small>
                  <?php endif; ?>
                </td>
                <td><?= number_format((int)$m['km'], 0, ',', '.') ?></td>
                <td><span class="rozet-tip rozet-<?= $m['sonuc'] === 'gecti' ? 'aktif' : ($m['sonuc'] === 'ret' ? 'iptal' : 'beklemede') ?>"><?= e(str_replace('_',' ',$m['sonuc'])) ?></span></td>
                <td><?= tl((float)$m['tutar']) ?></td>
                <td>
                  <div class="islemler">
                    <a href="<?= admin_url('muayeneler.php?duzenle=' . (int)$m['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('muayeneler.php?islem=sil&id=' . (int)$m['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silmek istiyor musunuz?">Sil</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$liste): ?><tr><td colspan="7" class="bos-durum">Muayene kaydi yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Duzenle' : 'Yeni Muayene' ?></h2></div>
      <form method="post" class="kart-icerik" enctype="multipart/form-data">
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
        <div class="form-satir">
          <div class="form-grup"><label>Muayene Tarihi</label><input type="date" name="muayene_tarihi" value="<?= e($duzenle['muayene_tarihi'] ?? '') ?>"></div>
          <div class="form-grup"><label>Sonraki Muayene</label><input type="date" name="sonraki_muayene" value="<?= e($duzenle['sonraki_muayene'] ?? '') ?>"></div>
        </div>
        <div class="form-satir">
          <div class="form-grup"><label>KM</label><input type="number" name="km" value="<?= e($duzenle['km'] ?? '') ?>"></div>
          <div class="form-grup">
            <label>Sonuc</label>
            <select name="sonuc">
              <option value="gecti" <?= ($duzenle['sonuc'] ?? 'gecti') === 'gecti' ? 'selected' : '' ?>>Gecti</option>
              <option value="hafif_kusur" <?= ($duzenle['sonuc'] ?? '') === 'hafif_kusur' ? 'selected' : '' ?>>Hafif Kusur</option>
              <option value="agir_kusur" <?= ($duzenle['sonuc'] ?? '') === 'agir_kusur' ? 'selected' : '' ?>>Agir Kusur</option>
              <option value="ret" <?= ($duzenle['sonuc'] ?? '') === 'ret' ? 'selected' : '' ?>>Ret</option>
            </select>
          </div>
        </div>
        <div class="form-grup"><label>Istasyon</label><input type="text" name="istasyon" value="<?= e($duzenle['istasyon'] ?? '') ?>"></div>
        <div class="form-grup"><label>Tutar</label><input type="number" step="0.01" name="tutar" value="<?= e($duzenle['tutar'] ?? '0') ?>"></div>
        <div class="form-grup">
          <label>Belge (PDF/Resim)</label>
          <input type="file" name="dosya" accept="application/pdf,image/*">
          <?php if (!empty($duzenle['dosya'])): ?><small><a href="<?= e(upload_url($duzenle['dosya'])) ?>" target="_blank">Mevcut dosyayi gor</a></small><?php endif; ?>
        </div>
        <div class="form-grup"><label>Notlar</label><textarea name="notlar" rows="2"><?= e($duzenle['notlar'] ?? '') ?></textarea></div>
        <div style="margin-top:16px;"><button class="btn btn-birincil">Kaydet</button>
          <?php if ($duzenle): ?><a href="<?= admin_url('muayeneler.php') ?>" class="btn btn-cerceve">İptal</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
