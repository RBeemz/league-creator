</main>
<footer class="border-t border-[#21262d] mt-12 py-6 text-center text-slate-500 text-sm">
  <p>⚽ <?= APP_NAME ?? 'LeagueForge' ?> &mdash; Custom League Manager</p>
</footer>
<script>
// Auto-dismiss flash after 5s
setTimeout(() => {
  const el = document.getElementById('flashMsg');
  if (el) el.style.transition = 'opacity .5s', el.style.opacity = '0', setTimeout(() => el.remove(), 500);
}, 5000);

// Confirm delete dialogs
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', function(e) {
    if (!confirm(this.dataset.confirm)) e.preventDefault();
  });
});
</script>
</body>
</html>
