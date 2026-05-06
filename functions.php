<?php
// =============================================
// functions.php — Reusable Helper Functions
// =============================================

require_once 'db.php';

/**
 * Generate a cryptographically secure random token
 */
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Format bytes into human-readable size
 */
function formatFileSize(int $bytes): string {
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024)    return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}

/**
 * Delete expired files from disk and database
 */
function deleteExpiredFiles(): void {
    $now  = date('Y-m-d H:i:s');
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM files WHERE expiry_time < ?");
    $stmt->execute([$now]);
    $expired = $stmt->fetchAll();

    foreach ($expired as $file) {
        if (file_exists($file['file_path'])) {
            @unlink($file['file_path']);
        }
    }

    $stmt = $pdo->prepare("DELETE FROM files WHERE expiry_time < ?");
    $stmt->execute([$now]);
}

/**
 * Get a file record by token
 */
function getFileByToken(string $token): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM files WHERE token = ?");
    $stmt->execute([$token]);
    $file = $stmt->fetch();
    return $file ?: null;
}

/**
 * Get expiry datetime string from user selection
 */
function getExpiryTime(string $duration): string {
    return match ($duration) {
        '1m'  => date('Y-m-d H:i:s', strtotime('+1 minute')),
        '1h'  => date('Y-m-d H:i:s', strtotime('+1 hour')),
        '24h' => date('Y-m-d H:i:s', strtotime('+24 hours')),
        '7d'  => date('Y-m-d H:i:s', strtotime('+7 days')),
        default => date('Y-m-d H:i:s', strtotime('+24 hours')),
    };
}

/**
 * Validate uploaded file
 */
function validateFile(array $file): string|true {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds size limit.',
            UPLOAD_ERR_NO_FILE => 'No file selected.',
            default => 'Upload error code: ' . $file['error'],
        };
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'File size exceeds ' . formatFileSize(MAX_FILE_SIZE) . ' limit.';
    }

    if (!empty(ALLOWED_TYPES)) {
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, ALLOWED_TYPES, true)) {
            return 'Invalid file type.';
        }
    }
    return true;
}

/**
 * Get human-readable expiry label
 */
function expiryLabel(string $duration): string {
    return match ($duration) {
        '1m'  => '1 Minute',
        '1h'  => '1 Hour',
        '24h' => '24 Hours',
        '7d'  => '7 Days',
        default => '24 Hours',
    };
}