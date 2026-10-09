<?php

declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    $new = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
    $stmt = db()->prepare('SELECT password FROM admins WHERE id = :id');
    $stmt->execute(['id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($current, $admin['password'])) {
        $errors[] = 'Password saat ini tidak sesuai.';
    }
    if (strlen($new) < 12) {
        $errors[] = 'Password baru minimal 12 karakter.';
    }
    if (strlen($new) > 72) {
        $errors[] = 'Password baru maksimal 72 byte untuk kompatibilitas hashing.';
    }
    if ($new !== $confirm) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }
    if (!$errors) {
        $update = db()->prepare('UPDATE admins SET password = :password WHERE id = :id');
        $update->execute(['password' => password_hash($new, PASSWORD_DEFAULT), 'id' => $_SESSION['admin_id']]);
        session_regenerate_id(true);
        unset($_SESSION['must_change_password']);
        flash('success', 'Password berhasil diperbarui.');
        redirect('admin/account.php');
    }
}

$pageTitle = 'Akun & Password';
require __DIR__ . '/_header.php';
?>
<section class="panel account-panel">
  <h2>Ubah password admin</h2>
  <p class="muted">Gunakan password unik dengan minimal 12 karakter. Password disimpan menggunakan password_hash().</p>
  <?php foreach ($errors as $error): ?><div class="alert danger"><?= e($error) ?></div><?php endforeach; ?>
  <form method="post" class="stack-form">
    <?= csrf_field() ?>
    <label>Password saat ini<input type="password" name="current_password" autocomplete="current-password" required></label>
    <label>Password baru<input type="password" name="new_password" minlength="12" autocomplete="new-password" required></label>
    <label>Ulangi password baru<input type="password" name="confirm_password" minlength="12" autocomplete="new-password" required></label>
    <button class="btn primary" type="submit">Perbarui password</button>
  </form>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
