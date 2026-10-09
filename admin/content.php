<?php

declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

$types = [
    'skill' => 'Skills',
    'experience' => 'Experience',
    'education' => 'Education',
    'project' => 'Projects',
    'certificate' => 'Certificates',
    'organization' => 'Organization',
    'blog' => 'Blog',
];

$type = $_GET['type'] ?? 'skill';
if (!array_key_exists($type, $types)) {
    $type = 'skill';
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (($_POST['_method'] ?? '') === 'delete') {
        $existing = db()->prepare('SELECT image, document FROM content_items WHERE id = :id AND type = :type');
        $existing->execute(['id' => (int) $_POST['id'], 'type' => $type]);
        $oldFiles = $existing->fetch() ?: [];
        $galleryFiles = [];
        if ($type === 'project') {
            $gallery = db()->prepare('SELECT image_path FROM project_images WHERE project_id = :id');
            $gallery->execute(['id' => (int) $_POST['id']]);
            $galleryFiles = $gallery->fetchAll(PDO::FETCH_COLUMN);
        }
        $stmt = db()->prepare('DELETE FROM content_items WHERE id = :id AND type = :type');
        $stmt->execute(['id' => (int) $_POST['id'], 'type' => $type]);
        if ($stmt->rowCount() > 0) {
            delete_uploaded_file($oldFiles['image'] ?? null);
            delete_uploaded_file($oldFiles['document'] ?? null);
            foreach ($galleryFiles as $galleryFile) {
                delete_uploaded_file($galleryFile);
            }
        }
        flash('success', 'Konten berhasil dihapus.');
        redirect('admin/content.php?type=' . $type);
    }

    $data = [
        'type' => $type,
        'title' => post_text('title'),
        'subtitle' => post_text('subtitle'),
        'organization' => post_text('organization'),
        'start_date' => post_text('start_date'),
        'end_date' => post_text('end_date'),
        'description' => post_text('description'),
        'body' => post_text('body'),
        'technologies' => post_text('technologies'),
        'url' => post_text('url'),
        'sort_order' => post_text('sort_order', '0'),
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data['title'] === '') {
        $errors[] = 'Judul wajib diisi.';
    }
    if (strlen($data['title']) > 180 || strlen($data['subtitle']) > 220 || strlen($data['organization']) > 180 || strlen($data['technologies']) > 255 || strlen($data['url']) > 255) {
        $errors[] = 'Salah satu kolom melebihi batas panjang yang diizinkan.';
    }
    if ($data['url'] !== '' && (!filter_var($data['url'], FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $data['url']))) {
        $errors[] = 'URL harus valid dan menggunakan HTTPS atau HTTP.';
    }
    if (!preg_match('/^-?\d{1,6}$/', (string) $data['sort_order'])) {
        $errors[] = 'Urutan harus berupa angka yang valid.';
    } else {
        $data['sort_order'] = (int) $data['sort_order'];
    }

    $image = null;
    $document = null;
    $galleryUploads = [];
    $removeGalleryIds = [];
    if ($type === 'project' && isset($_POST['remove_gallery']) && is_array($_POST['remove_gallery'])) {
        foreach ($_POST['remove_gallery'] as $galleryId) {
            $validatedId = filter_var($galleryId, FILTER_VALIDATE_INT);
            if ($validatedId !== false && $validatedId > 0) {
                $removeGalleryIds[] = $validatedId;
            }
        }
        $removeGalleryIds = array_values(array_unique($removeGalleryIds));
    }
    $matchedRemovalCount = 0;
    if ($type === 'project' && $id > 0 && $removeGalleryIds) {
        $placeholders = [];
        $params = ['project_id' => $id];
        foreach ($removeGalleryIds as $index => $galleryId) {
            $placeholder = 'remove_id_' . $index;
            $placeholders[] = ':' . $placeholder;
            $params[$placeholder] = $galleryId;
        }
        $countRemovals = db()->prepare('SELECT COUNT(*) FROM project_images WHERE project_id = :project_id AND id IN (' . implode(',', $placeholders) . ')');
        $countRemovals->execute($params);
        $matchedRemovalCount = (int) $countRemovals->fetchColumn();
    }
    try {
        if (!$errors) {
            $image = upload_file('image', 'image');
            $document = upload_file('document', 'document');
            if ($type === 'project') {
                $currentCount = 0;
                if ($id > 0) {
                    $count = db()->prepare('SELECT COUNT(*) FROM project_images WHERE project_id = :id');
                    $count->execute(['id' => $id]);
                    $currentCount = (int) $count->fetchColumn();
                }
                if ($currentCount - $matchedRemovalCount > 30) {
                    $errors[] = 'Proyek dapat memiliki maksimal 30 foto galeri.';
                } else {
                    $galleryUploads = upload_multiple_files('gallery_images', 'image', 12);
                    if ($currentCount - $matchedRemovalCount + count($galleryUploads) > 30) {
                        foreach ($galleryUploads as $galleryPath) {
                            delete_uploaded_file($galleryPath);
                        }
                        $galleryUploads = [];
                        $errors[] = 'Proyek dapat memiliki maksimal 30 foto galeri.';
                    }
                }
            }
        }
    } catch (RuntimeException $exception) {
        delete_uploaded_file($image);
        delete_uploaded_file($document);
        foreach ($galleryUploads as $galleryPath) {
            delete_uploaded_file($galleryPath);
        }
        $errors[] = $exception->getMessage();
    }

    if (!$errors) {
        if ($id > 0) {
            $current = db()->prepare('SELECT image, document FROM content_items WHERE id = :id AND type = :type');
            $current->execute(['id' => $id, 'type' => $type]);
            $old = $current->fetch() ?: [];
            $removeImage = isset($_POST['remove_image']);
            $removeDocument = isset($_POST['remove_document']);
            $data['image'] = $image ?: ($removeImage ? null : ($old['image'] ?? null));
            $data['document'] = $document ?: ($removeDocument ? null : ($old['document'] ?? null));

            $sql = 'UPDATE content_items SET title=:title, subtitle=:subtitle, organization=:organization, start_date=:start_date, end_date=:end_date, description=:description, body=:body, technologies=:technologies, url=:url, image=:image, document=:document, sort_order=:sort_order, is_featured=:is_featured, is_active=:is_active WHERE id=:id AND type=:type';
            $data['id'] = $id;
            db()->prepare($sql)->execute($data);
            if (($image || $removeImage) && !empty($old['image'])) {
                delete_uploaded_file($old['image']);
            }
            if (($document || $removeDocument) && !empty($old['document'])) {
                delete_uploaded_file($old['document']);
            }
            if ($type === 'project') {
                if ($removeGalleryIds) {
                    $placeholders = [];
                    $params = ['project_id' => $id];
                    foreach ($removeGalleryIds as $index => $galleryId) {
                        $placeholder = 'gallery_id_' . $index;
                        $placeholders[] = ':' . $placeholder;
                        $params[$placeholder] = $galleryId;
                    }
                    $lookup = db()->prepare('SELECT id, image_path FROM project_images WHERE project_id = :project_id AND id IN (' . implode(',', $placeholders) . ')');
                    $lookup->execute($params);
                    $removedImages = $lookup->fetchAll();
                    if ($removedImages) {
                        $delete = db()->prepare('DELETE FROM project_images WHERE id = :id AND project_id = :project_id');
                        foreach ($removedImages as $removedImage) {
                            $delete->execute(['id' => $removedImage['id'], 'project_id' => $id]);
                            delete_uploaded_file($removedImage['image_path']);
                        }
                    }
                }
                if ($galleryUploads) {
                    $insertImage = db()->prepare('INSERT INTO project_images (project_id, image_path, sort_order) VALUES (:project_id, :image_path, :sort_order)');
                    $nextOrder = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM project_images WHERE project_id = ' . (int) $id)->fetchColumn();
                    foreach ($galleryUploads as $galleryPath) {
                        $insertImage->execute(['project_id' => $id, 'image_path' => $galleryPath, 'sort_order' => $nextOrder++]);
                    }
                }
            }
            flash('success', 'Konten berhasil diperbarui.');
        } else {
            $data['image'] = $image;
            $data['document'] = $document;
            $sql = 'INSERT INTO content_items (type,title,subtitle,organization,start_date,end_date,description,body,technologies,url,image,document,sort_order,is_featured,is_active) VALUES (:type,:title,:subtitle,:organization,:start_date,:end_date,:description,:body,:technologies,:url,:image,:document,:sort_order,:is_featured,:is_active)';
            $insert = db()->prepare($sql);
            $insert->execute($data);
            $newId = (int) db()->lastInsertId();
            if ($type === 'project' && $galleryUploads) {
                $insertImage = db()->prepare('INSERT INTO project_images (project_id, image_path, sort_order) VALUES (:project_id, :image_path, :sort_order)');
                foreach ($galleryUploads as $sortOrder => $galleryPath) {
                    $insertImage->execute(['project_id' => $newId, 'image_path' => $galleryPath, 'sort_order' => $sortOrder]);
                }
            }
            flash('success', 'Konten berhasil ditambahkan.');
        }

        redirect('admin/content.php?type=' . $type);
    }
}

