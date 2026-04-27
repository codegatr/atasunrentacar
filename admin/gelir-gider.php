<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Gelir / Gider';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $islem = $_POST['islem'] ?? '';
    if ($islem === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $tip = ($_POST['tip'] ?? 'gider') === 'gelir' ? 'gelir' : 'gider';
        $kategori = trim($_POST['kategori'] ?? '');
        $aciklama = trim($_POST['aciklama'] ?? '');
        $tutar = (float)str_replace([',', ' '], ['.', ''], $_POST['tutar'] ?? '0');
        $tarih = $_POST['tarih'] ?? date('Y-m-d');
        $aracId = !empty($_POST['arac_id']) ? (int)$_POST['arac_id'] : null;
        $rezId = !empty($_POST['rezervasyon_id']) ? (int)$_POST['rezervasyon_id'] : null;

        if (!$kategori || $tutar <= 0) {
            flash_set('hata', 'Kategori ve tutar zorunlu.');
        } else {
            $data = [
                'tip' => $tip,
                'kategori' => $kategori,
                'aciklama' => $aciklama,
                'tutar' => $tutar,
                'tarih' => $tarih,
                'arac_id' => $aracId,
                'rezervasyon_id' => $rezId,
            ];
            if ($id) {
                DB::guncelle('gelir_gider', $data, 'id = ?', [$id]);
                admin_log('Gelir-Gider guncelle', 'ID ' . $id);
            } else {
                DB::ekle('gelir_gider', $data);
                admin_log('Gelir-Gider ekle', $tip . ' ' . $tutar);
            }
            flash_set('basari', 'Kayıt tamamlandı.');
        }
        yonlendir(admin_url('gelir-gider.php'));
    }
}

