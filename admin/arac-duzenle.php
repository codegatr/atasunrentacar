<?php
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$arac = $id ? DB::tek("SELECT * FROM " . DB::tablo('araclar') . " WHERE id = ?", [$id]) : null;
$pageTitle = $id ? ('Arac Duzenle: ' . ($arac['marka'] ?? '')) : 'Yeni Arac';

$kategoriler = DB::liste("SELECT * FROM " . DB::tablo('kategoriler') . " WHERE aktif=1 ORDER BY sira ASC");
$resimler = $id ? DB::liste("SELECT * FROM " . DB::tablo('arac_resimler') . " WHERE arac_id = ? ORDER BY ana_resim DESC, sira ASC", [$id]) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    try {
        $islem = $_POST['islem'] ?? '';

        if ($islem === 'resim_sil' && !empty($_POST['resim_id'])) {
            $r = DB::tek("SELECT * FROM " . DB::tablo('arac_resimler') . " WHERE id = ? AND arac_id = ?", [(int)$_POST['resim_id'], $id]);
            if ($r) {
                dosya_sil('araclar/' . $r['dosya']);
                DB::sil('arac_resimler', 'id = ?', [(int)$r['id']]);
                flash_set('basari', 'Resim silindi.');
            }
            yonlendir(admin_url('arac-duzenle.php?id=' . $id));
        }

        if ($islem === 'ana_resim' && !empty($_POST['resim_id']) && $id) {
            DB::sorgu("UPDATE " . DB::tablo('arac_resimler') . " SET ana_resim = 0 WHERE arac_id = ?", [$id]);
            DB::sorgu("UPDATE " . DB::tablo('arac_resimler') . " SET ana_resim = 1 WHERE id = ? AND arac_id = ?", [(int)$_POST['resim_id'], $id]);
            flash_set('basari', 'Ana resim güncellendi.');
            yonlendir(admin_url('arac-duzenle.php?id=' . $id));
        }

        // Standart kaydetme
        $veri = [
            'plaka' => trim($_POST['plaka'] ?? ''),
            'marka' => trim($_POST['marka'] ?? ''),
            'model' => trim($_POST['model'] ?? ''),
            'yil' => (int)($_POST['yil'] ?? 0),
            'kategori_id' => (int)($_POST['kategori_id'] ?? 0) ?: null,
            'vites' => $_POST['vites'] ?? 'Manuel',
            'yakit' => $_POST['yakit'] ?? 'Benzin',
            'koltuk_sayisi' => (int)($_POST['koltuk_sayisi'] ?? 5),
            'bagaj_sayisi' => (int)($_POST['bagaj_sayisi'] ?? 1),
            'kapi_sayisi' => (int)($_POST['kapi_sayisi'] ?? 4),
            'motor_hacmi' => trim($_POST['motor_hacmi'] ?? ''),
            'motor_gucu' => trim($_POST['motor_gucu'] ?? ''),
            'renk' => trim($_POST['renk'] ?? ''),
            'klima' => isset($_POST['klima']) ? 1 : 0,
            'sasi_no' => trim($_POST['sasi_no'] ?? ''),
            'motor_no' => trim($_POST['motor_no'] ?? ''),
            'km' => (int)($_POST['km'] ?? 0),
            'alis_tarihi' => $_POST['alis_tarihi'] ?: null,
            'alis_fiyati' => (float)($_POST['alis_fiyati'] ?? 0),
            'gunluk_fiyat' => (float)($_POST['gunluk_fiyat'] ?? 0),
            'haftalik_fiyat' => (float)($_POST['haftalik_fiyat'] ?? 0) ?: null,
            'aylik_fiyat' => (float)($_POST['aylik_fiyat'] ?? 0) ?: null,
            'depozito' => (float)($_POST['depozito'] ?? 0),
            'min_yas' => (int)($_POST['min_yas'] ?? 21),
            'min_ehliyet_yili' => (int)($_POST['min_ehliyet_yili'] ?? 1),
            'aciklama' => $_POST['aciklama'] ?? '',
            'ozellikler' => $_POST['ozellikler'] ?? '',
            'seo_baslik' => trim($_POST['seo_baslik'] ?? '') ?: null,
            'seo_aciklama' => trim($_POST['seo_aciklama'] ?? '') ?: null,
            'seo_anahtarlar' => trim($_POST['seo_anahtarlar'] ?? '') ?: null,
            'durum' => $_POST['durum'] ?? 'musait',
            'aktif' => isset($_POST['aktif']) ? 1 : 0,
            'one_cikan' => isset($_POST['one_cikan']) ? 1 : 0,
        ];

        if (!$veri['plaka'] || !$veri['marka'] || !$veri['model']) {
            throw new Exception('Plaka, marka ve model zorunludur.');
        }

        // Slug
        $slugTemel = slug_olustur($veri['marka'] . '-' . $veri['model'] . '-' . $veri['yil']);
        $slug = $slugTemel;
        $sayac = 2;
        while (true) {
            $sql = "SELECT id FROM " . DB::tablo('araclar') . " WHERE slug = ?" . ($id ? " AND id != ?" : '');
            $params = $id ? [$slug, $id] : [$slug];
            if (!DB::tek($sql, $params)) break;
            $slug = $slugTemel . '-' . $sayac++;
        }
        $veri['slug'] = $slug;

        if ($id) {
            DB::guncelle('araclar', $veri, 'id = ?', [$id]);
            $aracId = $id;
            admin_log('Arac guncellendi', $veri['plaka'] . ' ' . $veri['marka'] . ' ' . $veri['model']);
        } else {
            $aracId = DB::ekle('araclar', $veri);
            admin_log('Arac eklendi', $veri['plaka'] . ' ' . $veri['marka'] . ' ' . $veri['model']);
        }

        // Resim yukle
        if (!empty($_FILES['resimler']['name'][0])) {
            $varOlanResim = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('arac_resimler') . " WHERE arac_id = ?", [$aracId])['c'] ?? 0);
            $sira = $varOlanResim;
            foreach ($_FILES['resimler']['name'] as $i => $name) {
                if (empty($name)) continue;
                $tek = [
                    'name' => $_FILES['resimler']['name'][$i],
                    'tmp_name' => $_FILES['resimler']['tmp_name'][$i],
                    'size' => $_FILES['resimler']['size'][$i],
                    'error' => $_FILES['resimler']['error'][$i],
                ];
                $rota = dosya_yukle($tek, 'araclar');
                if ($rota) {
                    $dosya = basename($rota);
                    DB::ekle('arac_resimler', [
                        'arac_id' => $aracId,
                        'dosya' => $dosya,
                        'sira' => $sira++,
                        'ana_resim' => $varOlanResim === 0 && $i === 0 ? 1 : 0,
                    ]);
                    if ($varOlanResim === 0 && $i === 0) $varOlanResim = 1;
                }
            }
        }

        flash_set('basari', $id ? 'Araç güncellendi.' : 'Araç eklendi.');
        yonlendir(admin_url('arac-duzenle.php?id=' . $aracId));

    } catch (Throwable $e) {
        flash_set('hata', $e->getMessage());
    }
}

