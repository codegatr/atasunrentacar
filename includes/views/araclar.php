<?php defined('ATASU') or exit('403'); ?>

<!-- Breadcrumbs -->
<nav aria-label="Breadcrumb" class="breadcrumb-bar" style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:10px 0;">
  <div class="kapsayici" style="font-size:14px;color:#64748b;">
    <a href="<?= url() ?>" style="color:#64748b;text-decoration:none;">Ana Sayfa</a>
    <span style="margin:0 8px;">›</span>
    <a href="<?= url('araclar') ?>" style="color:#64748b;text-decoration:none;">Araçlarımız</a>
    <?php if (!empty($kategoriAdi)): ?>
      <span style="margin:0 8px;">›</span>
      <span style="color:#1e3a5f;"><?= e($kategoriAdi) ?></span>
    <?php endif; ?>
  </div>
</nav>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <h1><?= !empty($kategoriAdi) ? e($kategoriAdi) . ' Sınıfı Araç Kiralama Konya' : 'Araç Kiralama Konya - Tüm Filomuz' ?></h1>
    <p>
      <?php if (!empty($kategoriAdi)): ?>
        Konya'da <strong><?= e(mb_strtolower($kategoriAdi, 'UTF-8')) ?></strong> sınıfı kiralık araç seçenekleri. Uygun fiyat, anlık rezervasyon, hızlı teslim.
      <?php else: ?>
        Ekonomi, konfor, SUV, lüks ve ticari araç seçeneklerimizden ihtiyacınıza uygun olanı seçin. <?= count($araclar) ?>+ araç filomuzla Konya araç kiralama hizmetinde lideriz.
      <?php endif; ?>
    </p>
  </div>
</section>

