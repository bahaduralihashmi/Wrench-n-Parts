<?php require_once __DIR__ . '/footer.php'; ?>
<?php if ($logged_in): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('chatbot-toggle');
    if (toggle) toggle.style.display = 'none';
});
</script>
<?php endif; ?>