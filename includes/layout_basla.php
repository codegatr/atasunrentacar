<?php defined('ATASU') or exit('403');

// SEO defaults - tum sayfalar icin
$siteAdi = ayar('site_baslik', 'ATA SU Rent A Car');
$siteAciklama = ayar('site_aciklama', 'Konya araç kiralama, rent a car ve oto kiralama hizmetleri. Geniş filo, uygun fiyat, 7/24 hizmet.');
$siteAnahtarlar = ayar('site_anahtar_kelimeler', 'konya araç kiralama, rent a car konya, oto kiralama, günlük araç kiralama, havalimanı araç kiralama, ata su rent a car');
$siteAdres = ayar('adres', 'Konya, Türkiye');
$siteTelefon = ayar('telefon', '');
$siteEmail = ayar('email', '');
$siteWhatsApp = ayar('whatsapp', '');
$siteFb = ayar('facebook', '');
$siteIg = ayar('instagram', '');
$siteTw = ayar('twitter', '');
$siteYt = ayar('youtube', '');
$siteLogo = ayar('logo') ? upload_url(ayar('logo')) : SITE_URL . '/assets/img/logo.png';
$siteFavicon = ayar('favicon') ? upload_url(ayar('favicon')) : '';
$puanOrt = ayar('puan', '5');
$mutluMusteri = ayar('mutlu_musteri', '500');

// Sayfa-bazli SEO degerleri
$tamBaslik = $pageTitle ? ($pageTitle . ' | ' . $siteAdi) : $siteAdi;
$tamAciklama = $pageDesc ?? $siteAciklama;
$tamGorsel = $pageImage ?? $siteLogo;
$tamUrl = SITE_URL . strtok($_SERVER['REQUEST_URI'], '?');

// Aktif arac sayisi (LocalBusiness icin)
try {
    $aracSayisi = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE aktif = 1")['c'] ?? 0);
} catch (Throwable $e) { $aracSayisi = 0; }

// Yorum sayisi (AggregateRating icin)
try {
    $yorumStat = DB::tek("SELECT COUNT(*) toplam, AVG(puan) ort FROM " . DB::tablo('yorumlar') . " WHERE onayli = 1");
    $yorumSayisi = (int)($yorumStat['toplam'] ?? 0);
    $yorumOrt = $yorumSayisi > 0 ? number_format((float)$yorumStat['ort'], 1, '.', '') : '5.0';
} catch (Throwable $e) { $yorumSayisi = 0; $yorumOrt = '5.0'; }

// JSON-LD: Organization + AutoRental (LocalBusiness alt tipi)
$jsonOrg = [
    '@context' => 'https://schema.org',
    '@type' => 'AutoRental',
    'name' => $siteAdi,
    'alternateName' => 'ATA SU Rent A Car',
    'description' => $siteAciklama,
    'url' => SITE_URL,
    'logo' => $siteLogo,
    'image' => $siteLogo,
    'telephone' => $siteTelefon,
    'email' => $siteEmail,
    'priceRange' => '₺₺',
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => $siteAdres,
        'addressLocality' => 'Konya',
        'addressRegion' => 'Konya',
        'addressCountry' => 'TR',
    ],
    'areaServed' => [
        ['@type' => 'City', 'name' => 'Konya'],
        ['@type' => 'AdministrativeArea', 'name' => 'İç Anadolu Bölgesi'],
    ],
    'geo' => [
        '@type' => 'GeoCoordinates',
        'latitude' => '37.8746',
        'longitude' => '32.4932',
    ],
    'openingHoursSpecification' => [
        '@type' => 'OpeningHoursSpecification',
        'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
        'opens' => '00:00',
        'closes' => '23:59',
    ],
];
if ($siteWhatsApp) {
    $jsonOrg['contactPoint'] = [
        '@type' => 'ContactPoint',
        'telephone' => '+' . $siteWhatsApp,
        'contactType' => 'customer service',
        'availableLanguage' => ['Turkish', 'English'],
    ];
}
$sosyal = array_filter([$siteFb, $siteIg, $siteTw, $siteYt]);
if ($sosyal) $jsonOrg['sameAs'] = array_values($sosyal);
if ($yorumSayisi > 0) {
    $jsonOrg['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $yorumOrt,
        'bestRating' => '5',
        'worstRating' => '1',
        'ratingCount' => $yorumSayisi,
    ];
}

