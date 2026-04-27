<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <h1>Hakkımızda</h1>
    <p><?= e(ayar('site_baslik','ATA SU Rent A Car')) ?></p>
  </div>
</section>

<section class="bolum">
  <div class="kapsayici">
    <div class="hakkimizda-detay">
      <?= ayar('hakkimizda_detay','<p>İçerik henüz hazır değil.</p>') ?>
    </div>

    <div class="avantaj-grid" style="margin-top:60px">
      <div class="avantaj"><div class="avantaj-ikon">🚗</div><h4>Geniş Filo</h4><p>Her ihtiyaca uygun araç</p></div>
      <div class="avantaj"><div class="avantaj-ikon">💰</div><h4>Uygun Fiyat</h4><p>Bütçeye dost seçenekler</p></div>
      <div class="avantaj"><div class="avantaj-ikon">🕐</div><h4>7/24 Destek</h4><p>Her zaman yanınızdayız</p></div>
      <div class="avantaj"><div class="avantaj-ikon">🛡️</div><h4>Güvence</h4><p>Sigorta dahil hizmet</p></div>
    </div>
  </div>
</section>
