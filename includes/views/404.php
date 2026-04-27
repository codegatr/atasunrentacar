<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <h1>404</h1>
    <p>Aradiginiz sayfa bulunamadi</p>
  </div>
</section>

<section class="bolum">
  <div class="kapsayici" style="text-align:center;">
    <p style="margin-bottom:24px;">Aradiginiz sayfa silinmis veya tasinmis olabilir. Anasayfaya donerek devam edebilirsiniz.</p>
    <div class="cta-butonlar" style="justify-content:center;">
      <a href="<?= url() ?>" class="btn btn-birincil">Ana Sayfa</a>
      <a href="<?= url('araclar') ?>" class="btn btn-cerceve">Araçlarimiz</a>
    </div>
  </div>
</section>
