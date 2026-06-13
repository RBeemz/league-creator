<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
$league = getLeague($id);
if (!$league) { header('Location: index.php'); exit; }

$pageTitle = htmlspecialchars($league['name']) . ' — LeagueForge';
$tab = $_GET['tab'] ?? 'schedule';

$db = getDB();

// Tabs data
$teams = $db->prepare("SELECT * FROM teams WHERE league_id = ? ORDER BY name");
$teams->execute([$id]);
$teams = $teams->fetchAll();

$totalTeams  = count($teams);
$hasMatches  = hasMatches($id);
$standings   = getStandings($id);
$topScorers  = getTopScorers($id);
$currentRound = getCurrentRound($id);

// Schedule grouped by round
$scheduleByRound = [];
if ($tab === 'schedule' || !$tab) {
    $stmt = $db->prepare("
        SELECT m.*, ht.name AS home_name, ht.logo_url AS home_logo,
               at.name AS away_name, at.logo_url AS away_logo,
               r.home_score, r.away_score
        FROM matches m
        JOIN teams ht ON ht.id = m.home_team_id
        JOIN teams at ON at.id = m.away_team_id
        LEFT JOIN match_results r ON r.match_id = m.id
        WHERE m.league_id = ?
        ORDER BY m.round ASC, m.match_date ASC, m.id ASC
    ");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $match) {
        $scheduleByRound[$match['round']][] = $match;
    }
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<!-- League header -->
<div class="flex flex-col sm:flex-row sm:items-center gap-4 mb-6">
  <div class="flex items-center gap-4">
    <?php if ($league['logo_url']): ?>
      <img src="<?= htmlspecialchars($league['logo_url']) ?>" class="w-16 h-16 rounded-full object-cover border-2 border-pitch-700" alt="">
    <?php else: ?>
      <div class="w-16 h-16 rounded-full bg-pitch-900 border-2 border-pitch-800 flex items-center justify-center text-3xl">🏆</div>
    <?php endif; ?>
    <div>
      <p class="text-slate-500 text-sm"><a href="index.php" class="hover:text-white">Leagues</a> /</p>
      <h1 class="font-display text-2xl sm:text-3xl font-bold text-white"><?= htmlspecialchars($league['name']) ?></h1>
      <p class="text-slate-400 text-sm">
        <?= $totalTeams ?> teams &middot;
        Format: <?= strtoupper($league['format']) ?>
        <?php if ($currentRound): ?>&middot; Round <?= $currentRound ?> played<?php endif; ?>
      </p>
    </div>
  </div>
  <div class="sm:ml-auto flex gap-2 flex-wrap">
    <a href="teams.php?league_id=<?= $id ?>" class="btn-secondary text-sm">Manage Teams</a>
    <?php if (!$hasMatches && $totalTeams >= 2): ?>
      <a href="generate_schedule.php?league_id=<?= $id ?>" class="btn-primary text-sm">Generate Schedule ⚡</a>
    <?php elseif ($hasMatches): ?>
      <a href="generate_schedule.php?league_id=<?= $id ?>" class="btn-secondary text-sm text-yellow-400 border-yellow-800">↺ Regenerate</a>
      <a href="export_pdf.php?league_id=<?= $id ?>" class="btn-secondary text-sm" target="_blank">📄 Export PDF</a>
    <?php endif; ?>
  </div>
</div>

<!-- Tabs -->
<div class="flex border-b border-[#21262d] mb-6 gap-1">
  <?php foreach (['schedule'=>'📅 Schedule','standings'=>'📊 Standings','topscorers'=>'⚽ Top Scorers'] as $t=>$label): ?>
    <a href="?id=<?= $id ?>&tab=<?= $t ?>"
       class="px-4 py-3 font-medium text-sm transition-colors <?= $tab===$t ? 'tab-active' : 'tab-inactive' ?>">
      <?= $label ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- SCHEDULE TAB -->
<?php if ($tab === 'schedule'): ?>
<?php if (empty($scheduleByRound)): ?>
  <div class="card text-center py-16">
    <div class="text-5xl mb-4">📅</div>
    <h2 class="text-lg font-semibold text-white mb-2">No schedule yet</h2>
    <p class="text-slate-400 mb-5">
      <?php if ($totalTeams < 2): ?>Add at least 2 teams, then generate a schedule.
      <?php else: ?>Click "Generate Schedule" to create the fixture list.<?php endif; ?>
    </p>
    <?php if ($totalTeams >= 2): ?>
      <a href="generate_schedule.php?league_id=<?= $id ?>" class="btn-primary">Generate Schedule ⚡</a>
    <?php else: ?>
      <a href="teams.php?league_id=<?= $id ?>" class="btn-primary">Add Teams</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <?php foreach ($scheduleByRound as $round => $matches): ?>
  <div class="mb-6">
    <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wider mb-3">Round <?= $round ?></h3>
    <div class="space-y-2">
      <?php foreach ($matches as $m): ?>
      <div class="card px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
        <!-- Home -->
        <div class="flex-1 flex items-center gap-2 justify-end sm:justify-end">
          <span class="font-semibold text-white"><?= htmlspecialchars($m['home_name']) ?></span>
          <?php if ($m['home_logo']): ?>
            <img src="<?= htmlspecialchars($m['home_logo']) ?>" class="w-7 h-7 rounded-full object-cover" alt="">
          <?php else: ?>
            <div class="w-7 h-7 rounded-full bg-[#21262d] flex items-center justify-center text-xs">⚽</div>
          <?php endif; ?>
        </div>
        <!-- Score / VS -->
        <div class="flex items-center justify-center gap-2 min-w-[90px]">
          <?php if ($m['status'] === 'played'): ?>
            <span class="bg-[#21262d] text-white font-bold text-lg px-4 py-1 rounded-lg"><?= $m['home_score'] ?> – <?= $m['away_score'] ?></span>
          <?php else: ?>
            <span class="text-slate-500 font-medium">vs</span>
          <?php endif; ?>
        </div>
        <!-- Away -->
        <div class="flex-1 flex items-center gap-2">
          <?php if ($m['away_logo']): ?>
            <img src="<?= htmlspecialchars($m['away_logo']) ?>" class="w-7 h-7 rounded-full object-cover" alt="">
          <?php else: ?>
            <div class="w-7 h-7 rounded-full bg-[#21262d] flex items-center justify-center text-xs">⚽</div>
          <?php endif; ?>
          <span class="font-semibold text-white"><?= htmlspecialchars($m['away_name']) ?></span>
        </div>
        <!-- Date & actions -->
        <div class="flex items-center gap-2 sm:ml-4">
          <span class="text-slate-500 text-xs"><?= $m['match_date'] ? date('d M Y', strtotime($m['match_date'])) : 'TBD' ?></span>
          <span class="px-2 py-0.5 rounded text-xs font-medium badge-<?= $m['status'] ?>"><?= ucfirst($m['status']) ?></span>
          <?php if ($m['status'] !== 'played'): ?>
            <a href="input_result.php?match_id=<?= $m['id'] ?>" class="btn-primary text-xs py-1 px-3">Result</a>
          <?php else: ?>
            <a href="input_result.php?match_id=<?= $m['id'] ?>" class="btn-secondary text-xs py-1 px-3">Edit</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<!-- STANDINGS TAB -->
<?php elseif ($tab === 'standings'): ?>
<?php if (empty($standings)): ?>
  <div class="card text-center py-16">
    <div class="text-5xl mb-4">📊</div>
    <p class="text-slate-400">Standings will appear once matches are played.</p>
  </div>
<?php else: ?>
  <div class="card overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b border-[#21262d]">
          <th class="text-left px-4 py-3 text-slate-400 font-medium w-8">#</th>
          <th class="text-left px-4 py-3 text-slate-400 font-medium">Team</th>
          <?php foreach (['MP','W','D','L','GF','GA','GD','Pts'] as $col): ?>
            <th class="px-3 py-3 text-slate-400 font-medium text-center <?= $col==='Pts'?'text-pitch-400':'' ?>"><?= $col ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($standings as $i => $s): ?>
        <tr class="border-b border-[#21262d] hover:bg-[#1a2030] transition-colors <?= $i===0?'text-pitch-300':'' ?>">
          <td class="px-4 py-3 text-slate-500"><?= $i+1 ?></td>
          <td class="px-4 py-3">
            <div class="flex items-center gap-2">
              <?php if ($s['logo']): ?>
                <img src="<?= htmlspecialchars($s['logo']) ?>" class="w-6 h-6 rounded-full object-cover" alt="">
              <?php else: ?>
                <div class="w-6 h-6 rounded-full bg-[#21262d] flex items-center justify-center text-xs">⚽</div>
              <?php endif; ?>
              <span class="font-medium text-white"><?= htmlspecialchars($s['name']) ?></span>
            </div>
          </td>
          <?php foreach (['MP','W','D','L','GF','GA','GD','Pts'] as $col): ?>
            <td class="px-3 py-3 text-center <?= $col==='Pts'?'font-bold text-pitch-400':($col==='GD'?($s['GD']>0?'text-pitch-400':($s['GD']<0?'text-red-400':'text-slate-400')):'text-slate-300') ?>">
              <?= $col==='GD' && $s['GD']>0 ? '+'.$s[$col] : $s[$col] ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<!-- TOP SCORERS TAB -->
<?php elseif ($tab === 'topscorers'): ?>
<?php if (empty($topScorers)): ?>
  <div class="card text-center py-16">
    <div class="text-5xl mb-4">⚽</div>
    <p class="text-slate-400">Top scorers will appear once player stats are entered.</p>
  </div>
<?php else: ?>
  <div class="card overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr class="border-b border-[#21262d]">
          <th class="text-left px-4 py-3 text-slate-400 font-medium w-8">#</th>
          <th class="text-left px-4 py-3 text-slate-400 font-medium">Player</th>
          <th class="text-left px-4 py-3 text-slate-400 font-medium">Team</th>
          <th class="px-4 py-3 text-slate-400 font-medium text-center">⚽ Goals</th>
          <th class="px-4 py-3 text-slate-400 font-medium text-center">🅰 Assists</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topScorers as $i => $s): ?>
        <tr class="border-b border-[#21262d] hover:bg-[#1a2030] transition-colors">
          <td class="px-4 py-3 text-slate-500"><?= $i+1 ?></td>
          <td class="px-4 py-3 font-medium text-white"><?= htmlspecialchars($s['player_name']) ?></td>
          <td class="px-4 py-3 text-slate-400"><?= htmlspecialchars($s['team_name']) ?></td>
          <td class="px-4 py-3 text-center font-bold text-pitch-400"><?= $s['goals'] ?></td>
          <td class="px-4 py-3 text-center text-slate-300"><?= $s['assists'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
