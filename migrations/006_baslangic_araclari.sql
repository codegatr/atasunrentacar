-- ATA SU Rent A Car - Migration 006: Baslangic araclari
-- Not: Plakalar gecici degerlerdir (PLAKA-1 vs). Admin panelinden gercek plakalari girin.
-- Fiyatlar varsayilan olarak 0 birakildi; admin panelinden girin.

INSERT IGNORE INTO {{prefix}}araclar
  (plaka, marka, model, slug, yil, kategori_id, vites, yakit, koltuk_sayisi, bagaj_sayisi, kapi_sayisi, klima, gunluk_fiyat, aciklama, ozellikler, durum, aktif, one_cikan, seo_baslik, seo_aciklama)
VALUES
  -- Fiat Egea 2019 - Ekonomi - Manuel - Dizel
  ('42-EGEA-19', 'Fiat', 'Egea', 'fiat-egea-2019',
   2019, 1, 'Manuel', 'Motorin', 5, 2, 4, 1,
   0,
   'Fiat Egea 2019 model, manuel vites, dizel, ekonomik yakit tuketimi, sehir ve uzun yol icin ideal aile aracidir.',
   'ABS\nESP\nKlima\nElektrikli camlar\nMerkezi kilit\nUSB / Bluetooth\nABS / EBD\nHava yastigi (surucu + yolcu)\nDirek hidrolik\nServis bakimli',
   'musait', 1, 1,
   'Fiat Egea 2019 Kiralama Konya - Ekonomik Dizel Aile Araci',
   'Konya Fiat Egea kiralama: 2019 model, manuel vites, dizel, 5 kisilik. Ekonomik yakit, dustuk maliyet, gunluk-haftalik-aylik kiralama secenekleri.'),

  -- Dacia Sandero Stepway 2017 - Ekonomi - Manuel - Dizel
  ('42-DCS-17', 'Dacia', 'Sandero Stepway', 'dacia-sandero-stepway-2017',
   2017, 1, 'Manuel', 'Motorin', 5, 2, 5, 1,
   0,
   'Dacia Sandero Stepway 2017 model, manuel vites, dizel motor. Yuksek surus pozisyonu, genis bagaj hacmi ve dusuk yakit tuketimi ile ekonomik secimdir.',
   'ABS\nESP\nKlima\nElektrikli camlar\nMerkezi kilit\nUSB / AUX\nHava yastiklari\nYukseltilmis sasi\nGenis bagaj\nServis bakimli',
   'musait', 1, 0,
   'Dacia Sandero Stepway 2017 Kiralama Konya - Ekonomik Crossover',
   'Konya Dacia Sandero Stepway kiralama: 2017 model, manuel, dizel, 5 kisilik. Yuksek sasi, genis bagaj, dusuk yakit tuketimi.'),

  -- Renault Symbol 2016 - Ekonomi - Manuel - Dizel
  ('42-SYM-16', 'Renault', 'Symbol', 'renault-symbol-2016',
   2016, 1, 'Manuel', 'Motorin', 5, 2, 4, 1,
   0,
   'Renault Symbol 2016 model, manuel vites, dizel motor. Genis ic hacim, dusuk yakit tuketimi ile sehir ve sehirler arasi yolculuklar icin ideal sedan.',
   'ABS\nESP\nKlima\nElektrikli on cam\nMerkezi kilit\nRadio / USB\nHava yastiklari\nGenis bagaj\nDusuk yakit tuketimi\nServis bakimli',
   'musait', 1, 0,
   'Renault Symbol 2016 Kiralama Konya - Ekonomik Sedan',
   'Konya Renault Symbol kiralama: 2016 model, manuel, dizel, 5 kisilik sedan. Ekonomik yakit, ferah bagaj, sehir ve uzun yol icin ideal.'),

  -- Volkswagen Jetta 2012 - Konfor - Otomatik - Dizel
  ('42-JETTA-12', 'Volkswagen', 'Jetta', 'volkswagen-jetta-2012',
   2012, 2, 'Otomatik', 'Motorin', 5, 2, 4, 1,
   0,
   'Volkswagen Jetta 2012 model, otomatik (DSG) vites, dizel motor. Konforlu surus, sessiz kabin, otomatik vites kolayligi ile uzun yolculuklarda ust duzey rahatlik.',
   'ABS\nESP\nKlima (otomatik)\nElektrikli camlar (4)\nMerkezi kilit\nMultimedya / Bluetooth\nHava yastiklari\nDeri direksiyon\nOtomatik DSG vites\nServis bakimli',
   'musait', 1, 0,
   'Volkswagen Jetta 2012 Kiralama Konya - Otomatik Vites Konfor',
   'Konya Volkswagen Jetta kiralama: 2012 model, otomatik vites (DSG), dizel, 5 kisilik. Konforlu sedan, uzun yolculuk icin ideal.');
