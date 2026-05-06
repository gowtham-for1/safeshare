<?php
// =============================================
// config.php — Database & App Configuration
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'safeshare');

define('BASE_URL', 'http://localhost/safeshare');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 1024 * 1024 * 1024); // 1GB
define('ALLOWED_TYPES', []); // Empty array allows all file formats