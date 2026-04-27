<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Kategoriler';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $islem = $_POST['islem'] ?? '';
    if ($islem === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $ad = trim($_POST['ad'] ?? '');
        $slug = slug_olustur(trim($_POST['slug'] ?? '') ?: $ad);
        if (!$ad) {
            flash_set('hata', 'Ad zorunlu.');
        } else {
            $data = [
                'ad' => $ad,
                'slug' => $slug,
                'aciklama' => trim($_POST['aciklama'] ?? ''),
                'sira' => (int)($_POST['sira'] ?? 0),
                'aktif' => !empty($_POST['aktif']) ? 1 : 0,
            ];
            if ($id) {
                DB::guncelle('kategoriler', $data, 'id = ?', [$id]);
                admin_log('Kategori guncelle', 'ID ' . $id);
            } else {
                DB::ekle('kategoriler', $data);
                admin_log('Kategori olustur', $ad);
            }
            flash_set('basari', 'Kategori kaydedildi.');
        }
        yonlendir(admin_url('kategoriler.php'));
    }
}

if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $sayi = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE kategori_id = ?", [$id])['c'] ?? 0);
    if ($sayi > 0) {
        flash_set('hata', 'Bu kategoriye ait araç var, silinemez.');
    } else {
        DB::sil('kategoriler', 'id = ?', [$id]);
        admin_log('Kategori sil', 'ID ' . $id);
        flash_set('basari', 'Silindi.');
    }
    yonlendir(admin_url('kategoriler.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) {
    $duzenle = DB::tek("SELECT * FROM " . DB::tablo('kategoriler') . " WHERE id = ?", [(int)$_GET['duzenle']]);
}

$kategoriler = DB::liste("SELECT k.*, (SELECT COUNT(*) FROM " . DB::tablo('araclar') . " a WHERE a.kategori_id = k.id) arac_sayisi FROM " . DB::tablo('kategoriler') . " k ORDER BY k.sira, k.ad");

require __DIR__ . '/_layout_basla.php';
?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Kategoriler (<?= count($kategoriler) ?>)</h2></div>
      <div class="kart-icerik">
        <div class="tablo-kapsayici">
          <table class="tablo">
            <thead><tr><th>Sıra</th><th>Ad</th><th>Slug</th><th>Araç</th><th>Durum</th><th>İşlemler</th></tr></thead>
            <tbody>
            <?php foreach ($kategoriler as $k): ?>
              <tr>
                <td><?= (int)$k['sira'] ?></td>
                <td><strong><?= e($k['ad']) ?></strong></td>
                <td><?= e($k['slug']) ?></td>
                <td><?= (int)$k['arac_sayisi'] ?></td>
                <td><span class="rozet-tip rozet-<?= $k['aktif'] ? 'aktif' : 'pasif' ?>"><?= $k['aktif'] ? 'Aktif' : 'Pasif' ?></span></td>
                <td>
                  <div class="islemler">
                    <a href="<?= admin_url('kategoriler.php?duzenle=' . (int)$k['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('kategoriler.php?islem=sil&id=' . (int)$k['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silmek istiyor musunuz?">Sil</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$kategoriler): ?><tr><td colspan="6" class="bos-durum">Kategori yok.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Duzenle' : 'Yeni Kategori' ?></h2></div>
      <form method="post" class="kart-icerik">
        <?= csrf_input() ?>
        <input type="hidden" name="islem" value="kaydet">
        <input type="hidden" name="id" value="<?= (int)($duzenle['id'] ?? 0) ?>">
        <div class="form-grup"><label>Ad *</label><input type="text" name="ad" value="<?= e($duzenle['ad'] ?? '') ?>" required></div>
        <div class="form-grup"><label>Slug (bos = otomatik)</label><input type="text" name="slug" value="<?= e($duzenle['slug'] ?? '') ?>"></div>
        <div class="form-grup"><label>Açıklama</label><textarea name="aciklama" rows="3"><?= e($duzenle['aciklama'] ?? '') ?></textarea></div>
        <div class="form-grup"><label>Sıra</label><input type="number" name="sira" value="<?= e($duzenle['sira'] ?? '0') ?>"></div>
        <label style="display:flex; gap:8px;"><input type="checkbox" name="aktif" value="1" <?= !isset($duzenle) || !empty($duzenle['aktif']) ? 'checked' : '' ?>> Aktif</label>
        <div style="margin-top:16px; display:flex; gap:8px;">
          <button class="btn btn-birincil">Kaydet</button>
          <?php if ($duzenle): ?><a href="<?= admin_url('kategoriler.php') ?>" class="btn btn-cerceve">İptal</a><?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
