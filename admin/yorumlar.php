<?php
require_once __DIR__ . '/_init.php';
$pageTitle = 'Yorumlar';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $islem = $_POST['islem'] ?? '';
    if ($islem === 'kaydet') {
        $id = (int)($_POST['id'] ?? 0);
        $ad = trim($_POST['ad'] ?? '');
        $sehir = trim($_POST['sehir'] ?? '');
        $yorum = trim($_POST['yorum'] ?? '');
        $puan = max(1, min(5, (int)($_POST['puan'] ?? 5)));
        $sira = (int)($_POST['sira'] ?? 0);
        $onayli = !empty($_POST['onayli']) ? 1 : 0;

        if (!$ad || !$yorum) {
            flash_set('hata', 'Ad ve yorum zorunlu.');
        } else {
            $data = [
                'ad' => $ad,
                'sehir' => $sehir,
                'yorum' => $yorum,
                'puan' => $puan,
                'sira' => $sira,
                'onayli' => $onayli,
            ];
            if ($id) {
                DB::guncelle('yorumlar', $data, 'id = ?', [$id]);
                admin_log('Yorum guncelle', 'ID ' . $id);
            } else {
                DB::ekle('yorumlar', $data);
                admin_log('Yorum ekle', $ad);
            }
            flash_set('basari', 'Yorum kaydedildi.');
        }
        yonlendir(admin_url('yorumlar.php'));
    }
}

if (!empty($_GET['islem']) && !empty($_GET['id'])) {
    csrf_zorunlu();
    $id = (int)$_GET['id'];
    $islem = $_GET['islem'];
    if ($islem === 'onayla') {
        DB::guncelle('yorumlar', ['onayli' => 1], 'id = ?', [$id]);
        admin_log('Yorum onayla', 'ID ' . $id);
        flash_set('basari', 'Onaylandı.');
    } elseif ($islem === 'reddet') {
        DB::guncelle('yorumlar', ['onayli' => 0], 'id = ?', [$id]);
        admin_log('Yorum reddet', 'ID ' . $id);
        flash_set('basari', 'Yorum gizlendi.');
    } elseif ($islem === 'sil') {
        DB::sil('yorumlar', 'id = ?', [$id]);
        admin_log('Yorum sil', 'ID ' . $id);
        flash_set('basari', 'Silindi.');
    }
    yonlendir(admin_url('yorumlar.php'));
}

$duzenle = null;
if (!empty($_GET['duzenle'])) {
    $duzenle = DB::tek("SELECT * FROM " . DB::tablo('yorumlar') . " WHERE id = ?", [(int)$_GET['duzenle']]);
}

$durumFiltre = $_GET['durum'] ?? '';
$kosullar = [];
$params = [];
if ($durumFiltre === 'bekleyen') { $kosullar[] = 'onayli = 0'; }
elseif ($durumFiltre === 'onayli') { $kosullar[] = 'onayli = 1'; }
$where = $kosullar ? 'WHERE ' . implode(' AND ', $kosullar) : '';
$yorumlar = DB::liste("SELECT * FROM " . DB::tablo('yorumlar') . " $where ORDER BY id DESC LIMIT 200", $params);

require __DIR__ . '/_layout_basla.php';
?>