// Sayfa bazli ek schemas (her sayfa kendi $extraJsonLd dizisini koyabilir)
$extraJsonLd = $extraJsonLd ?? [];

// Robots metası
$robotsMeta = $robotsMeta ?? 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1';
?><!DOCTYPE html>
<html lang="tr" prefix="og: https://ogp.me/ns#">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Temel SEO -->
<title><?= e($tamBaslik) ?></title>
<meta name="description" content="<?= e($tamAciklama) ?>">
<meta name="keywords" content="<?= e($siteAnahtarlar) ?>">
<meta name="robots" content="<?= e($robotsMeta) ?>">
<meta name="googlebot" content="<?= e($robotsMeta) ?>">
<meta name="bingbot" content="<?= e($robotsMeta) ?>">
<meta name="author" content="<?= e($siteAdi) ?>">
<meta name="generator" content="ATASU CMS">
<meta name="theme-color" content="#1e3a5f">
<meta name="format-detection" content="telephone=yes">
<meta name="geo.region" content="TR-42">
<meta name="geo.placename" content="Konya">
<meta name="geo.position" content="37.8746;32.4932">
<meta name="ICBM" content="37.8746, 32.4932">
<meta name="language" content="Turkish">
<meta http-equiv="content-language" content="tr">

<!-- Canonical -->
<link rel="canonical" href="<?= e($tamUrl) ?>">

<!-- Open Graph / Facebook -->
<meta property="og:title" content="<?= e($tamBaslik) ?>">
<meta property="og:description" content="<?= e($tamAciklama) ?>">
<meta property="og:url" content="<?= e($tamUrl) ?>">
<meta property="og:type" content="<?= e($pageOgType ?? 'website') ?>">
<meta property="og:site_name" content="<?= e($siteAdi) ?>">
<meta property="og:locale" content="tr_TR">
<meta property="og:image" content="<?= e($tamGorsel) ?>">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="<?= e($tamBaslik) ?>">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($tamBaslik) ?>">
<meta name="twitter:description" content="<?= e($tamAciklama) ?>">
<meta name="twitter:image" content="<?= e($tamGorsel) ?>">
<meta name="twitter:image:alt" content="<?= e($tamBaslik) ?>">

<!-- Favicon ve uygulama ikonları -->
<?php if ($siteFavicon): ?>
<link rel="icon" href="<?= e($siteFavicon) ?>">
<link rel="apple-touch-icon" href="<?= e($siteFavicon) ?>">
<?php else: ?>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%231e3a5f' rx='15'/%3E%3Ctext x='50' y='65' font-family='Arial' font-size='42' font-weight='bold' text-anchor='middle' fill='%2360a5fa'%3EAS%3C/text%3E%3C/svg%3E">
<?php endif; ?>

<!-- Fontlar -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

<!-- Stil -->
<link rel="stylesheet" href="<?= url('assets/css/style.css?v=' . filemtime(__DIR__ . '/../assets/css/style.css')) ?>">

<!-- DNS Prefetch performans -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//www.google-analytics.com">
<link rel="dns-prefetch" href="//www.googletagmanager.com">

