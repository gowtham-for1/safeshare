<?php
// =============================================
// db.php — Database Connection
// =============================================

require_once 'config.php';

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:2rem;color:#c00">
                <strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
                <br><small>Make sure XAMPP MySQL is running and the database exists.</small>
                </div>');
        }
    }
    return $pdo;
}