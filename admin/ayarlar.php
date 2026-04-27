<?php
require_once __DIR__ . '/_init.php';
admin_yetki('admin');
$pageTitle = 'Ayarlar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $aktifSekme = $_POST['_sekme'] ?? 'genel';

    // Logo / favicon yukleme
    foreach (['logo', 'favicon'] as $alan) {
        if (!empty($_FILES[$alan]['name'])) {
            $yeni = dosya_yukle($_FILES[$alan], '');
            if ($yeni) {
                $eski = ayar($alan);
                if ($eski) dosya_sil($eski);
                ayar_kaydet($alan, $yeni);
            }
        }
        if (!empty($_POST[$alan . '_sil'])) {
            $eski = ayar($alan);
            if ($eski) dosya_sil($eski);
            ayar_kaydet($alan, '');
        }
    }

    // Tum gonderilen text/textarea ayarlarini kaydet
    if (!empty($_POST['ayar']) && is_array($_POST['ayar'])) {
        foreach ($_POST['ayar'] as $anahtar => $deger) {
            $anahtar = preg_replace('/[^a-z0-9_]/i', '', (string)$anahtar);
            if ($anahtar === '') continue;
            ayar_kaydet($anahtar, is_array($deger) ? json_encode($deger, JSON_UNESCAPED_UNICODE) : (string)$deger);
        }
    }

    // Checkbox alanlari (gonderilmediginde 0)
    foreach (['rezervasyon_aktif'] as $cb) {
        ayar_kaydet($cb, !empty($_POST['ayar_cb'][$cb]) ? '1' : '0');
    }

    admin_log('Ayarlar guncellendi', $aktifSekme);
    flash_set('basari', 'Ayarlar kaydedildi.');
    yonlendir(admin_url('ayarlar.php?sekme=' . urlencode($aktifSekme)));
}

$aktif = $_GET['sekme'] ?? 'genel';
$logoYol = ayar('logo');
$faviconYol = ayar('favicon');

require __DIR__ . '/_layout_basla.php';

function ayarTxt(string $anahtar, string $varsayilan = ''): string {
    return e((string)ayar($anahtar, $varsayilan));
}
?>

