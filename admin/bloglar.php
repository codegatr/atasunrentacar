<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Blog Yazıları';

if (($_GET['islem'] ?? '') === 'sil' && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $blog = DB::tek("SELECT kapak FROM " . DB::tablo('bloglar') . " WHERE id = ?", [$id]);
    if ($blog && !empty($blog['kapak'])) {
        dosya_sil($blog['kapak']);
    }
    DB::sil('bloglar', 'id = ?', [$id]);
    admin_log('Blog sil', 'ID ' . $id);
    flash_set('basari', 'Blog silindi.');
    yonlendir(admin_url('bloglar.php'));
}

$durumFiltre = $_GET['durum'] ?? '';
$ara = trim($_GET['ara'] ?? '');

$kosullar = [];
$params = [];
if (in_array($durumFiltre, ['taslak', 'yayinda'], true)) {
    $kosullar[] = 'durum = ?';
    $params[] = $durumFiltre;
}
if ($ara) {
    $kosullar[] = '(baslik LIKE ? OR ozet LIKE ?)';
    $params[] = '%' . $ara . '%';
    $params[] = '%' . $ara . '%';
}
$where = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';

$sayfa = max(1, (int)($_GET['s'] ?? 1));
$sayfaBoyut = 20;
$toplam = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('bloglar') . " $where", $params)['c'] ?? 0);
$bloglar = DB::liste("SELECT * FROM " . DB::tablo('bloglar') . " $where ORDER BY yayin_tarihi DESC, id DESC LIMIT " . (int)$sayfaBoyut . " OFFSET " . (int)(($sayfa - 1) * $sayfaBoyut), $params);

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2>Blog Yazıları (<?= $toplam ?>)</h2>
    <a href="<?= admin_url('blog-duzenle.php') ?>" class="btn btn-birincil">+ Yeni Yazi</a>
  </div>
  <div class="kart-icerik">
    <form method="get" class="filtre">
      <input type="text" name="ara" value="<?= e($ara) ?>" placeholder="Başlık veya özet ara...">
      <select name="durum">
        <option value="">Tüm Durumlar</option>
        <option value="yayinda" <?= $durumFiltre === 'yayinda' ? 'selected' : '' ?>>Yayında</option>
        <option value="taslak" <?= $durumFiltre === 'taslak' ? 'selected' : '' ?>>Taslak</option>
      </select>
      <button class="btn btn-cerceve">Filtrele</button>
    </form>

    <?php if (!$bloglar): ?>
      <div class="bos-durum">Henuz blog yazisi yok. <a href="<?= admin_url('blog-duzenle.php') ?>">Ilk yaziyi olustur</a></div>
    <?php else: ?>
    <div class="tablo-kapsayici">
      <table class="tablo">
        <thead>
          <tr><th>Kapak</th><th>Başlık</th><th>Yazar</th><th>Yayın Tarihi</th><th>Durum</th><th>Görüntüleme</th><th>İşlemler</th></tr>
        </thead>
        <tbody>
          <?php foreach ($bloglar as $b): ?>
          <tr>
            <td>
              <?php if (!empty($b['kapak'])): ?>
                <img src="<?= e(upload_url($b['kapak'])) ?>" style="width:60px;height:40px;object-fit:cover;border-radius:4px;">
              <?php else: ?>
                <span style="color:#9ca3af">-</span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= e($b['baslik']) ?></strong>
              <br><small style="color:#64748b"><?= e($b['slug']) ?></small>
            </td>
            <td><?= e($b['yazar'] ?: '-') ?></td>
            <td><?= tarih_tr($b['yayin_tarihi']) ?></td>
            <td><span class="rozet-tip rozet-<?= $b['durum'] === 'yayinda' ? 'yayinda' : 'taslak' ?>"><?= ucfirst($b['durum']) ?></span></td>
            <td><?= (int)$b['goruntuleme'] ?></td>
            <td>
              <div class="islemler">
                <?php if ($b['durum'] === 'yayinda'): ?>
                  <a href="<?= url('yazi/' . $b['slug']) ?>" target="_blank" class="duzenle">Görüntüle</a>
                <?php endif; ?>
                <a href="<?= admin_url('blog-duzenle.php?id=' . (int)$b['id']) ?>" class="duzenle">Düzenle</a>
                <a href="<?= admin_url('bloglar.php?islem=sil&id=' . (int)$b['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Yazı silinsin mi?">Sil</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= sayfalama($toplam, $sayfa, $sayfaBoyut, admin_url('bloglar.php?ara=' . urlencode($ara) . '&durum=' . urlencode($durumFiltre) . '&s={p}')) ?>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
