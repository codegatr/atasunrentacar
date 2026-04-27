<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Panel';

// Istatistikler
$arac_t = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE aktif=1")['c'] ?? 0);
$arac_musait = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE aktif=1 AND durum='musait'")['c'] ?? 0);
$arac_kirada = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('araclar') . " WHERE aktif=1 AND durum='kirada'")['c'] ?? 0);
$musteri_t = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('musteriler'))['c'] ?? 0);

$rez_t = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar'))['c'] ?? 0);
$rez_bekleyen = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE durum='beklemede'")['c'] ?? 0);
$rez_aktif = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('rezervasyonlar') . " WHERE durum IN ('onaylandi','teslim')")['c'] ?? 0);

$ay_basi = date('Y-m-01');
$ay_sonu = date('Y-m-t');
$ay_gelir = (float)(DB::tek("SELECT COALESCE(SUM(toplam_tutar),0) t FROM " . DB::tablo('rezervasyonlar') . " WHERE durum != 'iptal' AND alis_tarihi BETWEEN ? AND ?", [$ay_basi, $ay_sonu])['t'] ?? 0);
$ay_gider = (float)(DB::tek("SELECT COALESCE(SUM(tutar),0) t FROM " . DB::tablo('gelir_gider') . " WHERE tip='gider' AND tarih BETWEEN ? AND ?", [$ay_basi, $ay_sonu])['t'] ?? 0);

// Yaklaşan sigortalar (30 gun)
$bitis = date('Y-m-d', strtotime('+30 days'));
$yaklaşanSigortalar = DB::liste(
    "SELECT s.*, a.plaka, a.marka, a.model FROM " . DB::tablo('sigortalar') . " s
     JOIN " . DB::tablo('araclar') . " a ON a.id = s.arac_id
     WHERE s.bitis_tarihi BETWEEN CURDATE() AND ?
     ORDER BY s.bitis_tarihi ASC LIMIT 5",
    [$bitis]
);

$yaklaşanMuayeneler = DB::liste(
    "SELECT m.*, a.plaka, a.marka, a.model FROM " . DB::tablo('muayeneler') . " m
     JOIN " . DB::tablo('araclar') . " a ON a.id = m.arac_id
     WHERE m.sonraki_muayene BETWEEN CURDATE() AND ?
     ORDER BY m.sonraki_muayene ASC LIMIT 5",
    [$bitis]
);

$sonRezervasyonlar = DB::liste(
    "SELECT r.*, a.plaka, a.marka, a.model
     FROM " . DB::tablo('rezervasyonlar') . " r
     LEFT JOIN " . DB::tablo('araclar') . " a ON a.id = r.arac_id
     ORDER BY r.olusturma DESC LIMIT 8"
);

$bekleyenMesajlar = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('iletisim_mesajlari') . " WHERE okundu=0")['c'] ?? 0);

require __DIR__ . '/_layout_basla.php';
?>

<div class="ist-grid">
  <div class="ist-kart bilgi">
    <div class="ist-ikon">🚗</div>
    <div class="ist-icerik">
      <strong><?= $arac_t ?></strong>
      <span>Toplam Arac (<?= $arac_musait ?> musait, <?= $arac_kirada ?> kirada)</span>
    </div>
  </div>
  <div class="ist-kart basari">
    <div class="ist-ikon">📅</div>
    <div class="ist-icerik">
      <strong><?= $rez_aktif ?></strong>
      <span>Aktif Rezervasyon (<?= $rez_t ?> toplam)</span>
    </div>
  </div>
  <div class="ist-kart uyari">
    <div class="ist-ikon">⏳</div>
    <div class="ist-icerik">
      <strong><?= $rez_bekleyen ?></strong>
      <span>Bekleyen Rezervasyon</span>
    </div>
  </div>
  <div class="ist-kart bilgi">
    <div class="ist-ikon">👥</div>
    <div class="ist-icerik">
      <strong><?= $musteri_t ?></strong>
      <span>Toplam Müşteri</span>
    </div>
  </div>
  <div class="ist-kart basari">
    <div class="ist-ikon">💰</div>
    <div class="ist-icerik">
      <strong><?= tl($ay_gelir) ?></strong>
      <span>Bu Ay Gelir</span>
    </div>
  </div>
  <div class="ist-kart hata">
    <div class="ist-ikon">📉</div>
    <div class="ist-icerik">
      <strong><?= tl($ay_gider) ?></strong>
      <span>Bu Ay Gider</span>
    </div>
  </div>