<div class="kart">
  <div class="kart-baslik"><h2>Site Ayarları</h2></div>
  <div class="kart-icerik">
    <div class="sekmeler">
      <div class="sekme <?= $aktif === 'genel' ? 'aktif' : '' ?>" data-hedef="genel">Genel</div>
      <div class="sekme <?= $aktif === 'iletisim' ? 'aktif' : '' ?>" data-hedef="iletisim">Iletisim</div>
      <div class="sekme <?= $aktif === 'sosyal' ? 'aktif' : '' ?>" data-hedef="sosyal">Sosyal</div>
      <div class="sekme <?= $aktif === 'icerik' ? 'aktif' : '' ?>" data-hedef="icerik">İçerik</div>
      <div class="sekme <?= $aktif === 'seo' ? 'aktif' : '' ?>" data-hedef="seo">SEO</div>
      <div class="sekme <?= $aktif === 'yasal' ? 'aktif' : '' ?>" data-hedef="yasal">Yasal</div>
      <div class="sekme <?= $aktif === 'mail' ? 'aktif' : '' ?>" data-hedef="mail">Mail</div>
      <div class="sekme <?= $aktif === 'rezervasyon' ? 'aktif' : '' ?>" data-hedef="rezervasyon">Rezervasyon</div>
    </div>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_input() ?>
      <input type="hidden" name="_sekme" id="_sekme" value="<?= e($aktif) ?>">

      <!-- GENEL -->
      <div class="sekme-icerik <?= $aktif === 'genel' ? 'aktif' : '' ?>" data-icerik="genel">
        <div class="form-grup">
          <label>Site Başlığı</label>
          <input type="text" name="ayar[site_baslik]" value="<?= ayarTxt('site_baslik') ?>">
        </div>
        <div class="form-grup">
          <label>Site Açıklaması (meta description)</label>
          <textarea name="ayar[site_aciklama]" rows="2"><?= ayarTxt('site_aciklama') ?></textarea>
        </div>

        <div class="form-satir-4">
          <div class="form-grup">
            <label>Araç Sayısı (anasayfa)</label>
            <input type="text" name="ayar[arac_sayisi]" value="<?= ayarTxt('arac_sayisi') ?>">
          </div>
          <div class="form-grup">
            <label>Mutlu Müşteri</label>
            <input type="text" name="ayar[mutlu_musteri]" value="<?= ayarTxt('mutlu_musteri') ?>">
          </div>
          <div class="form-grup">
            <label>Hizmet Yılı</label>
            <input type="text" name="ayar[hizmet_yili]" value="<?= ayarTxt('hizmet_yili') ?>">
          </div>
          <div class="form-grup">
            <label>Puan</label>
            <input type="text" name="ayar[puan]" value="<?= ayarTxt('puan') ?>">
          </div>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Logo</label>
            <?php if ($logoYol): ?>
              <div style="margin-bottom:8px;"><img src="<?= e(upload_url($logoYol)) ?>" style="height:60px;border:1px solid #e2e8f0;padding:4px;border-radius:4px;background:#fff;"></div>
              <label style="display:flex;align-items:center;gap:6px;font-weight:normal;">
                <input type="checkbox" name="logo_sil" value="1"> Logoyu sil
              </label>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/*">
          </div>
          <div class="form-grup">
            <label>Favicon</label>
            <?php if ($faviconYol): ?>
              <div style="margin-bottom:8px;"><img src="<?= e(upload_url($faviconYol)) ?>" style="height:32px;border:1px solid #e2e8f0;padding:4px;border-radius:4px;background:#fff;"></div>
              <label style="display:flex;align-items:center;gap:6px;font-weight:normal;">
                <input type="checkbox" name="favicon_sil" value="1"> Favicon sil
              </label>
            <?php endif; ?>
            <input type="file" name="favicon" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/*">
            <small>PNG (32x32) önerilir.</small>
          </div>
        </div>
      </div>

      <!-- ILETISIM -->
      <div class="sekme-icerik <?= $aktif === 'iletisim' ? 'aktif' : '' ?>" data-icerik="iletisim">
        <div class="form-satir">
          <div class="form-grup">
            <label>Telefon</label>
            <input type="text" name="ayar[telefon]" value="<?= ayarTxt('telefon') ?>">
          </div>
          <div class="form-grup">
            <label>Telefon 2</label>
            <input type="text" name="ayar[telefon2]" value="<?= ayarTxt('telefon2') ?>">
          </div>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>WhatsApp (905XXXXXXXXX)</label>
            <input type="text" name="ayar[whatsapp]" value="<?= ayarTxt('whatsapp') ?>">
          </div>
          <div class="form-grup">
            <label>E-posta</label>
            <input type="email" name="ayar[email]" value="<?= ayarTxt('email') ?>">
          </div>
        </div>

        <div class="form-grup">
          <label>Adres</label>
          <textarea name="ayar[adres]" rows="2"><?= ayarTxt('adres') ?></textarea>
        </div>

        <div class="form-grup">
          <label>Çalışma Saatleri</label>
          <input type="text" name="ayar[calisma_saati]" value="<?= ayarTxt('calisma_saati') ?>">
        </div>

        <div class="form-grup">
          <label>Google Maps Embed Kodu (iframe src)</label>
          <textarea name="ayar[harita_embed]" rows="3" style="font-family:monospace;font-size:12px;"><?= ayarTxt('harita_embed') ?></textarea>
        </div>
      </div>

      <!-- SOSYAL -->
      <div class="sekme-icerik <?= $aktif === 'sosyal' ? 'aktif' : '' ?>" data-icerik="sosyal">
        <div class="form-grup">
          <label>Facebook URL</label>
          <input type="url" name="ayar[facebook]" value="<?= ayarTxt('facebook') ?>">
        </div>
        <div class="form-grup">
          <label>Instagram URL</label>
          <input type="url" name="ayar[instagram]" value="<?= ayarTxt('instagram') ?>">
        </div>
        <div class="form-grup">
          <label>Twitter / X URL</label>
          <input type="url" name="ayar[twitter]" value="<?= ayarTxt('twitter') ?>">
        </div>
        <div class="form-grup">
          <label>YouTube URL</label>
          <input type="url" name="ayar[youtube]" value="<?= ayarTxt('youtube') ?>">
        </div>
      </div>

      <!-- ICERIK -->
      <div class="sekme-icerik <?= $aktif === 'icerik' ? 'aktif' : '' ?>" data-icerik="icerik">
        <div class="form-grup">
          <label>Hero Üst Başlık</label>
          <input type="text" name="ayar[hero_alt_baslik]" value="<?= ayarTxt('hero_alt_baslik') ?>">
        </div>
        <div class="form-grup">
          <label>Hero Ana Başlık</label>
          <input type="text" name="ayar[hero_baslik]" value="<?= ayarTxt('hero_baslik') ?>">
        </div>
        <div class="form-grup">
          <label>Hero Açıklama</label>
          <textarea name="ayar[hero_aciklama]" rows="3"><?= ayarTxt('hero_aciklama') ?></textarea>
        </div>

        <hr style="margin:20px 0;border:none;border-top:1px solid #e2e8f0;">

        <div class="form-grup">
          <label>Hakkımızda - Kısa (anasayfa)</label>
          <textarea name="ayar[hakkimizda_kisa]" rows="3"><?= ayarTxt('hakkimizda_kisa') ?></textarea>
        </div>
        <div class="form-grup">
          <label>Hakkımızda - Detaylı (HTML)</label>
          <textarea name="ayar[hakkimizda_detay]" rows="10" style="font-family:monospace;font-size:13px;"><?= ayarTxt('hakkimizda_detay') ?></textarea>
        </div>
      </div>

      <!-- SEO -->
      <div class="sekme-icerik <?= $aktif === 'seo' ? 'aktif' : '' ?>" data-icerik="seo">
        <div class="alert alert-bilgi">
          <strong>Anahtar Kelime Önerileri (Konya araç kiralama için):</strong><br>
          konya araç kiralama, konya rent a car, oto kiralama konya, günlük araç kiralama,
          haftalık araç kiralama, aylık araç kiralama, havalimanı araç kiralama,
          ucuz araç kiralama konya, ekonomik araç kiralama, otomatik vites kiralık araç,
          suv kiralama konya, lüks araç kiralama, ticari araç kiralama, ata su rent a car
        </div>

        <div class="form-grup">
          <label>Meta Anahtar Kelimeler (virgülle ayırın)</label>
          <textarea name="ayar[site_anahtar_kelimeler]" rows="3"><?= ayarTxt('site_anahtar_kelimeler') ?></textarea>
          <small>Tüm sayfalarda gösterilen genel anahtar kelimeler. Yandex/Bing için hâlâ kullanışlı.</small>
        </div>

        <div class="form-satir">
          <div class="form-grup">
            <label>Google Analytics ID (G-XXXX)</label>
            <input type="text" name="ayar[google_analytics]" value="<?= ayarTxt('google_analytics') ?>" placeholder="G-XXXXXXXXXX">
          </div>
          <div class="form-grup">
            <label>Google Tag Manager ID</label>
            <input type="text" name="ayar[google_tag_manager]" value="<?= ayarTxt('google_tag_manager') ?>" placeholder="GTM-XXXXXXX">
          </div>
        </div>

        <h3 style="margin:20px 0 8px;border-top:1px solid #e2e8f0;padding-top:16px;">Site Doğrulama Kodları</h3>
        <p style="color:#64748b;font-size:13px;margin-bottom:14px;">Search Console'dan aldığınız content="..." kısmını yapıştırın (HTML etiketinin tamamını değil).</p>

        <div class="form-grup">
          <label>Google Search Console</label>
          <input type="text" name="ayar[google_site_verification]" value="<?= ayarTxt('google_site_verification') ?>" placeholder="abc123XYZ...">
        </div>
        <div class="form-grup">
          <label>Yandex Webmaster</label>
          <input type="text" name="ayar[yandex_verification]" value="<?= ayarTxt('yandex_verification') ?>" placeholder="abc123...">
        </div>
        <div class="form-grup">
          <label>Bing Webmaster</label>
          <input type="text" name="ayar[bing_verification]" value="<?= ayarTxt('bing_verification') ?>" placeholder="abc123...">
        </div>

        <h3 style="margin:20px 0 8px;border-top:1px solid #e2e8f0;padding-top:16px;">Pazarlama / Pixeller</h3>
        <div class="form-grup">
          <label>Facebook Pixel ID</label>
          <input type="text" name="ayar[facebook_pixel]" value="<?= ayarTxt('facebook_pixel') ?>" placeholder="1234567890123456">
        </div>

        <div class="alert alert-uyari" style="margin-top:16px;">
          <strong>Sitemap & Robots:</strong>
          Bu site otomatik olarak <a href="<?= url('sitemap.xml') ?>" target="_blank"><code>/sitemap.xml</code></a>
          ve <a href="<?= url('robots.txt') ?>" target="_blank"><code>/robots.txt</code></a> dosyalarını üretir.
          Search Console'da bu URL'leri kayıt edebilirsiniz.
        </div>
      </div>

      <!-- YASAL -->
      <div class="sekme-icerik <?= $aktif === 'yasal' ? 'aktif' : '' ?>" data-icerik="yasal">
        <div class="form-grup">
          <label>KVKK Aydınlatma Metni (HTML)</label>
          <textarea name="ayar[kvkk_aciklama]" rows="12" style="font-family:monospace;font-size:13px;"><?= ayarTxt('kvkk_aciklama') ?></textarea>
        </div>
        <div class="form-grup">
          <label>Gizlilik Politikası (HTML)</label>
          <textarea name="ayar[gizlilik_politikasi]" rows="12" style="font-family:monospace;font-size:13px;"><?= ayarTxt('gizlilik_politikasi') ?></textarea>
        </div>
        <div class="form-grup">
          <label>Çerez Politikası (HTML)</label>
          <textarea name="ayar[cerez_politikasi]" rows="12" style="font-family:monospace;font-size:13px;"><?= ayarTxt('cerez_politikasi') ?></textarea>
        </div>
        <div class="form-grup">
          <label>Kiralama Sözleşmesi (HTML)</label>
          <textarea name="ayar[kiralama_sozlesmesi]" rows="14" style="font-family:monospace;font-size:13px;"><?= ayarTxt('kiralama_sozlesmesi') ?></textarea>
          <small>Müşteri kiralama sözleşmesi şablonu. Sitede <code>/kiralama-sozlesmesi</code> sayfasında gösterilir.</small>
        </div>
      </div>

      <!-- MAIL -->
      <div class="sekme-icerik <?= $aktif === 'mail' ? 'aktif' : '' ?>" data-icerik="mail">
        <div class="alert alert-bilgi">SMTP ayarları rezervasyon onay ve iletişim formu bildirimleri için kullanılır.</div>
        <div class="form-satir">
          <div class="form-grup">
            <label>SMTP Host</label>
            <input type="text" name="ayar[mail_smtp_host]" value="<?= ayarTxt('mail_smtp_host') ?>" placeholder="mail.atasurentacar.com">
          </div>
          <div class="form-grup">
            <label>SMTP Port</label>
            <input type="number" name="ayar[mail_smtp_port]" value="<?= ayarTxt('mail_smtp_port', '587') ?>">
          </div>
        </div>
        <div class="form-satir">
          <div class="form-grup">
            <label>Kullanıcı Adı</label>
            <input type="text" name="ayar[mail_smtp_user]" value="<?= ayarTxt('mail_smtp_user') ?>">
          </div>
          <div class="form-grup">
            <label>Şifre</label>
            <input type="password" name="ayar[mail_smtp_pass]" value="<?= ayarTxt('mail_smtp_pass') ?>" autocomplete="new-password">
          </div>
        </div>
        <div class="form-satir">
          <div class="form-grup">
            <label>Güvenlik</label>
            <select name="ayar[mail_smtp_secure]">
              <?php $sec = ayar('mail_smtp_secure', 'tls'); ?>
              <option value="tls" <?= $sec === 'tls' ? 'selected' : '' ?>>TLS</option>
              <option value="ssl" <?= $sec === 'ssl' ? 'selected' : '' ?>>SSL</option>
              <option value="" <?= $sec === '' ? 'selected' : '' ?>>Yok</option>
            </select>
          </div>
          <div class="form-grup">
            <label>Gönderen E-posta</label>
            <input type="email" name="ayar[mail_from]" value="<?= ayarTxt('mail_from') ?>">
          </div>
        </div>
      </div>

      <!-- REZERVASYON -->
      <div class="sekme-icerik <?= $aktif === 'rezervasyon' ? 'aktif' : '' ?>" data-icerik="rezervasyon">
        <div class="form-satir">
          <div class="form-grup">
            <label>Min. Kiralama Gün</label>
            <input type="number" name="ayar[min_kiralama_gun]" value="<?= ayarTxt('min_kiralama_gun', '1') ?>" min="1">
          </div>
          <div class="form-grup">
            <label>KDV Oranı (%)</label>
            <input type="number" name="ayar[kdv_orani]" value="<?= ayarTxt('kdv_orani', '20') ?>" min="0" step="0.01">
          </div>
        </div>

        <label style="display:flex;align-items:center;gap:8px;margin-top:10px;">
          <input type="checkbox" name="ayar_cb[rezervasyon_aktif]" value="1" <?= ayar('rezervasyon_aktif', '1') == '1' ? 'checked' : '' ?>>
          <span>Online rezervasyonlar açık</span>
        </label>
      </div>

      <div style="margin-top:20px;display:flex;gap:8px;">
        <button class="btn btn-birincil">Ayarlari Kaydet</button>
      </div>
    </form>
  </div>
</div>

<script>
document.querySelectorAll('.sekme').forEach(s => {
  s.addEventListener('click', () => {
    const hedef = s.dataset.hedef;
    document.querySelectorAll('.sekme').forEach(x => x.classList.toggle('aktif', x === s));
    document.querySelectorAll('.sekme-icerik').forEach(c => c.classList.toggle('aktif', c.dataset.icerik === hedef));
    const inp = document.getElementById('_sekme');
    if (inp) inp.value = hedef;
    history.replaceState(null, '', '?sekme=' + encodeURIComponent(hedef));
  });
});
</script>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
