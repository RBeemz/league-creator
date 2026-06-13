# ⚽ LeagueForge — Custom League Creator

A full-stack PHP/MySQL web application for managing custom football leagues. Create competitions, register teams and players, generate fixtures, input results with stats, and view live standings.

---

## Stack
- **Backend:** PHP 7.4+, PDO/MySQL
- **Frontend:** Tailwind CSS (CDN), Vanilla JS
- **Server:** XAMPP / WAMP / LAMP
- **PDF Export:** mPDF (optional, via Composer)

---

## Setup (XAMPP)

### 1. Place files
```
C:\xampp\htdocs\league-creator\   (Windows)
/Applications/XAMPP/htdocs/league-creator/  (macOS)
```

### 2. Import the database

**Option A — phpMyAdmin:**
1. Open `http://localhost/phpmyadmin`
2. Click "New" → create database named `league_creator`
3. Select it → click "Import" → choose `schema.sql` → click "Go"

**Option B — Command line:**
```bash
mysql -u root -p < schema.sql
```

### 3. Configure database credentials
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'league_creator');
define('DB_USER', 'root');
define('DB_PASS', '');  // your MySQL password
```

### 4. Ensure upload directory is writable
```bash
chmod 755 assets/uploads/
```
On Windows with XAMPP, the folder is writable by default.

### 5. Open the app
Visit: `http://localhost/league-creator/`

---

## Install mPDF (optional, for PDF download)

PDF export works via browser print by default. For direct PDF download, install mPDF:

```bash
cd /path/to/league-creator
composer require mpdf/mpdf
```

> Requires [Composer](https://getcomposer.org/) to be installed.

After installation, the "Download PDF" button will appear on the export page.

---

## Features

| Feature | Description |
|---|---|
| Dashboard | List all leagues with stats |
| Create League | Name, format (1x/2x/custom), logo |
| Team Management | Add/remove teams + player squads |
| Schedule Generator | Randomized round-robin algorithm |
| Input Results | Score + per-player goals/assists/cards |
| Standings | Live table with Pts/GD/GF tiebreakers |
| Top Scorers | Ranked by goals, ties broken by assists |
| PDF Export | Print or download standings report |

---

## File Structure

```
league-creator/
├── assets/
│   ├── css/             # Custom CSS overrides (if any)
│   └── uploads/         # Team/league logo uploads
├── includes/
│   ├── config.php       # DB credentials (keep private)
│   ├── db.php           # PDO connection singleton
│   ├── functions.php    # Standings, schedule logic, helpers
│   ├── header.php       # HTML head + nav
│   └── footer.php       # Scripts + closing tags
├── schema.sql           # Database schema
├── index.php            # Dashboard
├── create_league.php    # New league form
├── league.php           # League detail (tabs: schedule/standings/scorers)
├── teams.php            # Team management
├── players.php          # Player squad management
├── generate_schedule.php# Schedule generator
├── input_result.php     # Match result + stats input
├── delete_league.php    # League deletion handler
├── export_pdf.php       # PDF/print export
└── README.md
```

---

## Security Notes
- All DB queries use PDO prepared statements
- Image uploads validate MIME type (not just extension)
- File sizes capped at 2MB
- `config.php` should be added to `.gitignore`
