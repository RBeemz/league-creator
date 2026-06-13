<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';

$leagueId = (int)($_GET['league_id'] ?? 0);
$league = getLeague($leagueId);
if (!$league) { header('Location: index.php'); exit; }

$pageTitle = 'Teams — ' . htmlspecialchars($league['name']);
$db = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_team') {
        $name = trim($_POST['name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        if (!$name) { setFlash('error', 'Team name is required.'); }
        else {
            $logo = null;
            if (!empty($_FILES['logo']['name'])) {
                $logo = handleImageUpload($_FILES['logo'], 'team');
                if (!$logo) { setFlash('error', 'Invalid logo file.'); header("Location: teams.php?league_id={$leagueId}"); exit; }
            }
            $stmt = $db->prepare("INSERT INTO teams (league_id, name, city, logo_url) VALUES (?,?,?,?)");
            $stmt->execute([$leagueId, $name, $city, $logo]);
            setFlash('success', "Team '{$name}' added.");
        }
    }

    if ($action === 'delete_team') {
        $teamId = (int)($_POST['team_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM teams WHERE id = ? AND league_id = ?");
        $stmt->execute([$teamId, $leagueId]);
        setFlash('success', 'Team removed.');
    }

    header("Location: teams.php?league_id={$leagueId}"); exit;
}

$stmt = $db->prepare("
    SELECT t.*, COUNT(p.id) AS player_count
    FROM teams t
    LEFT JOIN players p ON p.team_id = t.id
    WHERE t.league_id = ?
    GROUP BY t.id
    ORDER BY t.name
");
$stmt->execute([$leagueId]);
$teams = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="mb-6">
  <a href="league.php?id=<?= $leagueId ?>" class="text-slate-400 hover:text-white text-sm">← Back to <?= htmlspecialchars($league['name']) ?></a>
  <h1 class="font-display text-2xl font-bold text-white mt-2">Team Management</h1>
  <p class="text-slate-400 text-sm"><?= count($teams) ?> team(s) in this league</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
  <!-- Add Team Form -->
  <div class="lg:col-span-1">
    <div class="card p-5 sticky top-20">
      <h2 class="font-semibold text-white mb-4">Add New Team</h2>
      <form method="post" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="action" value="add_team">
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Team Name *</label>
          <input type="text" name="name" class="input-field" placeholder="e.g. Arsenal FC" required>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">City</label>
          <input type="text" name="city" class="input-field" placeholder="e.g. London">
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-400 mb-1">Logo (JPG/PNG, max 2MB)</label>
          <input type="file" name="logo" accept="image/jpeg,image/png" class="input-field py-2 text-xs cursor-pointer">
        </div>
        <button type="submit" class="btn-primary w-full text-center">Add Team</button>
      </form>
    </div>
  </div>

  <!-- Teams List -->
  <div class="lg:col-span-2 space-y-3">
    <?php if (empty($teams)): ?>
      <div class="card text-center py-16">
        <div class="text-4xl mb-3">👥</div>
        <p class="text-slate-400">No teams yet. Add your first team.</p>
      </div>
    <?php else: ?>
      <?php foreach ($teams as $team): ?>
      <div class="card p-4">
        <div class="flex items-center gap-3">
          <?php if ($team['logo_url']): ?>
            <img src="<?= htmlspecialchars($team['logo_url']) ?>" class="w-12 h-12 rounded-full object-cover border-2 border-[#30363d]" alt="">
          <?php else: ?>
            <div class="w-12 h-12 rounded-full bg-[#21262d] border-2 border-[#30363d] flex items-center justify-center text-xl">⚽</div>
          <?php endif; ?>
          <div class="flex-1">
            <h3 class="font-semibold text-white"><?= htmlspecialchars($team['name']) ?></h3>
            <p class="text-slate-500 text-xs">
              <?= $team['city'] ? htmlspecialchars($team['city']).' &middot; ' : '' ?>
              <?= $team['player_count'] ?> players
            </p>
          </div>
          <div class="flex gap-2">
            <a href="players.php?team_id=<?= $team['id'] ?>&league_id=<?= $leagueId ?>" class="btn-secondary text-xs py-1.5 px-3">Players</a>
            <form method="post">
              <input type="hidden" name="action" value="delete_team">
              <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
              <button type="submit" class="btn-danger text-xs py-1.5 px-3"
                data-confirm="Remove '<?= htmlspecialchars($team['name'], ENT_QUOTES) ?>' and all their players?">Remove</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (count($teams) >= 2): ?>
        <div class="text-center pt-3">
          <a href="league.php?id=<?= $leagueId ?>" class="btn-primary">
            <?= hasMatches($leagueId) ? 'View League →' : 'Generate Schedule →' ?>
          </a>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