if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    DB::sil('gelir_gider', 'id = ?', [$id]);
    admin_log('Gelir-Gider sil', 'ID ' . $id);
    flash_set('basari', 'Silindi.');
    yonlendir(admin_url('gelir-gider.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) {
    $duzenle = DB::tek("SELECT * FROM " . DB::tablo('gelir_gider') . " WHERE id = ?", [(int)$_GET['duzenle']]);
}

// Filtreler
$tipFiltre = $_GET['tip'] ?? '';
$ay = $_GET['ay'] ?? date('Y-m');
$kosullar = [];
$params = [];
if (in_array($tipFiltre, ['gelir', 'gider'], true)) {
    $kosullar[] = 'tip = ?';
    $params[] = $tipFiltre;
}
if ($ay && preg_match('/^\d{4}-\d{2}$/', $ay)) {
    $kosullar[] = 'DATE_FORMAT(tarih, "%Y-%m") = ?';
    $params[] = $ay;
}
$where = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';

$kayitlar = DB::liste("SELECT g.*, a.marka, a.model, a.plaka FROM " . DB::tablo('gelir_gider') . " g LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = g.arac_id $where ORDER BY g.tarih DESC, g.id DESC LIMIT 500", $params);

$ozet = DB::tek("SELECT
    SUM(CASE WHEN tip='gelir' THEN tutar ELSE 0 END) toplam_gelir,
    SUM(CASE WHEN tip='gider' THEN tutar ELSE 0 END) toplam_gider
    FROM " . DB::tablo('gelir_gider') . " $where", $params);
$gelir = (float)($ozet['toplam_gelir'] ?? 0);
$gider = (float)($ozet['toplam_gider'] ?? 0);
$kar = $gelir - $gider;

$araclar = DB::liste("SELECT id, marka, model, plaka FROM " . DB::tablo('araclar') . " ORDER BY marka, model");

require __DIR__ . '/_layout_basla.php';
?>

<div class="ist-grid">
  <div class="ist-kart basari">
    <div class="ist-baslik">Toplam Gelir</div>
    <div class="ist-deger"><?= tl($gelir) ?></div>
  </div>
  <div class="ist-kart hata">
    <div class="ist-baslik">Toplam Gider</div>
    <div class="ist-deger"><?= tl($gider) ?></div>
  </div>
  <div class="ist-kart <?= $kar >= 0 ? 'bilgi' : 'uyari' ?>">
    <div class="ist-baslik">Net Kâr</div>
    <div class="ist-deger"><?= tl($kar) ?></div>
  </div>
  <div class="ist-kart">
    <div class="ist-baslik">Kayıt Sayısı</div>
    <div class="ist-deger"><?= count($kayitlar) ?></div>
  </div>
</div>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Kayıtlar</h2></div>
      <div class="kart-icerik">
        <form method="get" class="filtre">
          <select name="tip">
            <option value="">Hepsi</option>
            <option value="gelir" <?= $tipFiltre === 'gelir' ? 'selected' : '' ?>>Gelir</option>
            <option value="gider" <?= $tipFiltre === 'gider' ? 'selected' : '' ?>>Gider</option>
          </select>
          <input type="month" name="ay" value="<?= e($ay) ?>">
          <button class="btn btn-cerceve">Filtrele</button>
        </form>

        <?php if (!$kayitlar): ?>
          <div class="bos-durum">Kayit yok.</div>
        <?php else: ?>
        <div class="tablo-kapsayici">
          <table class="tablo">
            <thead><tr><th>Tarih</th><th>Tip</th><th>Kategori</th><th>Araç</th><th>Tutar</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($kayitlar as $k): ?>
              <tr>
                <td><?= tarih_tr($k['tarih']) ?></td>
                <td><span class="rozet-tip rozet-<?= $k['tip'] === 'gelir' ? 'aktif' : 'iptal' ?>"><?= ucfirst($k['tip']) ?></span></td>
                <td><?= e($k['kategori']) ?><?php if ($k['aciklama']): ?><br><small><?= e(kisalt($k['aciklama'], 60)) ?></small><?php endif; ?></td>
                <td><?= $k['marka'] ? e($k['marka'] . ' ' . $k['model']) . '<br><small>' . e($k['plaka']) . '</small>' : '-' ?></td>
                <td><strong style="color:<?= $k['tip'] === 'gelir' ? '#15803d' : '#b91c1c' ?>"><?= tl($k['tutar']) ?></strong></td>
                <td>
                  <div class="islemler">
                    <a href="<?= admin_url('gelir-gider.php?duzenle=' . (int)$k['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('gelir-gider.php?islem=sil&id=' . (int)$k['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silinsin mi?">Sil</a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Kaydi Duzenle' : 'Yeni Kayit' ?></h2></div>
      <div class="kart-icerik">
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="islem" value="kaydet">
          <input type="hidden" name="id" value="<?= (int)($duzenle['id'] ?? 0) ?>">

          <div class="form-grup">
            <label>Tip</label>
            <select name="tip" required>
              <option value="gider" <?= ($duzenle['tip'] ?? '') === 'gider' ? 'selected' : '' ?>>Gider</option>
              <option value="gelir" <?= ($duzenle['tip'] ?? '') === 'gelir' ? 'selected' : '' ?>>Gelir</option>
            </select>
          </div>

          <div class="form-grup">
            <label>Kategori *</label>
            <input type="text" name="kategori" value="<?= e($duzenle['kategori'] ?? '') ?>" placeholder="Yakit, Bakim, Kira Geliri..." required>
          </div>

          <div class="form-satir">
            <div class="form-grup">
              <label>Tutar (TL) *</label>
              <input type="number" step="0.01" name="tutar" value="<?= e($duzenle['tutar'] ?? '') ?>" required>
            </div>
            <div class="form-grup">
              <label>Tarih *</label>
              <input type="date" name="tarih" value="<?= e($duzenle['tarih'] ?? date('Y-m-d')) ?>" required>
            </div>
          </div>

          <div class="form-grup">
            <label>Arac (opsiyonel)</label>
            <select name="arac_id">
              <option value="">- Yok -</option>
              <?php foreach ($araclar as $a): ?>
                <option value="<?= (int)$a['id'] ?>" <?= ($duzenle['arac_id'] ?? '') == $a['id'] ? 'selected' : '' ?>>
                  <?= e($a['marka'] . ' ' . $a['model'] . ' - ' . $a['plaka']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-grup">
            <label>Açıklama</label>
            <textarea name="aciklama" rows="3"><?= e($duzenle['aciklama'] ?? '') ?></textarea>
          </div>

          <button class="btn btn-birincil btn-blok"><?= $duzenle ? 'Guncelle' : 'Kaydet' ?></button>
          <?php if ($duzenle): ?>
            <a href="<?= admin_url('gelir-gider.php') ?>" class="btn btn-cerceve btn-blok">İptal</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
