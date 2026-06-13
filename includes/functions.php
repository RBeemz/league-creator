<?php
require_once __DIR__ . '/db.php';

// ─── Flash messages ──────────────────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ─── Image upload ─────────────────────────────────────────────────────────────
function handleImageUpload(array $file, string $prefix = 'img'): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > MAX_UPLOAD_SIZE) return null;
    $ext = $mime === 'image/png' ? 'png' : 'jpg';
    $filename = $prefix . '_' . uniqid() . '.' . $ext;
    $dest = UPLOAD_DIR . $filename;
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return UPLOAD_URL . $filename;
    }
    return null;
}

// ─── Standings calculation ────────────────────────────────────────────────────
function getStandings(int $leagueId): array {
    $db = getDB();
    // Get all teams in league
    $stmt = $db->prepare("SELECT id, name, logo_url FROM teams WHERE league_id = ?");
    $stmt->execute([$leagueId]);
    $teams = $stmt->fetchAll();

    $stats = [];
    foreach ($teams as $t) {
        $stats[$t['id']] = [
            'id'      => $t['id'],
            'name'    => $t['name'],
            'logo'    => $t['logo_url'],
            'MP' => 0, 'W' => 0, 'D' => 0, 'L' => 0,
            'GF' => 0, 'GA' => 0, 'GD' => 0, 'Pts' => 0
        ];
    }

    // Get all played match results for this league
    $stmt = $db->prepare("
        SELECT m.home_team_id, m.away_team_id, r.home_score, r.away_score
        FROM matches m
        JOIN match_results r ON r.match_id = m.id
        WHERE m.league_id = ? AND m.status = 'played'
    ");
    $stmt->execute([$leagueId]);
    $results = $stmt->fetchAll();

    foreach ($results as $r) {
        $h = $r['home_team_id'];
        $a = $r['away_team_id'];
        $hs = (int)$r['home_score'];
        $as = (int)$r['away_score'];

        if (!isset($stats[$h]) || !isset($stats[$a])) continue;

        $stats[$h]['MP']++;  $stats[$a]['MP']++;
        $stats[$h]['GF'] += $hs; $stats[$h]['GA'] += $as;
        $stats[$a]['GF'] += $as; $stats[$a]['GA'] += $hs;

        if ($hs > $as) {
            $stats[$h]['W']++; $stats[$h]['Pts'] += 3;
            $stats[$a]['L']++;
        } elseif ($hs < $as) {
            $stats[$a]['W']++; $stats[$a]['Pts'] += 3;
            $stats[$h]['L']++;
        } else {
            $stats[$h]['D']++; $stats[$h]['Pts']++;
            $stats[$a]['D']++; $stats[$a]['Pts']++;
        }
    }

    foreach ($stats as &$s) {
        $s['GD'] = $s['GF'] - $s['GA'];
    }
    unset($s);

    usort($stats, function($a, $b) {
        if ($b['Pts'] !== $a['Pts']) return $b['Pts'] - $a['Pts'];
        if ($b['GD']  !== $a['GD'])  return $b['GD']  - $a['GD'];
        return $b['GF'] - $a['GF'];
    });

    return array_values($stats);
}

// ─── Top scorers ──────────────────────────────────────────────────────────────
function getTopScorers(int $leagueId, int $limit = 20): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.name AS player_name, t.name AS team_name,
               SUM(ps.goals) AS goals, SUM(ps.assists) AS assists
        FROM player_stats ps
        JOIN players p ON p.id = ps.player_id
        JOIN teams t ON t.id = p.team_id
        JOIN matches m ON m.id = ps.match_id
        WHERE m.league_id = ?
        GROUP BY p.id, p.name, t.name
        HAVING goals > 0
        ORDER BY goals DESC, assists DESC
        LIMIT ?
    ");
    $stmt->execute([$leagueId, $limit]);
    return $stmt->fetchAll();
}

// ─── Round-robin schedule generator ──────────────────────────────────────────
function generateRoundRobin(array $teams): array {
    $n = count($teams);
    if ($n < 2) return [];

    shuffle($teams);
    $rounds = [];
    $numRounds = ($n % 2 === 0) ? $n - 1 : $n;
    $half = (int)ceil($n / 2);

    // Pin first team if odd, add dummy if odd count
    $list = $teams;
    if ($n % 2 !== 0) $list[] = null; // bye
    $pinned = $list[0];
    $rotating = array_slice($list, 1);

    for ($r = 0; $r < $numRounds; $r++) {
        $roundMatches = [];
        $circle = array_merge([$pinned], $rotating);
        for ($i = 0; $i < $half; $i++) {
            $home = $circle[$i];
            $away = $circle[count($circle) - 1 - $i];
            if ($home !== null && $away !== null) {
                // Alternate home/away for variety
                if ($r % 2 === 0) {
                    $roundMatches[] = ['home' => $home, 'away' => $away];
                } else {
                    $roundMatches[] = ['home' => $away, 'away' => $home];
                }
            }
        }
        $rounds[] = $roundMatches;
        // Rotate all except pinned
        array_unshift($rotating, array_pop($rotating));
    }

    return $rounds;
}

// ─── Get league with team count ───────────────────────────────────────────────
function getLeague(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM leagues WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function hasMatches(int $leagueId): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM matches WHERE league_id = ?");
    $stmt->execute([$leagueId]);
    return (int)$stmt->fetchColumn() > 0;
}

function getCurrentRound(int $leagueId): int {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT MAX(round) FROM matches
        WHERE league_id = ? AND status = 'played'
    ");
    $stmt->execute([$leagueId]);
    $r = $stmt->fetchColumn();
    return $r ? (int)$r : 0;
}
