<?php
define('ATASU', true);
require __DIR__ . '/includes/bootstrap.php';

$sayfa = $_GET['sayfa'] ?? 'anasayfa';
$sayfa = preg_replace('/[^a-z0-9_\-]/', '', $sayfa);

// Bos veya / istekleri
if ($sayfa === '' || $sayfa === 'index') {
    $sayfa = 'anasayfa';
}

// Slug bazli rotalar
if ($sayfa === 'arac' && isset($_GET['slug'])) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']);
    $arac = DB::tek("SELECT a.*, k.ad AS kategori_ad FROM " . DB::tablo('araclar') . " a LEFT JOIN " . DB::tablo('kategoriler') . " k ON k.id = a.kategori_id WHERE a.slug = :slug AND a.aktif = 1", ['slug' => $slug]);
    if (!$arac) { http_response_code(404); $sayfa = '404'; }
}
if ($sayfa === 'yazi' && isset($_GET['slug'])) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', $_GET['slug']);
    $yazi = DB::tek("SELECT * FROM " . DB::tablo('bloglar') . " WHERE slug = :s AND durum = 'yayinda'", ['s' => $slug]);
    if (!$yazi) { http_response_code(404); $sayfa = '404'; }
    else {
        DB::sorgu("UPDATE " . DB::tablo('bloglar') . " SET goruntuleme = goruntuleme + 1 WHERE id = :id", ['id' => $yazi['id']]);
    }
}

// Sitemap / robots
if ($sayfa === 'sitemap') {
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $bugun = date('Y-m-d');
    foreach ([
        ['', 1.0, 'daily'],
        ['araclar', 0.9, 'daily'],
        ['hakkimizda', 0.7, 'monthly'],
        ['iletisim', 0.7, 'monthly'],
        ['rezervasyon', 0.8, 'weekly'],
        ['blog', 0.6, 'weekly'],
        ['kvkk', 0.3, 'yearly'],
        ['gizlilik-politikasi', 0.3, 'yearly'],
        ['cerez-politikasi', 0.3, 'yearly'],
        ['kiralama-sozlesmesi', 0.4, 'yearly'],
    ] as $r) {
        echo '<url><loc>' . htmlspecialchars(url($r[0])) . '</loc><lastmod>' . $bugun . '</lastmod><changefreq>' . $r[2] . '</changefreq><priority>' . $r[1] . '</priority></url>' . "\n";
    }
    foreach (DB::liste("SELECT slug, slug as g FROM " . DB::tablo('kategoriler') . " WHERE aktif = 1") as $k) {
        echo '<url><loc>' . htmlspecialchars(url('araclar?sinif=' . $k['slug'])) . '</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>' . "\n";
    }
    foreach (DB::liste("SELECT slug, guncelleme FROM " . DB::tablo('araclar') . " WHERE aktif = 1") as $a) {
        $lm = !empty($a['guncelleme']) ? substr($a['guncelleme'], 0, 10) : $bugun;
        echo '<url><loc>' . htmlspecialchars(url('arac/' . $a['slug'])) . '</loc><lastmod>' . $lm . '</lastmod><changefreq>weekly</changefreq><priority>0.8</priority></url>' . "\n";
    }
    foreach (DB::liste("SELECT slug, yayin_tarihi FROM " . DB::tablo('bloglar') . " WHERE durum = 'yayinda'") as $b) {
        $lm = !empty($b['yayin_tarihi']) ? substr($b['yayin_tarihi'], 0, 10) : $bugun;
        echo '<url><loc>' . htmlspecialchars(url('blog/' . $b['slug'])) . '</loc><lastmod>' . $lm . '</lastmod><changefreq>monthly</changefreq><priority>0.5</priority></url>' . "\n";
    }
    echo '</urlset>';
    exit;
}
if ($sayfa === 'robots') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /includes/\n";
    echo "Disallow: /migrations/\n";
    echo "Disallow: /install.php\n";
    echo "Disallow: /config*.php\n";
    echo "Disallow: /api\n";
    echo "Disallow: /assets/uploads/sigorta/\n";
    echo "Disallow: /assets/uploads/hasar/\n";
    echo "Disallow: /assets/uploads/muayene/\n";
    echo "\n";
    echo "User-agent: GPTBot\nDisallow: /\n\n";
    echo "User-agent: CCBot\nDisallow: /\n\n";
    echo "Sitemap: " . url('sitemap.xml') . "\n";
    exit;
}

