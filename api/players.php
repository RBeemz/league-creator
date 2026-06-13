<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';

$teamId   = (int)($_GET['team_id'] ?? 0);
$leagueId = (int)($_GET['league_id'] ?? 0);
$db = getDB();

$team = $db->prepare("SELECT * FROM teams WHERE id = ?");
$team->execute([$teamId]);
$team = $team->fetch();
if (!$team) { header('Location: index.php'); exit; }

$league = getLeague($leagueId ?: $team['league_id']);
$leagueId = $league['id'];
$pageTitle = 'Players — ' . htmlspecialchars($team['name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_player') {
        $name   = trim($_POST['name'] ?? '');
        $pos    = $_POST['position'] ?? 'MID';
        $number = (int)($_POST['number'] ?? 0) ?: null;
        if (!$name) { setFlash('error', 'Player name required.'); }
        else {
            $stmt = $db->prepare("INSERT INTO players (team_id, name, position, number) VALUES (?,?,?,?)");
            $stmt->execute([$teamId, $name, $pos, $number]);
            setFlash('success', "{$name} added to squad.");
        }
    }

    if ($action === 'delete_player') {
        $pid = (int)$_POST['player_id'];
        $db->prepare("DELETE FROM players WHERE id = ? AND team_id = ?")->execute([$pid, $teamId]);
        setFlash('success', 'Player removed.');
    }

    header("Location: players.php?team_id={$teamId}&league_id={$leagueId}"); exit;
}

$stmt = $db->prepare("SELECT * FROM players WHERE team_id = ? ORDER BY position, number");
$stmt->execute([$teamId]);
$players = $stmt->fetchAll();
$positions = ['GK','DEF','MID','FWD'];
$posColors = ['GK'=>'text-yellow-400','DEF'=>'text-blue-400','MID'=>'text-green-400','FWD'=>'text-red-400'];
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="mb-6">
  <a href="teams.php?league_id=<?= $leagueId ?>" class="text-slate-400 hover:text-white text-sm">← Back to Teams</a>
  <div class="flex items-center gap-3 mt-2">
    <?php if ($team['logo_url']): ?>
      <img src="<?= htmlspecialchars($team['logo_url']) ?>" class="w-10 h-10 rounded-full object-cover border-2 border-[#30363d]" alt="">
    <?php endif; ?>
    <div>
      <h1 class="font-display text-2xl font-bold text-white"><?= htmlspecialchars($team['name']) ?> — Squad</h1>
      <p class="text-slate-400 text-sm"><?= $team['city'] ? htmlspecialchars($team['city']).' &middot; ' : '' ?><?= count($players) ?> players</p>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Add Player Form -->
  <div class="lg:col-span-1">
    <div class="card p-5 sticky top-20">
      <h2 class="font-semibold text-white mb-4">Add Player</h2>
      <form method="post" class="space-y-4">
        <input type="hidden" name="action" value="add_player">
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Full Name *</label>
          <input type="text" name="name" class="input-field" placeholder="e.g. Erling Haaland" required>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Position</label>
          <select name="position" class="input-field">
            <?php foreach ($positions as $p): ?>
              <option value="<?= $p ?>"><?= $p ?> — <?= ['GK'=>'Goalkeeper','DEF'=>'Defender','MID'=>'Midfielder','FWD'=>'Forward'][$p] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Jersey Number</label>
          <input type="number" name="number" class="input-field" min="1" max="99" placeholder="e.g. 9">
        </div>
        <button type="submit" class="btn-primary w-full text-center">Add to Squad</button>
      </form>
    </div>
  </div>

  <!-- Players List -->
  <div class="lg:col-span-2">
    <?php if (empty($players)): ?>
      <div class="card text-center py-16">
        <div class="text-4xl mb-3">👤</div>
        <p class="text-slate-400">No players in this squad yet.</p>
      </div>
    <?php else: ?>
      <div class="card overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-[#21262d]">
              <th class="px-4 py-3 text-left text-slate-400 font-medium">#</th>
              <th class="px-4 py-3 text-left text-slate-400 font-medium">Name</th>
              <th class="px-4 py-3 text-left text-slate-400 font-medium">Position</th>
              <th class="px-4 py-3 text-right text-slate-400 font-medium">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($players as $p): ?>
            <tr class="border-b border-[#21262d] hover:bg-[#1a2030] transition-colors">
              <td class="px-4 py-3 text-slate-500 w-10"><?= $p['number'] ?? '—' ?></td>
              <td class="px-4 py-3 font-medium text-white"><?= htmlspecialchars($p['name']) ?></td>
              <td class="px-4 py-3">
                <span class="text-xs font-bold <?= $posColors[$p['position']] ?? '' ?>"><?= $p['position'] ?></span>
              </td>
              <td class="px-4 py-3 text-right">
                <form method="post" class="inline">
                  <input type="hidden" name="action" value="delete_player">
                  <input type="hidden" name="player_id" value="<?= $p['id'] ?>">
                  <button type="submit" class="text-red-400 hover:text-red-300 text-xs"
                    data-confirm="Remove <?= htmlspecialchars($p['name'], ENT_QUOTES) ?>?">Remove</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