</div>

<div class="iki-sutun">
  <div class="kart">
    <div class="kart-baslik">
      <h2>Son Rezervasyonlar</h2>
      <a href="<?= admin_url('rezervasyonlar.php') ?>" class="btn btn-cerceve btn-mini">Tümü</a>
    </div>
    <div class="tablo-kapsayici">
      <?php if ($sonRezervasyonlar): ?>
      <table class="tablo">
        <thead>
          <tr>
            <th>No</th>
            <th>Araç / Müşteri</th>
            <th>Tarih</th>
            <th>Tutar</th>
            <th>Durum</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($sonRezervasyonlar as $r): ?>
          <tr>
            <td><a href="<?= admin_url('rezervasyon-duzenle.php?id=' . (int)$r['id']) ?>"><?= e($r['rezervasyon_no']) ?></a></td>
            <td>
              <strong><?= e(($r['marka'] ?? '') . ' ' . ($r['model'] ?? '')) ?></strong>
              <small style="display:block;color:var(--renk-yazi-acik);">
                <?= e(($r['misafir_ad'] ?? '') . ' ' . ($r['misafir_soyad'] ?? '')) ?>
              </small>
            </td>
            <td><?= tarih_tr($r['alis_tarihi']) ?></td>
            <td><?= tl((float)$r['toplam_tutar']) ?></td>
            <td><span class="rozet-tip rozet-<?= e($r['durum']) ?>"><?= e(ucfirst($r['durum'])) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div class="bos-durum">Henuz rezervasyon yok.</div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <?php if ($yaklaşanSigortalar): ?>
    <div class="kart">
      <div class="kart-baslik">
        <h2>⏰ Yaklaşan Sigortalar</h2>
      </div>
      <div class="kart-icerik" style="padding:0;">
        <?php foreach ($yaklaşanSigortalar as $s): ?>
          <div style="padding:12px 18px; border-bottom:1px solid var(--renk-cizgi);">
            <strong><?= e($s['plaka']) ?></strong> <?= e($s['marka'] . ' ' . $s['model']) ?>
            <div style="font-size:0.85rem; color:var(--renk-yazi-acik);">
              <?= e(ucfirst($s['tip'])) ?> - <?= tarih_tr($s['bitis_tarihi']) ?>
              <?php $kalan = (int)((strtotime($s['bitis_tarihi']) - time()) / 86400); ?>
              <strong style="color:<?= $kalan < 7 ? 'var(--renk-hata)' : 'var(--renk-uyari)' ?>;">(<?= $kalan ?> gun)</strong>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($yaklaşanMuayeneler): ?>
    <div class="kart">
      <div class="kart-baslik">
        <h2>📋 Yaklaşan Muayeneler</h2>
      </div>
      <div class="kart-icerik" style="padding:0;">
        <?php foreach ($yaklaşanMuayeneler as $m): ?>
          <div style="padding:12px 18px; border-bottom:1px solid var(--renk-cizgi);">
            <strong><?= e($m['plaka']) ?></strong> <?= e($m['marka'] . ' ' . $m['model']) ?>
            <div style="font-size:0.85rem; color:var(--renk-yazi-acik);">
              <?= tarih_tr($m['sonraki_muayene']) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($bekleyenMesajlar): ?>
    <div class="kart">
      <div class="kart-baslik">
        <h2>✉️ Yeni Mesajlar</h2>
      </div>
      <div class="kart-icerik">
        <p><?= $bekleyenMesajlar ?> okunmamis mesaj bulunuyor.</p>
        <a href="<?= admin_url('iletisim-mesajlari.php') ?>" class="btn btn-birincil btn-mini" style="margin-top:10px;">Mesajlari Görüntüle</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
