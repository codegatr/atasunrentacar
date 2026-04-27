-- ATA SU Rent A Car - Migration 004: SEO alanlari ve verification

-- Araclar tablosuna SEO alanlari ekle
ALTER TABLE {{prefix}}araclar
  ADD COLUMN IF NOT EXISTS seo_baslik VARCHAR(160) NULL AFTER ozellikler,
  ADD COLUMN IF NOT EXISTS seo_aciklama VARCHAR(300) NULL AFTER seo_baslik,
  ADD COLUMN IF NOT EXISTS seo_anahtarlar VARCHAR(255) NULL AFTER seo_aciklama;

-- Bloglar tablosunda guncelleme alani yoksa ekle (Article schema icin)
ALTER TABLE {{prefix}}bloglar
  ADD COLUMN IF NOT EXISTS guncelleme DATETIME NULL ON UPDATE CURRENT_TIMESTAMP;

-- Yeni SEO ayar anahtarlari
INSERT IGNORE INTO {{prefix}}ayarlar (anahtar, deger, aciklama, grup) VALUES
('google_site_verification', '', 'Google Search Console dogrulama kodu (icerik metasi)', 'seo'),
('yandex_verification', '', 'Yandex Webmaster dogrulama kodu', 'seo'),
('bing_verification', '', 'Bing Webmaster dogrulama kodu', 'seo'),
('facebook_pixel', '', 'Facebook Pixel ID (orn: 1234567890)', 'seo'),
('google_tag_manager', '', 'Google Tag Manager ID (orn: GTM-XXXXX)', 'seo'),
('seo_default_image', '', 'Varsayilan paylasim gorseli (Open Graph fallback)', 'seo'),
('schema_business_type', 'AutoRental', 'Schema.org isletme tipi', 'seo');

-- Mevcut SEO icerigini varsayilan deger olarak guncelle (eger bos ise)
UPDATE {{prefix}}ayarlar SET deger = 'konya araç kiralama, konya rent a car, oto kiralama konya, günlük araç kiralama, haftalık araç kiralama, aylık araç kiralama, havalimanı araç kiralama, ucuz araç kiralama konya, ekonomik araç kiralama, otomatik vites kiralık araç, suv kiralama konya, ata su rent a car, kiralık araba konya, araba kiralama konya, lüks araç kiralama konya, ticari araç kiralama'
  WHERE anahtar = 'site_anahtar_kelimeler' AND (deger IS NULL OR deger = '' OR LENGTH(deger) < 30);

UPDATE {{prefix}}ayarlar SET deger = 'Konya araç kiralama hizmetinde güvenilir adres. Ekonomi, konfor, SUV ve lüks kiralık araç seçenekleri. Günlük, haftalık, aylık kiralama; havalimanı teslim; 7/24 destek. ATA SU Rent A Car güvencesiyle uygun fiyatlar.'
  WHERE anahtar = 'site_aciklama' AND (deger IS NULL OR deger = '' OR LENGTH(deger) < 60);

UPDATE {{prefix}}ayarlar SET deger = 'ATA SU Rent A Car - Konya Araç Kiralama, Rent A Car, Oto Kiralama'
  WHERE anahtar = 'site_baslik' AND (deger IS NULL OR deger = '' OR LENGTH(deger) < 25);