<section class="bolum" style="padding:40px 0 60px;">
  <div class="kapsayici">
    <div class="liste-duzen">
      <!-- FILTRE -->
      <aside class="liste-filtre">
        <form method="get" action="">
          <h3>Filtrele</h3>
          <div class="form-grup">
            <label>Kategori</label>
            <select name="sinif" onchange="this.form.submit()">
              <option value="">Tümü</option>
              <?php foreach ($kategoriler as $k): ?>
              <option value="<?= e($k['slug']) ?>" <?= ($kategori === $k['slug']) ? 'selected' : '' ?>><?= e($k['ad']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-grup">
            <label>Vites</label>
            <select name="vites" onchange="this.form.submit()">
              <option value="">Tümü</option>
              <option value="Manuel" <?= ($vites === 'Manuel') ? 'selected' : '' ?>>Manuel</option>
              <option value="Otomatik" <?= ($vites === 'Otomatik') ? 'selected' : '' ?>>Otomatik</option>
            </select>
          </div>
          <div class="form-grup">
            <label>Yakıt</label>
            <select name="yakit" onchange="this.form.submit()">
              <option value="">Tümü</option>
              <?php foreach (['Benzin','Motorin','LPG','Hibrit','Elektrik'] as $y): ?>
              <option value="<?= e($y) ?>" <?= ($yakit === $y) ? 'selected' : '' ?>><?= e($y) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-birincil btn-blok">Uygula</button>
          <a href="<?= url('araclar') ?>" class="btn btn-cerceve btn-blok" style="margin-top:8px">Temizle</a>
        </form>
      </aside>

      <!-- LISTE -->
      <div class="liste-icerik">
        <div class="liste-toplam"><?= count($araclar) ?> araç bulundu</div>

        <?php if (count($araclar)): ?>
        <div class="arac-grid">
          <?php foreach ($araclar as $a): ?>
          <article class="arac-kart" itemscope itemtype="https://schema.org/Vehicle">
            <div class="arac-resim">
              <?php if ($a['ana_resim']): ?>
                <img src="<?= e(upload_url('araclar/' . $a['ana_resim'])) ?>" alt="<?= e($a['marka'] . ' ' . $a['model'] . ' kiralama Konya - ' . $a['yil']) ?>" loading="lazy" width="400" height="250" itemprop="image">
              <?php else: ?>
                <div class="arac-resim-yok" aria-label="<?= e($a['marka'] . ' ' . $a['model']) ?>">🚗</div>
              <?php endif; ?>
              <?php if ($a['kategori_ad']): ?>
              <span class="arac-etiket"><?= e($a['kategori_ad']) ?></span>
              <?php endif; ?>
              <span class="arac-durum durum-<?= e($a['durum']) ?>">
                <?= $a['durum'] === 'musait' ? 'Müsait' : ($a['durum'] === 'kirada' ? 'Kirada' : ($a['durum'] === 'rezerve' ? 'Rezerve' : 'Bakımda')) ?>
              </span>
            </div>
            <div class="arac-bilgi">
              <h2 style="font-size:1.15rem;margin:0 0 8px;" itemprop="name"><a href="<?= url('arac/' . $a['slug']) ?>" style="color:inherit;text-decoration:none;" title="<?= e($a['marka'] . ' ' . $a['model']) ?> kiralama detayı"><?= e($a['marka'] . ' ' . $a['model']) ?></a></h2>
              <ul class="arac-ozet">
                <li><span itemprop="vehicleModelDate"><?= e($a['yil']) ?></span></li>
                <li><span itemprop="vehicleTransmission"><?= e($a['vites']) ?></span></li>
                <li><span itemprop="seatingCapacity"><?= e($a['koltuk_sayisi']) ?></span> Koltuk</li>
                <li><?= e($a['bagaj_sayisi']) ?> Bavul</li>
                <li><span itemprop="fuelType"><?= e($a['yakit']) ?></span></li>
              </ul>
              <div class="arac-fiyat">
                <span><?= tl($a['gunluk_fiyat']) ?></span> / günlük
              </div>
              <a href="<?= url('arac/' . $a['slug']) ?>" class="btn btn-birincil btn-blok" title="<?= e($a['marka'] . ' ' . $a['model']) ?> kirala">Detay &amp; Kirala</a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bilgi">Aradığınız kriterlere uygun araç bulunamadı. <a href="<?= url('araclar') ?>">Tüm araçları görüntüle</a> veya <a href="<?= url('iletisim') ?>">bizimle iletişime geçin</a>.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- Alt SEO icerik -->
<?php if (!empty($araclar)): ?>
<section class="bolum bolum-acik" style="padding:40px 0;">
  <div class="kapsayici">
    <div style="max-width:880px;margin:0 auto;font-size:0.95rem;">
      <?php if (!empty($kategoriAdi)): ?>
        <h2><?= e($kategoriAdi) ?> Sınıfı Araç Kiralama - Konya</h2>
        <p style="line-height:1.8;color:#334155;">
          <?= e($kategoriAdi) ?> sınıfı araç kiralama, Konya'da <strong>uygun fiyat</strong> ve <strong>kaliteli hizmet</strong> arayanların ilk tercihidir. ATA SU Rent A Car olarak <?= e(mb_strtolower($kategoriAdi, 'UTF-8')) ?> segmentinde ihtiyacınızı karşılayan farklı modellerimiz mevcuttur. Günlük, haftalık ve aylık kiralama seçenekleriyle her bütçeye uygun çözüm sunuyoruz.
        </p>
        <p style="line-height:1.8;color:#334155;">
          Tüm <?= e(mb_strtolower($kategoriAdi, 'UTF-8')) ?> sınıfı araçlarımız düzenli bakımdan geçer; zorunlu trafik sigortası ve isteğe bağlı tam kasko ile güvenle yolculuk edersiniz. Konya merkezi, havalimanı ve adrese teslim hizmetimizden faydalanabilirsiniz.
        </p>
      <?php else: ?>
        <h2>Konya'da Araç Kiralama - Geniş Filo, Uygun Fiyat</h2>
        <p style="line-height:1.8;color:#334155;">
          Konya araç kiralama hizmetinde geniş filomuzla yanınızdayız. <strong>Ekonomi sınıfı</strong> hesaplı seçeneklerden <strong>SUV</strong> ve <strong>lüks segmente</strong>, kompakt sedanlardan <strong>ticari araç kiralama</strong> alternatiflerine kadar her ihtiyaca uygun model bulabilirsiniz. <strong>Otomatik veya manuel vites</strong>, benzin, dizel ve hibrit yakıt tipleriyle tercih sizin.
        </p>
        <p style="line-height:1.8;color:#334155;">
          <strong>Günlük araç kiralama</strong>, <strong>haftalık araç kiralama</strong> ve <strong>aylık araç kiralama</strong> seçeneklerinde indirimli fiyatlarımızla bütçenize uygun çözüm sunuyoruz. Konya Havalimanı (KYA) için özel teslim hizmetimiz, otele veya iş yerine adrese teslimat opsiyonumuz mevcuttur.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
