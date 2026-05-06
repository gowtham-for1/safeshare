<?php
// =============================================
// upload.php — Handle File Upload
// =============================================

session_start();
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ── Validate file ──
if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    $_SESSION['error'] = 'Please select a file to upload.';
    header('Location: index.php#upload');
    exit;
}

$file   = $_FILES['file'];
$result = validateFile($file);

if ($result !== true) {
    $_SESSION['error'] = $result;
    header('Location: index.php#upload');
    exit;
}

// ── Generate unique stored filename ──
$ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$storedName  = generateToken(16) . '.' . $ext;
$destination = UPLOAD_DIR . $storedName;

// Ensure the upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    $_SESSION['error'] = 'Failed to save file. Check uploads/ folder permissions.';
    header('Location: index.php#upload');
    exit;
}

// ── Collect options ──
$expiry       = in_array($_POST['expiry'] ?? '', ['1m','1h','24h','7d']) ? $_POST['expiry'] : '24h';
$expiryTime   = getExpiryTime($expiry);
$maxDownloads = (!empty($_POST['max_downloads']) && is_numeric($_POST['max_downloads']))
                ? (int)$_POST['max_downloads'] : null;
$password     = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
$token        = generateToken(32);

// ── Insert into DB ──
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO files
            (original_name, stored_name, token, file_path, file_size, expiry_time, max_downloads, password, created_at)
        VALUES
            (:original_name, :stored_name, :token, :file_path, :file_size, :expiry_time, :max_downloads, :password, :created_at)
    ");
    $stmt->execute([
        ':original_name' => $file['name'],
        ':stored_name'   => $storedName,
        ':token'         => $token,
        ':file_path'     => $destination,
        ':file_size'     => $file['size'],
        ':expiry_time'   => $expiryTime,
        ':max_downloads' => $maxDownloads,
        ':password'      => $password,
        ':created_at'    => date('Y-m-d H:i:s'),
    ]);
} catch (Exception $e) {
    @unlink($destination);
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: index.php#upload');
    exit;
}

// ── Pass data to success page via session ──
$_SESSION['upload_success'] = [
    'token'         => $token,
    'original_name' => $file['name'],
    'file_size'     => $file['size'],
    'expiry_time'   => $expiryTime,
    'expiry_label'  => expiryLabel($expiry),
    'max_downloads' => $maxDownloads,
    'has_password'  => !empty($_POST['password']),
];

header('Location: success.php');
exit;