<div class="iki-sutun">
  <div>
    <div class="kart">
      <div class="kart-baslik"><h2>Yorumlar</h2></div>
      <div class="kart-icerik">
        <form method="get" class="filtre">
          <select name="durum">
            <option value="">Hepsi</option>
            <option value="bekleyen" <?= $durumFiltre === 'bekleyen' ? 'selected' : '' ?>>Bekleyenler</option>
            <option value="onayli" <?= $durumFiltre === 'onayli' ? 'selected' : '' ?>>Onaylananlar</option>
          </select>
          <button class="btn btn-cerceve">Filtrele</button>
        </form>

        <?php if (!$yorumlar): ?>
          <div class="bos-durum">Yorum yok.</div>
        <?php else: ?>
          <?php foreach ($yorumlar as $y): ?>
            <div class="kart" style="margin-bottom:12px;border:1px solid <?= $y['onayli'] ? '#dcfce7' : '#fef3c7' ?>;">
              <div class="kart-icerik">
                <div style="display:flex;justify-content:space-between;align-items:start;gap:10px;">
                  <div style="flex:1;">
                    <strong><?= e($y['ad']) ?></strong>
                    <?php if ($y['sehir']): ?><span style="color:#64748b;"> - <?= e($y['sehir']) ?></span><?php endif; ?>
                    <span style="color:#f59e0b;margin-left:8px;"><?= str_repeat('★', (int)$y['puan']) . str_repeat('☆', 5 - (int)$y['puan']) ?></span>
                    <span class="rozet-tip rozet-<?= $y['onayli'] ? 'onayli' : 'beklemede' ?>" style="margin-left:8px;">
                      <?= $y['onayli'] ? 'Onayli' : 'Bekliyor' ?>
                    </span>
                    <p style="margin:8px 0;color:#475569;"><?= nl2br(e($y['yorum'])) ?></p>
                    <small style="color:#94a3b8;">Sira: <?= (int)$y['sira'] ?> · <?= tarih_tr($y['olusturma'] ?? '') ?></small>
                  </div>
                  <div class="islemler" style="white-space:nowrap;">
                    <?php if (!$y['onayli']): ?>
                      <a href="<?= admin_url('yorumlar.php?islem=onayla&id=' . (int)$y['id'] . '&_csrf=' . csrf_token()) ?>" class="duzenle">Onayla</a>
                    <?php else: ?>
                      <a href="<?= admin_url('yorumlar.php?islem=reddet&id=' . (int)$y['id'] . '&_csrf=' . csrf_token()) ?>" class="duzenle">Gizle</a>
                    <?php endif; ?>
                    <a href="<?= admin_url('yorumlar.php?duzenle=' . (int)$y['id']) ?>" class="duzenle">Düzenle</a>
                    <a href="<?= admin_url('yorumlar.php?islem=sil&id=' . (int)$y['id'] . '&_csrf=' . csrf_token()) ?>" class="sil" data-onay="Silinsin mi?">Sil</a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="kart">
      <div class="kart-baslik"><h2><?= $duzenle ? 'Yorumu Duzenle' : 'Yeni Yorum' ?></h2></div>
      <div class="kart-icerik">
        <form method="post">
          <?= csrf_input() ?>
          <input type="hidden" name="islem" value="kaydet">
          <input type="hidden" name="id" value="<?= (int)($duzenle['id'] ?? 0) ?>">

          <div class="form-grup">
            <label>Ad Soyad *</label>
            <input type="text" name="ad" value="<?= e($duzenle['ad'] ?? '') ?>" required>
          </div>

          <div class="form-grup">
            <label>Şehir</label>
            <input type="text" name="sehir" value="<?= e($duzenle['sehir'] ?? '') ?>">
          </div>

          <div class="form-satir">
            <div class="form-grup">
              <label>Puan (1-5)</label>
              <select name="puan">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <option value="<?= $i ?>" <?= ($duzenle['puan'] ?? 5) == $i ? 'selected' : '' ?>><?= str_repeat('★', $i) ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-grup">
              <label>Sıra</label>
              <input type="number" name="sira" value="<?= (int)($duzenle['sira'] ?? 0) ?>">
            </div>
          </div>

          <div class="form-grup">
            <label>Yorum *</label>
            <textarea name="yorum" rows="5" required><?= e($duzenle['yorum'] ?? '') ?></textarea>
          </div>

          <label style="display:flex;align-items:center;gap:6px;margin-bottom:10px;">
            <input type="checkbox" name="onayli" value="1" <?= !empty($duzenle['onayli']) ? 'checked' : '' ?>> Onayli (sitede gorunsun)
          </label>

          <button class="btn btn-birincil btn-blok"><?= $duzenle ? 'Guncelle' : 'Ekle' ?></button>
          <?php if ($duzenle): ?>
            <a href="<?= admin_url('yorumlar.php') ?>" class="btn btn-cerceve btn-blok">İptal</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
