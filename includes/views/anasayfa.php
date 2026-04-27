<?php defined('ATASU') or exit('403'); ?>

<!-- HERO -->
<section class="hero">
  <div class="hero-arka"></div>
  <div class="kapsayici hero-icerik">
    <div class="hero-metin">
      <span class="hero-rozet"><?= e(ayar('hero_alt_baslik','Konya\'nın Güvenilir Araç Kiralama Firması')) ?></span>
      <h1><?= e(ayar('hero_baslik','Hayalinizdeki Araçla Yola Çıkın')) ?></h1>
      <p><?= e(ayar('hero_aciklama','')) ?></p>
      <div class="hero-butonlar">
        <a href="<?= url('rezervasyon') ?>" class="btn btn-birincil">Hemen Rezervasyon Yap</a>
        <a href="<?= url('araclar') ?>" class="btn btn-cerceve">Araçları Gör</a>
      </div>
    </div>

    <div class="hero-istatistik">
      <div class="ist-kutu">
        <div class="ist-rakam"><?= count(DB::liste("SELECT id FROM " . DB::tablo('araclar') . " WHERE aktif=1")) ?>+</div>
        <div class="ist-etiket">Araç</div>
      </div>
      <div class="ist-kutu">
        <div class="ist-rakam"><?= e(ayar('mutlu_musteri','500')) ?>+</div>
        <div class="ist-etiket">Mutlu Müşteri</div>
      </div>
      <div class="ist-kutu">
        <div class="ist-rakam">7/24</div>
        <div class="ist-etiket">Hizmet</div>
      </div>
      <div class="ist-kutu">
        <div class="ist-rakam"><?= e(ayar('puan','5')) ?>★</div>
        <div class="ist-etiket">Değerlendirme</div>
      </div>
    </div>
  </div>
</section>