// AJAX endpoint - rezervasyon hesaplama
if ($sayfa === 'api') {
    header('Content-Type: application/json; charset=utf-8');
    $islem = $_GET['islem'] ?? '';
    if ($islem === 'fiyat_hesapla') {
        $aracId = (int)($_POST['arac_id'] ?? 0);
        $alis = $_POST['alis_tarihi'] ?? '';
        $iade = $_POST['iade_tarihi'] ?? '';
        $arac = DB::tek("SELECT gunluk_fiyat, haftalik_fiyat, aylik_fiyat FROM " . DB::tablo('araclar') . " WHERE id = :id AND aktif = 1", ['id' => $aracId]);
        if (!$arac || !$alis || !$iade) {
            echo json_encode(['hata' => 'Eksik bilgi']);
            exit;
        }
        $gun = gun_farki($alis, $iade);
        $fiyat = (float)$arac['gunluk_fiyat'];
        if ($gun >= 30 && $arac['aylik_fiyat'] > 0) $fiyat = (float)$arac['aylik_fiyat'] / 30;
        elseif ($gun >= 7 && $arac['haftalik_fiyat'] > 0) $fiyat = (float)$arac['haftalik_fiyat'] / 7;
        $araTutar = $fiyat * $gun;
        $kdvOran = (float)ayar('kdv_orani', 20);
        $kdvTutar = $araTutar * ($kdvOran / 100);
        $toplam = $araTutar + $kdvTutar;
        echo json_encode([
            'gun' => $gun,
            'gunluk' => round($fiyat, 2),
            'ara_tutar' => round($araTutar, 2),
            'kdv_orani' => $kdvOran,
            'kdv_tutar' => round($kdvTutar, 2),
            'toplam' => round($toplam, 2),
            'gunluk_format' => tl(round($fiyat, 2)),
            'ara_format' => tl(round($araTutar, 2)),
            'kdv_format' => tl(round($kdvTutar, 2)),
            'toplam_format' => tl(round($toplam, 2)),
        ]);
        exit;
    }
    echo json_encode(['hata' => 'Bilinmeyen islem']);
    exit;
}

// Sayfa render
$gecerli_sayfalar = ['anasayfa','araclar','arac','hakkimizda','iletisim','rezervasyon','blog','yazi','gizlilik-politikasi','cerez-politikasi','kvkk','kiralama-sozlesmesi','404'];
if (!in_array($sayfa, $gecerli_sayfalar, true)) {
    http_response_code(404);
    $sayfa = '404';
}

// Header
$pageTitle = '';
$pageDesc = ayar('site_aciklama', '');
$pageImage = ayar('logo') ? upload_url(ayar('logo')) : '';

ob_start();

switch ($sayfa) {
    case 'anasayfa': render_anasayfa(); break;
    case 'araclar': render_araclar(); break;
    case 'arac': render_arac($arac ?? []); break;
    case 'hakkimizda': render_hakkimizda(); break;
    case 'iletisim': render_iletisim(); break;
    case 'rezervasyon': render_rezervasyon(); break;
    case 'blog': render_blog_liste(); break;
    case 'yazi': render_blog_detay($yazi ?? []); break;
    case 'gizlilik-politikasi': render_yasal('gizlilik_politikasi', 'Gizlilik Politikası'); break;
    case 'cerez-politikasi': render_yasal('cerez_politikasi', 'Çerez Politikası'); break;
    case 'kvkk': render_yasal('kvkk_aciklama', 'KVKK'); break;
    case 'kiralama-sozlesmesi': render_yasal('kiralama_sozlesmesi', 'Kiralama Sözleşmesi'); break;
    case '404': render_404(); break;
}

$icerik = ob_get_clean();

// Layout
require __DIR__ . '/includes/layout_basla.php';
echo $icerik;
require __DIR__ . '/includes/layout_bitir.php';

// ==================== SAYFA FONKSIYONLARI ====================

