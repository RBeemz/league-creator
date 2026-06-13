<?php
require_once __DIR__ . '/../includes/functions.php';
require_once 'bootstrap.php';
$pageTitle = 'Create League — LeagueForge';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name   = trim($_POST['name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $format = $_POST['format'] ?? '2x';
    $rounds = ($format === 'custom') ? (int)($_POST['rounds'] ?? 1) : null;

    if (!$name) {
        setFlash('error', 'League name is required.');
        header('Location: create_league.php'); exit;
    }

    $logo = null;
    if (!empty($_FILES['logo']['name'])) {
        $logo = handleImageUpload($_FILES['logo'], 'league');
        if (!$logo) {
            setFlash('error', 'Invalid logo. Use JPG/PNG under 2MB.');
            header('Location: create_league.php'); exit;
        }
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO leagues (name, description, format, rounds, logo_url) VALUES (?,?,?,?,?)");
    $stmt->execute([$name, $desc, $format, $rounds, $logo]);
    $id = $db->lastInsertId();

    setFlash('success', "League '{$name}' created! Now add your teams.");
    header("Location: teams.php?league_id={$id}"); exit;
}
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="max-w-2xl mx-auto">
  <div class="mb-6">
    <a href="index.php" class="text-slate-400 hover:text-white text-sm">← Back to Leagues</a>
    <h1 class="font-display text-3xl font-bold text-white mt-2">Create a New League</h1>
  </div>

  <div class="card p-6">
    <form method="post" enctype="multipart/form-data" class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">League Name *</label>
        <input type="text" name="name" class="input-field" placeholder="e.g. Premier League Season 2025" required>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
        <textarea name="description" class="input-field" rows="3" placeholder="A brief description of this competition..."></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Format</label>
        <select name="format" id="formatSelect" class="input-field">
          <option value="1x">Round Robin — Single (each team plays once)</option>
          <option value="2x" selected>Round Robin — Double (home & away)</option>
          <option value="custom">Custom Rounds</option>
        </select>
      </div>
      <div id="customRoundsRow" class="hidden">
        <label class="block text-sm font-medium text-slate-300 mb-1.5">Number of Rounds</label>
        <input type="number" name="rounds" class="input-field" min="1" max="50" value="3">
        <p class="text-slate-500 text-xs mt-1">The schedule generator will produce this many rounds of matches.</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-300 mb-1.5">League Logo <span class="text-slate-500">(optional, JPG/PNG, max 2MB)</span></label>
        <input type="file" name="logo" accept="image/jpeg,image/png" class="input-field py-2 cursor-pointer">
      </div>
      <div class="flex gap-3 pt-2">
        <button type="submit" class="btn-primary px-8">Create League & Add Teams →</button>
        <a href="index.php" class="btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('formatSelect').addEventListener('change', function() {
  document.getElementById('customRoundsRow').classList.toggle('hidden', this.value !== 'custom');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
