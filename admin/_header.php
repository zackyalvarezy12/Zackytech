<?php
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Admin') ?> - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body>
  <aside class="sidebar">
    <a class="brand" href="<?= e(url('admin/index.php')) ?>">ZA<span>Admin</span></a>
    <nav>
      <a href="<?= e(url('admin/index.php')) ?>">Dashboard</a>
      <a href="<?= e(url('admin/profile.php')) ?>">Profil</a>
      <a href="<?= e(url('admin/content.php?type=skill')) ?>">Skills</a>
      <a href="<?= e(url('admin/content.php?type=experience')) ?>">Experience</a>
      <a href="<?= e(url('admin/content.php?type=education')) ?>">Education</a>
      <a href="<?= e(url('admin/content.php?type=project')) ?>">Projects</a>
      <a href="<?= e(url('admin/content.php?type=certificate')) ?>">Certificates</a>
      <a href="<?= e(url('admin/content.php?type=organization')) ?>">Organization</a>
      <a href="<?= e(url('admin/content.php?type=blog')) ?>">Blog</a>
      <a href="<?= e(url('admin/manage.php?section=navigations')) ?>">Navigasi</a>
      <a href="<?= e(url('admin/manage.php?section=social_links')) ?>">Sosial Media</a>
      <a href="<?= e(url('admin/manage.php?section=contacts')) ?>">Kontak</a>
      <a href="<?= e(url('admin/manage.php?section=messages')) ?>">Pesan Masuk</a>
      <a href="<?= e(url('admin/account.php')) ?>">Akun & Password</a>
      <a href="<?= e(url('index.php')) ?>" target="_blank">Lihat Website</a>
      <form class="logout-form" method="post" action="<?= e(url('admin/logout.php')) ?>"><?= csrf_field() ?><button type="submit">Logout</button></form>
    </nav>
  </aside>
  <main class="admin-main">
    <header class="topbar">
      <div>
        <p class="eyebrow">Dashboard Portfolio</p>
        <h1><?= e($pageTitle ?? 'Admin') ?></h1>
      </div>
      <p class="admin-user"><?= e($_SESSION['admin_name'] ?? 'Admin') ?></p>
    </header>
    <?php if ($flash): ?>
      <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