$item = null;
$galleryImages = [];
if ($action === 'edit' && $id > 0) {
    $stmt = db()->prepare('SELECT * FROM content_items WHERE id = :id AND type = :type');
    $stmt->execute(['id' => $id, 'type' => $type]);
    $item = $stmt->fetch();
    if ($item && $type === 'project') {
        $gallery = db()->prepare('SELECT id, image_path FROM project_images WHERE project_id = :id ORDER BY sort_order ASC, id ASC');
        $gallery->execute(['id' => $id]);
        $galleryImages = $gallery->fetchAll();
    }
}

$pageTitle = $types[$type];
require __DIR__ . '/_header.php';

if ($action === 'create' || $item):
?>
<section class="panel">
  <div class="panel-head">
    <h2><?= $item ? 'Edit' : 'Tambah' ?> <?= e($types[$type]) ?></h2>
    <a class="btn ghost" href="<?= e(url('admin/content.php?type=' . $type)) ?>">Kembali</a>
  </div>
  <?php foreach ($errors as $error): ?><div class="alert danger"><?= e($error) ?></div><?php endforeach; ?>
  <form class="form-grid" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label>Judul<input name="title" required value="<?= e($item['title'] ?? $_POST['title'] ?? '') ?>"></label>
    <label>Subjudul<input name="subtitle" value="<?= e($item['subtitle'] ?? $_POST['subtitle'] ?? '') ?>"></label>
    <label>Organisasi/Instansi<input name="organization" value="<?= e($item['organization'] ?? $_POST['organization'] ?? '') ?>"></label>
    <label>Mulai<input name="start_date" value="<?= e($item['start_date'] ?? $_POST['start_date'] ?? '') ?>"></label>
    <label>Selesai<input name="end_date" value="<?= e($item['end_date'] ?? $_POST['end_date'] ?? '') ?>"></label>
    <label>Teknologi/Keyword<input name="technologies" value="<?= e($item['technologies'] ?? $_POST['technologies'] ?? '') ?>"></label>
    <label>URL<input type="url" name="url" value="<?= e($item['url'] ?? $_POST['url'] ?? '') ?>"></label>
    <label>Urutan<input type="number" name="sort_order" value="<?= e((string)($item['sort_order'] ?? $_POST['sort_order'] ?? 0)) ?>"></label>
    <label class="full">Deskripsi Singkat<textarea name="description" rows="4"><?= e($item['description'] ?? $_POST['description'] ?? '') ?></textarea></label>
    <label class="full">Konten Detail<textarea name="body" rows="7"><?= e($item['body'] ?? $_POST['body'] ?? '') ?></textarea></label>
    <label>Gambar sampul JPG/PNG/WEBP<input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><?php if (!empty($item['image'])): ?><span class="check"><input type="checkbox" name="remove_image"> Hapus gambar sampul</span><?php endif; ?></label>
    <?php if ($type === 'project'): ?>
      <label class="full">Foto galeri proyek (bisa pilih banyak sekaligus, maks. 12 tiap upload; 30 foto total)<input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple></label>
      <?php if ($galleryImages): ?><div class="gallery-admin full"><strong>Foto galeri saat ini</strong><div class="gallery-admin-grid"><?php foreach ($galleryImages as $galleryImage): ?><label class="gallery-admin-item"><img src="<?= e(asset_path($galleryImage['image_path'])) ?>" alt="Foto proyek"><span><input type="checkbox" name="remove_gallery[]" value="<?= e((string) $galleryImage['id']) ?>"> Hapus foto</span></label><?php endforeach; ?></div></div><?php endif; ?>
    <?php endif; ?>
    <label>Dokumen PDF<input type="file" name="document" accept=".pdf"><?php if (!empty($item['document'])): ?><span class="check"><input type="checkbox" name="remove_document"> Hapus dokumen</span><?php endif; ?></label>
    <label class="check"><input type="checkbox" name="is_featured" <?= !empty($item['is_featured']) ? 'checked' : '' ?>> Featured</label>
    <label class="check"><input type="checkbox" name="is_active" <?= !isset($item) || !empty($item['is_active']) ? 'checked' : '' ?>> Aktif</label>
    <button class="btn primary" type="submit">Simpan</button>
  </form>
