<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Hasarlar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    if (($_POST['islem'] ?? '') === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $aracId = (int)($_POST['arac_id'] ?? 0);
        if (!$aracId) { flash_set('hata', 'Araç seçilmedi.'); }
        else {
            $mevcutFoto = $duzenle['fotograflar'] ?? '';
            $fotoListe = $mevcutFoto ? json_decode($mevcutFoto, true) ?: [] : [];

            if (!empty($_FILES['fotograflar']['name'][0])) {
                foreach ($_FILES['fotograflar']['name'] as $i => $ad) {
                    if (!$ad) continue;
                    $tek = [
                        'name' => $_FILES['fotograflar']['name'][$i],
                        'type' => $_FILES['fotograflar']['type'][$i],
                        'tmp_name' => $_FILES['fotograflar']['tmp_name'][$i],
                        'error' => $_FILES['fotograflar']['error'][$i],
                        'size' => $_FILES['fotograflar']['size'][$i],
                    ];
                    $r = dosya_yukle($tek, 'hasar');
                    if ($r) $fotoListe[] = $r;
                }
            }

            $data = [
                'arac_id' => $aracId,
                'rezervasyon_id' => (int)($_POST['rezervasyon_id'] ?? 0) ?: null,
                'tarih' => $_POST['tarih'] ?: null,
                'aciklama' => trim($_POST['aciklama'] ?? ''),
                'tutar' => (float)($_POST['tutar'] ?? 0),
                'sigorta_kapsiyor' => !empty($_POST['sigorta_kapsiyor']) ? 1 : 0,
                'sigorta_dosya_no' => trim($_POST['sigorta_dosya_no'] ?? ''),
                'durum' => in_array($_POST['durum'] ?? '', ['acik','islemde','kapali'], true) ? $_POST['durum'] : 'acik',
                'fotograflar' => $fotoListe ? json_encode($fotoListe) : null,
            ];
            if ($id) { DB::guncelle('hasarlar', $data, 'id = ?', [$id]); admin_log('Hasar guncelle', 'ID ' . $id); }
            else { DB::ekle('hasarlar', $data); admin_log('Hasar olustur', 'Arac ' . $aracId); }
            flash_set('basari', 'Kaydedildi.');
        }
        yonlendir(admin_url('hasarlar.php'));
    }
}
if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $h = DB::tek("SELECT fotograflar FROM " . DB::tablo('hasarlar') . " WHERE id = ?", [(int)$_GET['id']]);
    if ($h && $h['fotograflar']) {
        foreach (json_decode($h['fotograflar'], true) ?: [] as $f) dosya_sil($f);
    }
    DB::sil('hasarlar', 'id = ?', [(int)$_GET['id']]);
    admin_log('Hasar sil', 'ID ' . (int)$_GET['id']);
    flash_set('basari', 'Silindi.');
    yonlendir(admin_url('hasarlar.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) $duzenle = DB::tek("SELECT * FROM " . DB::tablo('hasarlar') . " WHERE id = ?", [(int)$_GET['duzenle']]);

$liste = DB::liste(
    "SELECT h.*, a.plaka, a.marka, a.model, r.rezervasyon_no FROM " . DB::tablo('hasarlar') . " h
     LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = h.arac_id
     LEFT JOIN " . DB::tablo('rezervasyonlar') . " r ON r.id = h.rezervasyon_id
     ORDER BY h.tarih DESC LIMIT 100"
);
$araclar = DB::liste("SELECT id, plaka, marka, model FROM " . DB::tablo('araclar') . " ORDER BY plaka");
require __DIR__ . '/_layout_basla.php';
?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Hasarlar (<?= count($liste) ?>)</h2></div>
      <div class="kart-icerik">
        <div class="tablo-kapsayici">
          <table class="tablo">
            <thead><tr><th>Araç</th><th>Tarih</th><th>Açıklama</th><th>Rez.</th><th>Tutar</th><th>Durum</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($liste as $h): ?>
              <tr>
                <td><strong><?= e($h['plaka']) ?></strong><br><small><?= e($h['marka'] . ' ' . $h['model']) ?></small></td>
                <td><?= tarih_tr($h['tarih']) ?></td>
                <td><?= e(kisalt($h['aciklama'], 80)) ?></td>
                <td><?= e($h['rezervasyon_no'] ?? '-') ?></td>
                <td><?= tl((float)$h['tutar']) ?>
                  <?php if ($h['sigorta_kapsiyor']): ?><br><small style="color:var(--renk-basari);">Sigorta</small><?php endif; ?>
                </td>
                <td><span class="rozet-tip rozet-<?= $h['durum'] === 'kapali' ? 'aktif' : ($h['durum'] === 'islemde' ? 'beklemede' : 'iptal') ?>"><?= e(ucfirst($h['durum'])) ?></span></td>
                <td>
                  <div class="islemler">
                    <a href="<?= admin_url('hasarlar.php?duzenle=' . (int)$h['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('hasarlar.php?islem=sil&id=' . (int)$h['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silmek istiyor musunuz?">Sil</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$liste): ?><tr><td colspan="7" class="bos-durum">Hasar kaydi yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Duzenle' : 'Yeni Hasar' ?></h2></div>
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
        <div class="form-grup"><label>Rezervasyon ID (opsiyonel)</label><input type="number" name="rezervasyon_id" value="<?= e($duzenle['rezervasyon_id'] ?? '') ?>"></div>
        <div class="form-grup"><label>Tarih</label><input type="date" name="tarih" value="<?= e($duzenle['tarih'] ?? date('Y-m-d')) ?>"></div>
        <div class="form-grup"><label>Açıklama</label><textarea name="aciklama" rows="3"><?= e($duzenle['aciklama'] ?? '') ?></textarea></div>
        <div class="form-satir">
          <div class="form-grup"><label>Tutar</label><input type="number" step="0.01" name="tutar" value="<?= e($duzenle['tutar'] ?? '0') ?>"></div>
          <div class="form-grup">
            <label>Durum</label>
            <select name="durum">
              <option value="acik" <?= ($duzenle['durum'] ?? 'acik') === 'acik' ? 'selected' : '' ?>>Acik</option>
              <option value="islemde" <?= ($duzenle['durum'] ?? '') === 'islemde' ? 'selected' : '' ?>>Islemde</option>
              <option value="kapali" <?= ($duzenle['durum'] ?? '') === 'kapali' ? 'selected' : '' ?>>Kapali</option>
            </select>
          </div>
        </div>
        <label style="display:flex; gap:8px;"><input type="checkbox" name="sigorta_kapsiyor" value="1" <?= !empty($duzenle['sigorta_kapsiyor']) ? 'checked' : '' ?>> Sigorta kapsiyor</label>
        <div class="form-grup" style="margin-top:8px;"><label>Sigorta Dosya No</label><input type="text" name="sigorta_dosya_no" value="<?= e($duzenle['sigorta_dosya_no'] ?? '') ?>"></div>

        <div class="form-grup">
          <label>Fotograflar (coklu yukleme)</label>
          <input type="file" name="fotograflar[]" multiple accept="image/*">
          <?php if (!empty($duzenle['fotograflar'])): ?>
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">
              <?php foreach (json_decode($duzenle['fotograflar'], true) ?: [] as $f): ?>
                <a href="<?= e(upload_url($f)) ?>" target="_blank"><img src="<?= e(upload_url($f)) ?>" style="width:80px; height:80px; object-fit:cover; border-radius:6px;"></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <div style="margin-top:16px;"><button class="btn btn-birincil">Kaydet</button>
          <?php if ($duzenle): ?><a href="<?= admin_url('hasarlar.php') ?>" class="btn btn-cerceve">İptal</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