function render_anasayfa(): void
{
    global $pageTitle, $pageDesc, $extraJsonLd;
    $pageTitle = '';  // anasayfada full siteAdi gosterilsin
    $pageDesc = ayar('site_aciklama', 'Konya araç kiralama, rent a car ve oto kiralama hizmetleri. Geniş filo, uygun fiyat, 7/24 hizmet, havalimanı teslim. ATA SU Rent A Car ile güvenli yolculuk.');

    $oneCikan = DB::liste("SELECT a.*, k.ad AS kategori_ad,
        (SELECT dosya FROM " . DB::tablo('arac_resimler') . " ar WHERE ar.arac_id = a.id ORDER BY ana_resim DESC, sira ASC LIMIT 1) AS ana_resim
        FROM " . DB::tablo('araclar') . " a
        LEFT JOIN " . DB::tablo('kategoriler') . " k ON k.id = a.kategori_id
        WHERE a.aktif = 1 AND a.one_cikan = 1
        ORDER BY a.olusturma DESC LIMIT 8");

    if (count($oneCikan) === 0) {
        $oneCikan = DB::liste("SELECT a.*, k.ad AS kategori_ad,
            (SELECT dosya FROM " . DB::tablo('arac_resimler') . " ar WHERE ar.arac_id = a.id ORDER BY ana_resim DESC, sira ASC LIMIT 1) AS ana_resim
            FROM " . DB::tablo('araclar') . " a
            LEFT JOIN " . DB::tablo('kategoriler') . " k ON k.id = a.kategori_id
            WHERE a.aktif = 1
            ORDER BY a.olusturma DESC LIMIT 8");
    }

    $kategoriler = DB::liste("SELECT * FROM " . DB::tablo('kategoriler') . " WHERE aktif = 1 ORDER BY sira");
    $yorumlar = DB::liste("SELECT * FROM " . DB::tablo('yorumlar') . " WHERE onayli = 1 ORDER BY sira ASC LIMIT 6");
    $bloglar = DB::liste("SELECT * FROM " . DB::tablo('bloglar') . " WHERE durum = 'yayinda' ORDER BY yayin_tarihi DESC LIMIT 3");

    // FAQ Schema - Anasayfa icin sik sorulan sorular
    $extraJsonLd[] = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => [
            [
                '@type' => 'Question',
                'name' => 'Konya\'da araç kiralamak için hangi belgeler gerekir?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Konya\'da araç kiralamak için T.C. kimlik kartı, geçerli sürücü belgesi (en az 1 yıllık) ve kredi kartı/depozito gereklidir. Yabancı uyruklular için pasaport ve uluslararası ehliyet kabul edilir.'],
            ],
            [
                '@type' => 'Question',
                'name' => 'Araç kiralamak için minimum yaş kaçtır?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'ATA SU Rent A Car\'da araç kiralamak için minimum 21 yaşında olmak ve en az 1 yıllık sürücü belgesine sahip olmak yeterlidir. Lüks araçlar için yaş şartı 25 olabilir.'],
            ],
            [
                '@type' => 'Question',
                'name' => 'Konya havalimanına araç teslim ediyor musunuz?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Evet, Konya Havalimanı (KYA) için ücretli teslim ve karşılama hizmetimiz mevcuttur. Otele veya istediğiniz adrese de araç teslimi yapılabilir.'],
            ],
            [
                '@type' => 'Question',
                'name' => 'Günlük araç kiralama fiyatları ne kadar?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Konya günlük araç kiralama fiyatları araç sınıfına göre değişir; ekonomi sınıfında uygun fiyatlardan başlar, SUV ve lüks segmente kadar geniş yelpaze sunar. Haftalık ve aylık kiralamalarda indirimli fiyatlar uygulanır.'],
            ],
            [
                '@type' => 'Question',
                'name' => 'Sigorta ve kasko dahil mi?',
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Tüm araçlarımızda zorunlu trafik sigortası standart olarak bulunur. Tam kasko güvencesi ek hizmet olarak sunulmaktadır; rezervasyon sırasında seçilebilir.'],
            ],
        ],
    ];

    require __DIR__ . '/includes/views/anasayfa.php';
}

function render_araclar(): void
{
    global $pageTitle, $pageDesc, $extraJsonLd;

    $kategori = $_GET['sinif'] ?? '';
    $vites = $_GET['vites'] ?? '';
    $yakit = $_GET['yakit'] ?? '';

    $where = "a.aktif = 1";
    $params = [];
    if ($kategori) {
        $where .= " AND k.slug = :slug";
        $params['slug'] = slug_olustur($kategori);
    }
    if ($vites && in_array($vites, ['Manuel','Otomatik'])) {
        $where .= " AND a.vites = :vites";
        $params['vites'] = $vites;
    }
    if ($yakit) {
        $where .= " AND a.yakit = :yakit";
        $params['yakit'] = $yakit;
    }

    $araclar = DB::liste("SELECT a.*, k.ad AS kategori_ad, k.slug AS kategori_slug,
        (SELECT dosya FROM " . DB::tablo('arac_resimler') . " ar WHERE ar.arac_id = a.id ORDER BY ana_resim DESC, sira ASC LIMIT 1) AS ana_resim
        FROM " . DB::tablo('araclar') . " a
        LEFT JOIN " . DB::tablo('kategoriler') . " k ON k.id = a.kategori_id
        WHERE {$where} ORDER BY a.one_cikan DESC, a.gunluk_fiyat ASC", $params);

    $kategoriler = DB::liste("SELECT * FROM " . DB::tablo('kategoriler') . " WHERE aktif = 1 ORDER BY sira");

    // SEO: dinamik baslik/aciklama (kategori filtresine gore)
    $kategoriAdi = '';
    if ($kategori) {
        foreach ($kategoriler as $k) {
            if ($k['slug'] === slug_olustur($kategori)) { $kategoriAdi = $k['ad']; break; }
        }
    }
    if ($kategoriAdi) {
        $pageTitle = $kategoriAdi . ' Sınıfı Araç Kiralama Konya';
        $pageDesc = 'Konya\'da ' . $kategoriAdi . ' sınıfı araç kiralama. ATA SU Rent A Car ile uygun fiyatlarla kiralık ' . mb_strtolower($kategoriAdi, 'UTF-8') . ' araçlar. Anlık rezervasyon, hızlı teslim.';
    } else {
        $pageTitle = 'Araç Kiralama Konya - Tüm Araçlar';
        $pageDesc = 'Konya araç kiralama filomuzdaki tüm araçları inceleyin. Ekonomi, konfor, SUV, lüks ve ticari araç seçenekleri. Günlük, haftalık, aylık kiralama. ' . count($araclar) . '+ araç.';
    }

    // BreadcrumbList
    $breadcrumb = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Araçlar', 'item' => SITE_URL . '/araclar'],
    ];
    if ($kategoriAdi) {
        $breadcrumb[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $kategoriAdi, 'item' => SITE_URL . '/araclar?sinif=' . $kategori];
    }
    $extraJsonLd[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumb];

    // ItemList: filo listesi
    $itemListElements = [];
    foreach ($araclar as $i => $a) {
        $itemListElements[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'item' => [
                '@type' => 'Vehicle',
                'name' => $a['marka'] . ' ' . $a['model'],
                'url' => SITE_URL . '/arac/' . $a['slug'],
                'brand' => ['@type' => 'Brand', 'name' => $a['marka']],
                'model' => $a['model'],
            ],
        ];
    }
    if ($itemListElements) {
        $extraJsonLd[] = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $itemListElements,
            'numberOfItems' => count($itemListElements),
        ];
    }

    require __DIR__ . '/includes/views/araclar.php';
}