require __DIR__ . '/_layout_basla.php';
?>

<form method="post" enctype="multipart/form-data">
<?= csrf_input() ?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Genel Bilgiler</h2></div>
      <div class="kart-icerik">
        <div class="form-satir-3">
          <div class="form-grup">
            <label>Plaka *</label>
            <input type="text" name="plaka" value="<?= e($arac['plaka'] ?? '') ?>" required style="text-transform:uppercase;">
          </div>
          <div class="form-grup">
            <label>Marka *</label>
            <input type="text" name="marka" value="<?= e($arac['marka'] ?? '') ?>" required>
          </div>
          <div class="form-grup">
            <label>Model *</label>
            <input type="text" name="model" value="<?= e($arac['model'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-satir-3">
          <div class="form-grup">
            <label>Yil</label>
            <input type="number" name="yil" value="<?= (int)($arac['yil'] ?? date('Y')) ?>" min="1990" max="<?= date('Y') + 1 ?>">
          </div>
          <div class="form-grup">
            <label>Kategori</label>
            <select name="kategori_id">
              <option value="">- Sec -</option>
              <?php foreach ($kategoriler as $k): ?>
              <option value="<?= (int)$k['id'] ?>" <?= ((int)($arac['kategori_id'] ?? 0) === (int)$k['id']) ? 'selected' : '' ?>><?= e($k['ad']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-grup">
            <label>Renk</label>
            <input type="text" name="renk" value="<?= e($arac['renk'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Teknik Ozellikler</h2></div>
      <div class="kart-icerik">
        <div class="form-satir-3">
          <div class="form-grup">
            <label>Vites</label>
            <select name="vites">
              <?php foreach (['Manuel','Otomatik','Yari Otomatik'] as $v): ?>
              <option <?= ($arac['vites'] ?? '') === $v ? 'selected' : '' ?>><?= e($v) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-grup">
            <label>Yakit</label>
            <select name="yakit">
              <?php foreach (['Benzin','Dizel','Hibrit','Elektrik','LPG'] as $y): ?>
              <option <?= ($arac['yakit'] ?? '') === $y ? 'selected' : '' ?>><?= e($y) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-grup">
            <label>Klima</label>
            <select name="klima">
              <option value="0" <?= empty($arac['klima']) ? 'selected' : '' ?>>Yok</option>
              <option value="1" <?= !empty($arac['klima']) ? 'selected' : '' ?>>Var</option>
            </select>
          </div>
        </div>

        <div class="form-satir-4">
          <div class="form-grup">
            <label>Koltuk</label>
            <input type="number" name="koltuk_sayisi" value="<?= (int)($arac['koltuk_sayisi'] ?? 5) ?>" min="2" max="20">
          </div>
          <div class="form-grup">
            <label>Bavul</label>
            <input type="number" name="bagaj_sayisi" value="<?= (int)($arac['bagaj_sayisi'] ?? 1) ?>" min="0" max="10">
          </div>
          <div class="form-grup">
            <label>Kapi</label>
            <input type="number" name="kapi_sayisi" value="<?= (int)($arac['kapi_sayisi'] ?? 4) ?>" min="2" max="6">
          </div>
          <div class="form-grup">
            <label>KM</label>
            <input type="number" name="km" value="<?= (int)($arac['km'] ?? 0) ?>" min="0">
          </div>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Motor Hacmi</label>
            <input type="text" name="motor_hacmi" value="<?= e($arac['motor_hacmi'] ?? '') ?>" placeholder="1.6">
          </div>
          <div class="form-grup">
            <label>Motor Gucu</label>
            <input type="text" name="motor_gucu" value="<?= e($arac['motor_gucu'] ?? '') ?>" placeholder="110 HP">
          </div>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Sasi No</label>
            <input type="text" name="sasi_no" value="<?= e($arac['sasi_no'] ?? '') ?>">
          </div>
          <div class="form-grup">
            <label>Motor No</label>
            <input type="text" name="motor_no" value="<?= e($arac['motor_no'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Açıklama ve Özellikler</h2></div>
      <div class="kart-icerik">
        <div class="form-grup">
          <label>Açıklama</label>
          <textarea name="aciklama" rows="4"><?= e($arac['aciklama'] ?? '') ?></textarea>
        </div>
        <div class="form-grup">
          <label>Özellikler (her satır bir madde)</label>
          <textarea name="ozellikler" rows="4" placeholder="ABS&#10;ESP&#10;Bluetooth&#10;..."><?= e($arac['ozellikler'] ?? '') ?></textarea>
          <small>Her satır ayrı bir özellik olarak gösterilir.</small>
        </div>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>SEO Ayarları</h2></div>
      <div class="kart-icerik">
        <p style="color:#64748b;font-size:13px;margin-bottom:14px;">Boş bırakırsanız sistem otomatik olarak <strong>"<?= e(($arac['marka'] ?? 'Marka') . ' ' . ($arac['model'] ?? 'Model')) ?> Kiralama Konya"</strong> şeklinde başlık ve araç bilgilerinden açıklama üretir.</p>
        <div class="form-grup">
          <label>SEO Başlık (max 160 karakter)</label>
          <input type="text" name="seo_baslik" value="<?= e($arac['seo_baslik'] ?? '') ?>" maxlength="160" placeholder="Örn: Renault Clio Kiralama Konya - Otomatik Vites Ekonomi Sınıfı">
        </div>
        <div class="form-grup">
          <label>SEO Açıklama (max 300 karakter, ideal 150-160)</label>
          <textarea name="seo_aciklama" rows="3" maxlength="300" placeholder="Konya'da ekonomik araç kiralama. Renault Clio modeliyle uygun fiyatlı, yakıt tasarruflu seyahat. Hemen rezervasyon yapın."><?= e($arac['seo_aciklama'] ?? '') ?></textarea>
        </div>
        <div class="form-grup">
          <label>Hedef Anahtar Kelimeler (virgülle ayırın)</label>
          <input type="text" name="seo_anahtarlar" value="<?= e($arac['seo_anahtarlar'] ?? '') ?>" placeholder="renault clio kiralama, konya clio kiralama, ekonomik araç kiralama">
        </div>
      </div>
    </div>

    <?php if ($id): ?>
    <div class="kart">
      <div class="kart-baslik"><h2>Resimler</h2></div>
      <div class="kart-icerik">
        <div class="form-grup">
          <label>Yeni Resim Yukle (birden fazla secebilirsiniz)</label>
          <input type="file" name="resimler[]" multiple accept="image/jpeg,image/png,image/webp">
          <small>JPG, PNG veya WEBP - en fazla 8 MB</small>
        </div>

        <?php if ($resimler): ?>
        <div class="resim-onizleme">
          <?php foreach ($resimler as $r): ?>
          <div class="resim-kart <?= $r['ana_resim'] ? 'ana-resim' : '' ?>">
            <img src="<?= e(upload_url('araclar/' . $r['dosya'])) ?>">
            <?php if ($r['ana_resim']): ?>
              <span class="ana-isaret">ANA</span>
            <?php else: ?>
              <button type="button" class="sil-btn" onclick="anaResimYap(<?= (int)$r['id'] ?>)" title="Ana resim yap" style="background:rgba(16,185,129,0.95); right:34px;">★</button>
            <?php endif; ?>
            <button type="button" class="sil-btn" onclick="resimSil(<?= (int)$r['id'] ?>)" title="Sil">×</button>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Yayın</h2></div>
      <div class="kart-icerik">
        <div class="form-grup">
          <label>Durum</label>
          <select name="durum">
            <?php foreach (['musait','kirada','rezerve','bakimda','satildi'] as $d): ?>
            <option value="<?= e($d) ?>" <?= ($arac['durum'] ?? 'musait') === $d ? 'selected' : '' ?>><?= e(ucfirst($d)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grup">
          <label><input type="checkbox" name="aktif" value="1" <?= !$arac || !empty($arac['aktif']) ? 'checked' : '' ?>> Aktif (sitede goster)</label>
        </div>
        <div class="form-grup">
          <label><input type="checkbox" name="one_cikan" value="1" <?= !empty($arac['one_cikan']) ? 'checked' : '' ?>> One cikan</label>
        </div>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Fiyatlandirma</h2></div>
      <div class="kart-icerik">
        <div class="form-grup">
          <label>Gunluk Fiyat (₺) *</label>
          <input type="number" name="gunluk_fiyat" value="<?= (float)($arac['gunluk_fiyat'] ?? 0) ?>" step="0.01" min="0" required>
        </div>
        <div class="form-grup">
          <label>Haftalik (gunluk * 7'den dusuk)</label>
          <input type="number" name="haftalik_fiyat" value="<?= (float)($arac['haftalik_fiyat'] ?? 0) ?>" step="0.01" min="0">
        </div>
        <div class="form-grup">
          <label>Aylik (gunluk * 30'dan dusuk)</label>
          <input type="number" name="aylik_fiyat" value="<?= (float)($arac['aylik_fiyat'] ?? 0) ?>" step="0.01" min="0">
        </div>
        <div class="form-grup">
          <label>Depozito (₺)</label>
          <input type="number" name="depozito" value="<?= (float)($arac['depozito'] ?? 0) ?>" step="0.01" min="0">
        </div>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Kosullar</h2></div>
      <div class="kart-icerik">
        <div class="form-grup">
          <label>Min. Yaş</label>
          <input type="number" name="min_yas" value="<?= (int)($arac['min_yas'] ?? 21) ?>" min="18" max="80">
        </div>
        <div class="form-grup">
          <label>Min. Ehliyet (yil)</label>
          <input type="number" name="min_ehliyet_yili" value="<?= (int)($arac['min_ehliyet_yili'] ?? 1) ?>" min="0" max="20">
        </div>
      </div>
    </div>

    <div class="kart">
      <div class="kart-baslik"><h2>Filo Bilgileri</h2></div>
      <div class="kart-icerik">
        <div class="form-grup">
          <label>Alis Tarihi</label>
          <input type="date" name="alis_tarihi" value="<?= e($arac['alis_tarihi'] ?? '') ?>">
        </div>
        <div class="form-grup">
          <label>Alis Fiyati (₺)</label>
          <input type="number" name="alis_fiyati" value="<?= (float)($arac['alis_fiyati'] ?? 0) ?>" step="0.01" min="0">
        </div>
      </div>
    </div>

    <div style="display:flex; gap:8px;">
      <button type="submit" class="btn btn-birincil" style="flex:1;">Kaydet</button>
      <a href="<?= admin_url('araclar.php') ?>" class="btn btn-cerceve">İptal</a>
    </div>
  </div>
</div>
</form>

<?php if ($id): ?>
<form id="resimForm" method="post" style="display:none;">
  <?= csrf_input() ?>
  <input type="hidden" name="islem" id="resimIslem">
  <input type="hidden" name="resim_id" id="resimId">
</form>
<script>
function resimSil(id){ if(!confirm('Bu resmi silmek istediginize emin misiniz?')) return; document.getElementById('resimIslem').value='resim_sil'; document.getElementById('resimId').value=id; document.getElementById('resimForm').submit(); }
function anaResimYap(id){ document.getElementById('resimIslem').value='ana_resim'; document.getElementById('resimId').value=id; document.getElementById('resimForm').submit(); }
</script>
<?php endif; ?>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
