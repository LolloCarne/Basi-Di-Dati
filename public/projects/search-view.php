<?php
require_once '../../includes/functions.php';
require_login();

// La lista progetti è visibile a tutti gli utenti autenticati. Il bottone 'Crea Nuovo Progetto'
// è invece mostrato solo a creator/admin (vedi più sotto).

// Connessione
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'bostarter';
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die('Connessione fallita: ' . $conn->connect_error);
}

// Inizializza lista risultati vuota
$results = [];

// Esegui ricerca solo se il form è stato inviato
if (!empty($_GET)) {
    // Parametri di ricerca (filtro vuoto diventa null)
    $nome          = $_GET['nome']         ?? null;
    $descrizione   = $_GET['descrizione']  ?? null;
    $budget        = $_GET['budget']       !== '' ? $_GET['budget'] : null;
    $data_limite   = $_GET['data_limite']  !== '' ? $_GET['data_limite'] : null;
    $stato         = $_GET['stato']        !== '' ? $_GET['stato'] : null;
    $tipo_progetto = $_GET['tipo_progetto'] !== '' ? $_GET['tipo_progetto'] : null;

    // Prepara e chiama la stored procedure
    $stmt = $conn->prepare('CALL ricercaProgetti(?, ?, ?, ?, ?, ?)');
    if (! $stmt) {
        die('Errore prepare: ' . $conn->error);
    }
    $stmt->bind_param(
        'ssdsss',
        $nome,
        $descrizione,
        $budget,
        $data_limite,
        $stato,
        $tipo_progetto
    );
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
    // Consumare eventuali resultset rimanenti
    while ($conn->more_results() && $conn->next_result()) {
        $conn->use_result();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Ricerca Progetti</title>
    <style> li { margin-bottom: 1em; } </style>
</head>
<body>
<?php include_once __DIR__ . '/../../includes/topbar.php'; ?>
<h2>Ricerca Progetti</h2>
<?php if(!empty($_SESSION['flash_upload_error'])): ?>
    <div style="background:#fee;border:1px solid #f99;padding:8px;margin-bottom:1em;">
        <strong>Attenzione:</strong> <?= htmlspecialchars($_SESSION['flash_upload_error']) ?>
    </div>
    <?php unset($_SESSION['flash_upload_error']); ?>
<?php endif; ?>
<?php if (check_permission(['creator','admin'], true)): ?>
    <form action="create-view.php" method="get" style="margin-bottom:1em;">
        <button type="submit">Crea Nuovo Progetto</button>
    </form>
<?php endif; ?>
<form method="GET">
    <label for="nome">Nome:</label>
    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($_GET['nome'] ?? '') ?>"><br>

    <label for="descrizione">Descrizione:</label>
    <input type="text" id="descrizione" name="descrizione" value="<?= htmlspecialchars($_GET['descrizione'] ?? '') ?>"><br>

    <label for="budget">Budget (€):</label>
    <input type="number" step="0.01" id="budget" name="budget" value="<?= htmlspecialchars($_GET['budget'] ?? '') ?>"><br>

    <label for="data_limite">Data Limite:</label>
    <input type="date" id="data_limite" name="data_limite" value="<?= htmlspecialchars($_GET['data_limite'] ?? '') ?>"><br>

    <label for="stato">Stato:</label>
    <select id="stato" name="stato">
        <option value="">Tutti</option>
        <option value="aperto" <?= (($_GET['stato'] ?? '')==='aperto')?'selected':'' ?>>Aperto</option>
        <option value="chiuso" <?= (($_GET['stato'] ?? '')==='chiuso')?'selected':'' ?>>Chiuso</option>
    </select><br>

    <label for="tipo_progetto">Tipo:</label>
    <select id="tipo_progetto" name="tipo_progetto">
        <option value="">Tutti</option>
        <option value="hardware" <?= (($_GET['tipo_progetto'] ?? '')==='hardware')?'selected':'' ?>>Hardware</option>
        <option value="software" <?= (($_GET['tipo_progetto'] ?? '')==='software')?'selected':'' ?>>Software</option>
    </select><br><br>

    <button type="submit">Cerca</button>
</form>

<h3>Risultati:</h3>
<?php if (empty($results)): ?>
    <p>Nessun progetto da mostrare.</p>
<?php else: ?>
    <ul>
    <?php foreach ($results as $row): ?>
        <li>
            <strong><a href="detail.php?nome=<?= urlencode($row['nome']) ?>"><?= htmlspecialchars($row['nome']) ?></a></strong><br>
            Creatore: <?= htmlspecialchars($row['creatore_email']) ?><br>
            Descrizione: <?= nl2br(htmlspecialchars($row['descrizione'])) ?><br>
            Budget: €<?= number_format($row['budget'], 2, ',', '.') ?><br>
            Scadenza: <?= htmlspecialchars($row['data_limite']) ?><br>
            Stato: <?= htmlspecialchars($row['stato']) ?><br>
            Tipo: <?= htmlspecialchars($row['tipo_progetto']) ?>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>