<?php
// =============================================
// admin.php — Simple Admin Dashboard
// =============================================

session_start();
require_once 'functions.php';
deleteExpiredFiles();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_token'])) {
    $token = $_POST['delete_token'];
    $pdo   = getDB();
    $stmt  = $pdo->prepare("SELECT file_path FROM files WHERE token = ?");
    $stmt->execute([$token]);
    $row   = $stmt->fetch();
    if ($row && file_exists($row['file_path'])) {
        @unlink($row['file_path']);
    }
    $pdo->prepare("DELETE FROM files WHERE token = ?")->execute([$token]);
    header('Location: admin.php?deleted=1');
    exit;
}

// Fetch all files
$pdo   = getDB();
$files = $pdo->query("SELECT * FROM files ORDER BY created_at DESC")->fetchAll();

// Stats
$totalFiles     = count($files);
$totalDownloads = array_sum(array_column($files, 'download_count'));
$totalSize      = array_sum(array_column($files, 'file_size'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SafeShare Pro — Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-[#0a0e1a] text-white min-h-screen">

<nav class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-[#0a0e1a]">
  <div class="flex items-center gap-2">
    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center text-sm font-black">S</div>
    <span class="font-display font-bold text-lg">SafeShare <span class="text-cyan-400">Pro</span> <span class="text-white/30 text-sm font-normal">/ Admin</span></span>
  </div>
  <a href="index.php" class="text-sm text-white/40 hover:text-white transition">← Back to App</a>
</nav>

<div class="max-w-6xl mx-auto px-6 py-10">

  <?php if (!empty($_GET['deleted'])): ?>
  <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-sm">✅ File deleted successfully.</div>
  <?php endif; ?>

  <h1 class="font-display text-3xl font-bold mb-8">Admin Dashboard</h1>

  <!-- Stats -->
  <div class="grid grid-cols-3 gap-4 mb-10">
    <?php
    $stats = [
      ['📂', 'Total Files', $totalFiles],
      ['📥', 'Total Downloads', $totalDownloads],
      ['💾', 'Total Size', formatFileSize($totalSize)],
    ];
    foreach ($stats as [$icon, $label, $value]): ?>
    <div class="card-glass rounded-2xl p-5 text-center">
      <div class="text-3xl mb-2"><?= $icon ?></div>
      <p class="font-display text-2xl font-bold"><?= $value ?></p>
      <p class="text-white/40 text-sm"><?= $label ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Files Table -->
  <div class="card-glass rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-white/5">
      <h2 class="font-display font-semibold text-lg">Uploaded Files</h2>
    </div>

    <?php if (empty($files)): ?>
    <div class="text-center py-16 text-white/30">
      <div class="text-5xl mb-4">📭</div>
      <p>No files uploaded yet.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-white/5 text-white/40 text-left">
            <th class="px-6 py-3 font-medium">File</th>
            <th class="px-6 py-3 font-medium">Size</th>
            <th class="px-6 py-3 font-medium">Downloads</th>
            <th class="px-6 py-3 font-medium">Expires</th>
            <th class="px-6 py-3 font-medium">Password</th>
            <th class="px-6 py-3 font-medium">Link</th>
            <th class="px-6 py-3 font-medium">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($files as $f):
            $isExpired = strtotime($f['expiry_time']) < time();
            $dlInfo = $f['max_downloads']
              ? $f['download_count'] . ' / ' . $f['max_downloads']
              : $f['download_count'] . ' / ∞';
            $link = BASE_URL . '/download.php?token=' . urlencode($f['token']);
          ?>
          <tr class="border-b border-white/5 hover:bg-white/2 transition <?= $isExpired ? 'opacity-40' : '' ?>">
            <td class="px-6 py-4">
              <div class="font-medium truncate max-w-[180px]"><?= htmlspecialchars($f['original_name']) ?></div>
              <div class="text-white/30 text-xs"><?= date('M j, Y g:i A', strtotime($f['created_at'])) ?></div>
            </td>
            <td class="px-6 py-4 text-white/60"><?= formatFileSize($f['file_size']) ?></td>
            <td class="px-6 py-4">
              <span class="inline-block px-2 py-0.5 rounded-md bg-white/5 text-white/70 text-xs"><?= $dlInfo ?></span>
            </td>
            <td class="px-6 py-4">
              <?php if ($isExpired): ?>
                <span class="text-red-400 text-xs font-semibold">EXPIRED</span>
              <?php else: ?>
                <span class="text-white/60 text-xs"><?= date('M j, g:i A', strtotime($f['expiry_time'])) ?></span>
              <?php endif; ?>
            </td>
            <td class="px-6 py-4 text-center"><?= $f['password'] ? '🔐' : '—' ?></td>
            <td class="px-6 py-4">
              <a href="<?= htmlspecialchars($link) ?>" target="_blank"
                 class="text-cyan-400 hover:underline text-xs font-mono truncate block max-w-[100px]">Open ↗</a>
            </td>
            <td class="px-6 py-4">
              <form method="POST" onsubmit="return confirm('Delete this file permanently?')">
                <input type="hidden" name="delete_token" value="<?= htmlspecialchars($f['token']) ?>">
                <button type="submit"
                  class="px-3 py-1.5 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 hover:bg-red-500/20 transition text-xs font-medium">
                  Delete
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>