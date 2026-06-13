<?php
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Baca Environment Variables (untuk production di Vercel)
        $dbUrl = getenv('DATABASE_URL');
        if (!$dbUrl) {
            // Atau baca dari konstanta (untuk development lokal)
            $dbUrl = DATABASE_URL;
        }

        if (!$dbUrl) {
            die('Konfigurasi database (DATABASE_URL) tidak ditemukan.');
        }

        // Koneksi ke PostgreSQL via PDO
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