function render_arac(array $arac): void
{
    global $pageTitle, $pageDesc, $pageImage, $pageOgType, $extraJsonLd;
    if (!$arac) { render_404(); return; }

    $aracTamAd = $arac['marka'] . ' ' . $arac['model'];
    $kategoriAd = '';
    if (!empty($arac['kategori_id'])) {
        $kategoriAd = (string)(DB::tek("SELECT ad FROM " . DB::tablo('kategoriler') . " WHERE id = ?", [(int)$arac['kategori_id']])['ad'] ?? '');
    }

    // SEO baslik/aciklama (varsa ozel, yoksa otomatik)
    $pageTitle = !empty($arac['seo_baslik']) ? $arac['seo_baslik'] : $aracTamAd . ' Kiralama Konya - ' . tl($arac['gunluk_fiyat']) . '/gün';
    if (!empty($arac['seo_aciklama'])) {
        $pageDesc = $arac['seo_aciklama'];
    } else {
        $kisaltilmis = kisalt(strip_tags($arac['aciklama'] ?? ''), 160);
        $pageDesc = $kisaltilmis ?: 'Konya\'da ' . $aracTamAd . ' (' . (int)$arac['yil'] . ') kiralayın. ' . $arac['vites'] . ' vites, ' . $arac['yakit'] . ' yakıt, ' . (int)$arac['koltuk_sayisi'] . ' kişilik. Günlük ' . tl($arac['gunluk_fiyat']) . '. Hemen rezervasyon.';
    }
    $pageOgType = 'product';

    $resimler = DB::liste("SELECT * FROM " . DB::tablo('arac_resimler') . " WHERE arac_id = :id ORDER BY ana_resim DESC, sira ASC", ['id' => $arac['id']]);
    if ($resimler) $pageImage = upload_url('araclar/' . $resimler[0]['dosya']);

    $ekHizmetler = DB::liste("SELECT * FROM " . DB::tablo('ek_hizmetler') . " WHERE aktif = 1 ORDER BY sira");

    // Schema.org Vehicle + Product/Offer
    $vehicleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'Vehicle',
        'name' => $aracTamAd,
        'description' => kisalt(strip_tags($arac['aciklama'] ?? ''), 500),
        'url' => SITE_URL . '/arac/' . $arac['slug'],
        'brand' => ['@type' => 'Brand', 'name' => $arac['marka']],
        'model' => $arac['model'],
        'vehicleModelDate' => (string)$arac['yil'],
        'productionDate' => (string)$arac['yil'],
        'numberOfDoors' => (int)$arac['kapi_sayisi'],
        'seatingCapacity' => (int)$arac['koltuk_sayisi'],
        'vehicleTransmission' => $arac['vites'],
        'fuelType' => $arac['yakit'],
        'color' => $arac['renk'] ?? '',
        'offers' => [
            '@type' => 'Offer',
            'priceCurrency' => 'TRY',
            'price' => (string)(float)$arac['gunluk_fiyat'],
            'availability' => $arac['durum'] === 'musait' ? 'https://schema.org/InStock' : 'https://schema.org/PreOrder',
            'priceSpecification' => [
                '@type' => 'UnitPriceSpecification',
                'price' => (string)(float)$arac['gunluk_fiyat'],
                'priceCurrency' => 'TRY',
                'unitText' => 'Günlük',
                'referenceQuantity' => ['@type' => 'QuantitativeValue', 'value' => 1, 'unitCode' => 'DAY'],
            ],
            'seller' => [
                '@type' => 'AutoRental',
                'name' => ayar('site_baslik', 'ATA SU Rent A Car'),
                'url' => SITE_URL,
            ],
            'areaServed' => ['@type' => 'City', 'name' => 'Konya'],
            'url' => SITE_URL . '/arac/' . $arac['slug'],
        ],
    ];
    if (!empty($arac['motor_hacmi'])) {
        $vehicleSchema['vehicleEngine'] = [
            '@type' => 'EngineSpecification',
            'engineDisplacement' => ['@type' => 'QuantitativeValue', 'value' => $arac['motor_hacmi']],
        ];
    }
    if ($pageImage) {
        $vehicleSchema['image'] = $pageImage;
    } elseif ($resimler) {
        $vehicleSchema['image'] = array_map(fn($r) => upload_url('araclar/' . $r['dosya']), $resimler);
    }
    $extraJsonLd[] = $vehicleSchema;

    // BreadcrumbList
    $breadcrumb = [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Araçlar', 'item' => SITE_URL . '/araclar'],
    ];
    if ($kategoriAd) {
        $breadcrumb[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $kategoriAd, 'item' => SITE_URL . '/araclar?sinif=' . slug_olustur($kategoriAd)];
        $breadcrumb[] = ['@type' => 'ListItem', 'position' => 4, 'name' => $aracTamAd, 'item' => SITE_URL . '/arac/' . $arac['slug']];
    } else {
        $breadcrumb[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $aracTamAd, 'item' => SITE_URL . '/arac/' . $arac['slug']];
    }
    $extraJsonLd[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumb];

    require __DIR__ . '/includes/views/arac.php';
}

