<?php
// Database configuration - keep this file out of version control
define('DB_HOST', 'localhost');
define('DB_NAME', 'league_creator');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// App settings
define('APP_NAME', 'LeagueForge');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', 'assets/uploads/');
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
