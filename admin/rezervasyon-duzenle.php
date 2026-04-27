<?php
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$rez = null;
$ekHizmetSecili = [];

if ($id) {
    $rez = DB::tek("SELECT * FROM " . DB::tablo('rezervasyonlar') . " WHERE id = ?", [$id]);
    if (!$rez) { flash_set('hata', 'Rezervasyon bulunamadi.'); yonlendir(admin_url('rezervasyonlar.php')); }
    $ekHizmetSecili = array_column(
        DB::liste("SELECT ek_hizmet_id FROM " . DB::tablo('rez_ek_hizmet') . " WHERE rezervasyon_id = ?", [$id]),
        'ek_hizmet_id'
    );
}
$pageTitle = $rez ? 'Rezervasyon: ' . $rez['rezervasyon_no'] : 'Yeni Rezervasyon';

$araclar = DB::liste("SELECT id, plaka, marka, model, gunluk_fiyat, haftalik_fiyat, aylik_fiyat FROM " . DB::tablo('araclar') . " WHERE aktif = 1 ORDER BY marka, model");
$musteriler = DB::liste("SELECT id, ad, soyad, telefon FROM " . DB::tablo('musteriler') . " ORDER BY ad");
$ekHizmetler = DB::liste("SELECT * FROM " . DB::tablo('ek_hizmetler') . " WHERE aktif = 1 ORDER BY ad");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();

    $alisTarihi = $_POST['alis_tarihi'] ?? '';
    $iadeTarihi = $_POST['iade_tarihi'] ?? '';
    $aracId = (int)($_POST['arac_id'] ?? 0);

    if (!$alisTarihi || !$iadeTarihi || !$aracId) {
        flash_set('hata', 'Arac, alis ve iade tarihi zorunludur.');
    } else {
        $gun = gun_farki($alisTarihi, $iadeTarihi);
        $arac = DB::tek("SELECT * FROM " . DB::tablo('araclar') . " WHERE id = ?", [$aracId]);
        $gunlukFiyat = (float)($_POST['gunluk_fiyat'] ?? $arac['gunluk_fiyat']);

        // Otomatik fiyat hesabi (haftalik / aylik kademesi)
        if ($gun >= 30 && $arac['aylik_fiyat'] > 0) {
            $gunlukFiyat = (float)$arac['aylik_fiyat'] / 30;
        } elseif ($gun >= 7 && $arac['haftalik_fiyat'] > 0) {
            $gunlukFiyat = (float)$arac['haftalik_fiyat'] / 7;
        }
        if (!empty($_POST['gunluk_fiyat_manuel']) && (float)$_POST['gunluk_fiyat_manuel'] > 0) {
            $gunlukFiyat = (float)$_POST['gunluk_fiyat_manuel'];
        }

        $aracTutar = $gunlukFiyat * $gun;

        // Ek hizmet hesabi
        $ekTutar = 0;
        $ekIds = array_map('intval', $_POST['ek_hizmetler'] ?? []);
        $ekDetay = [];
        foreach ($ekIds as $ehId) {
            foreach ($ekHizmetler as $eh) {
                if ((int)$eh['id'] === $ehId) {
                    $tutar = (float)$eh['fiyat'];
                    if ($eh['fiyat_tipi'] === 'gunluk') $tutar *= $gun;
                    $ekTutar += $tutar;
                    $ekDetay[] = ['id' => $ehId, 'tutar' => $tutar];
                }
            }
        }

        $indirim = (float)($_POST['indirim_tutar'] ?? 0);
        $kdvOrani = (float)($_POST['kdv_orani'] ?? ayar('kdv_orani', '20'));
        $araToplam = $aracTutar + $ekTutar - $indirim;
        $kdvTutar = $araToplam * ($kdvOrani / (100 + $kdvOrani));
        $toplam = $araToplam;

        $data = [
            'arac_id' => $aracId,
            'musteri_id' => (int)($_POST['musteri_id'] ?? 0) ?: null,
            'misafir_ad' => trim($_POST['misafir_ad'] ?? ''),
            'misafir_soyad' => trim($_POST['misafir_soyad'] ?? ''),
            'misafir_telefon' => trim($_POST['misafir_telefon'] ?? ''),
            'misafir_email' => trim($_POST['misafir_email'] ?? ''),
            'misafir_tc' => trim($_POST['misafir_tc'] ?? ''),
            'alis_tarihi' => $alisTarihi,
            'iade_tarihi' => $iadeTarihi,
            'alis_yeri' => trim($_POST['alis_yeri'] ?? ''),
            'iade_yeri' => trim($_POST['iade_yeri'] ?? ''),
            'gunluk_fiyat' => round($gunlukFiyat, 2),
            'toplam_gun' => $gun,
            'arac_tutar' => round($aracTutar, 2),
            'ek_hizmet_tutar' => round($ekTutar, 2),
            'indirim_tutar' => $indirim,
            'kdv_orani' => $kdvOrani,
            'kdv_tutar' => round($kdvTutar, 2),
            'toplam_tutar' => round($toplam, 2),
            'depozito_tutar' => (float)($_POST['depozito_tutar'] ?? 0),
            'durum' => $_POST['durum'] ?? 'beklemede',
            'odeme_durumu' => $_POST['odeme_durumu'] ?? 'beklemede',
            'odeme_yontemi' => $_POST['odeme_yontemi'] ?? '',
            'teslim_km' => $_POST['teslim_km'] !== '' ? (int)$_POST['teslim_km'] : null,
            'iade_km' => $_POST['iade_km'] !== '' ? (int)$_POST['iade_km'] : null,
            'teslim_yakit' => trim($_POST['teslim_yakit'] ?? ''),
            'iade_yakit' => trim($_POST['iade_yakit'] ?? ''),
            'musteri_notu' => trim($_POST['musteri_notu'] ?? ''),
            'admin_notu' => trim($_POST['admin_notu'] ?? ''),
        ];

        if ($id) {
            DB::guncelle('rezervasyonlar', $data, 'id = ?', [$id]);
            DB::sorgu("DELETE FROM " . DB::tablo('rez_ek_hizmet') . " WHERE rezervasyon_id = ?", [$id]);
            admin_log('Rezervasyon guncelle', 'ID ' . $id);
        } else {
            $data['rezervasyon_no'] = 'ATR-' . date('ymd') . sprintf('%03d', random_int(1, 999));
            $data['olusturma'] = date('Y-m-d H:i:s');
            $id = DB::ekle('rezervasyonlar', $data);
            admin_log('Rezervasyon olustur', 'ID ' . $id);
        }

        foreach ($ekDetay as $ed) {
            DB::ekle('rez_ek_hizmet', [
                'rezervasyon_id' => $id,
                'ek_hizmet_id' => $ed['id'],
                'tutar' => $ed['tutar'],
            ]);
        }

        flash_set('basari', 'Rezervasyon kaydedildi.');
        yonlendir(admin_url('rezervasyon-duzenle.php?id=' . $id));
    }
}

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2><?= $rez ? 'Rezervasyon Duzenle: ' . e($rez['rezervasyon_no']) : 'Yeni Rezervasyon' ?></h2>
    <a href="<?= admin_url('rezervasyonlar.php') ?>" class="btn btn-cerceve">← Listeye Don</a>
  </div>

  <form method="post" class="kart-icerik">
    <?= csrf_input() ?>

    <div class="iki-sutun">
      <div>
        <h3>Arac & Tarih</h3>
        <div class="form-grup">
          <label>Arac *</label>
          <select name="arac_id" required>
            <option value="">Sec...</option>
            <?php foreach ($araclar as $a): ?>
            <option value="<?= $a['id'] ?>" data-fiyat="<?= $a['gunluk_fiyat'] ?>" <?= ($rez['arac_id'] ?? 0) == $a['id'] ? 'selected' : '' ?>>
              <?= e($a['plaka'] . ' - ' . $a['marka'] . ' ' . $a['model']) ?> (<?= tl((float)$a['gunluk_fiyat']) ?>/gun)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Alis Tarihi *</label>
            <input type="datetime-local" name="alis_tarihi" value="<?= e(str_replace(' ', 'T', substr($rez['alis_tarihi'] ?? '', 0, 16))) ?>" required>
          </div>
          <div class="form-grup">
            <label>Iade Tarihi *</label>
            <input type="datetime-local" name="iade_tarihi" value="<?= e(str_replace(' ', 'T', substr($rez['iade_tarihi'] ?? '', 0, 16))) ?>" required>
          </div>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Alis Yeri</label>
            <input type="text" name="alis_yeri" value="<?= e($rez['alis_yeri'] ?? 'Ofis') ?>">
          </div>
          <div class="form-grup">
            <label>Iade Yeri</label>
            <input type="text" name="iade_yeri" value="<?= e($rez['iade_yeri'] ?? 'Ofis') ?>">
          </div>
        </div>

        <h3 style="margin-top:24px;">Musteri Bilgileri</h3>
        <div class="form-grup">
          <label>Kayitli Musteri (opsiyonel)</label>
          <select name="musteri_id">
            <option value="">- Misafir rezervasyonu -</option>
            <?php foreach ($musteriler as $m): ?>
            <option value="<?= $m['id'] ?>" <?= ($rez['musteri_id'] ?? 0) == $m['id'] ? 'selected' : '' ?>>
              <?= e($m['ad'] . ' ' . $m['soyad'] . ' - ' . $m['telefon']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Ad</label>
            <input type="text" name="misafir_ad" value="<?= e($rez['misafir_ad'] ?? '') ?>">
          </div>
          <div class="form-grup">
            <label>Soyad</label>
            <input type="text" name="misafir_soyad" value="<?= e($rez['misafir_soyad'] ?? '') ?>">
          </div>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Telefon</label>
            <input type="text" name="misafir_telefon" value="<?= e($rez['misafir_telefon'] ?? '') ?>">
          </div>
          <div class="form-grup">
            <label>E-posta</label>
            <input type="email" name="misafir_email" value="<?= e($rez['misafir_email'] ?? '') ?>">
          </div>
        </div>

        <div class="form-grup">
          <label>TC Kimlik No</label>
          <input type="text" name="misafir_tc" value="<?= e($rez['misafir_tc'] ?? '') ?>" maxlength="11">
        </div>
      </div>

      <div>
        <h3>Durum & Odeme</h3>
        <div class="form-grup">
          <label>Durum</label>
          <select name="durum">
            <?php foreach (['beklemede','onaylandi','teslim','iade','iptal'] as $d): ?>
            <option value="<?= $d ?>" <?= ($rez['durum'] ?? 'beklemede') === $d ? 'selected' : '' ?>><?= ucfirst($d) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Odeme Durumu</label>
            <select name="odeme_durumu">
              <?php foreach (['beklemede','kismi','tamamlandi','iade'] as $od): ?>
              <option value="<?= $od ?>" <?= ($rez['odeme_durumu'] ?? 'beklemede') === $od ? 'selected' : '' ?>><?= ucfirst($od) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-grup">
            <label>Odeme Yontemi</label>
            <select name="odeme_yontemi">
              <option value="">-</option>
              <?php foreach (['nakit','kredi_karti','havale','kapida'] as $oy): ?>
              <option value="<?= $oy ?>" <?= ($rez['odeme_yontemi'] ?? '') === $oy ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$oy)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <h3 style="margin-top:24px;">Fiyatlandirma</h3>
        <div class="form-grup">
          <label>Manuel Gunluk Fiyat (bos = otomatik)</label>
          <input type="number" step="0.01" name="gunluk_fiyat_manuel" value="<?= $rez && $rez['gunluk_fiyat'] ? e($rez['gunluk_fiyat']) : '' ?>">
        </div>
        <div class="form-satir">
          <div class="form-grup">
            <label>Indirim (₺)</label>
            <input type="number" step="0.01" name="indirim_tutar" value="<?= e($rez['indirim_tutar'] ?? '0') ?>">
          </div>
          <div class="form-grup">
            <label>KDV Oranı (%)</label>
            <input type="number" step="0.01" name="kdv_orani" value="<?= e($rez['kdv_orani'] ?? ayar('kdv_orani', '20')) ?>">
          </div>
        </div>
        <div class="form-grup">
          <label>Depozito (₺)</label>
          <input type="number" step="0.01" name="depozito_tutar" value="<?= e($rez['depozito_tutar'] ?? '0') ?>">
        </div>

        <h3 style="margin-top:24px;">Ek Hizmetler</h3>
        <div style="display:flex; flex-direction:column; gap:8px;">
          <?php foreach ($ekHizmetler as $eh): ?>
          <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
            <input type="checkbox" name="ek_hizmetler[]" value="<?= $eh['id'] ?>" <?= in_array($eh['id'], $ekHizmetSecili) ? 'checked' : '' ?>>
            <span><?= e($eh['ad']) ?> — <?= tl((float)$eh['fiyat']) ?> <small>(<?= $eh['fiyat_tipi'] === 'gunluk' ? '/ gun' : 'tek seferlik' ?>)</small></span>
          </label>
          <?php endforeach; ?>
        </div>

        <?php if ($rez): ?>
        <h3 style="margin-top:24px;">Teslim / Iade</h3>
        <div class="form-satir">
          <div class="form-grup">
            <label>Teslim KM</label>
            <input type="number" name="teslim_km" value="<?= e($rez['teslim_km'] ?? '') ?>">
          </div>
          <div class="form-grup">
            <label>Iade KM</label>
            <input type="number" name="iade_km" value="<?= e($rez['iade_km'] ?? '') ?>">
          </div>
        </div>
        <div class="form-satir">
          <div class="form-grup">
            <label>Teslim Yakit</label>
            <select name="teslim_yakit">
              <option value="">-</option>
              <?php foreach (['cey','yarim','dort_bes','dolu'] as $y): ?>
              <option value="<?= $y ?>" <?= ($rez['teslim_yakit'] ?? '') === $y ? 'selected' : '' ?>><?= str_replace('_',' ',ucfirst($y)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-grup">
            <label>Iade Yakit</label>
            <select name="iade_yakit">
              <option value="">-</option>
              <?php foreach (['cey','yarim','dort_bes','dolu'] as $y): ?>
              <option value="<?= $y ?>" <?= ($rez['iade_yakit'] ?? '') === $y ? 'selected' : '' ?>><?= str_replace('_',' ',ucfirst($y)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <h3>Notlar</h3>
    <div class="form-satir">
      <div class="form-grup">
        <label>Musteri Notu</label>
        <textarea name="musteri_notu" rows="3"><?= e($rez['musteri_notu'] ?? '') ?></textarea>
      </div>
      <div class="form-grup">
        <label>Admin Notu (musteriye gosterilmez)</label>
        <textarea name="admin_notu" rows="3"><?= e($rez['admin_notu'] ?? '') ?></textarea>
      </div>
    </div>

    <?php if ($rez): ?>
    <div class="alert alert-bilgi">
      <strong>Toplam:</strong> <?= tl((float)$rez['toplam_tutar']) ?> ·
      <strong>Arac:</strong> <?= tl((float)$rez['arac_tutar']) ?> ·
      <strong>Ek Hizmet:</strong> <?= tl((float)$rez['ek_hizmet_tutar']) ?> ·
      <strong>KDV:</strong> <?= tl((float)$rez['kdv_tutar']) ?> ·
      <strong>Gun:</strong> <?= (int)$rez['toplam_gun'] ?>
    </div>
    <?php endif; ?>

    <div style="display:flex; gap:8px; margin-top:16px;">
      <button class="btn btn-birincil">Kaydet</button>
      <a href="<?= admin_url('rezervasyonlar.php') ?>" class="btn btn-cerceve">İptal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
