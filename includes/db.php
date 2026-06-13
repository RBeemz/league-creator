<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="padding:2rem;background:#1a1a2e;color:#ef4444;font-family:monospace;">
                <h2>Database Connection Error</h2>
                <p>Could not connect to MySQL. Please check your config.php settings.</p>
                <p style="opacity:.6;font-size:.85em;">' . htmlspecialchars($e->getMessage()) . '</p>
            </div>');
        }
    }
    return $pdo;
}
