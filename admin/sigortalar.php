<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Sigortalar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    if (($_POST['islem'] ?? '') === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $aracId = (int)($_POST['arac_id'] ?? 0);
        if (!$aracId) { flash_set('hata', 'Araç seçilmedi.'); }
        else {
            $data = [
                'arac_id' => $aracId,
                'tip' => in_array($_POST['tip'] ?? '', ['trafik','kasko','imm'], true) ? $_POST['tip'] : 'trafik',
                'sirket' => trim($_POST['sirket'] ?? ''),
                'police_no' => trim($_POST['police_no'] ?? ''),
                'baslangic_tarihi' => $_POST['baslangic_tarihi'] ?: null,
                'bitis_tarihi' => $_POST['bitis_tarihi'] ?: null,
                'tutar' => (float)($_POST['tutar'] ?? 0),
                'notlar' => trim($_POST['notlar'] ?? ''),
            ];
            if (!empty($_FILES['dosya']['name'])) {
                $r = dosya_yukle($_FILES['dosya'], 'sigorta');
                if ($r) $data['dosya'] = $r;
            }
            if ($id) { DB::guncelle('sigortalar', $data, 'id = ?', [$id]); admin_log('Sigorta guncelle', 'ID ' . $id); }
            else { DB::ekle('sigortalar', $data); admin_log('Sigorta olustur', 'Arac ' . $aracId); }
            flash_set('basari', 'Kaydedildi.');
        }
        yonlendir(admin_url('sigortalar.php'));
    }
}
if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $s = DB::tek("SELECT dosya FROM " . DB::tablo('sigortalar') . " WHERE id = ?", [(int)$_GET['id']]);
    if ($s && $s['dosya']) dosya_sil($s['dosya']);
    DB::sil('sigortalar', 'id = ?', [(int)$_GET['id']]);
    admin_log('Sigorta sil', 'ID ' . (int)$_GET['id']);
    flash_set('basari', 'Silindi.');
    yonlendir(admin_url('sigortalar.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) {
    $duzenle = DB::tek("SELECT * FROM " . DB::tablo('sigortalar') . " WHERE id = ?", [(int)$_GET['duzenle']]);
}

$aracId = (int)($_GET['arac'] ?? 0);
$where = ['1=1']; $params = [];
if ($aracId) { $where[] = 's.arac_id = ?'; $params[] = $aracId; }
$whereSql = implode(' AND ', $where);

$liste = DB::liste(
    "SELECT s.*, a.plaka, a.marka, a.model
     FROM " . DB::tablo('sigortalar') . " s
     LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = s.arac_id
     WHERE $whereSql
     ORDER BY s.bitis_tarihi DESC",
    $params
);

$araclar = DB::liste("SELECT id, plaka, marka, model FROM " . DB::tablo('araclar') . " ORDER BY plaka");
require __DIR__ . '/_layout_basla.php';
?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Sigortalar (<?= count($liste) ?>)</h2></div>
      <div class="kart-icerik">
        <form class="filtre" method="get">
          <select name="arac">
            <option value="">Tum araclar</option>
            <?php foreach ($araclar as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $aracId == $a['id'] ? 'selected' : '' ?>><?= e($a['plaka'] . ' - ' . $a['marka'] . ' ' . $a['model']) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-cerceve">Filtrele</button>
        </form>

        <div class="tablo-kapsayici">
          <table class="tablo">
            <thead><tr><th>Araç</th><th>Tip</th><th>Sirket</th><th>Police</th><th>Bitis</th><th>Tutar</th><th>Dosya</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($liste as $s):
              $kalan = $s['bitis_tarihi'] ? (strtotime($s['bitis_tarihi']) - time()) / 86400 : null;
            ?>
              <tr>
                <td><strong><?= e($s['plaka']) ?></strong><br><small><?= e($s['marka'] . ' ' . $s['model']) ?></small></td>
                <td><span class="rozet-tip rozet-aktif"><?= e(strtoupper($s['tip'])) ?></span></td>
                <td><?= e($s['sirket']) ?></td>
                <td><?= e($s['police_no']) ?></td>
                <td>
                  <?= tarih_tr($s['bitis_tarihi']) ?>
                  <?php if ($kalan !== null && $kalan < 30 && $kalan > 0): ?>
                    <small style="display:block; color:var(--renk-uyari);"><?= (int)$kalan ?> gun</small>
                  <?php elseif ($kalan !== null && $kalan <= 0): ?>
                    <small style="display:block; color:var(--renk-hata);">Sona erdi</small>
                  <?php endif; ?>
                </td>
                <td><?= tl((float)$s['tutar']) ?></td>
                <td><?php if ($s['dosya']): ?><a href="<?= e(upload_url($s['dosya'])) ?>" target="_blank">📎</a><?php endif; ?></td>
                <td>
                  <div class="islemler">
                    <a href="<?= admin_url('sigortalar.php?duzenle=' . (int)$s['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('sigortalar.php?islem=sil&id=' . (int)$s['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silmek istiyor musunuz?">Sil</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$liste): ?><tr><td colspan="8" class="bos-durum">Sigorta kaydi yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Duzenle' : 'Yeni Sigorta' ?></h2></div>
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
        <div class="form-grup">
          <label>Tip</label>
          <select name="tip">
            <option value="trafik" <?= ($duzenle['tip'] ?? '') === 'trafik' ? 'selected' : '' ?>>Trafik</option>
            <option value="kasko" <?= ($duzenle['tip'] ?? '') === 'kasko' ? 'selected' : '' ?>>Kasko</option>
            <option value="imm" <?= ($duzenle['tip'] ?? '') === 'imm' ? 'selected' : '' ?>>IMM</option>
          </select>
        </div>
        <div class="form-grup"><label>Sirket</label><input type="text" name="sirket" value="<?= e($duzenle['sirket'] ?? '') ?>"></div>
        <div class="form-grup"><label>Police No</label><input type="text" name="police_no" value="<?= e($duzenle['police_no'] ?? '') ?>"></div>
        <div class="form-satir">
          <div class="form-grup"><label>Baslangic</label><input type="date" name="baslangic_tarihi" value="<?= e($duzenle['baslangic_tarihi'] ?? '') ?>"></div>
          <div class="form-grup"><label>Bitis</label><input type="date" name="bitis_tarihi" value="<?= e($duzenle['bitis_tarihi'] ?? '') ?>"></div>
        </div>
        <div class="form-grup"><label>Tutar</label><input type="number" step="0.01" name="tutar" value="<?= e($duzenle['tutar'] ?? '0') ?>"></div>
        <div class="form-grup">
          <label>Police Dosyasi (PDF)</label>
          <input type="file" name="dosya" accept="application/pdf,image/*">
          <?php if (!empty($duzenle['dosya'])): ?><small><a href="<?= e(upload_url($duzenle['dosya'])) ?>" target="_blank">Mevcut dosyayi gor</a></small><?php endif; ?>
        </div>
        <div class="form-grup"><label>Notlar</label><textarea name="notlar" rows="2"><?= e($duzenle['notlar'] ?? '') ?></textarea></div>
        <div style="margin-top:16px;"><button class="btn btn-birincil">Kaydet</button>
          <?php if ($duzenle): ?><a href="<?= admin_url('sigortalar.php') ?>" class="btn btn-cerceve">İptal</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
