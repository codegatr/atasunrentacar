<?php defined('ATASU') or exit('403'); ?>

<section class="sayfa-baslik">
  <div class="kapsayici">
    <h1>İletişim</h1>
    <p>Bize ulaşın, sorularınızı yanıtlayalım</p>
  </div>
</section>

<section class="bolum">
  <div class="kapsayici">
    <div class="iletisim-grid">
      <!-- BILGI -->
      <div class="iletisim-bilgi">
        <h3>Bize Ulaşın</h3>

        <div class="il-kart">
          <div class="il-ikon">📍</div>
          <div>
            <strong>Adres</strong>
            <p><?= e(ayar('adres','')) ?></p>
          </div>
        </div>

        <div class="il-kart">
          <div class="il-ikon">📞</div>
          <div>
            <strong>Telefon</strong>
            <p><a href="tel:<?= e(preg_replace('/\s+/','',ayar('telefon',''))) ?>"><?= e(ayar('telefon','')) ?></a></p>
            <?php if (ayar('telefon2')): ?>
            <p><a href="tel:<?= e(preg_replace('/\s+/','',ayar('telefon2',''))) ?>"><?= e(ayar('telefon2','')) ?></a></p>
            <?php endif; ?>
          </div>
        </div>

        <?php if (ayar('whatsapp')): ?>
        <div class="il-kart">
          <div class="il-ikon">💬</div>
          <div>
            <strong>WhatsApp</strong>
            <p><a href="https://wa.me/<?= e(ayar('whatsapp')) ?>" target="_blank" rel="noopener">+<?= e(ayar('whatsapp')) ?></a></p>
          </div>
        </div>
        <?php endif; ?>

        <div class="il-kart">
          <div class="il-ikon">✉️</div>
          <div>
            <strong>E-Posta</strong>
            <p><a href="mailto:<?= e(ayar('email','')) ?>"><?= e(ayar('email','')) ?></a></p>
          </div>
        </div>

        <div class="il-kart">
          <div class="il-ikon">🕐</div>
          <div>
            <strong>Çalışma Saatleri</strong>
            <p><?= e(ayar('calisma_saati','7/24 Hizmet')) ?></p>
          </div>
        </div>
      </div>

      <!-- FORM -->
      <div class="iletisim-form">
        <h3>Mesaj Gönderin</h3>
        <?php if ($basarili): ?>
        <div class="alert alert-basarili">Mesajınız başarıyla gönderildi. En kısa sürede dönüş yapacağız.</div>
        <?php endif; ?>
        <?php if ($hata): ?>
        <div class="alert alert-hata"><?= e($hata) ?></div>
        <?php endif; ?>

        <form method="post" action="">
          <?= csrf_input() ?>
          <input type="hidden" name="iletisim_gonder" value="1">

          <div class="form-grup">
            <label>Ad Soyad *</label>
            <input type="text" name="ad_soyad" required minlength="3" value="<?= e($_POST['ad_soyad'] ?? '') ?>">
          </div>
          <div class="form-grup-grid">
            <div class="form-grup">
              <label>E-Posta *</label>
              <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-grup">
              <label>Telefon</label>
              <input type="tel" name="telefon" value="<?= e($_POST['telefon'] ?? '') ?>">
            </div>
          </div>
          <div class="form-grup">
            <label>Konu</label>
            <input type="text" name="konu" value="<?= e($_POST['konu'] ?? '') ?>">
          </div>
          <div class="form-grup">
            <label>Mesaj *</label>
            <textarea name="mesaj" required minlength="10" rows="6"><?= e($_POST['mesaj'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-birincil btn-blok">Mesajı Gönder</button>
        </form>
      </div>
    </div>

    <?php if (ayar('harita_embed')): ?>
    <div class="harita">
      <iframe src="<?= e(ayar('harita_embed')) ?>" width="100%" height="450" style="border:0;border-radius:12px" allowfullscreen loading="lazy"></iframe>
    </div>
    <?php endif; ?>
  </div>
</section>
