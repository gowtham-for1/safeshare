<?php
// =============================================
// download.php — Secure File Download
// =============================================

session_start();
require_once 'functions.php';

// ── Clean up expired files ──
deleteExpiredFiles();

$token = trim($_GET['token'] ?? '');

// Helper: show error page
function showError(string $title, string $message, string $icon = '❌'): void {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeShare Pro — <?= htmlspecialchars($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    </head>
    <body class="bg-[#0a0e1a] text-white min-h-screen flex items-center justify-center px-6">
      <div class="text-center max-w-md">
        <div class="text-7xl mb-6"><?= $icon ?></div>
        <h1 class="font-display text-3xl font-bold mb-3"><?= htmlspecialchars($title) ?></h1>
        <p class="text-white/50 mb-8"><?= htmlspecialchars($message) ?></p>
        <a href="index.php" class="btn-primary px-6 py-3 rounded-xl inline-block">← Back to Home</a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// ── Validate token ──
if (empty($token) || strlen($token) < 32) {
    showError('Invalid Link', 'This download link is invalid or malformed.', '🔗');
}

$file = getFileByToken($token);
if (!$file) {
    showError('File Not Found', 'This link doesn\'t exist or has already expired.', '🕳️');
}

// ── Check expiry ──
if (strtotime($file['expiry_time']) < time()) {
    if (file_exists($file['file_path'])) @unlink($file['file_path']);
    $pdo = getDB();
    $pdo->prepare("DELETE FROM files WHERE token = ?")->execute([$token]);
    showError('Link Expired', 'This file has expired and been automatically deleted.', '⏰');
}

// ── Check download limit ──
if ($file['max_downloads'] !== null && $file['download_count'] >= $file['max_downloads']) {
    showError('Download Limit Reached', 'This file has reached its maximum number of downloads.', '🚫');
}

// ── Password check ──
$passwordVerified = !$file['password']; // No password = already verified

if ($file['password'] && !$passwordVerified) {
    // Check session
    $sessionKey = 'verified_' . $token;
    if (!empty($_SESSION[$sessionKey])) {
        $passwordVerified = true;
    }
}

if ($file['password'] && !$passwordVerified) {
    // Handle password form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_password'])) {
        if (password_verify($_POST['file_password'], $file['password'])) {
            $_SESSION['verified_' . $token] = true;
            $passwordVerified = true;
        } else {
            $wrongPassword = true;
        }
    }

    if (!$passwordVerified) {
        // Show password form
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SafeShare Pro — Password Required</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/style.css">
        </head>
        <body class="bg-[#0a0e1a] text-white min-h-screen flex items-center justify-center px-6">
        <div class="glow-orb glow-blue"></div>
        <div class="w-full max-w-md relative z-10">
          <div class="text-center mb-8">
            <div class="text-6xl mb-4">🔐</div>
            <h1 class="font-display text-3xl font-bold mb-2">Password Protected</h1>
            <p class="text-white/40 text-sm">Enter the password to access this file.</p>
          </div>
          <div class="card-glass rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-6 p-3 bg-white/5 rounded-xl">
              <span class="text-2xl">📄</span>
              <div>
                <p class="text-sm font-semibold truncate"><?= htmlspecialchars($file['original_name']) ?></p>
                <p class="text-xs text-white/40"><?= formatFileSize($file['file_size']) ?></p>
              </div>
            </div>
            <?php if (!empty($wrongPassword)): ?>
            <div class="mb-4 p-3 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">⚠ Incorrect password. Try again.</div>
            <?php endif; ?>
            <form method="POST">
              <input type="password" name="file_password" placeholder="Enter password"
                class="form-input w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:border-cyan-500 focus:outline-none mb-4"
                autofocus required>
              <button type="submit" class="btn-primary w-full py-3 rounded-xl font-semibold">Unlock File →</button>
            </form>
          </div>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// ── File Preview Screen ──
$action = $_GET['action'] ?? 'preview';

if ($action !== 'download') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeShare Pro — Preview File</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    </head>
    <body class="bg-[#0a0e1a] text-white min-h-screen flex items-center justify-center px-6">
    <div class="glow-orb glow-cyan"></div>
    <div class="w-full max-w-md relative z-10 text-center">
      <div class="mb-8 text-center">
        <div class="text-6xl mb-4">📦</div>
        <h1 class="font-display text-3xl font-bold mb-2">File Ready</h1>
        <p class="text-white/40 text-sm">Review the details below before downloading.</p>
      </div>
      <div class="card-glass rounded-2xl p-8">
        <div class="flex items-center gap-4 mb-8 p-4 bg-white/5 rounded-2xl text-left">
          <div class="text-4xl">📄</div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold truncate text-lg"><?= htmlspecialchars($file['original_name']) ?></p>
            <p class="text-sm text-white/40"><?= formatFileSize($file['file_size']) ?></p>
          </div>
        </div>
        
        <div class="space-y-4 mb-8 text-left text-sm">
            <div class="flex justify-between border-b border-white/5 pb-2">
                <span class="text-white/30">Expires At</span>
                <span class="text-white/60"><?= date('M j, Y H:i', strtotime($file['expiry_time'])) ?></span>
            </div>
            <?php if ($file['max_downloads']): ?>
            <div class="flex justify-between border-b border-white/5 pb-2">
                <span class="text-white/30">Downloads Left</span>
                <span class="text-white/60"><?= ($file['max_downloads'] - $file['download_count']) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <a href="download.php?token=<?= urlencode($token) ?>&action=download" 
           class="btn-primary w-full py-4 rounded-xl font-semibold flex items-center justify-center gap-2">
           📥 Download File
        </a>
      </div>
      <a href="index.php" class="inline-block mt-8 text-white/40 hover:text-white transition text-sm">← Back to Home</a>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// ── File existence check ──
if (!file_exists($file['file_path'])) {
    showError('File Missing', 'The file could not be found on the server.', '📂');
}

// ── Increment download count ──
$pdo  = getDB();
$stmt = $pdo->prepare("UPDATE files SET download_count = download_count + 1 WHERE token = ?");
$stmt->execute([$token]);

// ── Check if this was the final download and clean up ──
if ($file['max_downloads'] !== null && ($file['download_count'] + 1) >= $file['max_downloads']) {
    // We'll let the next request show the limit-reached page; file stays for this download
}

// ── Force download ──
$filePath = $file['file_path'];
$mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . str_replace('"', '\"', $file['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;