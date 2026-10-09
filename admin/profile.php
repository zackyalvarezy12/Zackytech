<?php

declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$profile = get_profile();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fields = ['full_name', 'headline', 'summary', 'about', 'domicile', 'education', 'gpa', 'email', 'phone'];
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = post_text($field);
    }
    if ($data['full_name'] === '') {
        $errors[] = 'Nama wajib diisi.';
    }
    if ($data['headline'] === '') {
        $errors[] = 'Headline wajib diisi.';
    }
    foreach (['full_name' => 160, 'headline' => 255, 'domicile' => 160, 'education' => 180, 'gpa' => 20, 'email' => 160, 'phone' => 60] as $field => $maximum) {
        if (strlen($data[$field]) > $maximum) {
            $errors[] = 'Kolom ' . $field . ' maksimal ' . $maximum . ' karakter.';
        }
    }
    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }
    if (strlen($data['summary']) > 5000 || strlen($data['about']) > 10000) {
        $errors[] = 'Ringkasan atau profil terlalu panjang.';
    }
    $siteTitle = post_text('site_title');
    $siteDescription = post_text('site_description');
    if ($siteTitle === '' || strlen($siteTitle) > 180) {
        $errors[] = 'Judul SEO wajib diisi dan maksimal 180 karakter.';
    }
    if (strlen($siteDescription) > 320) {
        $errors[] = 'Deskripsi SEO maksimal 320 karakter.';
    }

    $image = null;
    $document = null;
    if (!$errors) {
        try {
            $image = upload_file('photo', 'image');
            $document = upload_file('cv_file', 'document');
        } catch (RuntimeException $exception) {
            delete_uploaded_file($image);
            $errors[] = $exception->getMessage();
        }
    }

    if (!$errors) {
        $removePhoto = isset($_POST['remove_photo']);
        $removeCv = isset($_POST['remove_cv']);
        $data['photo'] = $image ?: ($removePhoto ? null : ($profile['photo'] ?? null));
        $data['cv_file'] = $document ?: ($removeCv ? null : ($profile['cv_file'] ?? null));
        if (!empty($profile['id'])) {
            $data['id'] = $profile['id'];
            db()->prepare('UPDATE profiles SET full_name=:full_name, headline=:headline, summary=:summary, about=:about, domicile=:domicile, education=:education, gpa=:gpa, email=:email, phone=:phone, photo=:photo, cv_file=:cv_file WHERE id=:id')->execute($data);
        } else {
            db()->prepare('INSERT INTO profiles (full_name,headline,summary,about,domicile,education,gpa,email,phone,photo,cv_file) VALUES (:full_name,:headline,:summary,:about,:domicile,:education,:gpa,:email,:phone,:photo,:cv_file)')->execute($data);
        }
        foreach (['site_title' => $siteTitle, 'site_description' => $siteDescription] as $key => $value) {
            db()->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (:key,:value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)')->execute(['key' => $key, 'value' => $value]);
        }
        if (($image || $removePhoto) && !empty($profile['photo'])) {
            delete_uploaded_file($profile['photo']);
        }
        if (($document || $removeCv) && !empty($profile['cv_file'])) {
            delete_uploaded_file($profile['cv_file']);
        }
        flash('success', 'Profil dan pengaturan SEO berhasil disimpan.');
        redirect('admin/profile.php');
    }
}

$pageTitle = 'Profil & SEO';
require __DIR__ . '/_header.php';
?>
<section class="panel">
  <h2>Identitas & informasi profil publik</h2>
  <?php foreach ($errors as $error): ?><div class="alert danger"><?= e($error) ?></div><?php endforeach; ?>
  <form class="form-grid" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php foreach (['full_name' => 'Nama lengkap', 'headline' => 'Headline / posisi', 'domicile' => 'Domisili', 'education' => 'Pendidikan', 'gpa' => 'IPK', 'email' => 'Email', 'phone' => 'Telepon'] as $field => $label): ?>
      <label><?= e($label) ?><input name="<?= e($field) ?>" <?= $field === 'email' ? 'type="email"' : 'type="text"' ?> value="<?= e(array_key_exists($field, $_POST) ? post_text($field) : ($profile[$field] ?? '')) ?>" <?= in_array($field, ['full_name', 'headline'], true) ? 'required' : '' ?>></label>
    <?php endforeach; ?>
    <label class="full">Ringkasan hero<textarea name="summary" rows="4" maxlength="5000" required><?= e(array_key_exists('summary', $_POST) ? post_text('summary') : ($profile['summary'] ?? '')) ?></textarea></label>
    <label class="full">Tentang saya<textarea name="about" rows="6" maxlength="10000" required><?= e(array_key_exists('about', $_POST) ? post_text('about') : ($profile['about'] ?? '')) ?></textarea></label>
    <label>Foto profil JPG/PNG/WEBP (maks. 2 MB)<input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"><?php if (!empty($profile['photo'])): ?><small>File saat ini: <?= e($profile['photo']) ?></small><span class="check"><input type="checkbox" name="remove_photo"> Hapus foto profil</span><?php endif; ?></label>
    <label>CV PDF (maks. 5 MB)<input type="file" name="cv_file" accept=".pdf"><?php if (!empty($profile['cv_file'])): ?><small>File saat ini: <?= e($profile['cv_file']) ?></small><span class="check"><input type="checkbox" name="remove_cv"> Hapus CV</span><?php endif; ?></label>
    <label>Judul SEO<input name="site_title" maxlength="180" required value="<?= e(array_key_exists('site_title', $_POST) ? post_text('site_title') : setting('site_title', 'Zacky Alvarezy - Portfolio')) ?>"></label>
    <label>Deskripsi SEO<input name="site_description" maxlength="320" value="<?= e(array_key_exists('site_description', $_POST) ? post_text('site_description') : setting('site_description', 'Portfolio profesional Zacky Alvarezy.')) ?>"></label>
    <button class="btn primary" type="submit">Simpan profil</button>
  </form>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
