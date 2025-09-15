<?php
// Topbar semplice: logout fisso e link a index
if (session_status() === PHP_SESSION_NONE) session_start();
$user_logged = isset($_SESSION['user_email']);

$baseUrl = defined('BASE_URL') ? BASE_URL : '/public/';
?>
<style>
  .bd-topbar { position: fixed; top: 8px; right: 12px; z-index:9999; }
  .bd-topbar a, .bd-topbar form { display:inline-block; margin-left:6px; }
  .bd-topbar button { background:#e74c3c; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; }
  .bd-topbar .home-btn { background:#3498db; }
</style>
<div class="bd-topbar">
  <a href="<?= htmlspecialchars($baseUrl . 'index.php') ?>" class="home-btn"><button class="home-btn">Home</button></a>
  <?php if($user_logged): ?>
  <form method="post" action="<?= htmlspecialchars($baseUrl . 'logout.php') ?>" style="display:inline;">
      <button type="submit">Logout</button>
    </form>
  <?php else: ?>
  <a href="<?= htmlspecialchars($baseUrl . 'login.php') ?>"><button>Login</button></a>
  <?php endif; ?>
</div>
