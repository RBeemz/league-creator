<?php
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Baca dari environment variable (Vercel atau lokal)
        $dbUrl = getenv('DATABASE_URL');
        
        if (!$dbUrl) {
            die('DATABASE_URL environment variable is not set.');
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dbUrl, null, null, $options);
        } catch (PDOException $e) {
            die('Database Connection Error: ' . $e->getMessage());
        }
    }
    return $pdo;
}
?>