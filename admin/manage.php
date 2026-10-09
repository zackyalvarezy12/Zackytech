<?php

declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$sections = [
    'navigations' => ['title' => 'Navigasi', 'table' => 'navigations', 'fields' => [
        'label' => ['Label', 'text', true], 'url' => ['URL / anchor', 'text', true], 'sort_order' => ['Urutan', 'number', true],
    ]],
    'social_links' => ['title' => 'Media sosial', 'table' => 'social_links', 'fields' => [
        'platform' => ['Platform', 'text', true], 'url' => ['URL profil', 'url', true], 'sort_order' => ['Urutan', 'number', true],
    ]],
    'contacts' => ['title' => 'Kontak publik', 'table' => 'contacts', 'fields' => [
        'label' => ['Label', 'text', true], 'value' => ['Informasi', 'text', true], 'url' => ['URL (opsional)', 'text', false], 'sort_order' => ['Urutan', 'number', true],
    ]],
    'messages' => ['title' => 'Pesan masuk', 'table' => 'contact_messages', 'fields' => []],
];

$sectionKey = (string) ($_GET['section'] ?? 'navigations');
if (!isset($sections[$sectionKey])) {
    http_response_code(404);
    exit('Bagian tidak ditemukan.');
}
$section = $sections[$sectionKey];
$table = $section['table'];
$isMessages = $sectionKey === 'messages';
$action = (string) ($_GET['action'] ?? 'list');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $postAction = (string) ($_POST['action'] ?? 'save');
    $postId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;

    if ($sectionKey === 'navigations' && $postAction === 'toggle' && $postId > 0) {
        db()->prepare('UPDATE navigations SET is_active = IF(is_active = 1, 0, 1) WHERE id = :id')->execute(['id' => $postId]);
        flash('success', 'Status halaman berhasil diubah.');
        redirect('admin/manage.php?section=navigations');
    }

    if ($postAction === 'delete' && $postId > 0) {
        $stmt = db()->prepare("DELETE FROM `$table` WHERE id = :id");
        $stmt->execute(['id' => $postId]);
        flash('success', $isMessages ? 'Pesan berhasil dihapus.' : 'Data berhasil dihapus.');
        redirect('admin/manage.php?section=' . $sectionKey);
    }

    if ($isMessages && $postAction === 'mark_read' && $postId > 0) {
        db()->prepare('UPDATE contact_messages SET is_read = 1 WHERE id = :id')->execute(['id' => $postId]);
        flash('success', 'Pesan ditandai telah dibaca.');
        redirect('admin/manage.php?section=messages');
    }

    if (!$isMessages) {
        $data = [];
        foreach ($section['fields'] as $field => [$label, $inputType, $required]) {
            $value = post_text($field);
            if ($required && $value === '') {
                $errors[] = $label . ' wajib diisi.';
            }
            $maximum = match ($field) {
                'label', 'platform' => 80,
                'value' => 180,
                'url' => $sectionKey === 'navigations' ? 180 : 255,
                default => 20,
            };
            if (strlen($value) > $maximum) {
                $errors[] = $label . ' maksimal ' . $maximum . ' karakter.';
            }
            if ($field === 'url' && $value !== '' && $inputType === 'url' && (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $value))) {
                $errors[] = 'URL profil harus menggunakan URL yang valid.';
            }
            if ($field === 'url' && $sectionKey === 'navigations' && $value !== '' && !preg_match('/^(#[a-zA-Z0-9_-]+|https:\/\/[^\s]+)$/', $value)) {
                $errors[] = 'Navigasi harus berupa anchor (#bagian) atau URL HTTPS.';
            }
            if ($field === 'url' && $sectionKey === 'contacts' && $value !== '' && !preg_match('/^(https:\/\/[^\s]+|mailto:[^\s]+|tel:[0-9+(). -]+|#[a-zA-Z0-9_-]+)$/', $value)) {
                $errors[] = 'URL kontak harus berupa HTTPS, mailto, tel, atau anchor yang valid.';
            }
            if ($field === 'sort_order' && (!ctype_digit($value) || (int) $value > 100000)) {
                $errors[] = 'Urutan harus berupa angka antara 0 dan 100000.';
            }
            $data[$field] = $value === '' ? null : $value;
        }
        $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        if (!$errors) {
            if ($postId > 0) {
                $assignments = [];
                foreach (array_keys($data) as $field) {
                    $assignments[] = "`$field` = :$field";
                }
                $data['id'] = $postId;
                db()->prepare("UPDATE `$table` SET " . implode(', ', $assignments) . ' WHERE id = :id')->execute($data);
                flash('success', 'Data berhasil diperbarui.');
            } else {
                $columns = array_keys($data);
                $placeholders = array_map(static fn(string $field): string => ':' . $field, $columns);
                db()->prepare("INSERT INTO `$table` (`" . implode('`,`', $columns) . '`) VALUES (' . implode(',', $placeholders) . ')')->execute($data);
                flash('success', 'Data berhasil ditambahkan.');
            }
            redirect('admin/manage.php?section=' . $sectionKey);
        }
        $action = $postId > 0 ? 'edit' : 'create';
        $id = $postId;
    }
}

