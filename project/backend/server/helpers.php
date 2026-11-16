<?php
/** Send a JSON response and stop. */
function out($data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Request body: $_POST for multipart (file uploads), JSON otherwise. */
function input(): array
{
    if (str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) {
        return $_POST;
    }
    return json_decode(file_get_contents('php://input'), true) ?: [];
}

function today(): string
{
    return date('Y-m-d');
}

function current_role(): ?string
{
    return $_SESSION['role'] ?? null;
}

function is_staff(): bool
{
    return in_array(current_role(), ['admin', 'librarian'], true);
}

/** Stop with 403 unless $ok is true. */
function need($ok): void
{
    if (!$ok) {
        out(['error' => 'Access denied. Please log in with the proper role.'], 403);
    }
}

/**
 * Handle an uploaded book-cover image.
 * Returns the stored relative path, or $existing if no new file was sent.
 * Throws RuntimeException (safe to show the user) on validation failure.
 */
function handle_cover_upload(?array $file, ?string $existing = null): ?string
{
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image upload failed. Please try again.');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Image is too large — the maximum size is 2MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info) {
        throw new RuntimeException('That file does not look like a valid image.');
    }
    $ext = array_search($info['mime'], UPLOAD_ALLOWED_MIME, true);
    if (!$ext) {
        throw new RuntimeException('Only JPG, PNG or WEBP images are allowed.');
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true) && !is_dir(UPLOAD_DIR)) {
        throw new RuntimeException('Could not prepare the upload folder.');
    }
    $ht = UPLOAD_DIR . '.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "<IfModule mod_php.c>\nphp_flag engine off\n</IfModule>\n<FilesMatch \"\\.(php|phtml|php\\d|pl|py|cgi|exe|sh)$\">\nRequire all denied\n</FilesMatch>\nOptions -Indexes\n");
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    delete_cover($existing);
    return UPLOAD_URL . $filename;
}

/** Remove a stored cover image if it exists. */
function delete_cover(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }
    $full = BACKEND_DIR . '/' . $relativePath;
    if (is_file($full)) {
        @unlink($full);
    }
}
