<?php
session_start();
require_once 'functions.php';
deleteExpiredFiles(); // Clean up on every page load
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SafeShare Pro — Secure Temporary File Sharing</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-[#0a0e1a] text-white min-h-screen">

<!-- ── NAV ── -->
<nav class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-6 py-4 border-b border-white/5 backdrop-blur-md bg-[#0a0e1a]/80">
  <a href="index.php" class="flex items-center gap-2">
    <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 flex items-center justify-center text-sm font-black">S</div>
    <span class="font-display font-bold text-lg tracking-tight">SafeShare <span class="text-cyan-400">Pro</span></span>
  </a>
  <div class="flex items-center gap-4">
    <a href="admin.php" class="text-sm text-white/50 hover:text-white transition">Admin</a>
    <a href="#upload" class="btn-primary text-sm px-4 py-2 rounded-lg">Upload File</a>
  </div>
</nav>

<!-- ── HERO ── -->
<section class="pt-32 pb-20 px-6 text-center relative overflow-hidden">
  <div class="glow-orb glow-blue"></div>
  <div class="glow-orb glow-cyan"></div>

  <div class="relative z-10 max-w-3xl mx-auto">
    <div class="inline-flex items-center gap-2 bg-white/5 border border-white/10 rounded-full px-4 py-1.5 text-sm text-cyan-300 mb-6">
      <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
      No account required · Files auto-delete
    </div>
    <h1 class="font-display text-5xl md:text-7xl font-extrabold leading-tight mb-6">
      Share Files<br><span class="gradient-text">Securely.</span>
    </h1>
    <p class="text-white/50 text-lg md:text-xl font-body max-w-xl mx-auto mb-10">
      Upload once, share a link, files vanish automatically. Password protection, download limits, expiry timers — all built in.
    </p>
    <a href="#upload" class="btn-primary inline-block px-8 py-4 rounded-xl text-base font-semibold">
      Start Sharing Free →
    </a>
  </div>
</section>

<!-- ── FEATURES ── -->
<section class="py-16 px-6 max-w-5xl mx-auto">
  <h2 class="font-display text-3xl font-bold text-center mb-12">Why SafeShare Pro?</h2>
  <div class="grid md:grid-cols-3 gap-6">
    <?php
    $features = [
      ['🔐', 'Password Protected', 'Lock your files behind a password. Only people with the key can access them.'],
      ['⏱️', 'Auto-Expiry Links', 'Set links to expire in 1 hour, 24 hours, or 7 days — files delete themselves.'],
      ['📥', 'Download Limits', 'Cap how many times a file can be downloaded. Perfect for one-time sharing.'],
      ['🚀', 'Instant Sharing', 'No signup, no friction. Upload and get a shareable link in seconds.'],
      ['🛡️', 'Secure Tokens', 'Every link uses a cryptographically random 64-char token. Unguessable.'],
      ['📂', 'All Formats', 'Share any file type — images, videos, audio, or archives — up to 1GB.'],
    ];
    foreach ($features as [$icon, $title, $desc]): ?>
    <div class="feature-card p-6 rounded-2xl border border-white/8 bg-white/3 hover:border-cyan-500/30 transition-all group">
      <div class="text-3xl mb-4"><?= $icon ?></div>
      <h3 class="font-display font-semibold text-lg mb-2 group-hover:text-cyan-300 transition"><?= $title ?></h3>
      <p class="text-white/40 text-sm leading-relaxed"><?= $desc ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── HOW IT WORKS ── -->
<section class="py-16 px-6 max-w-4xl mx-auto">
  <h2 class="font-display text-3xl font-bold text-center mb-12">How It Works</h2>
  <div class="flex flex-col md:flex-row items-center gap-6">
    <?php
    $steps = [
      ['01', 'Upload', 'Select your file, set an expiry, optional password & download limit.'],
      ['02', 'Get Link', 'Receive a unique, secure download link instantly.'],
      ['03', 'Share & Expire', 'Send the link — it auto-deletes when time or downloads run out.'],
    ];
    foreach ($steps as $i => [$num, $title, $desc]): ?>
    <div class="flex-1 text-center px-4 relative">
      <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-blue-600/20 border border-cyan-500/30 flex items-center justify-center mx-auto mb-4">
        <span class="font-display font-black text-cyan-400 text-lg"><?= $num ?></span>
      </div>
      <h3 class="font-display font-bold text-xl mb-2"><?= $title ?></h3>
      <p class="text-white/40 text-sm"><?= $desc ?></p>
    </div>
    <?php if ($i < 2): ?>
    <div class="text-white/20 text-3xl hidden md:block">→</div>
    <?php endif; endforeach; ?>
  </div>
</section>

<!-- ── UPLOAD SECTION ── -->
<section id="upload" class="py-16 px-6">
  <div class="max-w-2xl mx-auto">
    <h2 class="font-display text-3xl font-bold text-center mb-2">Upload Your File</h2>
    <p class="text-center text-white/40 mb-10 text-sm">Max 1GB · All file formats supported</p>

    <?php if (!empty($_SESSION['error'])): ?>
    <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
      ⚠ <?= htmlspecialchars($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
      <!-- Drag & Drop Zone -->
      <div id="dropZone" class="drop-zone border-2 border-dashed border-white/15 rounded-2xl p-10 text-center mb-6 cursor-pointer hover:border-cyan-500/50 transition-all relative">
        <input type="file" name="file" id="fileInput" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required>
        <div id="dropContent">
          <div class="text-5xl mb-4">📁</div>
          <p class="font-display font-semibold text-lg mb-1">Drag & drop your file here</p>
          <p class="text-white/40 text-sm">or click to browse</p>
        </div>
        <div id="filePreview" class="hidden">
          <div class="text-4xl mb-3" id="fileIcon">📄</div>
          <p class="font-semibold" id="fileName">filename.pdf</p>
          <p class="text-white/40 text-sm" id="fileSize">2.3 MB</p>
        </div>
      </div>

      <!-- Upload Progress -->
      <div id="progressBar" class="hidden mb-6">
        <div class="flex justify-between text-xs text-white/40 mb-1">
          <span>Uploading...</span>
          <span id="progressPct">0%</span>
        </div>
        <div class="h-2 bg-white/10 rounded-full overflow-hidden">
          <div id="progressFill" class="h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full transition-all duration-300" style="width:0%"></div>
        </div>
      </div>

      <!-- Options Grid -->
      <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="form-group">
          <label class="block text-sm text-white/60 mb-2 font-medium">⏱ Expires in</label>
          <select name="expiry" class="form-select w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-cyan-500 focus:outline-none text-sm">
            <option value="1m">1 Minute (Demo)</option>
            <option value="1h">1 Hour</option>
            <option value="24h" selected>24 Hours</option>
            <option value="7d">7 Days</option>
          </select>
        </div>
        <div class="form-group">
          <label class="block text-sm text-white/60 mb-2 font-medium">📥 Max Downloads</label>
          <input type="number" name="max_downloads" min="1" max="100" placeholder="Unlimited"
            class="form-input w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:border-cyan-500 focus:outline-none text-sm">
        </div>
        <div class="form-group">
          <label class="block text-sm text-white/60 mb-2 font-medium">🔐 Password (optional)</label>
          <input type="password" name="password" placeholder="Leave blank = public"
            class="form-input w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-white/30 focus:border-cyan-500 focus:outline-none text-sm">
        </div>
      </div>

      <button type="submit" id="submitBtn" class="btn-primary w-full py-4 rounded-xl text-base font-semibold flex items-center justify-center gap-2">
        <span id="btnText">🚀 Upload & Generate Link</span>
        <span id="btnSpinner" class="hidden">⏳ Uploading...</span>
      </button>
    </form>
  </div>
</section>

<!-- ── FOOTER ── -->
<footer class="border-t border-white/5 py-10 px-6 text-center text-white/30 text-sm">
  <p class="font-display font-semibold text-white/60 mb-2">SafeShare <span class="text-cyan-400">Pro</span></p>
  <p>Secure temporary file sharing · Files auto-deleted after expiry</p>
</footer>

<script src="js/app.js"></script>
</body>
</html>