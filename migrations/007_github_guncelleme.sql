-- ATA SU Rent A Car - Migration 007: GitHub guncelleme entegrasyonu

-- Yeni ayar anahtarlari
INSERT IGNORE INTO {{prefix}}ayarlar (anahtar, deger, aciklama, grup) VALUES
('guncelleme_github_repo', 'codegatr/atasunrentacar', 'GitHub repo adi (kullanici/repo)', 'sistem'),
('guncelleme_kanali', 'releases', 'Guncelleme kanali: releases veya branch', 'sistem'),
('guncelleme_branch', 'main', 'Branch kanali icin branch adi', 'sistem'),
('guncelleme_github_token', '', 'Ozel repo erisimi icin GitHub Personal Access Token', 'sistem');

-- Eski URL bazli kaynak ayarini temizle (artik kullanilmiyor)
UPDATE {{prefix}}ayarlar SET deger = '' WHERE anahtar = 'guncelleme_kaynak_url';
