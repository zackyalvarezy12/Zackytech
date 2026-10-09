<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function post_text(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function is_admin(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('admin/login.php');
    }
    if (!empty($_SESSION['must_change_password']) && basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) !== 'account.php') {
        redirect('admin/account.php');
    }
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function url(string $path = ''): string
{
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

function asset_path(?string $path): string
{
    if (!$path) {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    return url($path);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';

    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sesi tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = compact('type', 'message');
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function active_content(string $type, ?int $limit = null): array
{
    $sql = 'SELECT * FROM content_items WHERE type = :type AND is_active = 1 ORDER BY sort_order ASC, id DESC';
    if ($limit !== null) {
        $sql .= ' LIMIT ' . max(1, $limit);
    }

    $stmt = db()->prepare($sql);
    $stmt->execute(['type' => $type]);

    return $stmt->fetchAll();
}

function get_profile(): array
{
    $profile = db()->query('SELECT * FROM profiles ORDER BY id ASC LIMIT 1')->fetch();

    return $profile ?: [];
}

function get_navigation(): array
{
    return db()->query('SELECT * FROM navigations WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function get_social_links(): array
{
    return db()->query('SELECT * FROM social_links WHERE is_active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
}

function setting(string $key, ?string $default = null): ?string
{
    $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = :key LIMIT 1');
    $stmt->execute(['key' => $key]);
    $row = $stmt->fetch();

    return $row['setting_value'] ?? $default;
}

function excerpt(?string $text, int $limit = 150): string
{
    $clean = trim(strip_tags((string) $text));
    if (function_exists('mb_strlen') && mb_strlen($clean) > $limit) {
        return mb_substr($clean, 0, $limit - 3) . '...';
    }
    if (strlen($clean) > $limit) {
        return substr($clean, 0, $limit - 3) . '...';
    }

    return $clean;
}

function upload_file(string $field, string $kind = 'image'): ?string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    return store_uploaded_file($_FILES[$field], $kind);
}

function upload_multiple_files(string $field, string $kind = 'image', int $maxFiles = 12): array
{
    $input = $_FILES[$field] ?? null;
    if (!is_array($input) || !isset($input['name']) || !is_array($input['name'])) {
        return [];
    }

    $files = [];
    foreach ($input['name'] as $index => $name) {
        if ($name === '' || ($input['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $files[] = [
            'name' => $name,
            'type' => $input['type'][$index] ?? '',
            'tmp_name' => $input['tmp_name'][$index] ?? '',
            'error' => $input['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $input['size'][$index] ?? 0,
        ];
    }
    if (count($files) > $maxFiles) {
        throw new RuntimeException('Maksimal ' . $maxFiles . ' gambar dapat diunggah sekaligus.');
    }

    $paths = [];
    try {
        foreach ($files as $file) {
            $paths[] = store_uploaded_file($file, $kind);
        }
    } catch (RuntimeException $exception) {
        foreach ($paths as $path) {
            delete_uploaded_file($path);
        }
        throw $exception;
    }

    return $paths;
}

function store_uploaded_file(array $file, string $kind): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new RuntimeException('Upload gagal. Coba lagi dengan file lain.');
    }

    $isDocument = $kind === 'document';
    $maxSize = $isDocument ? 5 * 1024 * 1024 : 2 * 1024 * 1024;
    if ((int) $file['size'] > $maxSize) {
        throw new RuntimeException($isDocument ? 'Ukuran dokumen maksimal 5 MB.' : 'Ukuran gambar maksimal 2 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = $isDocument
        ? ['application/pdf' => 'pdf']
        : ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException($isDocument ? 'Dokumen harus berupa PDF.' : 'Gambar harus JPG, PNG, atau WEBP.');
    }
    if (!$isDocument && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('File gambar tidak valid.');
    }

    $directory = $isDocument ? UPLOAD_DOCUMENT_DIR : UPLOAD_IMAGE_DIR;
    $publicDir = $isDocument ? UPLOAD_DOCUMENT_URL : UPLOAD_IMAGE_URL;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }

    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . DIRECTORY_SEPARATOR . $name)) {
        throw new RuntimeException('File tidak dapat disimpan.');
    }

    return $publicDir . '/' . $name;
}

function delete_uploaded_file(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    foreach ([UPLOAD_IMAGE_URL => UPLOAD_IMAGE_DIR, UPLOAD_DOCUMENT_URL => UPLOAD_DOCUMENT_DIR] as $publicDir => $directory) {
        $prefix = $publicDir . '/';
        if (str_starts_with($relativePath, $prefix)) {
            $name = substr($relativePath, strlen($prefix));
            if ($name !== '' && basename($name) === $name) {
                $path = $directory . DIRECTORY_SEPARATOR . $name;
                if (is_file($path)) {
                    unlink($path);
                }
            }
            return;
        }
    }
}
