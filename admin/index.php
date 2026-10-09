<?php

declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$pageTitle = 'Dashboard';
$counts = [];
foreach (['skill','experience','education','project','certificate','organization','blog'] as $type) {
    $stmt = db()->prepare('SELECT COUNT(*) AS total FROM content_items WHERE type = :type');
    $stmt->execute(['type' => $type]);
    $counts[$type] = (int) $stmt->fetch()['total'];
}
$counts['messages'] = (int) db()->query('SELECT COUNT(*) FROM contact_messages WHERE is_read = 0')->fetchColumn();

require __DIR__ . '/_header.php';
?>
<section class="stats-grid">
  <?php foreach ($counts as $type => $total): ?>
    <a class="stat-card" href="<?= e(url('admin/content.php?type=' . $type)) ?>">
      <span><?= e(ucfirst($type)) ?></span>
      <strong><?= e((string) $total) ?></strong>
    </a>
  <?php endforeach; ?>
</section>
<section class="panel">
  <h2>Ringkasan Sistem</h2>
  <p>Website berjalan dengan PHP native, PDO MySQL, session login, CSRF token, validasi backend, upload file aman, dan CRUD konten dinamis.</p>
  <p>Untuk keamanan, segera ganti password instalasi melalui menu <strong>Akun &amp; Password</strong>.</p>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
