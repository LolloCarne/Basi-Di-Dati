<?php
require_once '../includes/functions.php';
if(!is_logged_in()) { header('Location: login.php'); exit; }
$ruolo = $_SESSION['user_ruolo'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Home Bostarter</title>
<style>nav a{margin-right:1rem;} body{font-family:sans-serif;}</style>
</head>
<body>
<h1>Bostarter</h1>
<p>Benvenuto <?= htmlspecialchars($_SESSION['user_nickname'] ?? $_SESSION['user_email']) ?> (ruolo: <?= htmlspecialchars($ruolo) ?>)</p>
<nav>
	<a href="projects/search-view.php">Progetti</a>
	<a href="skill.php">Le mie Skill</a>
	<a href="stats.php">Statistiche</a>
	<?php if($ruolo==='admin' && !empty($_SESSION['is_admin_verified'])): ?>
		<a href="admin_skill.php">Competenze</a>
	<?php endif; ?>
	<a href="logout.php">Logout</a>
</nav>
<?php if($ruolo==='creator' || $ruolo==='admin'): ?>
	<p><a href="projects/create-view.php">Crea nuovo progetto</a></p>
<?php endif; ?>
<p>Usa il menu per navigare tra le sezioni.</p>
</body>
</html>