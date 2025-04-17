<?php
// Connessione al database (personalizza con i tuoi parametri)
$host = "localhost";
$user = "root";
$password = "";
$database = "nome_del_tuo_database";

$conn = new mysqli($host, $user, $password, $database);

// Controlla la connessione
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Controlla se il form è stato inviato
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $creatore_email = $_POST['creatore_email'];
    $descrizione = $_POST['descrizione'];
    $budget = $_POST['budget'];
    $data_limite = $_POST['data_limite'];
    $stato = $_POST['stato'];
    $tipo_progetto = $_POST['tipo_progetto'];

    // Prepara la chiamata alla procedura
    $stmt = $conn->prepare("CALL InserisciProgetto(?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdcss", $nome, $creatore_email, $descrizione, $budget, $data_limite, $stato, $tipo_progetto);

    // Esegui la procedura
    if ($stmt->execute()) {
        echo "<p>Progetto inserito correttamente!</p>";
    } else {
        echo "<p>Errore nell'inserimento: " . $stmt->error . "</p>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inserisci Nuovo Progetto</title>
</head>
<body>

<h2>Inserisci Nuovo Progetto</h2>
<form method="POST">
    <label for="nome">Nome Progetto</label><br>
    <input type="text" id="nome" name="nome" required><br><br>

    <label for="creatore_email">Email Creatore</label><br>
    <input type="email" id="creatore_email" name="creatore_email" required><br><br>

    <label for="descrizione">Descrizione</label><br>
    <textarea id="descrizione" name="descrizione" rows="4" required></textarea><br><br>

    <label for="budget">Budget (€)</label><br>
    <input type="number" step="0.01" id="budget" name="budget" required><br><br>

    <label for="data_limite">Data Limite</label><br>
    <input type="date" id="data_limite" name="data_limite" required><br><br>

    <label for="stato">Stato</label><br>
    <select id="stato" name="stato" required>
        <option value="aperto">Aperto</option>
        <option value="chiuso">Chiuso</option>
    </select><br><br>

    <label for="tipo_progetto">Tipo Progetto</label><br>
    <select id="tipo_progetto" name="tipo_progetto" required>
        <option value="hardware">Hardware</option>
        <option value="software">Software</option>
    </select><br><br>

    <button type="submit">Inserisci Progetto</button>
</form>

</body>
</html>
