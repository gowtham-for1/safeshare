<?php
// =============================================
// upload.php — Handle File Upload
// =============================================

session_start();
// Prevent timeout for large file moves
set_time_limit(0);

require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$fileId      = $_POST['file_id'] ?? '';
$chunkIndex  = (int)($_POST['chunk_index'] ?? 0);
$totalChunks = (int)($_POST['total_chunks'] ?? 0);
$fileName    = $_POST['file_name'] ?? 'unknown';

if (empty($fileId) || empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$tempPath = UPLOAD_DIR . 'tmp_' . $fileId;

// Append chunk to the temporary file
$out = fopen($tempPath, $chunkIndex === 0 ? "wb" : "ab");
if ($out) {
    $in = fopen($_FILES['file']['tmp_name'], "rb");
    if ($in) {
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
    }
    fclose($in);
    fclose($out);
}

// If this is not the last chunk, we stop here and wait for the next request
if ($chunkIndex < $totalChunks - 1) {
    echo json_encode(['status' => 'chunk_saved']);
    exit;
}

// ── Final Chunk Received: Process the complete file ──
$ext         = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$storedName  = generateToken(16) . '.' . $ext;
$destination = UPLOAD_DIR . $storedName;

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

if (!rename($tempPath, $destination)) {
    http_response_code(500);
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
        ':original_name' => $fileName,
        ':stored_name'   => $storedName,
        ':token'         => $token,
        ':file_path'     => $destination,
        ':file_size'     => filesize($destination),
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
    'original_name' => $fileName,
    'file_size'     => filesize($destination),
    'expiry_time'   => $expiryTime,
    'expiry_label'  => expiryLabel($expiry),
    'max_downloads' => $maxDownloads,
    'has_password'  => !empty($_POST['password']),
];

echo json_encode(['redirect' => 'success.php']);
exit;