</section>
<?php
else:
    $q = trim($_GET['q'] ?? '');
    $sql = 'SELECT * FROM content_items WHERE type = :type';
    $params = ['type' => $type];
    if ($q !== '') {
        $sql .= ' AND (title LIKE :q1 OR subtitle LIKE :q2 OR organization LIKE :q3 OR technologies LIKE :q4)';
        $needle = '%' . $q . '%';
        $params += ['q1' => $needle, 'q2' => $needle, 'q3' => $needle, 'q4' => $needle];
    }
    $sql .= ' ORDER BY sort_order ASC, id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
?>
<section class="panel">
  <div class="panel-head">
    <h2>Kelola <?= e($types[$type]) ?></h2>
    <a class="btn primary" href="<?= e(url('admin/content.php?type=' . $type . '&action=create')) ?>">Tambah</a>
  </div>
  <form class="search-form">
    <input type="hidden" name="type" value="<?= e($type) ?>">
    <input name="q" placeholder="Cari judul, instansi, teknologi..." value="<?= e($q) ?>">
    <button class="btn ghost" type="submit">Cari</button>
  </form>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Judul</th><th>Info</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($items as $row): ?>
          <tr>
            <td><strong><?= e($row['title']) ?></strong><small><?= e($row['technologies']) ?></small></td>
            <td><?= e($row['organization'] ?: $row['subtitle']) ?></td>
            <td><?= e((string) $row['sort_order']) ?></td>
            <td><span class="badge <?= $row['is_active'] ? 'on' : 'off' ?>"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
            <td class="actions">
              <a class="btn small ghost" href="<?= e(url('admin/content.php?type=' . $type . '&action=edit&id=' . $row['id'])) ?>">Edit</a>
              <form method="post" onsubmit="return confirmDelete('Hapus konten ini?')">
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="delete">
                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                <button class="btn small danger" type="submit">Hapus</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; require __DIR__ . '/_footer.php'; ?>
