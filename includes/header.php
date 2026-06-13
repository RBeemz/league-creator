<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$flash = getFlash();
$appName = APP_NAME ?? 'LeagueForge';
$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? $appName) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            colors: {
                pitch: {
                    50:  '#f0fdf4',
                    100: '#dcfce7',
                    400: '#4ade80',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                    900: '#14532d',
                },
                slate: {
                    850: '#1a2030',
                    950: '#0d1117',
                }
            },
            fontFamily: {
                display: ['Georgia', 'serif'],
                mono: ['ui-monospace', 'monospace'],
            }
        }
    }
}
</script>
<style>
  body { background-color: #0d1117; }
  .tab-active { border-bottom: 3px solid #22c55e; color: #22c55e; }
  .tab-inactive { border-bottom: 3px solid transparent; color: #94a3b8; }
  .tab-inactive:hover { color: #e2e8f0; border-bottom-color: #334155; }
  .card { background: #161b22; border: 1px solid #21262d; border-radius: 0.75rem; }
  .input-field {
    background: #0d1117; border: 1px solid #30363d; color: #e2e8f0;
    border-radius: 0.5rem; padding: .5rem .75rem; width: 100%;
    transition: border-color .15s;
  }
  .input-field:focus { outline: none; border-color: #22c55e; box-shadow: 0 0 0 2px rgba(34,197,94,.15); }
  .btn-primary {
    background: #16a34a; color: #fff; padding: .5rem 1.25rem;
    border-radius: .5rem; font-weight: 600; transition: background .15s;
    display: inline-block; cursor: pointer;
  }
  .btn-primary:hover { background: #15803d; }
  .btn-secondary {
    background: #21262d; color: #e2e8f0; padding: .5rem 1.25rem;
    border-radius: .5rem; font-weight: 500; transition: background .15s;
    display: inline-block; cursor: pointer; border: 1px solid #30363d;
  }
  .btn-secondary:hover { background: #30363d; }
  .btn-danger {
    background: #991b1b; color: #fecaca; padding: .5rem 1.25rem;
    border-radius: .5rem; font-weight: 500; transition: background .15s;
    display: inline-block; cursor: pointer;
  }
  .btn-danger:hover { background: #7f1d1d; }
  .badge-scheduled { background: #1e3a5f; color: #93c5fd; }
  .badge-played     { background: #14532d; color: #86efac; }
  .badge-postponed  { background: #374151; color: #9ca3af; }
  select.input-field option { background: #161b22; }
</style>
</head>
<body class="text-slate-200 min-h-screen">

<!-- Navbar -->
<nav class="bg-[#161b22] border-b border-[#21262d] sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
    <a href="index.php" class="flex items-center gap-2 text-white font-display text-xl font-bold tracking-tight">
      <span class="text-2xl">⚽</span>
      <span class="text-pitch-500"><?= $appName ?></span>
    </a>
    <div class="flex items-center gap-2 text-sm text-slate-400">
      <a href="index.php" class="hover:text-white transition-colors px-3 py-1 rounded <?= $currentFile==='index.php'?'text-white bg-[#21262d]':'' ?>">Leagues</a>
      <a href="create_league.php" class="btn-primary text-sm py-1.5 px-4">+ New League</a>
    </div>
  </div>
</nav>

<!-- Flash message -->
<?php if ($flash): ?>
<div class="max-w-7xl mx-auto px-4 mt-4" id="flashMsg">
  <?php if ($flash['type'] === 'success'): ?>
    <div class="bg-pitch-900 border border-pitch-700 text-pitch-400 px-4 py-3 rounded-lg flex items-center justify-between">
      <span>✅ <?= htmlspecialchars($flash['message']) ?></span>
      <button onclick="this.parentElement.remove()" class="text-pitch-600 hover:text-pitch-400 ml-4">✕</button>
    </div>
  <?php else: ?>
    <div class="bg-red-950 border border-red-800 text-red-400 px-4 py-3 rounded-lg flex items-center justify-between">
      <span>❌ <?= htmlspecialchars($flash['message']) ?></span>
      <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-400 ml-4">✕</button>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<main class="max-w-7xl mx-auto px-4 py-6">