<!-- ARAMA FORMU -->
<section class="arama-bolumu">
  <div class="kapsayici">
    <form class="arama-form" action="<?= url('araclar') ?>" method="get">
      <h3>Araç Kirala</h3>
      <div class="arama-grid">
        <div class="form-grup">
          <label>Araç Tipi</label>
          <select name="sinif">
            <option value="">Tüm Kategoriler</option>
            <?php foreach ($kategoriler as $k): ?>
            <option value="<?= e($k['slug']) ?>"><?= e($k['ad']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-grup">
          <label>Alış Tarihi</label>
          <input type="date" name="alis" min="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-grup">
          <label>İade Tarihi</label>
          <input type="date" name="iade" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>
        <div class="form-grup">
          <label>Vites</label>
          <select name="vites">
            <option value="">Farketmez</option>
            <option value="Manuel">Manuel</option>
            <option value="Otomatik">Otomatik</option>
          </select>
        </div>
        <div class="form-grup form-buton">
          <label>&nbsp;</label>
          <button type="submit" class="btn btn-birincil btn-blok">Araç Ara</button>
        </div>
      </div>
    </form>
  </div>
</section>

<!-- AVANTAJLAR -->
<section class="avantajlar">
  <div class="kapsayici">
    <div class="avantaj-grid">
      <div class="avantaj">
        <div class="avantaj-ikon">🚗</div>
        <h4>Geniş Araç Filosu</h4>
        <p>Her ihtiyaca uygun araç</p>
      </div>
      <div class="avantaj">
        <div class="avantaj-ikon">💰</div>
        <h4>Uygun Fiyatlar</h4>
        <p>Bütçeye dost seçenekler</p>
      </div>
      <div class="avantaj">
        <div class="avantaj-ikon">🕐</div>
        <h4>7/24 Destek</h4>
        <p>Her zaman yanınızdayız</p>
      </div>
      <div class="avantaj">
        <div class="avantaj-ikon">✈️</div>
        <h4>Havalimanı Teslim</h4>
        <p>Kolay ulaşım garantili</p>
      </div>
      <div class="avantaj">
        <div class="avantaj-ikon">🛡️</div>
        <h4>Güvenceli Kiralama</h4>
        <p>Sigorta dahil hizmet</p>
      </div>
    </div>
  </div>
</section>

<!-- POPULER ARACLAR -->
<section class="bolum">
  <div class="kapsayici">
    <div class="bolum-baslik">
      <span class="bolum-ust">Araç Filomuz</span>
      <h2>Popüler Araçlarımız</h2>
      <p>Konfor, ekonomi ve performansı bir arada sunan araçlarımızla yolculuğunuzu unutulmaz kılın.</p>
    </div>

    <?php if ($oneCikan): ?>
    <div class="arac-grid">
      <?php foreach ($oneCikan as $a): ?>
      <div class="arac-kart">
        <div class="arac-resim">
          <?php if ($a['ana_resim']): ?>
            <img src="<?= e(upload_url('araclar/' . $a['ana_resim'])) ?>" alt="<?= e($a['marka'] . ' ' . $a['model'] . ' kiralama Konya - ' . ($a['yil'] ?? '')) ?>" loading="lazy" width="400" height="250">
          <?php else: ?>
            <div class="arac-resim-yok">🚗</div>
          <?php endif; ?>
          <?php if ($a['kategori_ad']): ?>
          <span class="arac-etiket"><?= e($a['kategori_ad']) ?></span>
          <?php endif; ?>
          <span class="arac-durum durum-<?= e($a['durum']) ?>">
            <?= $a['durum'] === 'musait' ? 'Müsait' : ($a['durum'] === 'kirada' ? 'Kirada' : ($a['durum'] === 'rezerve' ? 'Rezerve' : 'Bakımda')) ?>
          </span>
        </div>
        <div class="arac-bilgi">
          <h3><a href="<?= url('arac/' . $a['slug']) ?>" style="color:inherit;text-decoration:none;"><?= e($a['marka'] . ' ' . $a['model']) ?></a></h3>
          <ul class="arac-ozet">
            <li><?= e($a['yil']) ?></li>
            <li><?= e($a['vites']) ?></li>
            <li><?= e($a['koltuk_sayisi']) ?> Koltuk</li>
            <li><?= e($a['bagaj_sayisi']) ?> Bavul</li>
            <li><?= e($a['yakit']) ?></li>
          </ul>
          <div class="arac-fiyat">
            <span><?= tl($a['gunluk_fiyat']) ?></span> / günlük
          </div>
          <a href="<?= url('arac/' . $a['slug']) ?>" class="btn btn-birincil btn-blok" title="<?= e($a['marka'] . ' ' . $a['model']) ?> kirala">Kirala</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="bolum-buton">
      <a href="<?= url('araclar') ?>" class="btn btn-cerceve">Tüm Araçları Gör</a>
    </div>
    <?php else: ?>
    <p class="bilgi">Henüz araç eklenmedi.</p>
    <?php endif; ?>
  </div>
</section>

<!-- HAKKIMIZDA -->
<section class="bolum bolum-koyu">
  <div class="kapsayici">
    <div class="hakkimizda-grid">
      <div>
        <span class="bolum-ust">Hakkımızda</span>
        <h2><?= e(ayar('site_baslik','ATA SU Rent A Car')) ?> Kimdir?</h2>
        <?= ayar('hakkimizda_detay','') ?>
        <ul class="hak-liste">
          <li>✓ 7/24 Kesintisiz Müşteri Hizmetleri</li>
          <li>✓ Havalimanı &amp; Adrese Ücretsiz Teslimat</li>
          <li>✓ Tam Kasko ve Sigorta Güvencesi</li>
          <li>✓ Kurumsal Araç Kiralama Çözümleri</li>
          <li>✓ Temiz ve Bakımlı Araç Garantisi</li>
        </ul>
        <div class="hakkimizda-butonlar">
          <a href="<?= url('hakkimizda') ?>" class="btn btn-birincil">Daha Fazla Bilgi</a>
          <a href="tel:<?= e(preg_replace('/\s+/','',ayar('telefon',''))) ?>" class="btn btn-cerceve"><?= e(ayar('telefon','')) ?></a>
        </div>
      </div>
      <div class="hakkimizda-stat">
        <div class="stat-buyuk">
          <div class="stat-rakam"><?= e(ayar('hizmet_yili','5')) ?>+</div>
          <div class="stat-etiket">Yıl Deneyim</div>
        </div>
        <div class="stat-buyuk">
          <div class="stat-rakam"><?= count(DB::liste("SELECT id FROM " . DB::tablo('araclar') . " WHERE aktif=1 AND durum IN ('musait','rezerve')")) ?></div>
          <div class="stat-etiket">Müsait Araç</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- YORUMLAR -->
<?php if ($yorumlar): ?>
<section class="bolum">
  <div class="kapsayici">
    <div class="bolum-baslik">
      <span class="bolum-ust">Müşteri Yorumları</span>
      <h2>Müşterilerimiz Ne Diyor?</h2>
    </div>
    <div class="yorum-grid">
      <?php foreach ($yorumlar as $y): ?>
      <div class="yorum-kart">
        <div class="yorum-puan"><?= str_repeat('★', (int)$y['puan']) ?></div>
        <p>"<?= e($y['yorum']) ?>"</p>
        <div class="yorum-kisi">
          <div class="yorum-baslangic"><?= e(mb_substr($y['ad'], 0, 1)) ?></div>
          <div>
            <strong><?= e($y['ad']) ?></strong>
            <?php if ($y['sehir']): ?><span><?= e($y['sehir']) ?></span><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- BLOG -->
<?php if ($bloglar): ?>
<section class="bolum bolum-acik">
  <div class="kapsayici">
    <div class="bolum-baslik bolum-baslik-yatay">
      <div>
        <span class="bolum-ust">Blog</span>
        <h2>Son Yazılarımız</h2>
      </div>
      <a href="<?= url('blog') ?>" class="btn btn-cerceve">Tümünü Gör</a>
    </div>
    <div class="blog-grid">
      <?php foreach ($bloglar as $b): ?>
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
          <p><?= e(kisalt($b['ozet'] ?? strip_tags($b['icerik'] ?? ''), 120)) ?></p>
          <a href="<?= url('blog/' . $b['slug']) ?>" class="blog-link">Devamını Oku →</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- KATEGORI BAGLANTILARI (Internal Linking + SEO) -->
<?php if ($kategoriler): ?>
<section class="bolum bolum-acik">
  <div class="kapsayici">
    <div class="bolum-baslik">
      <span class="bolum-ust">Araç Kategorileri</span>
      <h2>Konya Araç Kiralama Kategorilerimiz</h2>
      <p>İhtiyacınıza uygun aracı kolayca bulun: ekonomi, konfor, SUV, lüks ve ticari segmentlerde geniş seçenekler.</p>
    </div>
    <div class="kategori-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-top:30px;">
      <?php foreach ($kategoriler as $k):
        $arac_say = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE kategori_id = ? AND aktif = 1", [(int)$k['id']])['c'] ?? 0);
      ?>
      <a href="<?= url('araclar?sinif=' . $k['slug']) ?>" class="kategori-kart" style="display:block;padding:24px 18px;background:#fff;border-radius:12px;text-align:center;text-decoration:none;color:#1e3a5f;border:1px solid #e2e8f0;transition:all .2s;" title="<?= e($k['ad']) ?> sınıfı araç kiralama Konya">
        <div style="font-size:32px;margin-bottom:8px;">🚗</div>
        <h3 style="font-size:18px;margin:0 0 4px;"><?= e($k['ad']) ?> Kiralama</h3>
        <span style="color:#64748b;font-size:13px;"><?= $arac_say ?> araç mevcut</span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- SIK SORULAN SORULAR -->
<section class="bolum">
  <div class="kapsayici">
    <div class="bolum-baslik">
      <span class="bolum-ust">SSS</span>
      <h2>Konya Araç Kiralama Hakkında Sıkça Sorulan Sorular</h2>
    </div>
    <div class="sss-liste" style="max-width:850px;margin:30px auto 0;">
      <details class="sss-item" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;margin-bottom:12px;">
        <summary style="cursor:pointer;font-weight:600;color:#1e3a5f;font-size:17px;">Konya'da araç kiralamak için hangi belgeler gerekir?</summary>
        <p style="margin-top:12px;color:#475569;line-height:1.7;">Konya'da araç kiralamak için T.C. kimlik kartı, geçerli sürücü belgesi (en az 1 yıllık) ve kredi kartı/depozito gereklidir. Yabancı uyruklular için pasaport ve uluslararası ehliyet kabul edilir.</p>
      </details>
      <details class="sss-item" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;margin-bottom:12px;">
        <summary style="cursor:pointer;font-weight:600;color:#1e3a5f;font-size:17px;">Araç kiralamak için minimum yaş kaçtır?</summary>
        <p style="margin-top:12px;color:#475569;line-height:1.7;">ATA SU Rent A Car'da araç kiralamak için minimum 21 yaşında olmak ve en az 1 yıllık sürücü belgesine sahip olmak yeterlidir. Lüks araçlar için yaş şartı 25 olabilir.</p>
      </details>
      <details class="sss-item" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;margin-bottom:12px;">
        <summary style="cursor:pointer;font-weight:600;color:#1e3a5f;font-size:17px;">Konya havalimanına araç teslim ediyor musunuz?</summary>
        <p style="margin-top:12px;color:#475569;line-height:1.7;">Evet, Konya Havalimanı (KYA) için ücretli teslim ve karşılama hizmetimiz mevcuttur. Otele veya istediğiniz adrese de araç teslimi yapılabilir.</p>
      </details>
      <details class="sss-item" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;margin-bottom:12px;">
        <summary style="cursor:pointer;font-weight:600;color:#1e3a5f;font-size:17px;">Günlük araç kiralama fiyatları ne kadar?</summary>
        <p style="margin-top:12px;color:#475569;line-height:1.7;">Konya günlük araç kiralama fiyatları araç sınıfına göre değişir; ekonomi sınıfında uygun fiyatlardan başlar, SUV ve lüks segmente kadar geniş yelpaze sunar. Haftalık ve aylık kiralamalarda indirimli fiyatlar uygulanır. Güncel fiyatlar için <a href="<?= url('araclar') ?>">araç filomuzu</a> inceleyebilirsiniz.</p>
      </details>
      <details class="sss-item" style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:18px 22px;margin-bottom:12px;">
        <summary style="cursor:pointer;font-weight:600;color:#1e3a5f;font-size:17px;">Sigorta ve kasko dahil mi?</summary>
        <p style="margin-top:12px;color:#475569;line-height:1.7;">Tüm araçlarımızda zorunlu trafik sigortası standart olarak bulunur. Tam kasko güvencesi ek hizmet olarak sunulmaktadır; rezervasyon sırasında seçilebilir.</p>
      </details>
    </div>
  </div>
</section>

<!-- SEO ICERIK BLOGU -->
<section class="bolum bolum-acik">
  <div class="kapsayici">
    <div class="seo-icerik" style="max-width:920px;margin:0 auto;">
      <h2 style="text-align:center;margin-bottom:24px;">Neden Konya Araç Kiralama için ATA SU Rent A Car?</h2>
      <p style="line-height:1.8;color:#334155;font-size:16px;">
        <strong>Konya araç kiralama</strong> hizmetinde uzun yıllardır müşteri memnuniyeti odaklı çalışan ATA SU Rent A Car, şehrin önde gelen <strong>rent a car</strong> firmalarındandır. <strong>Oto kiralama</strong> ihtiyacınız ister kısa süreli bir iş seyahati için, ister uzun dönem kullanım için olsun; geniş filomuz, şeffaf fiyat politikamız ve 7/24 müşteri desteğimizle yanınızdayız.
      </p>
      <h3 style="margin-top:30px;">Geniş Filo, Esnek Kiralama Seçenekleri</h3>
      <p style="line-height:1.8;color:#334155;font-size:16px;">
        Filomuzda <strong>ekonomi sınıfı kiralık araç</strong>tan <strong>SUV kiralama</strong> ve <strong>lüks araç kiralama</strong> seçeneklerine, kompakt sedanlardan <strong>ticari araç kiralama</strong> alternatiflerine kadar her ihtiyaca uygun model bulunur. <strong>Otomatik vites</strong> veya <strong>manuel</strong> tercih farketmez; benzin, dizel ve hibrit yakıt tipleri ile geniş seçenek sunarız.
      </p>
      <h3 style="margin-top:30px;">Günlük, Haftalık ve Aylık Araç Kiralama</h3>
      <p style="line-height:1.8;color:#334155;font-size:16px;">
        <strong>Günlük araç kiralama</strong> fiyatlarımız bütçenize uygun şekilde planlanmıştır; <strong>haftalık araç kiralama</strong> ve <strong>aylık araç kiralama</strong> tercih ettiğinizde özel indirimlerden yararlanabilirsiniz. Uzun dönem kurumsal kiralama için özel fiyat tekliflerimiz mevcuttur.
      </p>
      <h3 style="margin-top:30px;">Havalimanı ve Adrese Teslim Hizmeti</h3>
      <p style="line-height:1.8;color:#334155;font-size:16px;">
        Konya Havalimanı'na (KYA) inişinizde <strong>havalimanı araç kiralama</strong> hizmetimizle aracınız sizi karşılar. Otelinize, ofisinize veya istediğiniz herhangi bir adrese teslim edilebilir. <strong>Konya rent a car</strong> deneyimini en kolay ve hızlı şekilde yaşayın.
      </p>
      <h3 style="margin-top:30px;">Sigorta ve Güvenlik</h3>
      <p style="line-height:1.8;color:#334155;font-size:16px;">
        Tüm araçlarımız zorunlu trafik sigortası ve isteğe bağlı tam kasko ile güvence altındadır. Düzenli bakım ve teknik kontrolden geçen araçlarımız, yolculuğunuzun en güvenli partneri olur.
      </p>
      <h3 style="margin-top:30px;">Şeffaf Fiyat, Sürpriz Yok</h3>
      <p style="line-height:1.8;color:#334155;font-size:16px;">
        <strong>Ucuz araç kiralama Konya</strong> arayanlar için hesaplı seçenekler, premium konfor isteyenler için lüks segment — her bütçe için doğru çözüm bizde. Tüm fiyatlar KDV dahil olarak gösterilir; gizli ücret yoktur.
      </p>
      <p style="line-height:1.8;color:#334155;font-size:16px;margin-top:24px;text-align:center;">
        <a href="<?= url('araclar') ?>" class="btn btn-birincil" title="Konya araç kiralama filosu">Tüm Araçları İnceleyin</a>
        &nbsp;
        <a href="<?= url('rezervasyon') ?>" class="btn btn-cerceve" title="Hemen rezervasyon">Hemen Rezervasyon Yapın</a>
      </p>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta">
  <div class="kapsayici">
    <h2>Bir Araç Kiralama Şirketinden Fazlasıyız!</h2>
    <p>Şirketinize özel mobilite çözümleri oluşturuyor ve ihtiyaçlarınıza en uygun şekilde tasarlanmış araç kiralama programı geliştiriyoruz.</p>
    <div class="cta-butonlar">
      <a href="tel:<?= e(preg_replace('/\s+/','',ayar('telefon',''))) ?>" class="btn btn-beyaz">Hemen Arayın</a>
      <?php if (ayar('whatsapp')): ?>
      <a href="https://wa.me/<?= e(ayar('whatsapp')) ?>" target="_blank" rel="noopener" class="btn btn-cerceve-beyaz">WhatsApp</a>
      <?php endif; ?>
    </div>
  </div>
</section>
