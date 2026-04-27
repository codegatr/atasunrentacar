<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Ek Hizmetler';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    if (($_POST['islem'] ?? '') === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $ad = trim($_POST['ad'] ?? '');
        if (!$ad) { flash_set('hata', 'Ad zorunlu.'); }
        else {
            $data = [
                'ad' => $ad,
                'aciklama' => trim($_POST['aciklama'] ?? ''),
                'fiyat' => (float)($_POST['fiyat'] ?? 0),
                'fiyat_tipi' => in_array($_POST['fiyat_tipi'] ?? '', ['gunluk','tek'], true) ? $_POST['fiyat_tipi'] : 'gunluk',
                'sira' => (int)($_POST['sira'] ?? 0),
                'aktif' => !empty($_POST['aktif']) ? 1 : 0,
            ];
            if ($id) { DB::guncelle('ek_hizmetler', $data, 'id = ?', [$id]); admin_log('Ek hizmet guncelle', 'ID ' . $id); }
            else { DB::ekle('ek_hizmetler', $data); admin_log('Ek hizmet olustur', $ad); }
            flash_set('basari', 'Kaydedildi.');
        }
        yonlendir(admin_url('ek-hizmetler.php'));
    }
}

if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    DB::sil('ek_hizmetler', 'id = ?', [(int)$_GET['id']]);
    admin_log('Ek hizmet sil', 'ID ' . (int)$_GET['id']);
    flash_set('basari', 'Silindi.');
    yonlendir(admin_url('ek-hizmetler.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) {
    $duzenle = DB::tek("SELECT * FROM " . DB::tablo('ek_hizmetler') . " WHERE id = ?", [(int)$_GET['duzenle']]);
}

$liste = DB::liste("SELECT * FROM " . DB::tablo('ek_hizmetler') . " ORDER BY sira, ad");
require __DIR__ . '/_layout_basla.php';
?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Ek Hizmetler (<?= count($liste) ?>)</h2></div>
      <div class="kart-icerik">
        <div class="tablo-kapsayici">
          <table class="tablo">
            <thead><tr><th>Ad</th><th>Fiyat</th><th>Tip</th><th>Durum</th><th>İşlemler</th></tr></thead>
            <tbody>
            <?php foreach ($liste as $h): ?>
              <tr>
                <td><strong><?= e($h['ad']) ?></strong>
                  <?php if ($h['aciklama']): ?><small style="display:block; color:var(--renk-yazi-acik);"><?= e($h['aciklama']) ?></small><?php endif; ?>
                </td>
                <td><?= tl((float)$h['fiyat']) ?></td>
                <td><?= $h['fiyat_tipi'] === 'gunluk' ? 'Gunluk' : 'Tek seferlik' ?></td>
                <td><span class="rozet-tip rozet-<?= $h['aktif'] ? 'aktif' : 'pasif' ?>"><?= $h['aktif'] ? 'Aktif' : 'Pasif' ?></span></td>
                <td>
                  <div class="islemler">
                    <a href="<?= admin_url('ek-hizmetler.php?duzenle=' . (int)$h['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('ek-hizmetler.php?islem=sil&id=' . (int)$h['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silmek istiyor musunuz?">Sil</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$liste): ?><tr><td colspan="5" class="bos-durum">Hizmet yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Duzenle' : 'Yeni Hizmet' ?></h2></div>
      <form method="post" class="kart-icerik">
        <?= csrf_input() ?>
        <input type="hidden" name="islem" value="kaydet">
        <input type="hidden" name="id" value="<?= (int)($duzenle['id'] ?? 0) ?>">
        <div class="form-grup"><label>Ad *</label><input type="text" name="ad" value="<?= e($duzenle['ad'] ?? '') ?>" required></div>
        <div class="form-grup"><label>Açıklama</label><textarea name="aciklama" rows="2"><?= e($duzenle['aciklama'] ?? '') ?></textarea></div>
        <div class="form-satir">
          <div class="form-grup"><label>Fiyat *</label><input type="number" step="0.01" name="fiyat" value="<?= e($duzenle['fiyat'] ?? '0') ?>" required></div>
          <div class="form-grup">
            <label>Fiyat Tipi</label>
            <select name="fiyat_tipi">
              <option value="gunluk" <?= ($duzenle['fiyat_tipi'] ?? 'gunluk') === 'gunluk' ? 'selected' : '' ?>>Gunluk</option>
              <option value="tek" <?= ($duzenle['fiyat_tipi'] ?? '') === 'tek' ? 'selected' : '' ?>>Tek seferlik</option>
            </select>
          </div>
        </div>
        <div class="form-grup"><label>Sıra</label><input type="number" name="sira" value="<?= e($duzenle['sira'] ?? '0') ?>"></div>
        <label style="display:flex; gap:8px;"><input type="checkbox" name="aktif" value="1" <?= !isset($duzenle) || !empty($duzenle['aktif']) ? 'checked' : '' ?>> Aktif</label>
        <div style="margin-top:16px;"><button class="btn btn-birincil">Kaydet</button>
          <?php if ($duzenle): ?><a href="<?= admin_url('ek-hizmetler.php') ?>" class="btn btn-cerceve">İptal</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
