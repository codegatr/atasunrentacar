<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Blog Yazı Düzenle';

$id = (int)($_GET['id'] ?? 0);
$blog = $id ? DB::tek("SELECT * FROM " . DB::tablo('bloglar') . " WHERE id = ?", [$id]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $baslik = trim($_POST['baslik'] ?? '');
    $slugInput = trim($_POST['slug'] ?? '');
    $slug = slug_olustur($slugInput ?: $baslik);
    $ozet = trim($_POST['ozet'] ?? '');
    $icerik = $_POST['icerik'] ?? '';
    $yazar = trim($_POST['yazar'] ?? '');
    $yayinTarihi = $_POST['yayin_tarihi'] ?? date('Y-m-d');
    $durum = ($_POST['durum'] ?? 'taslak') === 'yayinda' ? 'yayinda' : 'taslak';
    $seoBaslik = trim($_POST['seo_baslik'] ?? '');
    $seoAciklama = trim($_POST['seo_aciklama'] ?? '');

    if (!$baslik) {
        flash_set('hata', 'Başlık zorunlu.');
    } else {
        // Slug benzersizlik
        $kosul = "slug = ?";
        $par = [$slug];
        if ($id) { $kosul .= " AND id != ?"; $par[] = $id; }
        $cakisma = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('bloglar') . " WHERE $kosul", $par)['c'] ?? 0);
        if ($cakisma > 0) {
            $slug = $slug . '-' . substr(bin2hex(random_bytes(2)), 0, 4);
        }

        // Kapak yukleme
        $kapak = $blog['kapak'] ?? null;
        if (!empty($_FILES['kapak']['name'])) {
            $yeni = dosya_yukle($_FILES['kapak'], 'blog');
            if ($yeni) {
                if ($kapak) dosya_sil($kapak);
                $kapak = $yeni;
            }
        }
        if (!empty($_POST['kapak_sil']) && $kapak) {
            dosya_sil($kapak);
            $kapak = null;
        }

        $data = [
            'baslik' => $baslik,
            'slug' => $slug,
            'ozet' => $ozet,
            'icerik' => $icerik,
            'kapak' => $kapak,
            'yazar' => $yazar,
            'yayin_tarihi' => $yayinTarihi,
            'durum' => $durum,
            'seo_baslik' => $seoBaslik ?: null,
            'seo_aciklama' => $seoAciklama ?: null,
        ];

        if ($id) {
            DB::guncelle('bloglar', $data, 'id = ?', [$id]);
            admin_log('Blog guncelle', 'ID ' . $id);
            flash_set('basari', 'Yazı güncellendi.');
        } else {
            $yeniId = DB::ekle('bloglar', $data);
            admin_log('Blog olustur', $baslik);
            flash_set('basari', 'Yazı oluşturuldu.');
            yonlendir(admin_url('blog-duzenle.php?id=' . $yeniId));
        }
        yonlendir(admin_url('blog-duzenle.php?id=' . $id));
    }
}

require __DIR__ . '/_layout_basla.php';
?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_input() ?>

  <div class="iki-sutun">
    <div>
      <div class="kart">
        <div class="kart-baslik"><h2><?= $blog ? 'Yaziyi Duzenle' : 'Yeni Yazi' ?></h2></div>
        <div class="kart-icerik">
          <div class="form-grup">
            <label>Baslik *</label>
            <input type="text" name="baslik" value="<?= e($blog['baslik'] ?? '') ?>" required>
          </div>

          <div class="form-grup">
            <label>Slug (URL)</label>
            <input type="text" name="slug" value="<?= e($blog['slug'] ?? '') ?>" placeholder="Otomatik oluşturulur">
          </div>

          <div class="form-grup">
            <label>Özet (kısa açıklama)</label>
            <textarea name="ozet" rows="3"><?= e($blog['ozet'] ?? '') ?></textarea>
          </div>

          <div class="form-grup">
            <label>İçerik (HTML kullanılabilir)</label>
            <textarea name="icerik" rows="20" style="font-family:monospace;font-size:13px;"><?= e($blog['icerik'] ?? '') ?></textarea>
            <small>İpucu: Paragraf için &lt;p&gt;, başlık için &lt;h2&gt;, &lt;h3&gt;, kalın için &lt;strong&gt;, link için &lt;a href=""&gt; kullanın.</small>
          </div>
        </div>
      </div>

      <div class="kart">
        <div class="kart-baslik"><h2>SEO</h2></div>
        <div class="kart-icerik">
          <div class="form-grup">
            <label>SEO Başlık</label>
            <input type="text" name="seo_baslik" value="<?= e($blog['seo_baslik'] ?? '') ?>" maxlength="160">
          </div>
          <div class="form-grup">
            <label>SEO Açıklama</label>
            <textarea name="seo_aciklama" rows="2" maxlength="200"><?= e($blog['seo_aciklama'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div>
      <div class="kart">
        <div class="kart-baslik"><h2>Yayın</h2></div>
        <div class="kart-icerik">
          <div class="form-grup">
            <label>Durum</label>
            <select name="durum">
              <option value="taslak" <?= ($blog['durum'] ?? 'taslak') === 'taslak' ? 'selected' : '' ?>>Taslak</option>
              <option value="yayinda" <?= ($blog['durum'] ?? '') === 'yayinda' ? 'selected' : '' ?>>Yayında</option>
            </select>
          </div>

          <div class="form-grup">
            <label>Yayın Tarihi</label>
            <input type="date" name="yayin_tarihi" value="<?= e($blog['yayin_tarihi'] ?? date('Y-m-d')) ?>">
          </div>

          <div class="form-grup">
            <label>Yazar</label>
            <input type="text" name="yazar" value="<?= e($blog['yazar'] ?? ($GLOBALS['admin_kullanici']['ad_soyad'] ?? '')) ?>">
          </div>

          <button class="btn btn-birincil btn-blok"><?= $blog ? 'Guncelle' : 'Olustur' ?></button>
          <a href="<?= admin_url('bloglar.php') ?>" class="btn btn-cerceve btn-blok">Listeye Dön</a>
        </div>
      </div>

      <div class="kart">
        <div class="kart-baslik"><h2>Kapak Resmi</h2></div>
        <div class="kart-icerik">
          <?php if (!empty($blog['kapak'])): ?>
            <img src="<?= e(upload_url($blog['kapak'])) ?>" style="width:100%;border-radius:6px;margin-bottom:8px;">
            <label class="form-grup" style="display:flex;align-items:center;gap:6px;">
              <input type="checkbox" name="kapak_sil" value="1"> Mevcut kapağı sil
            </label>
          <?php endif; ?>
          <div class="form-grup">
            <label>Yeni Kapak Yükle</label>
            <input type="file" name="kapak" accept="image/*">
            <small>Önerilen: 1200x628 px, max 8 MB</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
