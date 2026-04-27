<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <h1><?= e($baslik ?? 'Yasal Metin') ?></h1>
    <div class="breadcrumb">
      <a href="<?= url() ?>">Ana Sayfa</a> &raquo; <?= e($baslik ?? '') ?>
    </div>
  </div>
</section>

<section class="hakkimizda-detay">
  <div class="kapsayici">
    <div class="metin">
      <?php if (!empty($icerik)): ?>
        <?= $icerik ?>
      <?php else: ?>
        <p>Bu sayfanin icerigi henuz duzenlenmemistir.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
