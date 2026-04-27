-- ATA SU Rent A Car - Baslangic Semasi
-- {{prefix}} = config'deki DB_PREFIX

-- Ayarlar
CREATE TABLE IF NOT EXISTS {{prefix}}ayarlar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    anahtar VARCHAR(100) NOT NULL UNIQUE,
    deger TEXT NULL,
    aciklama VARCHAR(255) NULL,
    grup VARCHAR(50) NULL DEFAULT 'genel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanicilar (admin)
CREATE TABLE IF NOT EXISTS {{prefix}}kullanicilar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    sifre_hash VARCHAR(255) NOT NULL,
    ad_soyad VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telefon VARCHAR(20) NULL,
    rol ENUM('admin','editor','operator') NOT NULL DEFAULT 'admin',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    son_giris DATETIME NULL,
    son_giris_ip VARCHAR(45) NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aktif (aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kategoriler
CREATE TABLE IF NOT EXISTS {{prefix}}kategoriler (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    aciklama TEXT NULL,
    sira INT NOT NULL DEFAULT 0,
    aktif TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Araclar
CREATE TABLE IF NOT EXISTS {{prefix}}araclar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plaka VARCHAR(15) NOT NULL UNIQUE,
    marka VARCHAR(50) NOT NULL,
    model VARCHAR(80) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    yil SMALLINT UNSIGNED NOT NULL,
    kategori_id INT UNSIGNED NULL,
    vites ENUM('Manuel','Otomatik','Yarı Otomatik') NOT NULL DEFAULT 'Manuel',
    yakit ENUM('Benzin','Motorin','LPG','Hibrit','Elektrik') NOT NULL DEFAULT 'Benzin',
    koltuk_sayisi TINYINT UNSIGNED NOT NULL DEFAULT 5,
    bagaj_sayisi TINYINT UNSIGNED NOT NULL DEFAULT 2,
    kapi_sayisi TINYINT UNSIGNED NOT NULL DEFAULT 4,
    motor_hacmi VARCHAR(20) NULL,
    motor_gucu VARCHAR(20) NULL,
    renk VARCHAR(40) NULL,
    klima TINYINT(1) NOT NULL DEFAULT 1,
    sasi_no VARCHAR(30) NULL,
    motor_no VARCHAR(30) NULL,
    km INT UNSIGNED NOT NULL DEFAULT 0,
    -- Ticari
    alis_tarihi DATE NULL,
    alis_fiyati DECIMAL(12,2) NULL,
    -- Fiyat
    gunluk_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
    haftalik_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
    aylik_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
    depozito DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_yas TINYINT UNSIGNED NOT NULL DEFAULT 21,
    min_ehliyet_yili TINYINT UNSIGNED NOT NULL DEFAULT 1,
    -- Icerik
    aciklama TEXT NULL,
    ozellikler TEXT NULL,
    -- Durum
    durum ENUM('musait','kirada','bakimda','satildi','rezerve') NOT NULL DEFAULT 'musait',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    one_cikan TINYINT(1) NOT NULL DEFAULT 0,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncelleme DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_durum (durum),
    INDEX idx_aktif (aktif),
    INDEX idx_kategori (kategori_id),
    CONSTRAINT fk_arac_kategori FOREIGN KEY (kategori_id) REFERENCES {{prefix}}kategoriler(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Arac resimleri
CREATE TABLE IF NOT EXISTS {{prefix}}arac_resimler (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arac_id INT UNSIGNED NOT NULL,
    dosya VARCHAR(255) NOT NULL,
    sira INT NOT NULL DEFAULT 0,
    ana_resim TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_arac (arac_id),
    CONSTRAINT fk_resim_arac FOREIGN KEY (arac_id) REFERENCES {{prefix}}araclar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Musteriler
CREATE TABLE IF NOT EXISTS {{prefix}}musteriler (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(80) NOT NULL,
    soyad VARCHAR(80) NOT NULL,
    tc_no VARCHAR(11) NULL,
    pasaport_no VARCHAR(30) NULL,
    telefon VARCHAR(20) NOT NULL,
    email VARCHAR(150) NULL,
    dogum_tarihi DATE NULL,
    ehliyet_no VARCHAR(30) NULL,
    ehliyet_sinifi VARCHAR(10) NULL,
    ehliyet_tarihi DATE NULL,
    adres TEXT NULL,
    sehir VARCHAR(60) NULL,
    notlar TEXT NULL,
    kara_liste TINYINT(1) NOT NULL DEFAULT 0,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_telefon (telefon),
    INDEX idx_tc (tc_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rezervasyonlar
CREATE TABLE IF NOT EXISTS {{prefix}}rezervasyonlar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rezervasyon_no VARCHAR(20) NOT NULL UNIQUE,
    arac_id INT UNSIGNED NOT NULL,
    musteri_id INT UNSIGNED NULL,
    -- Eger online rezervasyon ise, musteri kaydi olmadan da gelebilir
    misafir_ad VARCHAR(80) NULL,
    misafir_soyad VARCHAR(80) NULL,
    misafir_telefon VARCHAR(20) NULL,
    misafir_email VARCHAR(150) NULL,
    -- Tarihler
    alis_tarihi DATETIME NOT NULL,
    iade_tarihi DATETIME NOT NULL,
    alis_yeri VARCHAR(150) NULL,
    iade_yeri VARCHAR(150) NULL,
    -- Fiyat
    gunluk_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
    toplam_gun INT NOT NULL DEFAULT 1,
    arac_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
    ek_hizmet_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
    indirim_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
    kdv_orani DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    kdv_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
    toplam_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
    depozito_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
    -- Durum
    durum ENUM('beklemede','onaylandi','teslim','iade','iptal') NOT NULL DEFAULT 'beklemede',
    odeme_durumu ENUM('odenmedi','kismi','odendi','iade') NOT NULL DEFAULT 'odenmedi',
    odeme_yontemi ENUM('nakit','havale','kart','online') NULL,
    -- Teslim/Iade km
    teslim_km INT UNSIGNED NULL,
    iade_km INT UNSIGNED NULL,
    teslim_yakit TINYINT UNSIGNED NULL,
    iade_yakit TINYINT UNSIGNED NULL,
    -- Notlar
    musteri_notu TEXT NULL,
    admin_notu TEXT NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    guncelleme DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_arac (arac_id),
    INDEX idx_musteri (musteri_id),
    INDEX idx_durum (durum),
    INDEX idx_alis (alis_tarihi),
    INDEX idx_iade (iade_tarihi),
    CONSTRAINT fk_rez_arac FOREIGN KEY (arac_id) REFERENCES {{prefix}}araclar(id) ON DELETE RESTRICT,
    CONSTRAINT fk_rez_musteri FOREIGN KEY (musteri_id) REFERENCES {{prefix}}musteriler(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ek hizmetler katalogu
CREATE TABLE IF NOT EXISTS {{prefix}}ek_hizmetler (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(120) NOT NULL,
    aciklama VARCHAR(255) NULL,
    fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
    fiyat_tipi ENUM('gunluk','tek') NOT NULL DEFAULT 'gunluk',
    aktif TINYINT(1) NOT NULL DEFAULT 1,
    sira INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rezervasyon ek hizmet pivot
CREATE TABLE IF NOT EXISTS {{prefix}}rez_ek_hizmet (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rezervasyon_id INT UNSIGNED NOT NULL,
    ek_hizmet_id INT UNSIGNED NOT NULL,
    miktar INT NOT NULL DEFAULT 1,
    birim_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0,
    toplam DECIMAL(12,2) NOT NULL DEFAULT 0,
    INDEX idx_rez (rezervasyon_id),
    CONSTRAINT fk_reh_rez FOREIGN KEY (rezervasyon_id) REFERENCES {{prefix}}rezervasyonlar(id) ON DELETE CASCADE,
    CONSTRAINT fk_reh_eh FOREIGN KEY (ek_hizmet_id) REFERENCES {{prefix}}ek_hizmetler(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sigortalar
CREATE TABLE IF NOT EXISTS {{prefix}}sigortalar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arac_id INT UNSIGNED NOT NULL,
    tip ENUM('trafik','kasko','imm') NOT NULL DEFAULT 'kasko',
    sirket VARCHAR(120) NOT NULL,
    police_no VARCHAR(60) NULL,
    baslangic_tarihi DATE NOT NULL,
    bitis_tarihi DATE NOT NULL,
    tutar DECIMAL(10,2) NOT NULL DEFAULT 0,
    dosya VARCHAR(255) NULL,
    notlar TEXT NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_arac (arac_id),
    INDEX idx_bitis (bitis_tarihi),
    CONSTRAINT fk_sig_arac FOREIGN KEY (arac_id) REFERENCES {{prefix}}araclar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Muayeneler
CREATE TABLE IF NOT EXISTS {{prefix}}muayeneler (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arac_id INT UNSIGNED NOT NULL,
    muayene_tarihi DATE NOT NULL,
    sonraki_muayene DATE NOT NULL,
    km INT UNSIGNED NULL,
    sonuc ENUM('gecti','agir_kusur','hafif_kusur','ret') NOT NULL DEFAULT 'gecti',
    istasyon VARCHAR(150) NULL,
    tutar DECIMAL(10,2) NULL,
    dosya VARCHAR(255) NULL,
    notlar TEXT NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_arac (arac_id),
    INDEX idx_sonraki (sonraki_muayene),
    CONSTRAINT fk_muay_arac FOREIGN KEY (arac_id) REFERENCES {{prefix}}araclar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bakimlar
CREATE TABLE IF NOT EXISTS {{prefix}}bakimlar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arac_id INT UNSIGNED NOT NULL,
    tarih DATE NOT NULL,
    tip VARCHAR(80) NOT NULL,
    km INT UNSIGNED NULL,
    sonraki_bakim_km INT UNSIGNED NULL,
    sonraki_bakim_tarihi DATE NULL,
    yer VARCHAR(150) NULL,
    tutar DECIMAL(10,2) NULL,
    dosya VARCHAR(255) NULL,
    notlar TEXT NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_arac (arac_id),
    INDEX idx_sonraki_tarih (sonraki_bakim_tarihi),
    CONSTRAINT fk_bak_arac FOREIGN KEY (arac_id) REFERENCES {{prefix}}araclar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hasarlar
CREATE TABLE IF NOT EXISTS {{prefix}}hasarlar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    arac_id INT UNSIGNED NOT NULL,
    rezervasyon_id INT UNSIGNED NULL,
    tarih DATE NOT NULL,
    aciklama TEXT NOT NULL,
    tutar DECIMAL(10,2) NOT NULL DEFAULT 0,
    sigorta_kapsiyor TINYINT(1) NOT NULL DEFAULT 0,
    sigorta_dosya_no VARCHAR(60) NULL,
    durum ENUM('acik','islemde','kapali') NOT NULL DEFAULT 'acik',
    fotograflar TEXT NULL,
    notlar TEXT NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_arac (arac_id),
    INDEX idx_rez (rezervasyon_id),
    CONSTRAINT fk_has_arac FOREIGN KEY (arac_id) REFERENCES {{prefix}}araclar(id) ON DELETE CASCADE,
    CONSTRAINT fk_has_rez FOREIGN KEY (rezervasyon_id) REFERENCES {{prefix}}rezervasyonlar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Gelir Gider
CREATE TABLE IF NOT EXISTS {{prefix}}gelir_gider (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tip ENUM('gelir','gider') NOT NULL,
    kategori VARCHAR(80) NULL,
    tutar DECIMAL(12,2) NOT NULL,
    tarih DATE NOT NULL,
    aciklama VARCHAR(255) NULL,
    arac_id INT UNSIGNED NULL,
    rezervasyon_id INT UNSIGNED NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tarih (tarih),
    INDEX idx_tip (tip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Iletisim mesajlari
CREATE TABLE IF NOT EXISTS {{prefix}}iletisim_mesajlari (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefon VARCHAR(20) NULL,
    konu VARCHAR(200) NULL,
    mesaj TEXT NOT NULL,
    okundu TINYINT(1) NOT NULL DEFAULT 0,
    ip VARCHAR(45) NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_okundu (okundu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Yorumlar (musteri yorumlari - frontend)
CREATE TABLE IF NOT EXISTS {{prefix}}yorumlar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(80) NOT NULL,
    sehir VARCHAR(60) NULL,
    yorum TEXT NOT NULL,
    puan TINYINT UNSIGNED NOT NULL DEFAULT 5,
    onayli TINYINT(1) NOT NULL DEFAULT 0,
    sira INT NOT NULL DEFAULT 0,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blog yazilari
CREATE TABLE IF NOT EXISTS {{prefix}}bloglar (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    baslik VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    ozet VARCHAR(500) NULL,
    icerik LONGTEXT NULL,
    kapak VARCHAR(255) NULL,
    yazar VARCHAR(120) NULL,
    yayin_tarihi DATE NOT NULL,
    durum ENUM('taslak','yayinda') NOT NULL DEFAULT 'yayinda',
    goruntuleme INT UNSIGNED NOT NULL DEFAULT 0,
    seo_baslik VARCHAR(200) NULL,
    seo_aciklama VARCHAR(300) NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_durum (durum),
    INDEX idx_yayin (yayin_tarihi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log
CREATE TABLE IF NOT EXISTS {{prefix}}log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kullanici_id INT UNSIGNED NULL,
    islem VARCHAR(80) NOT NULL,
    aciklama TEXT NULL,
    ip VARCHAR(45) NULL,
    olusturma DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_kullanici (kullanici_id),
    INDEX idx_olusturma (olusturma)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
