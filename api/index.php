<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';
$pageTitle = 'LeagueForge — All Leagues';

$db = getDB();
$stmt = $db->query("
    SELECT l.*, COUNT(DISTINCT t.id) AS team_count
    FROM leagues l
    LEFT JOIN teams t ON t.league_id = l.id
    GROUP BY l.id
    ORDER BY l.created_at DESC
");
$leagues = $stmt->fetchAll();
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="mb-8">
  <h1 class="font-display text-3xl font-bold text-white mb-1">Your Leagues</h1>
  <p class="text-slate-400">Manage custom football competitions</p>
</div>

<?php if (empty($leagues)): ?>
<div class="card text-center py-20">
  <div class="text-6xl mb-4">🏆</div>
  <h2 class="text-xl font-semibold text-white mb-2">No leagues yet</h2>
  <p class="text-slate-400 mb-6">Create your first competition to get started.</p>
  <a href="create_league.php" class="btn-primary">Create a League</a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
  <?php foreach ($leagues as $league): ?>
  <?php $round = getCurrentRound($league['id']); ?>
  <div class="card hover:border-pitch-700 transition-colors">
    <div class="p-5">
      <div class="flex items-start gap-3 mb-4">
        <?php if ($league['logo_url']): ?>
          <img src="<?= htmlspecialchars($league['logo_url']) ?>" class="w-12 h-12 rounded-full object-cover border-2 border-[#30363d]" alt="">
        <?php else: ?>
          <div class="w-12 h-12 rounded-full bg-pitch-900 border-2 border-pitch-800 flex items-center justify-center text-xl">🏟️</div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
          <h2 class="font-semibold text-white text-lg leading-tight truncate"><?= htmlspecialchars($league['name']) ?></h2>
          <?php if ($league['description']): ?>
            <p class="text-slate-400 text-sm mt-0.5 line-clamp-2"><?= htmlspecialchars($league['description']) ?></p>
          <?php endif; ?>
        </div>
      </div>
      <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="bg-[#0d1117] rounded-lg p-3 text-center">
          <div class="text-pitch-400 text-xl font-bold"><?= $league['team_count'] ?></div>
          <div class="text-slate-500 text-xs mt-0.5">Teams</div>
        </div>
        <div class="bg-[#0d1117] rounded-lg p-3 text-center">
          <div class="text-pitch-400 text-xl font-bold"><?= $round ?: '—' ?></div>
          <div class="text-slate-500 text-xs mt-0.5">Round</div>
        </div>
        <div class="bg-[#0d1117] rounded-lg p-3 text-center">
          <div class="text-pitch-400 text-xl font-bold uppercase text-sm"><?= $league['format'] ?></div>
          <div class="text-slate-500 text-xs mt-0.5">Format</div>
        </div>
      </div>
      <div class="flex gap-2">
        <a href="league.php?id=<?= $league['id'] ?>" class="btn-primary flex-1 text-center text-sm py-2">Open League</a>
        <a href="teams.php?league_id=<?= $league['id'] ?>" class="btn-secondary text-center text-sm py-2 px-3">Teams</a>
        <form method="post" action="delete_league.php" class="inline">
          <input type="hidden" name="id" value="<?= $league['id'] ?>">
          <button type="submit" class="btn-danger text-sm py-2 px-3"
            data-confirm="Delete '<?= htmlspecialchars($league['name'], ENT_QUOTES) ?>'? This cannot be undone.">🗑</button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
