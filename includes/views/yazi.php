<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <nav class="breadcrumb">
      <a href="<?= url() ?>">Ana Sayfa</a> /
      <a href="<?= url('blog') ?>">Blog</a> /
      <span><?= e($yazi['baslik']) ?></span>
    </nav>
    <h1><?= e($yazi['baslik']) ?></h1>
    <p><time><?= tarih_tr($yazi['yayin_tarihi']) ?></time>
    <?php if ($yazi['yazar']): ?> · <?= e($yazi['yazar']) ?><?php endif; ?>
    · <?= number_format($yazi['goruntuleme']) ?> görüntüleme</p>
  </div>
</section>

<section class="bolum">
  <div class="kapsayici">
    <article class="yazi-icerik">
      <?php if ($yazi['kapak']): ?>
      <img src="<?= e(upload_url('blog/' . $yazi['kapak'])) ?>" alt="<?= e($yazi['baslik']) ?>" class="yazi-kapak">
      <?php endif; ?>
      <div class="metin">
        <?= $yazi['icerik'] ?>
      </div>
    </article>

    <?php if ($diger): ?>
    <div class="bolum-baslik" style="margin-top:60px">
      <h2>Diğer Yazılar</h2>
    </div>
    <div class="blog-grid">
      <?php foreach ($diger as $b): ?>
      <article class="blog-kart">
        <a href="<?= url('blog/' . $b['slug']) ?>" class="blog-kapak">
          <?php if ($b['kapak']): ?>
          <img src="<?= e(upload_url('blog/' . $b['kapak'])) ?>" alt="<?= e($b['baslik']) ?>" loading="lazy">
          <?php else: ?>
          <div class="blog-kapak-yok">📰</div>
          <?php endif; ?>
        </a>
        <div class="blog-ic">
          <time><?= tarih_tr($b['yayin_tarihi']) ?></time>
          <h3><a href="<?= url('blog/' . $b['slug']) ?>"><?= e($b['baslik']) ?></a></h3>
          <a href="<?= url('blog/' . $b['slug']) ?>" class="blog-link">Devamını Oku →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
