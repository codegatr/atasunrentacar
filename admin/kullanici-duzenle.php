<?php
require_once __DIR__ . '/_init.php';
admin_yetki('admin');
$pageTitle = 'Kullanıcı Düzenle';

$id = (int)($_GET['id'] ?? 0);
$kullanici = $id ? DB::tek("SELECT * FROM " . DB::tablo('kullanicilar') . " WHERE id = ?", [$id]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_zorunlu();
    $kullaniciAdi = trim($_POST['kullanici_adi'] ?? '');
    $adSoyad = trim($_POST['ad_soyad'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = in_array($_POST['rol'] ?? '', ['admin', 'editor', 'operator'], true) ? $_POST['rol'] : 'operator';
    $aktif = !empty($_POST['aktif']) ? 1 : 0;
    $sifre = $_POST['sifre'] ?? '';
    $sifreTekrar = $_POST['sifre_tekrar'] ?? '';

    $hatalar = [];
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $kullaniciAdi)) {
        $hatalar[] = 'Kullanıcı adı 3-50 karakter; sadece harf, rakam, _, -, . olabilir.';
    }
    if (!$adSoyad) $hatalar[] = 'Ad soyad zorunlu.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $hatalar[] = 'Geçersiz e-posta.';

    // Kullanici adi cakismasi
    $cakisma = "kullanici_adi = ?";
    $cParams = [$kullaniciAdi];
    if ($id) { $cakisma .= " AND id != ?"; $cParams[] = $id; }
    $varMi = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('kullanicilar') . " WHERE $cakisma", $cParams)['c'] ?? 0);
    if ($varMi > 0) $hatalar[] = 'Bu kullanıcı adı zaten alınmış.';

    if (!$kullanici) {
        if (strlen($sifre) < 8) $hatalar[] = 'Şifre en az 8 karakter olmalı.';
        if ($sifre !== $sifreTekrar) $hatalar[] = 'Şifreler eşleşmiyor.';
    } else {
        if ($sifre !== '' && strlen($sifre) < 8) $hatalar[] = 'Şifre en az 8 karakter olmalı.';
        if ($sifre !== '' && $sifre !== $sifreTekrar) $hatalar[] = 'Şifreler eşleşmiyor.';
    }

    // Kendi rolunu admin'den dusurmeyi engelle (son admin senaryosu)
    if ($kullanici && (int)$kullanici['id'] === (int)($GLOBALS['admin_kullanici']['id'] ?? 0) && $rol !== 'admin') {
        $adminSay = (int)(DB::tek("SELECT COUNT(*) c FROM " . DB::tablo('kullanicilar') . " WHERE rol='admin' AND aktif=1")['c'] ?? 0);
        if ($adminSay <= 1) $hatalar[] = 'Sistemdeki tek admin kendinizsiniz, rol değiştiremezsiniz.';
    }

    if ($hatalar) {
        flash_set('hata', implode('<br>', array_map('htmlspecialchars', $hatalar)));
    } else {
        $data = [
            'kullanici_adi' => $kullaniciAdi,
            'ad_soyad' => $adSoyad,
            'email' => $email ?: null,
            'rol' => $rol,
            'aktif' => $aktif,
        ];
        if ($sifre !== '') {
            $data['sifre_hash'] = password_hash($sifre, PASSWORD_BCRYPT);
        }
        if ($id) {
            DB::guncelle('kullanicilar', $data, 'id = ?', [$id]);
            admin_log('Kullanici guncelle', 'ID ' . $id);
            flash_set('basari', 'Kullanıcı güncellendi.');
            yonlendir(admin_url('kullanici-duzenle.php?id=' . $id));
        } else {
            $yeniId = DB::ekle('kullanicilar', $data);
            admin_log('Kullanici olustur', $kullaniciAdi);
            flash_set('basari', 'Kullanıcı oluşturuldu.');
            yonlendir(admin_url('kullanici-duzenle.php?id=' . $yeniId));
        }
    }
}

require __DIR__ . '/_layout_basla.php';
?>

<div class="kart">
  <div class="kart-baslik">
    <h2><?= $kullanici ? 'Kullaniciyi Duzenle: ' . e($kullanici['kullanici_adi']) : 'Yeni Kullanici' ?></h2>
    <a href="<?= admin_url('kullanicilar.php') ?>" class="btn btn-cerceve">< Listeye Don</a>
  </div>
  <div class="kart-icerik">
    <form method="post" autocomplete="off" style="max-width:700px;">
      <?= csrf_input() ?>

      <div class="form-satir">
        <div class="form-grup">
          <label>Kullanıcı Adı *</label>
          <input type="text" name="kullanici_adi" value="<?= e($kullanici['kullanici_adi'] ?? '') ?>" required pattern="[a-zA-Z0-9_.-]{3,50}">
          <small>Sadece harf, rakam, alt çizgi, tire, nokta. 3-50 karakter.</small>
        </div>
        <div class="form-grup">
          <label>Ad Soyad *</label>
          <input type="text" name="ad_soyad" value="<?= e($kullanici['ad_soyad'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-satir">
        <div class="form-grup">
          <label>E-posta</label>
          <input type="email" name="email" value="<?= e($kullanici['email'] ?? '') ?>">
        </div>
        <div class="form-grup">
          <label>Rol</label>
          <select name="rol">
            <option value="operator" <?= ($kullanici['rol'] ?? 'operator') === 'operator' ? 'selected' : '' ?>>Operator (rezervasyon, musteri)</option>
            <option value="editor" <?= ($kullanici['rol'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor (icerik + operator)</option>
            <option value="admin" <?= ($kullanici['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin (tam yetki)</option>
          </select>
        </div>
      </div>

      <div class="form-satir">
        <div class="form-grup">
          <label>Şifre <?= $kullanici ? '<small>(degistirmek istemiyorsaniz bos birakin)</small>' : '*' ?></label>
          <input type="password" name="sifre" autocomplete="new-password" <?= $kullanici ? '' : 'required' ?>>
        </div>
        <div class="form-grup">
          <label>Şifre Tekrar</label>
          <input type="password" name="sifre_tekrar" autocomplete="new-password" <?= $kullanici ? '' : 'required' ?>>
        </div>
      </div>

      <label style="display:flex;align-items:center;gap:6px;margin:12px 0;">
        <input type="checkbox" name="aktif" value="1" <?= !$kullanici || !empty($kullanici['aktif']) ? 'checked' : '' ?>> Aktif
      </label>

      <button class="btn btn-birincil"><?= $kullanici ? 'Guncelle' : 'Olustur' ?></button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/_layout_bitir.php'; ?>
