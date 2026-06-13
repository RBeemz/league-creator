<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';

$matchId = (int)($_GET['match_id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT m.*, l.name AS league_name,
           ht.name AS home_name, ht.id AS home_id,
           at.name AS away_name, at.id AS away_id,
           r.home_score, r.away_score
    FROM matches m
    JOIN leagues l ON l.id = m.league_id
    JOIN teams ht ON ht.id = m.home_team_id
    JOIN teams at ON at.id = m.away_team_id
    LEFT JOIN match_results r ON r.match_id = m.id
    WHERE m.id = ?
");
$stmt->execute([$matchId]);
$match = $stmt->fetch();
if (!$match) { header('Location: index.php'); exit; }

$leagueId = $match['league_id'];
$pageTitle = htmlspecialchars($match['home_name']) . ' vs ' . htmlspecialchars($match['away_name']);

// Get players for both teams
$homePlayers = $db->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY position, number");
$homePlayers->execute([$match['home_id']]);
$homePlayers = $homePlayers->fetchAll();

$awayPlayers = $db->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY position, number");
$awayPlayers->execute([$match['away_id']]);
$awayPlayers = $awayPlayers->fetchAll();

// Get existing stats if editing
$existingStats = [];
$statsStmt = $db->prepare("SELECT * FROM player_stats WHERE match_id = ?");
$statsStmt->execute([$matchId]);
foreach ($statsStmt->fetchAll() as $s) $existingStats[$s['player_id']] = $s;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $homeScore = max(0, (int)($_POST['home_score'] ?? 0));
    $awayScore = max(0, (int)($_POST['away_score'] ?? 0));
    $matchDate  = $_POST['match_date'] ?? null;

    $db->beginTransaction();
    try {
        // Update match status & date
        $db->prepare("UPDATE matches SET status='played', match_date=? WHERE id=?")->execute([$matchDate ?: null, $matchId]);

        // Upsert result
        $db->prepare("
            INSERT INTO match_results (match_id, home_score, away_score)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE home_score=?, away_score=?
        ")->execute([$matchId, $homeScore, $awayScore, $homeScore, $awayScore]);

        // Player stats
        $allPlayerIds = array_merge(array_column($homePlayers,'id'), array_column($awayPlayers,'id'));
        foreach ($allPlayerIds as $pid) {
            $goals   = max(0, (int)($_POST["goals_{$pid}"] ?? 0));
            $assists = max(0, (int)($_POST["assists_{$pid}"] ?? 0));
            $yellow  = max(0, (int)($_POST["yellow_{$pid}"] ?? 0));
            $red     = max(0, (int)($_POST["red_{$pid}"] ?? 0));
            if ($goals || $assists || $yellow || $red) {
                $db->prepare("
                    INSERT INTO player_stats (match_id, player_id, goals, assists, yellow_cards, red_cards)
                    VALUES (?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE goals=?, assists=?, yellow_cards=?, red_cards=?
                ")->execute([$matchId, $pid, $goals, $assists, $yellow, $red, $goals, $assists, $yellow, $red]);
            } else {
                $db->prepare("DELETE FROM player_stats WHERE match_id=? AND player_id=?")->execute([$matchId, $pid]);
            }
        }

        $db->commit();
        setFlash('success', 'Result saved successfully!');
        header("Location: league.php?id={$leagueId}&tab=schedule"); exit;
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Failed to save result: ' . $e->getMessage());
        header("Location: input_result.php?match_id={$matchId}"); exit;
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
  <div class="mb-6">
    <a href="league.php?id=<?= $leagueId ?>&tab=schedule" class="text-slate-400 hover:text-white text-sm">← Back to Schedule</a>
    <h1 class="font-display text-2xl font-bold text-white mt-2">Input Match Result</h1>
    <p class="text-slate-400 text-sm"><?= htmlspecialchars($match['league_name']) ?> — Round <?= $match['round'] ?></p>
  </div>

  <form method="post">
    <!-- Score Card -->
    <div class="card p-6 mb-5">
      <h2 class="text-sm font-medium text-slate-400 uppercase tracking-wider mb-5 text-center">Final Score</h2>
      <div class="flex items-center justify-center gap-6">
        <div class="flex-1 text-right">
          <p class="font-bold text-white text-lg"><?= htmlspecialchars($match['home_name']) ?></p>
          <p class="text-slate-500 text-xs">Home</p>
        </div>
        <div class="flex items-center gap-2">
          <input type="number" name="home_score" value="<?= $match['home_score'] ?? 0 ?>"
            class="input-field text-center text-2xl font-bold w-16 py-3" min="0" max="99">
          <span class="text-slate-500 text-xl font-bold">–</span>
          <input type="number" name="away_score" value="<?= $match['away_score'] ?? 0 ?>"
            class="input-field text-center text-2xl font-bold w-16 py-3" min="0" max="99">
        </div>
        <div class="flex-1 text-left">
          <p class="font-bold text-white text-lg"><?= htmlspecialchars($match['away_name']) ?></p>
          <p class="text-slate-500 text-xs">Away</p>
        </div>
      </div>
      <div class="mt-4 max-w-xs mx-auto">
        <label class="block text-xs font-medium text-slate-400 mb-1 text-center">Match Date</label>
        <input type="date" name="match_date" value="<?= $match['match_date'] ?? date('Y-m-d') ?>" class="input-field text-center">
      </div>
    </div>

    <!-- Player Stats -->
    <?php foreach ([['team_id'=>$match['home_id'],'team_name'=>$match['home_name'],'players'=>$homePlayers],['team_id'=>$match['away_id'],'team_name'=>$match['away_name'],'players'=>$awayPlayers]] as $side): ?>
    <div class="card mb-5">
      <div class="px-5 py-3 border-b border-[#21262d] flex items-center gap-2">
        <h2 class="font-semibold text-white"><?= htmlspecialchars($side['team_name']) ?></h2>
        <span class="text-slate-500 text-xs"><?= count($side['players']) ?> players</span>
      </div>
      <?php if (empty($side['players'])): ?>
        <p class="text-slate-500 text-sm text-center py-6">No players registered for this team.
          <a href="players.php?team_id=<?= $side['team_id'] ?>&league_id=<?= $leagueId ?>" class="text-pitch-400 hover:underline">Add players</a>
        </p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#21262d]">
                <th class="px-4 py-2.5 text-left text-slate-400 font-medium">Player</th>
                <th class="px-3 py-2.5 text-center text-slate-400 font-medium">⚽</th>
                <th class="px-3 py-2.5 text-center text-slate-400 font-medium">🅰</th>
                <th class="px-3 py-2.5 text-center text-slate-400 font-medium">🟨</th>
                <th class="px-3 py-2.5 text-center text-slate-400 font-medium">🟥</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($side['players'] as $p):
                $st = $existingStats[$p['id']] ?? [];
              ?>
              <tr class="border-b border-[#21262d] hover:bg-[#1a2030]">
                <td class="px-4 py-2.5">
                  <span class="font-medium text-white text-sm"><?= htmlspecialchars($p['name']) ?></span>
                  <span class="text-xs text-slate-500 ml-1"><?= $p['number'] ? '#'.$p['number'] : '' ?> <?= $p['position'] ?></span>
                </td>
                <td class="px-3 py-2.5 text-center">
                  <input type="number" name="goals_<?= $p['id'] ?>" value="<?= $st['goals'] ?? 0 ?>"
                    class="w-12 text-center bg-[#0d1117] border border-[#30363d] rounded text-white text-sm py-1 focus:border-pitch-500 focus:outline-none" min="0" max="99">
                </td>
                <td class="px-3 py-2.5 text-center">
                  <input type="number" name="assists_<?= $p['id'] ?>" value="<?= $st['assists'] ?? 0 ?>"
                    class="w-12 text-center bg-[#0d1117] border border-[#30363d] rounded text-white text-sm py-1 focus:border-pitch-500 focus:outline-none" min="0" max="99">
                </td>
                <td class="px-3 py-2.5 text-center">
                  <input type="number" name="yellow_<?= $p['id'] ?>" value="<?= $st['yellow_cards'] ?? 0 ?>"
                    class="w-12 text-center bg-[#0d1117] border border-[#30363d] rounded text-yellow-300 text-sm py-1 focus:border-yellow-500 focus:outline-none" min="0" max="2">
                </td>
                <td class="px-3 py-2.5 text-center">
                  <input type="number" name="red_<?= $p['id'] ?>" value="<?= $st['red_cards'] ?? 0 ?>"
                    class="w-12 text-center bg-[#0d1117] border border-[#30363d] rounded text-red-400 text-sm py-1 focus:border-red-500 focus:outline-none" min="0" max="1">
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <div class="flex gap-3">
      <button type="submit" class="btn-primary px-8 py-2.5">Save Result ✓</button>
      <a href="league.php?id=<?= $leagueId ?>&tab=schedule" class="btn-secondary py-2.5">Cancel</a>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
