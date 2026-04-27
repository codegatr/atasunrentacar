<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <h1>Rezervasyon</h1>
    <p>Hızlı ve güvenli rezervasyon</p>
  </div>
</section>

<section class="bolum">
  <div class="kapsayici">
    <?php if ($basarili): ?>
    <div class="rezervasyon-tamam">
      <div class="rt-ikon">✓</div>
      <h2>Rezervasyon Talebiniz Alındı!</h2>
      <p>Rezervasyon Numarası: <strong><?= e($rezNo) ?></strong></p>
      <p>En kısa sürede sizinle iletişime geçeceğiz. Bu numarayı saklayın.</p>
      <div class="cta-butonlar">
        <a href="<?= url() ?>" class="btn btn-birincil">Ana Sayfa</a>
        <a href="<?= url('araclar') ?>" class="btn btn-cerceve">Araçlara Dön</a>
      </div>
    </div>
    <?php else: ?>

    <?php if ($hata): ?>
    <div class="alert alert-hata"><?= e($hata) ?></div>
    <?php endif; ?>

    <form method="post" action="" class="rez-form">
      <?= csrf_input() ?>
      <input type="hidden" name="rezervasyon_gonder" value="1">

      <div class="rez-grid">
        <div class="rez-sol">
          <h3>Rezervasyon Bilgileri</h3>

          <div class="form-grup">
            <label>Araç Seçin *</label>
            <select name="arac_id" id="aracSec" required onchange="fiyatHesapla()">
              <option value="">-- Araç Seçin --</option>
              <?php foreach ($araclar as $a): ?>
              <option value="<?= (int)$a['id'] ?>" data-fiyat="<?= e($a['gunluk_fiyat']) ?>" <?= ($secilenArac === (int)$a['id'] || ($_POST['arac_id'] ?? 0) == $a['id']) ? 'selected' : '' ?>>
                <?= e($a['marka'] . ' ' . $a['model']) ?> - <?= tl($a['gunluk_fiyat']) ?>/gün
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-grup-grid">
            <div class="form-grup">
              <label>Alış Tarihi *</label>
              <input type="datetime-local" name="alis_tarihi" id="alisTarihi" required min="<?= date('Y-m-d\TH:i') ?>" value="<?= e($_POST['alis_tarihi'] ?? '') ?>" onchange="fiyatHesapla()">
            </div>
            <div class="form-grup">
              <label>İade Tarihi *</label>
              <input type="datetime-local" name="iade_tarihi" id="iadeTarihi" required value="<?= e($_POST['iade_tarihi'] ?? '') ?>" onchange="fiyatHesapla()">
            </div>
          </div>

          <div class="form-grup-grid">
            <div class="form-grup">
              <label>Alış Yeri</label>
              <input type="text" name="alis_yeri" placeholder="Örn: Konya Havalimanı" value="<?= e($_POST['alis_yeri'] ?? '') ?>">
            </div>
            <div class="form-grup">
              <label>İade Yeri</label>
              <input type="text" name="iade_yeri" placeholder="Örn: Konya Şehir Merkezi" value="<?= e($_POST['iade_yeri'] ?? '') ?>">
            </div>
          </div>

          <h3 style="margin-top:30px">Kişisel Bilgiler</h3>

          <div class="form-grup-grid">
            <div class="form-grup">
              <label>Ad *</label>
              <input type="text" name="ad" required value="<?= e($_POST['ad'] ?? '') ?>">
            </div>
            <div class="form-grup">
              <label>Soyad *</label>
              <input type="text" name="soyad" required value="<?= e($_POST['soyad'] ?? '') ?>">
            </div>
          </div>

          <div class="form-grup-grid">
            <div class="form-grup">
              <label>Telefon *</label>
              <input type="tel" name="telefon" required value="<?= e($_POST['telefon'] ?? '') ?>">
            </div>
            <div class="form-grup">
              <label>E-Posta</label>
              <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <div class="form-grup">
            <label>Notlar</label>
            <textarea name="notlar" rows="3"><?= e($_POST['notlar'] ?? '') ?></textarea>
          </div>

          <div class="form-grup form-onay">
            <label>
              <input type="checkbox" required>
              <span>
                <a href="<?= url('kvkk') ?>" target="_blank">KVKK Aydınlatma Metni</a> ve
                <a href="<?= url('gizlilik-politikasi') ?>" target="_blank">Gizlilik Politikasını</a> okudum, kabul ediyorum.
              </span>
            </label>
          </div>
        </div>

        <div class="rez-sag">
          <div class="rez-ozet" id="rezOzet">
            <h3>Rezervasyon Özeti</h3>
            <div class="ozet-satir"><span>Günlük Fiyat:</span><strong id="ozGunluk">-</strong></div>
            <div class="ozet-satir"><span>Süre:</span><strong id="ozGun">-</strong></div>
            <div class="ozet-satir"><span>Ara Toplam:</span><strong id="ozAra">-</strong></div>
            <div class="ozet-satir"><span>KDV:</span><strong id="ozKdv">-</strong></div>
            <hr>
            <div class="ozet-satir ozet-toplam"><span>Toplam:</span><strong id="ozToplam">-</strong></div>
            <button type="submit" class="btn btn-birincil btn-blok btn-buyuk" style="margin-top:20px">Rezervasyon Talep Et</button>
            <small style="display:block;margin-top:12px;color:#64748b">* Rezervasyon talebiniz onaylandıktan sonra ödeme bilgileri tarafınıza iletilecektir.</small>
          </div>
        </div>
      </div>
    </form>
    <?php endif; ?>
  </div>
</section>

<script>
async function fiyatHesapla() {
  const arac = document.getElementById('aracSec');
  const alis = document.getElementById('alisTarihi').value;
  const iade = document.getElementById('iadeTarihi').value;
  if (!arac.value || !alis || !iade) return;
  const fd = new FormData();
  fd.append('arac_id', arac.value);
  fd.append('alis_tarihi', alis);
  fd.append('iade_tarihi', iade);
  try {
    const r = await fetch('<?= url('?sayfa=api&islem=fiyat_hesapla') ?>', { method: 'POST', body: fd });
    const d = await r.json();
    if (d.hata) return;
    document.getElementById('ozGunluk').textContent = d.gunluk_format;
    document.getElementById('ozGun').textContent = d.gun + ' gün';
    document.getElementById('ozAra').textContent = d.ara_format;
    document.getElementById('ozKdv').textContent = d.kdv_format;
    document.getElementById('ozToplam').textContent = d.toplam_format;
  } catch(e) {}
}
document.addEventListener('DOMContentLoaded', fiyatHesapla);
</script>
