<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= url() ?>">Ana Sayfa</a> /
      <a href="<?= url('araclar') ?>">Araçlarımız</a> /
      <?php if (!empty($arac['kategori_ad'])): ?>
      <a href="<?= url('araclar?sinif=' . slug_olustur($arac['kategori_ad'])) ?>"><?= e($arac['kategori_ad']) ?></a> /
      <?php endif; ?>
      <span><?= e($arac['marka'] . ' ' . $arac['model']) ?></span>
    </nav>
    <h1><?= e($arac['marka'] . ' ' . $arac['model']) ?> Kiralama Konya</h1>
    <p style="color:#cbd5e1;margin-top:8px;"><?= e($arac['yil']) ?> model · <?= e($arac['vites']) ?> vites · <?= e($arac['yakit']) ?> · Günlük <?= tl($arac['gunluk_fiyat']) ?></p>
  </div>
</section>

<section class="bolum">
  <div class="kapsayici">
    <div class="arac-detay">
      <!-- GALERI -->
      <div class="arac-galeri">
        <?php if ($resimler): ?>
        <div class="galeri-ana">
          <img src="<?= e(upload_url('araclar/' . $resimler[0]['dosya'])) ?>" alt="<?= e($arac['marka'] . ' ' . $arac['model'] . ' kiralama Konya - ' . $arac['yil'] . ' model') ?>" id="galeriAna" width="800" height="500" fetchpriority="high">
        </div>
        <?php if (count($resimler) > 1): ?>
        <div class="galeri-kucuk">
          <?php foreach ($resimler as $i => $r): ?>
          <img src="<?= e(upload_url('araclar/' . $r['dosya'])) ?>" alt="<?= e($arac['marka'] . ' ' . $arac['model']) ?> görsel <?= $i + 1 ?>" onclick="document.getElementById('galeriAna').src=this.src" loading="lazy" width="100" height="70">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <div class="galeri-ana"><div class="arac-resim-yok" aria-label="<?= e($arac['marka'] . ' ' . $arac['model']) ?>">🚗</div></div>
        <?php endif; ?>
      </div>

      <!-- BILGI -->
      <div class="arac-detay-bilgi">
        <div class="detay-baslik">
          <?php if ($arac['kategori_ad']): ?>
          <span class="kategori-rozet"><?= e($arac['kategori_ad']) ?></span>
          <?php endif; ?>
          <span class="durum-rozet durum-<?= e($arac['durum']) ?>">
            <?= $arac['durum'] === 'musait' ? 'Müsait' : ($arac['durum'] === 'kirada' ? 'Kirada' : ($arac['durum'] === 'rezerve' ? 'Rezerve' : 'Bakımda')) ?>
          </span>
        </div>

        <h2><?= e($arac['marka'] . ' ' . $arac['model'] . ' (' . $arac['yil'] . ')') ?></h2>

        <div class="detay-ozellikler">
          <div class="ozellik">
            <span class="oz-ikon">📅</span>
            <div><strong><?= e($arac['yil']) ?></strong><small>Model Yılı</small></div>
          </div>
          <div class="ozellik">
            <span class="oz-ikon">⚙️</span>
            <div><strong><?= e($arac['vites']) ?></strong><small>Vites</small></div>
          </div>
          <div class="ozellik">
            <span class="oz-ikon">⛽</span>
            <div><strong><?= e($arac['yakit']) ?></strong><small>Yakıt</small></div>
          </div>
          <div class="ozellik">
            <span class="oz-ikon">👥</span>
            <div><strong><?= e($arac['koltuk_sayisi']) ?></strong><small>Koltuk</small></div>
          </div>
          <div class="ozellik">
            <span class="oz-ikon">🧳</span>
            <div><strong><?= e($arac['bagaj_sayisi']) ?></strong><small>Bavul</small></div>
          </div>
          <div class="ozellik">
            <span class="oz-ikon">🚪</span>
            <div><strong><?= e($arac['kapi_sayisi']) ?></strong><small>Kapı</small></div>
          </div>
          <?php if ($arac['motor_hacmi']): ?>
          <div class="ozellik">
            <span class="oz-ikon">🏎️</span>
            <div><strong><?= e($arac['motor_hacmi']) ?></strong><small>Motor</small></div>
          </div>
          <?php endif; ?>
          <?php if ($arac['klima']): ?>
          <div class="ozellik">
            <span class="oz-ikon">❄️</span>
            <div><strong>Var</strong><small>Klima</small></div>
          </div>
          <?php endif; ?>
        </div>

        <div class="detay-fiyat-kutu">
          <div class="fiyat-grid">
            <div>
              <small>Günlük</small>
              <strong><?= tl($arac['gunluk_fiyat']) ?></strong>
            </div>
            <?php if ($arac['haftalik_fiyat'] > 0): ?>
            <div>
              <small>Haftalık</small>
              <strong><?= tl($arac['haftalik_fiyat']) ?></strong>
            </div>
            <?php endif; ?>
            <?php if ($arac['aylik_fiyat'] > 0): ?>
            <div>
              <small>Aylık</small>
              <strong><?= tl($arac['aylik_fiyat']) ?></strong>
            </div>
            <?php endif; ?>
          </div>
          <small class="fiyat-aciklama">* Fiyatlara KDV dahil değildir.</small>
        </div>

        <div class="detay-kosullar">
          <h4>Kiralama Koşulları</h4>
          <ul>
            <li>✓ Minimum yaş: <?= e($arac['min_yas']) ?></li>
            <li>✓ Minimum ehliyet: <?= e($arac['min_ehliyet_yili']) ?> yıl</li>
            <?php if ($arac['depozito'] > 0): ?>
            <li>✓ Depozito: <?= tl($arac['depozito']) ?></li>
            <?php endif; ?>
            <li>✓ Sigorta dahil</li>
          </ul>
        </div>

        <div class="detay-butonlar">
          <a href="<?= url('rezervasyon?arac=' . $arac['id']) ?>" class="btn btn-birincil btn-buyuk">Rezervasyon Yap</a>
          <a href="tel:<?= e(preg_replace('/\s+/','',ayar('telefon',''))) ?>" class="btn btn-cerceve btn-buyuk">📞 Hemen Ara</a>
        </div>
      </div>
    </div>

    <?php if ($arac['aciklama']): ?>
    <div class="detay-aciklama">
      <h3>Araç Hakkında</h3>
      <div class="metin"><?= nl2br(e($arac['aciklama'])) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($arac['ozellikler']): ?>
    <div class="detay-aciklama">
      <h3>Donanım Özellikleri</h3>
      <div class="metin"><?= nl2br(e($arac['ozellikler'])) ?></div>
    </div>
    <?php endif; ?>

    <?php if ($ekHizmetler): ?>
    <div class="detay-aciklama">
      <h3>Ek Hizmetler</h3>
      <div class="ek-hizmet-grid">
        <?php foreach ($ekHizmetler as $eh): ?>
        <div class="ek-hizmet-kart">
          <strong><?= e($eh['ad']) ?></strong>
          <?php if ($eh['aciklama']): ?><p><?= e($eh['aciklama']) ?></p><?php endif; ?>
          <span><?= tl($eh['fiyat']) ?> / <?= $eh['fiyat_tipi'] === 'gunluk' ? 'gün' : 'tek' ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
