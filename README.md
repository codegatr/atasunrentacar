# ATA SU Rent A Car

Konya merkezli **ATA SU Rent A Car** firması için geliştirilmiş tam donanımlı araç kiralama yönetim sistemi.

🌐 **Canlı Site:** [atasurentacar.com](https://atasurentacar.com)

---

## Özellikler

### 🌍 Halka Açık Site
- Modern, responsive tasarım (mobil uyumlu)
- Anasayfa: hero alan, araç arama, popüler araçlar, avantajlar, müşteri yorumları, blog
- Araç filosu listesi (kategori / vites / yakıt filtreli)
- Araç detay sayfası (galeri, fiyatlandırma, AJAX fiyat hesaplama)
- Online rezervasyon formu
- Blog (SEO odaklı yazılar)
- İletişim formu + Google Maps entegrasyonu
- Yasal sayfalar: KVKK, Gizlilik, Çerez Politikası, Kiralama Sözleşmesi

### 🛠️ Yönetim Paneli
- Dashboard (istatistikler, bekleyen işlemler)
- Araç yönetimi (CRUD, çoklu fotoğraf yükleme)
- Rezervasyon yönetimi (durum, ödeme takibi)
- Müşteri yönetimi
- Sigorta, muayene, bakım, hasar takibi
- Gelir/gider muhasebesi + raporlar
- Blog yönetimi
- Yorum ve iletişim mesajları
- Kullanıcı/rol yönetimi (admin/editor/operator)
- GitHub release tabanlı otomatik güncelleme sistemi

### 🚀 SEO
- Schema.org JSON-LD: AutoRental, Vehicle, Product, BreadcrumbList, FAQ, Article
- Dinamik sitemap.xml
- Otomatik robots.txt
- Open Graph + Twitter Card
- Search Console / Yandex / Bing doğrulama
- Google Analytics & Tag Manager
- Facebook Pixel
- Anahtar kelime odaklı içerik

### 📧 E-posta Bildirimleri
- Native `mail()` veya SMTP
- Rezervasyon ve iletişim formu otomatik bildirim
- Müşteriye otomatik onay e-postası
- HTML şablon (logo + brand renkler)

---

## Teknik Bilgiler

- **PHP:** 8.3+
- **Veritabanı:** MariaDB / MySQL 5.7+
- **Mimari:** Tek dosya yönlendirici, MVC framework yok
- **Bağımlılık:** Yok (PHPMailer dahil sıfır external library)
- **Hosting:** DirectAdmin uyumlu, paylaşımlı hosting destekli
- **PDO + Prepared Statements:** SQL Injection koruması
- **CSRF Token:** Tüm formlarda
- **Güvenli Şifre:** bcrypt (`PASSWORD_BCRYPT`)
- **UTF-8 utf8mb4:** Tam Türkçe karakter desteği

---

## Kurulum

1. ZIP dosyasını sunucunuza (`public_html/`) çıkartın
2. `assets/uploads/` klasörüne **755** izni verin
3. Tarayıcıdan `https://siteadi.com/install.php` adresini açın
4. 5 adımlık kurulum sihirbazını takip edin:
   - Sistem gereksinim kontrolü
   - Veritabanı bağlantı bilgileri
   - Otomatik veritabanı kurulumu (migrations)
   - Yönetici kullanıcı oluşturma
   - Tamamlama
5. **`install.php` dosyasını silin** (güvenlik için)
6. `https://siteadi.com/admin/giris.php` ile yönetim paneline giriş yapın

---

## Klasör Yapısı

```
.
├── index.php                # Ana yönlendirici
├── install.php              # Kurulum sihirbazı (kurulumdan sonra silinir)
├── manifest.json            # Sürüm bilgileri ve dosya hash'leri
├── config.sample.php        # Konfigürasyon şablonu
├── .htaccess                # URL rewrite, güvenlik, cache
│
├── admin/                   # Yönetim paneli
│   ├── giris.php
│   ├── index.php            # Dashboard
│   ├── araclar.php / arac-duzenle.php
│   ├── rezervasyonlar.php / rezervasyon-duzenle.php
│   ├── musteriler.php / musteri-duzenle.php
│   ├── sigortalar.php, muayeneler.php, bakimlar.php, hasarlar.php
│   ├── gelir-gider.php, raporlar.php
│   ├── bloglar.php / blog-duzenle.php
│   ├── yorumlar.php, iletisim-mesajlari.php
│   ├── ayarlar.php
│   ├── kullanicilar.php / kullanici-duzenle.php
│   ├── guncelleme.php       # Otomatik güncelleme
│   └── assets/              # Admin CSS/JS
│
├── includes/                # Çekirdek sistem
│   ├── bootstrap.php
│   ├── baglanti.php         # PDO veritabanı sınıfı
│   ├── fonksiyonlar.php     # Yardımcı fonksiyonlar
│   ├── migration.php        # Migration runner
│   ├── mail.php             # E-posta gönderici (SMTP/native)
│   ├── layout_basla.php     # Header + SEO meta
│   ├── layout_bitir.php     # Footer
│   └── views/               # Sayfa şablonları
│
├── migrations/              # SQL migrationları
│   ├── 001_baslangic_semasi.sql
│   ├── 002_baslangic_verileri.sql
│   ├── 003_yasal_metinler.sql
│   ├── 004_seo_alanlari.sql
│   ├── 005_baslangic_bloglar.sql
│   └── 006_baslangic_araclari.sql
│
└── assets/
    ├── css/style.css
    ├── js/main.js
    ├── img/
    └── uploads/             # Yüklenen dosyalar (gitignore'da)
```

---

## Güncelleme

Sistem, GitHub release ZIP'leri üzerinden otomatik güncelleme destekler.

1. **Admin → Güncelleme** sayfasından kaynak URL'sini ayarlayın
2. "Güncelleme Kontrol Et" → "Şimdi Güncelle"
3. Otomatik yedekleme (`assets/yedekler/YYYYMMDD_HHMMSS/`) alınır
4. Hash doğrulaması (sha256) ile bütünlük kontrolü
5. `config.php`, `.htaccess`, `assets/uploads/` korunur (üzerine yazılmaz)

---

## Geliştirme Notları

### Kodlama Stili
- Türkçe değişken/fonksiyon isimleri (yerel takım kolaylığı için)
- ASCII-only PHP kaynak (üretici-tüketici kodlama uyumu)
- Görünür Türkçe metinler tam Unicode UTF-8

### Veritabanı
- Tablo öneki: `atasu_` (config'den özelleştirilebilir)
- Tüm tablolar `utf8mb4_unicode_ci`
- Foreign key constraint'lar aktif
- Soft-delete yok; kayıtlar `aktif = 0` ile pasifize edilir

### Güvenlik
- Tüm form girdileri `csrf_zorunlu()` doğrulamasından geçer
- Tüm SQL sorguları PDO prepared statement
- Şifre: `password_hash()` + `PASSWORD_BCRYPT`
- Session ID `session_regenerate_id()` ile yenileniyor
- HttpOnly + SameSite=Lax cookie

---

## Lisans

Bu yazılım **ATA SU Rent A Car** ve **CODEGA** için özel olarak geliştirilmiştir.
Tüm hakları saklıdır.

---

## Geliştirici

[CODEGA](https://codega.com.tr) - Web Tasarım & Yazılım Geliştirme

---

**Sürüm:** 1.0.4
**Son Güncelleme:** Nisan 2026