function render_hakkimizda(): void
{
    global $pageTitle, $pageDesc, $extraJsonLd;
    $pageTitle = 'Hakkımızda';
    $pageDesc = 'ATA SU Rent A Car hakkında: Konya\'nın güvenilir araç kiralama firması. ' . ayar('hizmet_yili', '5') . '+ yıllık tecrübe, ' . ayar('mutlu_musteri', '500') . '+ memnun müşteri. Profesyonel ve dürüst hizmet anlayışı.';
    $extraJsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Hakkımızda', 'item' => SITE_URL . '/hakkimizda'],
        ],
    ];
    require __DIR__ . '/includes/views/hakkimizda.php';
}

function render_iletisim(): void
{
    global $pageTitle, $pageDesc, $extraJsonLd;
    $pageTitle = 'İletişim - Konya Araç Kiralama';
    $pageDesc = 'ATA SU Rent A Car ile iletişime geçin. Konya merkezinde 7/24 hizmet, hızlı yanıt. Telefon: ' . ayar('telefon', '') . '. Adres: ' . ayar('adres', 'Konya') . '.';

    $extraJsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'ContactPage',
        'name' => 'İletişim - ' . ayar('site_baslik', ''),
        'url' => SITE_URL . '/iletisim',
    ];
    $extraJsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'İletişim', 'item' => SITE_URL . '/iletisim'],
        ],
    ];

    $hata = '';
    $basarili = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iletisim_gonder'])) {
        csrf_zorunlu();
        $ad = trim($_POST['ad_soyad'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tel = trim($_POST['telefon'] ?? '');
        $konu = trim($_POST['konu'] ?? '');
        $msj = trim($_POST['mesaj'] ?? '');

        if (mb_strlen($ad) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($msj) < 10) {
            $hata = 'Lütfen formu eksiksiz doldurun.';
        } else {
            DB::ekle('iletisim_mesajlari', [
                'ad_soyad' => $ad,
                'email' => $email,
                'telefon' => $tel,
                'konu' => $konu,
                'mesaj' => $msj,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'olusturma' => date('Y-m-d H:i:s'),
            ]);
            $basarili = true;

            // Admin'e bildirim e-postasi
            $adminEmail = ayar('email');
            if ($adminEmail) {
                $icerik = '<p><strong>' . htmlspecialchars($ad) . '</strong> sizinle iletişime geçmek istiyor.</p>';
                $icerik .= '<table cellpadding="6" style="width:100%;border-collapse:collapse;">';
                $icerik .= '<tr><td style="background:#f8fafc;width:130px;border:1px solid #e2e8f0;"><strong>E-posta</strong></td><td style="border:1px solid #e2e8f0;"><a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></td></tr>';
                if ($tel) $icerik .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Telefon</strong></td><td style="border:1px solid #e2e8f0;">' . htmlspecialchars($tel) . '</td></tr>';
                if ($konu) $icerik .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Konu</strong></td><td style="border:1px solid #e2e8f0;">' . htmlspecialchars($konu) . '</td></tr>';
                $icerik .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;vertical-align:top;"><strong>Mesaj</strong></td><td style="border:1px solid #e2e8f0;">' . nl2br(htmlspecialchars($msj)) . '</td></tr>';
                $icerik .= '</table>';
                $html = Mail::sablon('Yeni İletişim Mesajı', $icerik, SITE_URL . '/admin/iletisim-mesajlari.php', 'Yönetim Panelinde Görüntüle');
                Mail::gonder($adminEmail, '[ATA SU] Yeni İletişim Mesajı: ' . ($konu ?: 'Konusuz'), $html, null, ['reply_to' => $email]);
            }

            // Kullaniciya tesekkur e-postasi
            $tesekkur = '<p>Merhaba <strong>' . htmlspecialchars($ad) . '</strong>,</p>';
            $tesekkur .= '<p>Mesajınızı aldık. En kısa sürede sizinle iletişime geçeceğiz.</p>';
            $tesekkur .= '<p style="background:#f8fafc;padding:14px;border-radius:6px;border-left:3px solid #3b82f6;color:#475569;">' . nl2br(htmlspecialchars($msj)) . '</p>';
            $tesekkur .= '<p>Acil durumlar için bize <a href="tel:' . preg_replace('/\s+/', '', (string)ayar('telefon', '')) . '">' . htmlspecialchars((string)ayar('telefon', '')) . '</a> numarasından ulaşabilirsiniz.</p>';
            $html2 = Mail::sablon('Mesajınız İçin Teşekkürler', $tesekkur, SITE_URL, 'Web Sitemizi Ziyaret Edin');
            Mail::gonder($email, 'Mesajınız alındı - ' . ayar('site_baslik', 'ATA SU Rent A Car'), $html2);
        }
    }

    require __DIR__ . '/includes/views/iletisim.php';
}

