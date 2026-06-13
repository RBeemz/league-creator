<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';

$leagueId = (int)($_GET['league_id'] ?? 0);
$league = getLeague($leagueId);
if (!$league) { header('Location: index.php'); exit; }

$db = getDB();
$stmt = $db->prepare("SELECT id, name FROM teams WHERE league_id = ? ORDER BY name");
$stmt->execute([$leagueId]);
$teams = $stmt->fetchAll();
$alreadyHasMatches = hasMatches($leagueId);

$pageTitle = 'Generate Schedule — ' . htmlspecialchars($league['name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm = $_POST['confirm'] ?? '';
    if ($alreadyHasMatches && $confirm !== 'yes') {
        setFlash('error', 'Please confirm you want to overwrite the existing schedule.');
        header("Location: generate_schedule.php?league_id={$leagueId}"); exit;
    }

    if (count($teams) < 2) {
        setFlash('error', 'Need at least 2 teams to generate a schedule.');
        header("Location: generate_schedule.php?league_id={$leagueId}"); exit;
    }

    // Clear existing matches (cascade deletes results/stats)
    $db->prepare("DELETE FROM matches WHERE league_id = ?")->execute([$leagueId]);

    // Generate round-robin
    $teamIds = array_column($teams, 'id');
    $singleRounds = generateRoundRobin($teamIds);
    $format = $league['format'];

    $allRounds = [];
    if ($format === '1x') {
        $allRounds = $singleRounds;
    } elseif ($format === '2x') {
        // Double: first leg, then reverse home/away for second leg
        foreach ($singleRounds as $r) $allRounds[] = $r;
        foreach ($singleRounds as $r) {
            $reversed = array_map(fn($m) => ['home'=>$m['away'],'away'=>$m['home']], $r);
            $allRounds[] = $reversed;
        }
    } else {
        // Custom: repeat/truncate to fit $league['rounds']
        $targetRounds = max(1, (int)($league['rounds'] ?? 1));
        $base = $singleRounds;
        for ($i = 0; $i < $targetRounds; $i++) {
            $allRounds[] = $base[$i % count($base)];
        }
    }

    $stmt = $db->prepare("INSERT INTO matches (league_id, home_team_id, away_team_id, round) VALUES (?,?,?,?)");
    foreach ($allRounds as $roundNum => $matches) {
        foreach ($matches as $m) {
            $stmt->execute([$leagueId, $m['home'], $m['away'], $roundNum + 1]);
        }
    }

    $totalMatches = array_sum(array_map('count', $allRounds));
    setFlash('success', "Schedule generated! {$totalMatches} matches across " . count($allRounds) . " rounds.");
    header("Location: league.php?id={$leagueId}&tab=schedule"); exit;
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-2xl mx-auto">
  <div class="mb-6">
    <a href="league.php?id=<?= $leagueId ?>" class="text-slate-400 hover:text-white text-sm">← Back to League</a>
    <h1 class="font-display text-2xl font-bold text-white mt-2">Generate Match Schedule</h1>
  </div>

  <?php if (count($teams) < 2): ?>
    <div class="card p-6 text-center">
      <div class="text-4xl mb-3">⚠️</div>
      <p class="text-white font-semibold mb-2">Not enough teams</p>
      <p class="text-slate-400 mb-4">You need at least 2 teams to generate a schedule.</p>
      <a href="teams.php?league_id=<?= $leagueId ?>" class="btn-primary">Add Teams</a>
    </div>
  <?php else: ?>
    <div class="card p-6 space-y-5">
      <div class="bg-[#0d1117] rounded-lg p-4">
        <h2 class="text-sm font-medium text-slate-300 mb-3">Schedule Preview</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
          <div>
            <span class="text-slate-500">Teams</span>
            <div class="text-white font-semibold"><?= count($teams) ?></div>
          </div>
          <div>
            <span class="text-slate-500">Format</span>
            <div class="text-white font-semibold"><?= strtoupper($league['format']) ?></div>
          </div>
          <div>
            <span class="text-slate-500">Est. Rounds</span>
            <?php
            $n = count($teams);
            $baseR = ($n % 2 === 0) ? $n - 1 : $n;
            $estR = $league['format'] === '2x' ? $baseR * 2 : ($league['format'] === 'custom' ? (int)($league['rounds']??1) : $baseR);
            $estM = $estR * floor($n/2);
            ?>
            <div class="text-white font-semibold"><?= $estR ?></div>
          </div>
          <div>
            <span class="text-slate-500">Est. Matches</span>
            <div class="text-pitch-400 font-semibold"><?= $estM ?></div>
          </div>
        </div>
      </div>

      <div>
        <h3 class="text-sm font-medium text-slate-300 mb-2">Teams included (<?= count($teams) ?>)</h3>
        <div class="flex flex-wrap gap-2">
          <?php foreach ($teams as $t): ?>
            <span class="bg-[#21262d] text-slate-300 text-xs px-3 py-1.5 rounded-full"><?= htmlspecialchars($t['name']) ?></span>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($alreadyHasMatches): ?>
      <div class="bg-yellow-950 border border-yellow-800 text-yellow-300 rounded-lg p-4 text-sm">
        ⚠️ <strong>Warning:</strong> A schedule already exists. Regenerating will delete all existing matches and results. This cannot be undone.
      </div>
      <?php endif; ?>

      <form method="post">
        <?php if ($alreadyHasMatches): ?>
        <input type="hidden" name="confirm" value="yes">
        <button type="submit" class="btn-danger w-full text-center py-2.5"
          data-confirm="This will DELETE all existing matches and results for this league. Are you absolutely sure?">
          ⚡ Overwrite & Regenerate Schedule
        </button>
        <?php else: ?>
        <button type="submit" class="btn-primary w-full text-center py-2.5">⚡ Generate Schedule</button>
        <?php endif; ?>
      </form>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
