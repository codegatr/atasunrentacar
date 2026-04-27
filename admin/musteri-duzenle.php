<?php
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$m = null;
if ($id) {
    $m = DB::tek("SELECT * FROM " . DB::tablo('musteriler') . " WHERE id = ?", [$id]);
    if (!$m) { flash_set('hata', 'Musteri bulunamadi.'); yonlendir(admin_url('musteriler.php')); }
}
$pageTitle = $m ? 'Musteri: ' . $m['ad'] . ' ' . $m['soyad'] : 'Yeni Musteri';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $ad = trim($_POST['ad'] ?? '');
    $soyad = trim($_POST['soyad'] ?? '');
    if (!$ad || !$soyad) {
        flash_set('hata', 'Ad ve soyad zorunlu.');
    } else {
        $data = [
            'ad' => $ad,
            'soyad' => $soyad,
            'tc_no' => trim($_POST['tc_no'] ?? ''),
            'pasaport_no' => trim($_POST['pasaport_no'] ?? ''),
            'telefon' => trim($_POST['telefon'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'dogum_tarihi' => $_POST['dogum_tarihi'] ?: null,
            'ehliyet_no' => trim($_POST['ehliyet_no'] ?? ''),
            'ehliyet_sinifi' => trim($_POST['ehliyet_sinifi'] ?? ''),
            'ehliyet_tarihi' => $_POST['ehliyet_tarihi'] ?: null,
            'adres' => trim($_POST['adres'] ?? ''),
            'sehir' => trim($_POST['sehir'] ?? ''),
            'kara_liste' => !empty($_POST['kara_liste']) ? 1 : 0,
            'kara_liste_sebebi' => trim($_POST['kara_liste_sebebi'] ?? ''),
            'notlar' => trim($_POST['notlar'] ?? ''),
        ];

        if ($id) {
            DB::guncelle('musteriler', $data, 'id = ?', [$id]);
            admin_log('Musteri guncelle', 'ID ' . $id);
        } else {
            $data['olusturma'] = date('Y-m-d H:i:s');
            $id = DB::ekle('musteriler', $data);
            admin_log('Musteri olustur', 'ID ' . $id);
        }
        flash_set('basari', 'Musteri kaydedildi.');
        yonlendir(admin_url('musteri-duzenle.php?id=' . $id));
    }
}

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2><?= $m ? 'Musteri Duzenle' : 'Yeni Musteri' ?></h2>
    <a href="<?= admin_url('musteriler.php') ?>" class="btn btn-cerceve">← Listeye Don</a>
  </div>

  <form method="post" class="kart-icerik">
    <?= csrf_input() ?>

    <div class="iki-sutun">
      <div>
        <h3>Kisisel Bilgiler</h3>
        <div class="form-satir">
          <div class="form-grup">
            <label>Ad *</label>
            <input type="text" name="ad" value="<?= e($m['ad'] ?? '') ?>" required>
          </div>
          <div class="form-grup">
            <label>Soyad *</label>
            <input type="text" name="soyad" value="<?= e($m['soyad'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-satir">
          <div class="form-grup">
            <label>TC Kimlik No</label>
            <input type="text" name="tc_no" value="<?= e($m['tc_no'] ?? '') ?>" maxlength="11">
          </div>
          <div class="form-grup">
            <label>Pasaport No</label>
            <input type="text" name="pasaport_no" value="<?= e($m['pasaport_no'] ?? '') ?>">
          </div>
        </div>
        <div class="form-grup">
          <label>Dogum Tarihi</label>
          <input type="date" name="dogum_tarihi" value="<?= e($m['dogum_tarihi'] ?? '') ?>">
        </div>

        <h3 style="margin-top:24px;">Iletisim</h3>
        <div class="form-grup">
          <label>Telefon</label>
          <input type="text" name="telefon" value="<?= e($m['telefon'] ?? '') ?>">
        </div>
        <div class="form-grup">
          <label>E-posta</label>
          <input type="email" name="email" value="<?= e($m['email'] ?? '') ?>">
        </div>
        <div class="form-grup">
          <label>Şehir</label>
          <input type="text" name="sehir" value="<?= e($m['sehir'] ?? '') ?>">
        </div>
        <div class="form-grup">
          <label>Adres</label>
          <textarea name="adres" rows="2"><?= e($m['adres'] ?? '') ?></textarea>
        </div>
      </div>

      <div>
        <h3>Ehliyet</h3>
        <div class="form-grup">
          <label>Ehliyet No</label>
          <input type="text" name="ehliyet_no" value="<?= e($m['ehliyet_no'] ?? '') ?>">
        </div>
        <div class="form-satir">
          <div class="form-grup">
            <label>Sinif</label>
            <input type="text" name="ehliyet_sinifi" value="<?= e($m['ehliyet_sinifi'] ?? 'B') ?>">
          </div>
          <div class="form-grup">
            <label>Veriliş Tarihi</label>
            <input type="date" name="ehliyet_tarihi" value="<?= e($m['ehliyet_tarihi'] ?? '') ?>">
          </div>
        </div>

        <h3 style="margin-top:24px;">Durum</h3>
        <label style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="kara_liste" value="1" <?= !empty($m['kara_liste']) ? 'checked' : '' ?>>
          Kara listede
        </label>
        <div class="form-grup" style="margin-top:8px;">
          <label>Kara Liste Sebebi</label>
          <textarea name="kara_liste_sebebi" rows="2"><?= e($m['kara_liste_sebebi'] ?? '') ?></textarea>
        </div>

        <div class="form-grup">
          <label>Notlar</label>
          <textarea name="notlar" rows="4"><?= e($m['notlar'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <div style="display:flex; gap:8px; margin-top:16px;">
      <button class="btn btn-birincil">Kaydet</button>
      <a href="<?= admin_url('musteriler.php') ?>" class="btn btn-cerceve">İptal</a>
    </div>
  </form>
</div>

<?php if ($m):
  $rezler = DB::liste("SELECT r.*, a.plaka, a.marka, a.model FROM " . DB::tablo('rezervasyonlar') . " r LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = r.arac_id WHERE r.musteri_id = ? ORDER BY r.olusturma DESC LIMIT 20", [$id]);
?>
<div class="kart" style="margin-top:24px;">
  <div class="kart-baslik"><h2>Rezervasyon Gecmisi (<?= count($rezler) ?>)</h2></div>
  <div class="kart-icerik">
    <?php if ($rezler): ?>
    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead><tr><th>No</th><th>Araç</th><th>Tarih</th><th>Tutar</th><th>Durum</th></tr></thead>
        <tbody>
        <?php foreach ($rezler as $r): ?>
          <tr>
            <td><a href="<?= admin_url('rezervasyon-duzenle.php?id=' . (int)$r['id']) ?>"><?= e($r['rezervasyon_no']) ?></a></td>
            <td><?= e(($r['marka'] ?? '') . ' ' . ($r['model'] ?? '')) ?></td>
            <td><?= tarih_tr($r['alis_tarihi']) ?> - <?= tarih_tr($r['iade_tarihi']) ?></td>
            <td><?= tl((float)$r['toplam_tutar']) ?></td>
            <td><span class="rozet-tip rozet-<?= e($r['durum']) ?>"><?= e(ucfirst($r['durum'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <div class="bos-durum">Henuz rezervasyon yok.</div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
