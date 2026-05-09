<?php
session_start();
require_once 'functions.php';

if (empty($_SESSION['upload_success'])) {
    header('Location: index.php');
    exit;
}

$data = $_SESSION['upload_success'];
$link = BASE_URL . '/download.php?token=' . urlencode($data['token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upload Success — SafeShare Pro</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-[#0a0e1a] text-white min-h-screen flex items-center justify-center px-6">
  <div class="glow-orb glow-cyan"></div>
  
  <div class="max-w-xl w-full text-center relative z-10">
    <div class="mb-8">
      <div class="w-20 h-20 bg-emerald-500/20 border border-emerald-500/30 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">✅</div>
      <h1 class="font-display text-4xl font-bold mb-2">File Uploaded!</h1>
      <p class="text-white/50">Your secure link is ready to share.</p>
    </div>

    <div class="card-glass p-8 rounded-3xl border border-white/10 bg-white/5 backdrop-blur-xl">
      <div class="flex items-center gap-4 mb-8 p-4 bg-white/5 rounded-2xl text-left">
        <div class="text-3xl">📄</div>
        <div class="flex-1 min-w-0">
          <p class="font-semibold truncate"><?= htmlspecialchars($data['original_name']) ?></p>
          <p class="text-xs text-white/40"><?= formatFileSize($data['file_size']) ?> · Expires in <?= $data['expiry_label'] ?></p>
        </div>
      </div>

      <div class="mb-6">
        <label class="block text-xs font-semibold text-white/30 uppercase tracking-wider mb-2 text-left">Shareable Link</label>
        <div class="relative group">
          <input type="text" readonly value="<?= $link ?>" id="shareLink"
            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-4 text-cyan-400 font-mono text-sm focus:outline-none focus:border-cyan-500/50">
          <button onclick="copyLink()" class="absolute right-2 top-2 bottom-2 px-4 bg-cyan-500 hover:bg-cyan-400 text-black text-xs font-bold rounded-lg transition-all">
            Copy
          </button>
        </div>
      </div>

      <div class="flex flex-col gap-3">
        <a href="index.php" class="text-white/40 hover:text-white transition text-sm">Upload another file</a>
      </div>
    </div>
  </div>

  <script>
    async function copyLink() {
      const copyText = document.getElementById("shareLink");
      try {
        await navigator.clipboard.writeText(copyText.value);
        alert("Link copied to clipboard!");
      } catch (err) {
        // Fallback for older browsers
        copyText.select();
        document.execCommand("copy");
        alert("Link copied!");
      }
    }
  </script>
</body>
</html>