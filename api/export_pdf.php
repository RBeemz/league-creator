<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';

$leagueId = (int)($_GET['league_id'] ?? 0);
$league = getLeague($leagueId);
if (!$league) { header('Location: index.php'); exit; }

$standings  = getStandings($leagueId);
$topScorers = getTopScorers($leagueId);
$pageTitle  = 'Export — ' . htmlspecialchars($league['name']);

// Try mPDF if available
$useMpdf = class_exists('\Mpdf\Mpdf');

if ($useMpdf && isset($_GET['download'])) {
    require_once 'vendor/autoload.php';
    $mpdf = new \Mpdf\Mpdf(['margin_top'=>15,'margin_bottom'=>15,'margin_left'=>15,'margin_right'=>15]);

    ob_start();
    include __DIR__ . '/pdf_template.php';
    $html = ob_get_clean();

    $mpdf->WriteHTML($html);
    $mpdf->Output($league['name'] . '_standings.pdf', 'D');
    exit;
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-4xl mx-auto">
  <div class="mb-6 flex items-center justify-between flex-wrap gap-3">
    <div>
      <a href="league.php?id=<?= $leagueId ?>&tab=standings" class="text-slate-400 hover:text-white text-sm">← Back to League</a>
      <h1 class="font-display text-2xl font-bold text-white mt-2">Export League Report</h1>
      <p class="text-slate-400 text-sm"><?= htmlspecialchars($league['name']) ?></p>
    </div>
    <div class="flex gap-2">
      <?php if ($useMpdf): ?>
        <a href="?league_id=<?= $leagueId ?>&download=1" class="btn-primary">⬇ Download PDF</a>
      <?php endif; ?>
      <button onclick="window.print()" class="btn-secondary">🖨 Print</button>
    </div>
  </div>

  <?php if (!$useMpdf): ?>
  <div class="bg-yellow-950 border border-yellow-800 text-yellow-300 rounded-lg p-4 text-sm mb-5">
    💡 <strong>Tip:</strong> mPDF is not installed. You can still print this page as a PDF using your browser's print function (Ctrl/Cmd+P → Save as PDF). To enable direct PDF download, install mPDF via Composer: <code class="bg-black/30 px-1 rounded">composer require mpdf/mpdf</code>
  </div>
  <?php endif; ?>

  <!-- Print-friendly content -->
  <div id="printArea" class="card p-6 print:shadow-none print:border-0">
    <div class="text-center mb-8 pb-6 border-b border-[#21262d]">
      <h1 class="font-display text-3xl font-bold text-white"><?= htmlspecialchars($league['name']) ?></h1>
      <p class="text-slate-400 mt-1">League Report &mdash; Generated <?= date('d F Y') ?></p>
    </div>

    <!-- Standings -->
    <div class="mb-8">
      <h2 class="font-semibold text-white text-lg mb-3">📊 Final Standings</h2>
      <?php if (empty($standings)): ?>
        <p class="text-slate-500 text-sm">No standings data yet.</p>
      <?php else: ?>
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-[#0d1117]">
              <th class="text-left px-3 py-2 text-slate-400 border border-[#21262d]">#</th>
              <th class="text-left px-3 py-2 text-slate-400 border border-[#21262d]">Team</th>
              <?php foreach (['MP','W','D','L','GF','GA','GD','Pts'] as $col): ?>
                <th class="px-3 py-2 text-slate-400 border border-[#21262d] text-center"><?= $col ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($standings as $i => $s): ?>
            <tr class="<?= $i%2===0?'bg-[#161b22]':'bg-[#0d1117]' ?> border-b border-[#21262d]">
              <td class="px-3 py-2 text-slate-500 border border-[#21262d]"><?= $i+1 ?></td>
              <td class="px-3 py-2 font-medium text-white border border-[#21262d]"><?= htmlspecialchars($s['name']) ?></td>
              <?php foreach (['MP','W','D','L','GF','GA','GD','Pts'] as $col): ?>
                <td class="px-3 py-2 text-center border border-[#21262d] <?= $col==='Pts'?'font-bold text-pitch-400':'text-slate-300' ?>">
                  <?= $col==='GD' && $s['GD']>0 ? '+'.$s[$col] : $s[$col] ?>
                </td>
              <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Top Scorers -->
    <div>
      <h2 class="font-semibold text-white text-lg mb-3">⚽ Top Scorers</h2>
      <?php if (empty($topScorers)): ?>
        <p class="text-slate-500 text-sm">No goal data yet.</p>
      <?php else: ?>
        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="bg-[#0d1117]">
              <th class="text-left px-3 py-2 text-slate-400 border border-[#21262d]">#</th>
              <th class="text-left px-3 py-2 text-slate-400 border border-[#21262d]">Player</th>
              <th class="text-left px-3 py-2 text-slate-400 border border-[#21262d]">Team</th>
              <th class="px-3 py-2 text-slate-400 border border-[#21262d] text-center">Goals</th>
              <th class="px-3 py-2 text-slate-400 border border-[#21262d] text-center">Assists</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topScorers as $i => $s): ?>
            <tr class="<?= $i%2===0?'bg-[#161b22]':'bg-[#0d1117]' ?>">
              <td class="px-3 py-2 text-slate-500 border border-[#21262d]"><?= $i+1 ?></td>
              <td class="px-3 py-2 font-medium text-white border border-[#21262d]"><?= htmlspecialchars($s['player_name']) ?></td>
              <td class="px-3 py-2 text-slate-400 border border-[#21262d]"><?= htmlspecialchars($s['team_name']) ?></td>
              <td class="px-3 py-2 text-center font-bold text-pitch-400 border border-[#21262d]"><?= $s['goals'] ?></td>
              <td class="px-3 py-2 text-center text-slate-300 border border-[#21262d]"><?= $s['assists'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <div class="mt-8 pt-4 border-t border-[#21262d] text-center text-slate-500 text-xs">
      Generated by LeagueForge &mdash; <?= date('d F Y, H:i') ?>
    </div>
  </div>
</div>

<style>
@media print {
  nav, footer, .btn-primary, .btn-secondary, #flashMsg { display: none !important; }
  body { background: white !important; color: black !important; }
  #printArea { color: black !important; background: white !important; border: none !important; }
  #printArea * { color: black !important; background: transparent !important; border-color: #ccc !important; }
  table { border-collapse: collapse !important; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
