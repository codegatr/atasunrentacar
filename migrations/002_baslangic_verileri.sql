-- ATA SU Rent A Car - Baslangic Verileri

-- Kategoriler
INSERT IGNORE INTO {{prefix}}kategoriler (id, ad, slug, sira, aktif) VALUES
(1, 'Ekonomi', 'ekonomi', 1, 1),
(2, 'Konfor', 'konfor', 2, 1),
(3, 'SUV', 'suv', 3, 1),
(4, 'Lüks', 'luks', 4, 1),
(5, 'Minivan', 'minivan', 5, 1),
(6, 'Ticari', 'ticari', 6, 1);

-- Ek hizmetler
INSERT IGNORE INTO {{prefix}}ek_hizmetler (ad, aciklama, fiyat, fiyat_tipi, aktif, sira) VALUES
('GPS Navigasyon', 'Tüm Türkiye haritaları yüklü cihaz', 75.00, 'gunluk', 1, 1),
('Çocuk Koltuğu', 'Bebek ve çocuk koltuğu', 100.00, 'gunluk', 1, 2),
('Ek Sürücü', 'İkinci sürücü ataması', 150.00, 'tek', 1, 3),
('Tam Kasko', 'Hasarsız teslim güvencesi', 200.00, 'gunluk', 1, 4),
('Havalimanı Teslim', 'Havalimanına araç getirilmesi', 300.00, 'tek', 1, 5),
('Adrese Teslim', 'Konya il sınırları içinde', 200.00, 'tek', 1, 6);

-- Ayarlar
INSERT IGNORE INTO {{prefix}}ayarlar (anahtar, deger, aciklama, grup) VALUES
('site_baslik', 'ATA SU Rent A Car - Konya Araç Kiralama', 'Tarayıcı sekmesindeki başlık', 'genel'),
('site_aciklama', 'Konya''nın güvenilir araç kiralama firması. ATA SU Rent A Car ile konforlu, ekonomik ve güvenli yolculuklar.', 'Meta description', 'seo'),
('site_anahtar_kelimeler', 'konya araç kiralama, rent a car konya, atasu, ata su rent a car, oto kiralama', 'Meta keywords', 'seo'),
('telefon', '0 533 000 00 00', 'Ana iletişim numarası', 'iletisim'),
('telefon2', '', 'İkinci telefon', 'iletisim'),
('whatsapp', '905330000000', 'WhatsApp numarası (90 ile başlamalı)', 'iletisim'),
('email', 'info@atasurentacar.com', 'E-posta adresi', 'iletisim'),
('adres', 'Konya', 'Açık adres', 'iletisim'),
('calisma_saati', '7/24 Hizmet', 'Çalışma saatleri', 'iletisim'),
('harita_embed', '', 'Google Maps iframe src', 'iletisim'),
('facebook', '', 'Facebook URL', 'sosyal'),
('instagram', '', 'Instagram URL', 'sosyal'),
('twitter', '', 'Twitter/X URL', 'sosyal'),
('youtube', '', 'YouTube URL', 'sosyal'),
('arac_sayisi', '0', 'Otomatik güncellenir', 'genel'),
('mutlu_musteri', '500', 'Hakkımızda istatistik', 'genel'),
('hizmet_yili', '5', 'Kaç yıldır hizmet', 'genel'),
('puan', '5', 'Müşteri puanı', 'genel'),
('hero_baslik', 'Hayalinizdeki Araçla Yola Çıkın', 'Anasayfa hero başlığı', 'icerik'),
('hero_alt_baslik', 'Konya''nın Güvenilir Araç Kiralama Firması', 'Hero üst metin', 'icerik'),
('hero_aciklama', 'Konforlu, güvenilir ve uygun fiyatlı araçlarımızla Konya ve çevresinde özgürce gezin. 7/24 hizmet, havalimanı teslim.', 'Hero açıklama', 'icerik'),
('hakkimizda_kisa', 'ATA SU Rent A Car, Konya''da güvenilir ve konforlu araç kiralama hizmeti sunan öncü firmalardan biridir.', 'Anasayfa hakkımızda kutusu', 'icerik'),
('hakkimizda_detay', '<p>ATA SU Rent A Car olarak yıllar içinde binlerce müşterimize sorunsuz kiralama deneyimi sunarak sektörde güvenilir bir yer edinmiş bulunmaktayız.</p><p>Misafirlerimize hızlı, kolay ve ekonomik çözümler sağlayarak şehirdeki seyahati özgür ve keyifli hale getiriyoruz.</p>', 'Hakkımızda sayfası içeriği', 'icerik'),
('logo', '', 'Logo dosya yolu', 'genel'),
('favicon', '', 'Favicon dosya yolu', 'genel'),
('min_kiralama_gun', '1', 'Minimum kiralama süresi', 'rezervasyon'),
('rezervasyon_aktif', '1', 'Online rezervasyon açık mı', 'rezervasyon'),
('kdv_orani', '20', 'KDV oranı (%)', 'rezervasyon'),
('kvkk_aciklama', '<h2>KVKK Aydınlatma Metni</h2><p>Buraya KVKK metninizi ekleyiniz.</p>', 'KVKK sayfası', 'yasal'),
('gizlilik_politikasi', '<h2>Gizlilik Politikası</h2><p>Buraya gizlilik politikası metninizi ekleyiniz.</p>', 'Gizlilik politikası', 'yasal'),
('cerez_politikasi', '<h2>Çerez Politikası</h2><p>Buraya çerez politikası metninizi ekleyiniz.</p>', 'Çerez politikası', 'yasal'),
('google_analytics', '', 'Google Analytics ID (G-XXXX)', 'seo'),
('mail_smtp_host', '', 'SMTP Host', 'mail'),
('mail_smtp_port', '587', 'SMTP Port', 'mail'),
('mail_smtp_user', '', 'SMTP Kullanıcı', 'mail'),
('mail_smtp_pass', '', 'SMTP Şifre', 'mail'),
('mail_smtp_secure', 'tls', 'tls/ssl/yok', 'mail'),
('mail_from', 'info@atasurentacar.com', 'Gönderici e-posta', 'mail');

-- Ornek yorumlar
INSERT IGNORE INTO {{prefix}}yorumlar (ad, sehir, yorum, puan, onayli, sira) VALUES
('Mehmet K.', 'Ankara', 'Konya''da ilk defa geldim. ATA SU Rent A Car gerçekten çok profesyonel. Havalimanından çıktım, araç hazırdı. Her şey tertemizdi.', 5, 1, 1),
('Ayşe D.', 'İstanbul', 'Konya yolları malum, ama verdikleri SUV araç harikaydı. Soğukta bile içim rahat gezdim.', 5, 1, 2),
('Yusuf T.', 'İzmir', 'Araç tertemizdi, fiyat da gayet uygundu. GPS ve çocuk koltuğu da ücretsiz sağladılar, ailece çok memnun kaldık.', 5, 1, 3);
