<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <h1>Blog</h1>
    <p>Araç kiralama ve seyahat üzerine yazılarımız</p>
  </div>
</section>

<section class="bolum">
  <div class="kapsayici">
    <?php if ($yazilar): ?>
    <div class="blog-grid">
      <?php foreach ($yazilar as $b): ?>
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
          <p><?= e(kisalt($b['ozet'] ?? strip_tags($b['icerik'] ?? ''), 140)) ?></p>
          <a href="<?= url('blog/' . $b['slug']) ?>" class="blog-link">Devamını Oku →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="bilgi">Henüz yazı eklenmemiş.</div>
    <?php endif; ?>
  </div>
</section>