function render_rezervasyon(): void
{
    global $pageTitle, $pageDesc, $extraJsonLd, $robotsMeta;
    $pageTitle = 'Online Rezervasyon - Araç Kiralama Konya';
    $pageDesc = 'Konya araç kiralama rezervasyonu - hemen online rezervasyon yapın. ' . ayar('site_baslik', 'ATA SU Rent A Car') . ' güvencesiyle hızlı, kolay ve güvenli kiralama.';
    $extraJsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Rezervasyon', 'item' => SITE_URL . '/rezervasyon'],
        ],
    ];
    // Form sayfasi - normal index/follow ama search results'da formun kendisi gosterilmesin yeterli

    $hata = '';
    $basarili = false;
    $rezNo = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rezervasyon_gonder'])) {
        csrf_zorunlu();
        $aracId = (int)($_POST['arac_id'] ?? 0);
        $alis = $_POST['alis_tarihi'] ?? '';
        $iade = $_POST['iade_tarihi'] ?? '';
        $ad = trim($_POST['ad'] ?? '');
        $soyad = trim($_POST['soyad'] ?? '');
        $tel = trim($_POST['telefon'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $alisYer = trim($_POST['alis_yeri'] ?? '');
        $iadeYer = trim($_POST['iade_yeri'] ?? '');
        $not = trim($_POST['notlar'] ?? '');

        $arac = DB::tek("SELECT * FROM " . DB::tablo('araclar') . " WHERE id = :id AND aktif = 1", ['id' => $aracId]);

        if (!$arac || !$alis || !$iade || mb_strlen($ad) < 2 || mb_strlen($soyad) < 2 || mb_strlen($tel) < 7) {
            $hata = 'Lütfen tüm zorunlu alanları doldurun.';
        } elseif (strtotime($iade) <= strtotime($alis)) {
            $hata = 'İade tarihi alış tarihinden sonra olmalı.';
        } else {
            $gun = gun_farki($alis, $iade);
            $fiyat = (float)$arac['gunluk_fiyat'];
            if ($gun >= 30 && $arac['aylik_fiyat'] > 0) $fiyat = (float)$arac['aylik_fiyat'] / 30;
            elseif ($gun >= 7 && $arac['haftalik_fiyat'] > 0) $fiyat = (float)$arac['haftalik_fiyat'] / 7;
            $aracTutar = round($fiyat * $gun, 2);
            $kdvOran = (float)ayar('kdv_orani', 20);
            $kdvTutar = round($aracTutar * ($kdvOran / 100), 2);
            $toplam = round($aracTutar + $kdvTutar, 2);

            $rezNo = 'ATR-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

            DB::ekle('rezervasyonlar', [
                'rezervasyon_no' => $rezNo,
                'arac_id' => $aracId,
                'misafir_ad' => $ad,
                'misafir_soyad' => $soyad,
                'misafir_telefon' => $tel,
                'misafir_email' => $email,
                'alis_tarihi' => date('Y-m-d H:i:s', strtotime($alis)),
                'iade_tarihi' => date('Y-m-d H:i:s', strtotime($iade)),
                'alis_yeri' => $alisYer,
                'iade_yeri' => $iadeYer,
                'gunluk_fiyat' => $fiyat,
                'toplam_gun' => $gun,
                'arac_tutar' => $aracTutar,
                'kdv_orani' => $kdvOran,
                'kdv_tutar' => $kdvTutar,
                'toplam_tutar' => $toplam,
                'durum' => 'beklemede',
                'odeme_durumu' => 'odenmedi',
                'musteri_notu' => $not,
                'olusturma' => date('Y-m-d H:i:s'),
            ]);
            $basarili = true;

            // Admin'e bildirim
            $adminEmail = ayar('email');
            $aracAd = $arac['marka'] . ' ' . $arac['model'];
            if ($adminEmail) {
                $detay = '<p>Yeni rezervasyon talebi alındı.</p>';
                $detay .= '<table cellpadding="6" style="width:100%;border-collapse:collapse;font-size:14px;">';
                $detay .= '<tr><td style="background:#f8fafc;width:140px;border:1px solid #e2e8f0;"><strong>Rezervasyon No</strong></td><td style="border:1px solid #e2e8f0;"><code>' . htmlspecialchars($rezNo) . '</code></td></tr>';
                $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Müşteri</strong></td><td style="border:1px solid #e2e8f0;">' . htmlspecialchars($ad . ' ' . $soyad) . '</td></tr>';
                $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Telefon</strong></td><td style="border:1px solid #e2e8f0;"><a href="tel:' . htmlspecialchars($tel) . '">' . htmlspecialchars($tel) . '</a></td></tr>';
                if ($email) $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>E-posta</strong></td><td style="border:1px solid #e2e8f0;"><a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></td></tr>';
                $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Araç</strong></td><td style="border:1px solid #e2e8f0;">' . htmlspecialchars($aracAd) . '</td></tr>';
                $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Alış</strong></td><td style="border:1px solid #e2e8f0;">' . htmlspecialchars(tarih_tr($alis, true)) . ($alisYer ? ' · ' . htmlspecialchars($alisYer) : '') . '</td></tr>';
                $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>İade</strong></td><td style="border:1px solid #e2e8f0;">' . htmlspecialchars(tarih_tr($iade, true)) . ($iadeYer ? ' · ' . htmlspecialchars($iadeYer) : '') . '</td></tr>';
                $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Süre</strong></td><td style="border:1px solid #e2e8f0;">' . $gun . ' gün</td></tr>';
                $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;"><strong>Toplam</strong></td><td style="border:1px solid #e2e8f0;"><strong style="color:#1e3a5f;font-size:18px;">' . tl($toplam) . '</strong> <small>(KDV dahil)</small></td></tr>';
                if ($not) $detay .= '<tr><td style="background:#f8fafc;border:1px solid #e2e8f0;vertical-align:top;"><strong>Not</strong></td><td style="border:1px solid #e2e8f0;">' . nl2br(htmlspecialchars($not)) . '</td></tr>';
                $detay .= '</table>';
                $html = Mail::sablon('Yeni Rezervasyon: ' . $rezNo, $detay, SITE_URL . '/admin/rezervasyonlar.php', 'Yönetim Panelinde Görüntüle');
                Mail::gonder($adminEmail, '[ATA SU] Yeni Rezervasyon ' . $rezNo . ' - ' . $aracAd, $html, null, $email ? ['reply_to' => $email] : []);
            }

            // Musteriye onay
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $onay = '<p>Sayın <strong>' . htmlspecialchars($ad . ' ' . $soyad) . '</strong>,</p>';
                $onay .= '<p>Rezervasyon talebiniz başarıyla alınmıştır. En kısa sürede size dönüş yapılacak ve onaylanacaktır.</p>';
                $onay .= '<table cellpadding="8" style="width:100%;border-collapse:collapse;background:#f8fafc;border-radius:6px;margin:20px 0;">';
                $onay .= '<tr><td style="width:50%;color:#64748b;font-size:13px;">Rezervasyon No</td><td style="text-align:right;"><strong style="font-size:16px;color:#1e3a5f;">' . htmlspecialchars($rezNo) . '</strong></td></tr>';
                $onay .= '<tr><td style="color:#64748b;font-size:13px;">Araç</td><td style="text-align:right;"><strong>' . htmlspecialchars($aracAd) . '</strong></td></tr>';
                $onay .= '<tr><td style="color:#64748b;font-size:13px;">Alış Tarihi</td><td style="text-align:right;"><strong>' . htmlspecialchars(tarih_tr($alis, true)) . '</strong></td></tr>';
                $onay .= '<tr><td style="color:#64748b;font-size:13px;">İade Tarihi</td><td style="text-align:right;"><strong>' . htmlspecialchars(tarih_tr($iade, true)) . '</strong></td></tr>';
                $onay .= '<tr><td style="color:#64748b;font-size:13px;">Süre</td><td style="text-align:right;"><strong>' . $gun . ' gün</strong></td></tr>';
                $onay .= '<tr><td style="color:#64748b;font-size:13px;">Toplam Tutar</td><td style="text-align:right;"><strong style="font-size:18px;color:#3b82f6;">' . tl($toplam) . '</strong></td></tr>';
                $onay .= '</table>';
                $onay .= '<p style="font-size:13px;color:#64748b;">Sorularınız için <a href="mailto:' . htmlspecialchars($adminEmail ?: '') . '">' . htmlspecialchars($adminEmail ?: '') . '</a> veya <a href="tel:' . preg_replace('/\s+/', '', (string)ayar('telefon', '')) . '">' . htmlspecialchars((string)ayar('telefon', '')) . '</a> üzerinden bize ulaşabilirsiniz.</p>';
                $html2 = Mail::sablon('Rezervasyon Onayı: ' . $rezNo, $onay);
                Mail::gonder($email, 'Rezervasyon Talebiniz Alındı - ' . $rezNo, $html2);
            }
        }
    }

    $araclar = DB::liste("SELECT id, marka, model, plaka, gunluk_fiyat FROM " . DB::tablo('araclar') . " WHERE aktif = 1 AND durum IN ('musait','rezerve') ORDER BY marka, model");
    $secilenArac = (int)($_GET['arac'] ?? 0);

    require __DIR__ . '/includes/views/rezervasyon.php';
}

