<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'İletişim Mesajları';

if (!empty($_GET['islem']) && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $islem = $_GET['islem'];
    if ($islem === 'okundu') {
        DB::guncelle('iletisim_mesajlari', ['okundu' => 1], 'id = ?', [$id]);
        admin_log('Mesaj okundu', 'ID ' . $id);
    } elseif ($islem === 'okunmadi') {
        DB::guncelle('iletisim_mesajlari', ['okundu' => 0], 'id = ?', [$id]);
    } elseif ($islem === 'sil') {
        DB::sil('iletisim_mesajlari', 'id = ?', [$id]);
        admin_log('Mesaj sil', 'ID ' . $id);
        flash_set('basari', 'Mesaj silindi.');
    }
    yonlendir(admin_url('iletisim-mesajlari.php' . (!empty($_GET['detay']) ? '' : '')));
}

$detay = null;
if (!empty($_GET['detay'])) {
    $detay = DB::tek("SELECT * FROM " . DB::tablo('iletisim_mesajlari') . " WHERE id = ?", [(int)$_GET['detay']]);
    if ($detay && empty($detay['okundu'])) {
        DB::guncelle('iletisim_mesajlari', ['okundu' => 1], 'id = ?', [(int)$detay['id']]);
        $detay['okundu'] = 1;
    }
}

$durumFiltre = $_GET['durum'] ?? '';
$kosullar = [];
if ($durumFiltre === 'okunmamis') $kosullar[] = 'okundu = 0';
elseif ($durumFiltre === 'okundu') $kosullar[] = 'okundu = 1';
$where = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';

$sayfa = max(1, (int)($_GET['s'] ?? 1));
$sayfaBoyut = 30;
$toplam = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('iletisim_mesajlari') . " $where")['c'] ?? 0);
$mesajlar = DB::liste("SELECT * FROM " . DB::tablo('iletisim_mesajlari') . " $where ORDER BY id DESC LIMIT " . (int)$sayfaBoyut . " OFFSET " . (int)(($sayfa - 1) * $sayfaBoyut));

require __DIR__ . '/_layout_basla.php';
?>

<?php if ($detay): ?>
<div class="kart">
  <div class="kart-baslik">
    <h2>Mesaj Detayı</h2>
    <a href="<?= admin_url('iletisim-mesajlari.php') ?>" class="btn btn-cerceve">< Listeye Don</a>
  </div>
  <div class="kart-icerik">
    <table class="tablo" style="margin-bottom:16px;">
      <tr><th style="width:140px;">Gonderen</th><td><strong><?= e($detay['ad_soyad']) ?></strong></td></tr>
      <tr><th>E-posta</th><td><a href="mailto:<?= e($detay['email']) ?>"><?= e($detay['email']) ?></a></td></tr>
      <?php if ($detay['telefon']): ?>
        <tr><th>Telefon</th><td><a href="tel:<?= e($detay['telefon']) ?>"><?= e($detay['telefon']) ?></a></td></tr>
      <?php endif; ?>
      <tr><th>Konu</th><td><?= e($detay['konu'] ?: '-') ?></td></tr>
      <tr><th>Tarih</th><td><?= tarih_tr($detay['olusturma'] ?? '', true) ?></td></tr>
      <tr><th>IP</th><td><code><?= e($detay['ip'] ?? '') ?></code></td></tr>
    </table>

    <h3 style="margin:16px 0 8px;">Mesaj</h3>
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:14px;line-height:1.7;">
      <?= nl2br(e($detay['mesaj'])) ?>
    </div>

    <div style="margin-top:16px;display:flex;gap:8px;">
      <a href="mailto:<?= e($detay['email']) ?>?subject=<?= urlencode('Re: ' . ($detay['konu'] ?: 'Mesajiniz')) ?>" class="btn btn-birincil">E-posta ile Yanıtla</a>
      <a href="<?= admin_url('iletisim-mesajlari.php?islem=okunmadi&id=' . (int)$detay['id'] . '&_csrf=' . csrf_token()) ?>" class="btn btn-cerceve">Okunmadı Olarak İşaretle</a>
      <a href="<?= admin_url('iletisim-mesajlari.php?islem=sil&id=' . (int)$detay['id'] . '&_csrf=' . csrf_token()) ?>" class="btn btn-tehlike" data-onay="Mesaj silinsin mi?">Sil</a>
    </div>
  </div>
</div>

<?php else: ?>

<div class="kart">
  <div class="kart-baslik"><h2>Mesajlar (<?= $toplam ?>)</h2></div>
  <div class="kart-icerik">
    <form method="get" class="filtre">
      <select name="durum">
        <option value="">Tümü</option>
        <option value="okunmamis" <?= $durumFiltre === 'okunmamis' ? 'selected' : '' ?>>Okunmamis</option>
        <option value="okundu" <?= $durumFiltre === 'okundu' ? 'selected' : '' ?>>Okunmus</option>
      </select>
      <button class="btn btn-cerceve">Filtrele</button>
    </form>

    <?php if (!$mesajlar): ?>
      <div class="bos-durum">Mesaj yok.</div>
    <?php else: ?>
    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead><tr><th></th><th>Gönderen</th><th>Konu</th><th>Tarih</th><th>İşlemler</th></tr></thead>
        <tbody>
        <?php foreach ($mesajlar as $m): ?>
          <tr style="<?= empty($m['okundu']) ? 'background:#fffbeb;font-weight:600;' : '' ?>">
            <td><?= empty($m['okundu']) ? '<span style="color:#f59e0b;">●</span>' : '<span style="color:#cbd5e1;">○</span>' ?></td>
            <td>
              <?= e($m['ad_soyad']) ?>
              <br><small style="color:#64748b;font-weight:normal;"><?= e($m['email']) ?></small>
            </td>
            <td>
              <a href="<?= admin_url('iletisim-mesajlari.php?detay=' . (int)$m['id']) ?>" style="color:#1e3a5f;">
                <?= e($m['konu'] ?: kisalt($m['mesaj'], 60)) ?>
              </a>
            </td>
            <td><?= tarih_tr($m['olusturma'] ?? '') ?></td>
            <td>
              <div class="islemler">
                <a href="<?= admin_url('iletisim-mesajlari.php?detay=' . (int)$m['id']) ?>" class="duzenle">Görüntüle</a>
                <a href="<?= admin_url('iletisim-mesajlari.php?islem=sil&id=' . (int)$m['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silinsin mi?">Sil</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= sayfalama($toplam, $sayfa, $sayfaBoyut, admin_url('iletisim-mesajlari.php?durum=' . urlencode($durumFiltre) . '&s={p}')) ?>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
