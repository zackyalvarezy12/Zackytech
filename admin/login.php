<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/helpers.php';

if (is_admin()) {
    redirect('admin/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email = post_text('email');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    $stmt = db()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['name'];
        $_SESSION['must_change_password'] = ($password === 'admin123');
        redirect('admin/index.php');
    }

    $error = 'Email atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="login-page">
  <form class="login-card" method="post">
    <?= csrf_field() ?>
    <p class="eyebrow">Portfolio Admin</p>
    <h1>Masuk Dashboard</h1>
    <p class="muted">Kelola profil, konten publik, gambar, dokumen, navigasi, dan kontak.</p>
    <?php if ($error): ?><div class="alert danger"><?= e($error) ?></div><?php endif; ?>
    <label>Email</label>
    <input type="email" name="email" autocomplete="username" required value="<?= e($_POST['email'] ?? '') ?>">
    <label>Password</label>
    <input type="password" name="password" autocomplete="current-password" required>
    <button class="btn primary" type="submit">Login</button>
    <p class="hint">Gunakan akun instalasi dari panduan proyek. Ganti password awal segera setelah login.</p>
  </form>
</body>
</html>