function render_blog_liste(): void
{
    global $pageTitle, $pageDesc, $extraJsonLd;
    $pageTitle = 'Blog - Araç Kiralama Rehberi';
    $pageDesc = 'Konya araç kiralama, oto kiralama ve seyahat ipuçları. ATA SU Rent A Car blogunda yararlı rehberler ve güncel makaleler.';
    $yazilar = DB::liste("SELECT * FROM " . DB::tablo('bloglar') . " WHERE durum = 'yayinda' ORDER BY yayin_tarihi DESC LIMIT 30");
    $extraJsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => SITE_URL . '/blog'],
        ],
    ];
    require __DIR__ . '/includes/views/blog.php';
}

function render_blog_detay(array $yazi): void
{
    global $pageTitle, $pageDesc, $pageImage, $pageOgType, $extraJsonLd;
    if (!$yazi) { render_404(); return; }
    $pageTitle = !empty($yazi['seo_baslik']) ? $yazi['seo_baslik'] : $yazi['baslik'];
    $pageDesc = !empty($yazi['seo_aciklama']) ? $yazi['seo_aciklama'] : ($yazi['ozet'] ?? kisalt(strip_tags($yazi['icerik'] ?? ''), 160));
    $pageOgType = 'article';
    if ($yazi['kapak']) $pageImage = upload_url('blog/' . $yazi['kapak']);
    $diger = DB::liste("SELECT * FROM " . DB::tablo('bloglar') . " WHERE durum='yayinda' AND id != :id ORDER BY yayin_tarihi DESC LIMIT 3", ['id' => $yazi['id']]);

    // Article / BlogPosting schema
    $extraJsonLd[] = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $yazi['baslik'],
        'description' => $pageDesc,
        'image' => $pageImage ?? '',
        'datePublished' => $yazi['yayin_tarihi'] ?? date('Y-m-d'),
        'dateModified' => $yazi['guncelleme'] ?? $yazi['yayin_tarihi'] ?? date('Y-m-d'),
        'author' => ['@type' => 'Person', 'name' => $yazi['yazar'] ?? ayar('site_baslik', 'ATA SU Rent A Car')],
        'publisher' => [
            '@type' => 'Organization',
            'name' => ayar('site_baslik', 'ATA SU Rent A Car'),
            'logo' => ['@type' => 'ImageObject', 'url' => ayar('logo') ? upload_url(ayar('logo')) : SITE_URL . '/assets/img/logo.png'],
        ],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => SITE_URL . '/blog/' . $yazi['slug']],
        'url' => SITE_URL . '/blog/' . $yazi['slug'],
        'inLanguage' => 'tr-TR',
    ];

    $extraJsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => SITE_URL . '/blog'],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $yazi['baslik'], 'item' => SITE_URL . '/blog/' . $yazi['slug']],
        ],
    ];

    require __DIR__ . '/includes/views/yazi.php';
}

function render_yasal(string $key, string $baslik): void
{
    global $pageTitle, $pageDesc, $extraJsonLd;
    $pageTitle = $baslik;
    $pageDesc = $baslik . ' - ' . ayar('site_baslik', 'ATA SU Rent A Car') . ' resmi metni.';
    $icerik = ayar($key, '<p>İçerik henüz hazır değil.</p>');
    $extraJsonLd[] = [
        '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => SITE_URL],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $baslik, 'item' => SITE_URL . '/' . ($_GET['sayfa'] ?? '')],
        ],
    ];
    require __DIR__ . '/includes/views/yasal.php';
}

function render_404(): void
{
    global $pageTitle, $pageDesc, $robotsMeta;
    http_response_code(404);
    $pageTitle = 'Sayfa Bulunamadı';
    $pageDesc = 'Aradığınız sayfa bulunamadı. Ana sayfaya dönmek için tıklayın.';
    $robotsMeta = 'noindex, nofollow';
    require __DIR__ . '/includes/views/404.php';
}