$item = null;
if ($action === 'edit' && $id > 0 && !$isMessages) {
    $stmt = db()->prepare("SELECT * FROM `$table` WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch() ?: null;
}

$pageTitle = $section['title'];
require __DIR__ . '/_header.php';

if (!$isMessages && in_array($action, ['create', 'edit'], true)):
?>
<section class="panel">
  <div class="panel-head"><h2><?= $item ? 'Edit' : 'Tambah' ?> <?= e($section['title']) ?></h2><a class="btn ghost" href="<?= e(url('admin/manage.php?section=' . $sectionKey)) ?>">Kembali</a></div>
  <?php foreach ($errors as $error): ?><div class="alert danger"><?= e($error) ?></div><?php endforeach; ?>
  <form class="form-grid" method="post">
    <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= e((string) ($item['id'] ?? $id)) ?>">
    <?php foreach ($section['fields'] as $field => [$label, $inputType, $required]): $value = array_key_exists($field, $_POST) ? post_text($field) : ($item[$field] ?? ($field === 'sort_order' ? '0' : '')); ?>
      <label><?= e($label) ?><input type="<?= e($inputType) ?>" name="<?= e($field) ?>" value="<?= e($value) ?>" <?= $required ? 'required' : '' ?>></label>
    <?php endforeach; ?>
    <label class="check"><input type="checkbox" name="is_active" <?= !isset($item) || !empty($item['is_active']) ? 'checked' : '' ?>> Aktif dan tampil di website</label>
    <button class="btn primary" type="submit">Simpan perubahan</button>
  </form>
</section>
<?php else:
    $query = trim((string) ($_GET['q'] ?? ''));
    if ($isMessages) {
        $stmt = db()->prepare('SELECT * FROM contact_messages WHERE name LIKE :q1 OR email LIKE :q2 OR message LIKE :q3 ORDER BY created_at DESC LIMIT 300');
        $needle = '%' . $query . '%';
        $stmt->execute(['q1' => $needle, 'q2' => $needle, 'q3' => $needle]);
    } else {
        $searchColumns = array_keys($section['fields']);
        $placeholders = [];
        $params = [];
        foreach ($searchColumns as $index => $column) {
            $placeholder = 'q' . $index;
            $placeholders[] = "`$column` LIKE :$placeholder";
            $params[$placeholder] = '%' . $query . '%';
        }
        $searchSql = implode(' OR ', $placeholders);
        $stmt = db()->prepare("SELECT * FROM `$table` WHERE ($searchSql) ORDER BY sort_order ASC, id DESC");
        $stmt->execute($params);
    }
    $items = $stmt->fetchAll();
?>
<section class="panel">
  <div class="panel-head"><h2><?= $isMessages ? 'Pesan dari formulir publik' : 'Kelola ' . e($section['title']) ?></h2><?php if (!$isMessages): ?><a class="btn primary" href="<?= e(url('admin/manage.php?section=' . $sectionKey . '&action=create')) ?>">Tambah</a><?php endif; ?></div>
  <?php if ($sectionKey === 'navigations'): ?><p class="muted">Status <strong>Aktif</strong> menampilkan tautan menu sekaligus bagian terkait di website. Nonaktifkan, misalnya, menu Blog untuk menyembunyikan menu dan seluruh bagian Blog dari halaman publik.</p><?php endif; ?>
  <form class="search-form" method="get"><input type="hidden" name="section" value="<?= e($sectionKey) ?>"><input name="q" placeholder="Cari..." value="<?= e($query) ?>"><button class="btn ghost" type="submit">Cari</button></form>
  <div class="table-wrap"><table><thead><tr><?php if ($isMessages): ?><th>Pengirim</th><th>Pesan</th><th>Dikirim</th><th>Status</th><?php else: ?><th>Label</th><th>URL / Informasi</th><th>Urutan</th><th>Status</th><?php endif; ?><th>Aksi</th></tr></thead>
  <tbody><?php foreach ($items as $row): ?><tr>
    <?php if ($isMessages): ?><td><strong><?= e($row['name']) ?></strong><small><a href="mailto:<?= e($row['email']) ?>"><?= e($row['email']) ?></a></small></td><td class="message-cell"><?= nl2br(e($row['message'])) ?></td><td><?= e($row['created_at']) ?></td><td><span class="badge <?= $row['is_read'] ? 'on' : 'off' ?>"><?= $row['is_read'] ? 'Dibaca' : 'Baru' ?></span></td>
    <?php else: ?><td><strong><?= e($row[$sectionKey === 'social_links' ? 'platform' : 'label']) ?></strong></td><td><?= e($row[$sectionKey === 'contacts' ? 'value' : 'url']) ?></td><td><?= e((string) $row['sort_order']) ?></td><td><span class="badge <?= $row['is_active'] ? 'on' : 'off' ?>"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><?php endif; ?>
    <td class="actions"><?php if ($isMessages && !$row['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= e((string) $row['id']) ?>"><button class="btn small ghost" type="submit">Tandai dibaca</button></form><?php elseif (!$isMessages): ?><a class="btn small ghost" href="<?= e(url('admin/manage.php?section=' . $sectionKey . '&action=edit&id=' . $row['id'])) ?>">Edit</a><?php if ($sectionKey === 'navigations'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= e((string) $row['id']) ?>"><button class="btn small <?= $row['is_active'] ? 'danger' : 'primary' ?>" type="submit"><?= $row['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><?php endif; ?><?php endif; ?><form method="post" onsubmit="return confirmDelete('Hapus data ini secara permanen?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= e((string) $row['id']) ?>"><button class="btn small danger" type="submit">Hapus</button></form></td>
  </tr><?php endforeach; ?><?php if (!$items): ?><tr><td colspan="5">Belum ada data.</td></tr><?php endif; ?></tbody></table></div>
</section>
<?php endif; require __DIR__ . '/_footer.php'; ?>
