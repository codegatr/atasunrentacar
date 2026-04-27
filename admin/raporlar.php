<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Raporlar';

$tarihBitis = $_GET['bitis'] ?? date('Y-m-d');
$tarihBaslangic = $_GET['baslangic'] ?? date('Y-m-d', strtotime('-30 days'));

// Genel istatistikler
$rezToplam = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE alis_tarihi BETWEEN ? AND ?", [$tarihBaslangic, $tarihBitis])['c'] ?? 0);
$rezOnayli = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE alis_tarihi BETWEEN ? AND ? AND durum IN ('onaylandi','teslim','iade')", [$tarihBaslangic, $tarihBitis])['c'] ?? 0);
$rezIptal = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE alis_tarihi BETWEEN ? AND ? AND durum='iptal'", [$tarihBaslangic, $tarihBitis])['c'] ?? 0);
$rezGelir = (float)(DB::tek("SELECT COALESCE(SUM(toplam_tutar),0) t FROM " . DB::tablo('rezervasyonlar') . " WHERE alis_tarihi BETWEEN ? AND ? AND durum IN ('onaylandi','teslim','iade')", [$tarihBaslangic, $tarihBitis])['t'] ?? 0);

$gg = DB::tek("SELECT
    COALESCE(SUM(CASE WHEN tip='gelir' THEN tutar END),0) gelir,
    COALESCE(SUM(CASE WHEN tip='gider' THEN tutar END),0) gider
    FROM " . DB::tablo('gelir_gider') . " WHERE tarih BETWEEN ? AND ?", [$tarihBaslangic, $tarihBitis]);
$toplamGelir = (float)$gg['gelir'] + $rezGelir;
$toplamGider = (float)$gg['gider'];
$netKar = $toplamGelir - $toplamGider;

// Arac filo durumu
$filoDurum = DB::liste("SELECT durum, COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE aktif=1 GROUP BY durum");
$durumMap = ['musait' => 0, 'kirada' => 0, 'rezerve' => 0, 'bakimda' => 0, 'satildi' => 0];
foreach ($filoDurum as $d) {
    $durumMap[$d['durum']] = (int)$d['c'];
}
$toplamAktifArac = array_sum($durumMap) - $durumMap['satildi'];

// En cok kiralanan araclar
$enCokKiralanan = DB::liste("SELECT a.id, a.marka, a.model, a.plaka, COUNT(r.id) rez_sayisi, COALESCE(SUM(r.toplam_tutar),0) toplam_gelir
    FROM " . DB::tablo('araclar') . " a
    LEFT JOIN " . DB::tablo('rezervasyonlar') . " r ON r.arac_id = a.id AND r.alis_tarihi BETWEEN ? AND ? AND r.durum IN ('onaylandi','teslim','iade')
    GROUP BY a.id
    ORDER BY rez_sayisi DESC, toplam_gelir DESC
    LIMIT 10", [$tarihBaslangic, $tarihBitis]);

// Aylik gelir grafigi (son 12 ay)
$aylikGelir = DB::liste("SELECT DATE_FORMAT(alis_tarihi, '%Y-%m') ay, COALESCE(SUM(toplam_tutar),0) tutar
    FROM " . DB::tablo('rezervasyonlar') . "
    WHERE alis_tarihi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
      AND durum IN ('onaylandi','teslim','iade')
    GROUP BY ay
    ORDER BY ay");
$maxTutar = 0;
foreach ($aylikGelir as $a) { if ($a['tutar'] > $maxTutar) $maxTutar = (float)$a['tutar']; }
if ($maxTutar <= 0) $maxTutar = 1;

// Kategori bazli gelir
$kategoriGelir = DB::liste("SELECT k.ad, COUNT(r.id) rez_sayisi, COALESCE(SUM(r.toplam_tutar),0) tutar
    FROM " . DB::tablo('kategoriler') . " k
    LEFT JOIN " . DB::tablo('araclar') . " a ON a.kategori_id = k.id
    LEFT JOIN " . DB::tablo('rezervasyonlar') . " r ON r.arac_id = a.id AND r.alis_tarihi BETWEEN ? AND ? AND r.durum IN ('onaylandi','teslim','iade')
    GROUP BY k.id
    ORDER BY tutar DESC", [$tarihBaslangic, $tarihBitis]);

// Yeni musteriler
$yeniMusteri = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('musteriler') . " WHERE DATE(olusturma) BETWEEN ? AND ?", [$tarihBaslangic, $tarihBitis])['c'] ?? 0);

// Doluluk orani (basit: son 30 gunde rezerveli gun / (toplam arac * 30))
$rezerveliGun = (int)(DB::tek("SELECT COALESCE(SUM(toplam_gun),0) g FROM " . DB::tablo('rezervasyonlar') . " WHERE alis_tarihi BETWEEN ? AND ? AND durum IN ('onaylandi','teslim','iade')", [$tarihBaslangic, $tarihBitis])['g'] ?? 0);
$gunSayisi = max(1, gun_farki($tarihBaslangic, $tarihBitis));
$doluluk = $toplamAktifArac > 0 ? round(($rezerveliGun / ($toplamAktifArac * $gunSayisi)) * 100, 1) : 0;

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-icerik">
    <form method="get" class="filtre">
      <label>Tarih Araligi:</label>
      <input type="date" name="baslangic" value="<?= e($tarihBaslangic) ?>">
      <input type="date" name="bitis" value="<?= e($tarihBitis) ?>">
      <button class="btn btn-birincil">Filtrele</button>
      <a href="<?= admin_url('raporlar.php') ?>" class="btn btn-cerceve">Sıfırla</a>
    </form>
  </div>
</div>

<div class="ist-grid">
  <div class="ist-kart bilgi">
    <div class="ist-baslik">Rezervasyon</div>
    <div class="ist-deger"><?= $rezToplam ?></div>
    <div class="ist-alt"><?= $rezOnayli ?> onayli / <?= $rezIptal ?> iptal</div>
  </div>
  <div class="ist-kart basari">
    <div class="ist-baslik">Toplam Gelir</div>
    <div class="ist-deger"><?= tl($toplamGelir) ?></div>
  </div>
  <div class="ist-kart hata">
    <div class="ist-baslik">Toplam Gider</div>
    <div class="ist-deger"><?= tl($toplamGider) ?></div>
  </div>
  <div class="ist-kart <?= $netKar >= 0 ? 'basari' : 'uyari' ?>">
    <div class="ist-baslik">Net Kâr</div>
    <div class="ist-deger"><?= tl($netKar) ?></div>
  </div>
  <div class="ist-kart">
    <div class="ist-baslik">Yeni Müşteri</div>
    <div class="ist-deger"><?= $yeniMusteri ?></div>
  </div>
  <div class="ist-kart uyari">
    <div class="ist-baslik">Doluluk Oranı</div>
    <div class="ist-deger">%<?= $doluluk ?></div>
  </div>
</div>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Aylık Gelir (Son 12 Ay)</h2></div>
      <div class="kart-icerik">
        <?php if (!$aylikGelir): ?>
          <div class="bos-durum">Veri yok.</div>
        <?php else: ?>
          <div class="grafik-cubuk">
            <?php foreach ($aylikGelir as $ay): $yuzde = ($ay['tutar'] / $maxTutar) * 100; ?>
              <div class="cubuk-satir">
                <div class="cubuk-etiket"><?= e($ay['ay']) ?></div>
                <div class="cubuk-bar">
                  <div class="cubuk-dolu" style="width:<?= $yuzde ?>%"></div>
                </div>
                <div class="cubuk-deger"><?= tl($ay['tutar']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Filo Durumu</h2></div>
      <div class="kart-icerik">
        <table class="tablo">
          <tr><td>Müsait</td><td><strong style="color:#15803d"><?= $durumMap['musait'] ?></strong></td></tr>
          <tr><td>Kirada</td><td><strong style="color:#1e40af"><?= $durumMap['kirada'] ?></strong></td></tr>
          <tr><td>Rezerve</td><td><strong style="color:#a16207"><?= $durumMap['rezerve'] ?></strong></td></tr>
          <tr><td>Bakımda</td><td><strong style="color:#b91c1c"><?= $durumMap['bakimda'] ?></strong></td></tr>
          <tr><td>Satıldı</td><td><strong style="color:#6b7280"><?= $durumMap['satildi'] ?></strong></td></tr>
          <tr><td><strong>Aktif Filo</strong></td><td><strong><?= $toplamAktifArac ?></strong></td></tr>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="kart">
  <div class="kart-baslik"><h2>En Çok Kiralanan Araclar</h2></div>
  <div class="kart-icerik">
    <?php if (!$enCokKiralanan): ?>
      <div class="bos-durum">Veri yok.</div>
    <?php else: ?>
    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead><tr><th>#</th><th>Araç</th><th>Plaka</th><th>Rez. Sayısı</th><th>Toplam Gelir</th></tr></thead>
        <tbody>
        <?php foreach ($enCokKiralanan as $i => $a): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= e($a['marka'] . ' ' . $a['model']) ?></td>
            <td><?= e($a['plaka']) ?></td>
            <td><?= (int)$a['rez_sayisi'] ?></td>
            <td><?= tl($a['toplam_gelir']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="kart">
  <div class="kart-baslik"><h2>Kategori Bazlı Gelir</h2></div>
  <div class="kart-icerik">
    <?php if (!$kategoriGelir): ?>
      <div class="bos-durum">Veri yok.</div>
    <?php else: ?>
    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead><tr><th>Kategori</th><th>Rezervasyon</th><th>Gelir</th></tr></thead>
        <tbody>
        <?php foreach ($kategoriGelir as $k): ?>
          <tr>
            <td><strong><?= e($k['ad']) ?></strong></td>
            <td><?= (int)$k['rez_sayisi'] ?></td>
            <td><?= tl($k['tutar']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<style>
.grafik-cubuk { display:flex; flex-direction:column; gap:8px; }
.cubuk-satir { display:grid; grid-template-columns: 70px 1fr 110px; gap:10px; align-items:center; font-size:13px; }
.cubuk-bar { background:#f1f5f9; border-radius:4px; height:18px; overflow:hidden; }
.cubuk-dolu { background:linear-gradient(90deg, #3b82f6, #1e3a5f); height:100%; }
.cubuk-deger { text-align:right; font-weight:600; color:#1e3a5f; }
.ist-alt { font-size:12px; color:#64748b; margin-top:4px; }
</style>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