<!-- Schema.org JSON-LD: Organization / AutoRental -->
<script type="application/ld+json"><?= json_encode($jsonOrg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<!-- Schema.org JSON-LD: WebSite + SearchAction -->
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $siteAdi,
    'url' => SITE_URL,
    'inLanguage' => 'tr-TR',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => SITE_URL . '/araclar?ara={search_term_string}',
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>

<?php foreach ($extraJsonLd as $jsonItem): ?>
<script type="application/ld+json"><?= json_encode($jsonItem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php endforeach; ?>

<!-- Google Analytics -->
<?php if (ayar('google_analytics')): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e(ayar('google_analytics')) ?>"></script>
<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','<?= e(ayar('google_analytics')) ?>');</script>
<?php endif; ?>

<!-- Google Search Console verification -->
<?php if (ayar('google_site_verification')): ?>
<meta name="google-site-verification" content="<?= e(ayar('google_site_verification')) ?>">
<?php endif; ?>

<!-- Yandex / Bing verification -->
<?php if (ayar('yandex_verification')): ?>
<meta name="yandex-verification" content="<?= e(ayar('yandex_verification')) ?>">
<?php endif; ?>
<?php if (ayar('bing_verification')): ?>
<meta name="msvalidate.01" content="<?= e(ayar('bing_verification')) ?>">
<?php endif; ?>

<!-- Facebook Pixel / Diger trackerlar burada -->
<?php if (ayar('facebook_pixel')): ?>
<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= e(ayar('facebook_pixel')) ?>');fbq('track','PageView');</script>
<?php endif; ?>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
  <div class="kapsayici">
    <div class="top-info">
      <span>📍 <?= e($siteAdres) ?></span>
      <?php if ($siteEmail): ?>
      <span>✉️ <a href="mailto:<?= e($siteEmail) ?>"><?= e($siteEmail) ?></a></span>
      <?php endif; ?>
    </div>
    <div class="top-iletisim">
      <?php if ($siteTelefon): ?>
      <a href="tel:<?= e(preg_replace('/\s+/','',$siteTelefon)) ?>" rel="nofollow">📞 <?= e($siteTelefon) ?></a>
      <?php endif; ?>
      <?php if ($siteWhatsApp): ?>
      <a href="https://wa.me/<?= e($siteWhatsApp) ?>" target="_blank" rel="noopener nofollow">💬 WhatsApp</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Header -->
<header class="site-header" itemscope itemtype="https://schema.org/Organization">
  <div class="kapsayici header-icerik">
    <a href="<?= url() ?>" class="logo" aria-label="<?= e($siteAdi) ?> - Ana Sayfa">
      <?php if (ayar('logo')): ?>
        <img src="<?= e(upload_url(ayar('logo'))) ?>" alt="<?= e($siteAdi) ?> logosu" itemprop="logo" width="180" height="50">
      <?php else: ?>
        <div class="logo-yazi" itemprop="name">
          <span class="logo-ana">ATA SU</span>
          <span class="logo-alt">RENT A CAR</span>
        </div>
      <?php endif; ?>
    </a>

    <button class="mobil-menu-btn" aria-label="Menüyü aç/kapa" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <nav class="ana-menu" aria-label="Ana menü">
      <ul>
        <li><a href="<?= url() ?>"<?= ($_GET['sayfa'] ?? 'anasayfa') === 'anasayfa' ? ' aria-current="page"' : '' ?>>Ana Sayfa</a></li>
        <li><a href="<?= url('hakkimizda') ?>"<?= ($_GET['sayfa'] ?? '') === 'hakkimizda' ? ' aria-current="page"' : '' ?>>Hakkımızda</a></li>
        <li><a href="<?= url('araclar') ?>" title="Konya araç kiralama - tüm araçlar"<?= ($_GET['sayfa'] ?? '') === 'araclar' ? ' aria-current="page"' : '' ?>>Araçlarımız</a></li>
        <li><a href="<?= url('blog') ?>"<?= in_array($_GET['sayfa'] ?? '', ['blog','yazi'], true) ? ' aria-current="page"' : '' ?>>Blog</a></li>
        <li><a href="<?= url('iletisim') ?>"<?= ($_GET['sayfa'] ?? '') === 'iletisim' ? ' aria-current="page"' : '' ?>>İletişim</a></li>
        <li><a href="<?= url('rezervasyon') ?>" class="btn-rezervasyon" title="Hemen araç kirala">Rezervasyon</a></li>
      </ul>
    </nav>
  </div>
</header>
