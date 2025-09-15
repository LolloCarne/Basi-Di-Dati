<?php
require_once '../../includes/functions.php';
require_login();

// Parametri di ingresso
$nome_progetto = $_GET['nome'] ?? '';
if ($nome_progetto === '') { http_response_code(400); die('Parametro progetto mancante.'); }

// Connessione DB
$mysqli = db_conn();

// Carica progetto
$stmt = $mysqli->prepare('SELECT nome, creatore_email, descrizione, DATE_FORMAT(data_inserimento, "%Y-%m-%d") AS data_inserimento, budget, DATE_FORMAT(data_limite, "%Y-%m-%d") AS data_limite, stato, tipo_progetto FROM Progetto WHERE nome=?');
$stmt->bind_param('s', $nome_progetto);
$stmt->execute();
$rs = $stmt->get_result();
$progetto = $rs->fetch_assoc();
$stmt->close();
if(!$progetto){ die('Progetto non trovato.'); }

// Totale finanziato finora
$stmt = $mysqli->prepare('SELECT COALESCE(SUM(importo),0) FROM Finanziamento WHERE nome_progetto=?');
$stmt->bind_param('s', $nome_progetto);
$stmt->execute();
$stmt->bind_result($tot_fin);
$stmt->fetch();
$stmt->close();

// Permessi e stato progetto
$is_creator = isset($_SESSION['user_email']) && $_SESSION['user_email'] === $progetto['creatore_email'];
$can_manage = $is_creator && check_permission(['creator','admin'], true);
$project_open = $progetto['stato'] === 'aperto';

$errors = [];
$success = [];

// Inizializza strutture
$reward_list = [];

// Elenco competenze
$all_skills = [];
$stmt = $mysqli->prepare('SELECT competenza FROM Skill ORDER BY competenza');
if ($stmt) {
  $stmt->execute();
  $res = $stmt->get_result();
  while($row = $res->fetch_assoc()) { $all_skills[] = $row['competenza']; }
  $stmt->close();
}

// Gestione POST (azioni)
if($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $user_email = $_SESSION['user_email'] ?? '';

  $needs_manage = in_array($action, [
    'add_reward','edit_reward','add_component','edit_component','add_profile','edit_profile','manage_candidature','reply_comment'
  ], true);

  if($needs_manage && (!$can_manage || !$project_open)) {
    $errors[] = 'Permessi insufficienti o progetto chiuso.';
  } else {
    switch($action){
      case 'add_reward': {
        $descr = trim($_POST['descrizione'] ?? '');
        if ($descr === '') { $errors[] = 'Descrizione reward obbligatoria.'; break; }
        $tentativi = 0; $codice = '';
        do {
          $codice = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/','', $nome_progetto), 0, 4)).'-'.date('YmdHis').'-'.bin2hex(random_bytes(2));
          $chk = $mysqli->prepare('SELECT 1 FROM Reward WHERE codice=? LIMIT 1');
          $chk->bind_param('s', $codice);
          $chk->execute();
          $chk->store_result();
          $exists = $chk->num_rows > 0;
          $chk->close();
          $tentativi++;
          if ($tentativi > 5 && $exists) { $errors[] = 'Impossibile generare codice reward univoco.'; break 2; }
        } while($exists);

        $foto_path = '';
        $upload_dir = __DIR__ . '/../../uploads/rewards/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
        if (isset($_FILES['reward_foto_add']) && $_FILES['reward_foto_add']['error'] !== UPLOAD_ERR_NO_FILE) {
          $f = $_FILES['reward_foto_add'];
          if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Errore upload immagine reward. Codice: '.intval($f['error']);
          } else {
            if (!is_uploaded_file($f['tmp_name'])) { $errors[] = 'File upload non valido.'; }
            else {
              $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
              $safe = preg_replace('/[^A-Za-z0-9._-]/','_', $codice . '_' . time() . '.' . $ext);
              $dest = $upload_dir . $safe;
              if (move_uploaded_file($f['tmp_name'], $dest)) {
                $foto_path = '/uploads/rewards/' . $safe;
              } else {
                $errors[] = 'Impossibile salvare il file immagine sul server. Controllare permessi.';
                error_log('[UPLOAD] move_uploaded_file fallito src='. $f['tmp_name'] .' dest=' . $dest);
              }
            }
          }
        }
        $stmt = $mysqli->prepare('CALL InserisciReward(?,?,?,?,?)');
        if (!$stmt) { $errors[] = 'Prepare reward: '.htmlspecialchars($mysqli->error); break; }
        $stmt->bind_param('sssss', $codice, $descr, $foto_path, $user_email, $nome_progetto);
        if ($stmt->execute()) { $success[] = 'Reward inserita (codice: '.$codice.').'; } else { $errors[] = 'Errore reward: '.htmlspecialchars($stmt->error); }
        $stmt->close(); drain($mysqli); break;
      }
      case 'edit_reward': {
        $codice = trim($_POST['codice'] ?? '');
        $descr = trim($_POST['descrizione'] ?? '');
        if ($codice === '' || $descr === '') { $errors[] = 'Codice e nuova descrizione obbligatori.'; break; }
        $foto_path = '';
        $upload_dir = __DIR__ . '/../../uploads/rewards/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
        if (isset($_FILES['reward_foto_edit']) && $_FILES['reward_foto_edit']['error'] !== UPLOAD_ERR_NO_FILE) {
          $f = $_FILES['reward_foto_edit'];
          if ($f['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Errore upload immagine reward (modifica). Codice: '.intval($f['error']);
          } else {
            if (!is_uploaded_file($f['tmp_name'])) { $errors[] = 'File upload non valido.'; }
            else {
              $ext = pathinfo($f['name'], PATHINFO_EXTENSION);
              $safe = preg_replace('/[^A-Za-z0-9._-]/','_', $codice . '_' . time() . '.' . $ext);
              $dest = $upload_dir . $safe;
              if (move_uploaded_file($f['tmp_name'], $dest)) {
                $foto_path = '/uploads/rewards/' . $safe;
              } else {
                $errors[] = 'Impossibile salvare il file immagine sul server. Controllare permessi.';
                error_log('[UPLOAD] move_uploaded_file fallito src='. $f['tmp_name'] .' dest=' . $dest);
              }
            }
          }
        }
        $stmt = $mysqli->prepare('CALL ModificaReward(?,?,?,?)');
        if (!$stmt) { $errors[] = 'Prepare modifica reward.'; break; }
        $stmt->bind_param('ssss', $codice, $descr, $foto_path, $user_email);
        if ($stmt->execute()) { $success[] = 'Reward modificata.'; } else { $errors[] = 'Errore modifica reward: '.htmlspecialchars($stmt->error); }
        $stmt->close(); drain($mysqli); break;
      }
      case 'add_component': {
        if($progetto['tipo_progetto']!=='hardware'){ $errors[]='Progetto non hardware.'; break; }
        $nome_c = trim($_POST['nome_comp'] ?? '');
        $descr_c = trim($_POST['descr_comp'] ?? '');
        $prezzo = $_POST['prezzo'] ?? '';
        $qta = $_POST['quantita'] ?? '';
        if($nome_c===''||$descr_c===''||$prezzo===''||$qta===''){ $errors[]='Tutti i campi componente obbligatori.'; break; }
        if(!is_numeric($prezzo) || $prezzo<=0){ $errors[]='Prezzo non valido.'; break; }
        if(!ctype_digit($qta) || (int)$qta<0){ $errors[]='Quantità non valida.'; break; }
        $stmt=$mysqli->prepare('CALL InserisciComponente(?,?,?,?,?,?)');
        if(!$stmt){ $errors[]='Prepare componente.'; break; }
        $prezzo_f=(float)$prezzo; $qta_i=(int)$qta;
        $stmt->bind_param('ssdiis',$nome_c,$descr_c,$prezzo_f,$qta_i,$user_email,$nome_progetto);
        if($stmt->execute()){ $success[]='Componente inserita.'; } else { $errors[]='Errore componente: '.htmlspecialchars($stmt->error); }
        $stmt->close(); drain($mysqli); break;
      }
      case 'edit_component': {
        if($progetto['tipo_progetto']!=='hardware'){ $errors[]='Progetto non hardware.'; break; }
        $nome_c = trim($_POST['nome_comp'] ?? '');
        $descr_c = trim($_POST['descr_comp'] ?? '');
        $prezzo = $_POST['prezzo'] ?? '';
        $qta = $_POST['quantita'] ?? '';
        if($nome_c===''||$descr_c===''||$prezzo===''||$qta===''){ $errors[]='Tutti i campi modifica componente obbligatori.'; break; }
        if(!is_numeric($prezzo) || $prezzo<=0){ $errors[]='Prezzo non valido.'; break; }
        if(!ctype_digit($qta) || (int)$qta<0){ $errors[]='Quantità non valida.'; break; }
        $stmt=$mysqli->prepare('CALL ModificaComponente(?,?,?,?,?,?)');
        if(!$stmt){ $errors[]='Prepare modifica componente.'; break; }
        $prezzo_f=(float)$prezzo; $qta_i=(int)$qta;
        $stmt->bind_param('ssdiis',$nome_c,$descr_c,$prezzo_f,$qta_i,$user_email,$nome_progetto);
        if($stmt->execute()){ $success[]='Componente modificata.'; } else { $errors[]='Errore modifica componente: '.htmlspecialchars($stmt->error); }
        $stmt->close(); drain($mysqli); break;
      }
      case 'add_profile': {
        if($progetto['tipo_progetto']!=='software'){ $errors[]='Progetto non software.'; break; }
        $nome_p = trim($_POST['nome_prof'] ?? '');
        $comp = trim($_POST['competenza'] ?? '');
        $liv = $_POST['livello'] ?? '';
        if($nome_p===''||$comp===''||$liv===''){ $errors[]='Tutti i campi profilo obbligatori.'; break; }
        if(!in_array($comp, $all_skills, true)) { $errors[]='Competenza non valida.'; break; }
        if(!ctype_digit($liv) || (int)$liv<0 || (int)$liv>5){ $errors[]='Livello deve essere 0..5.'; break; }
        $lvl=(int)$liv;
        $stmt=$mysqli->prepare('CALL InserisciProfilo(?,?,?,?,?)');
        if(!$stmt){ $errors[]='Prepare profilo.'; break; }
        $stmt->bind_param('ssiss',$nome_p,$comp,$lvl,$user_email,$nome_progetto);
        if($stmt->execute()){ $success[]='Profilo inserito.'; } else { $errors[]='Errore profilo: '.htmlspecialchars($stmt->error); }
        $stmt->close(); drain($mysqli); break;
      }
      case 'edit_profile': {
        if($progetto['tipo_progetto']!=='software'){ $errors[]='Progetto non software.'; break; }
        $nome_p = trim($_POST['nome_prof'] ?? '');
        $comp = trim($_POST['competenza'] ?? '');
        $liv = $_POST['livello'] ?? '';
        if($nome_p===''||$comp===''||$liv===''){ $errors[]='Tutti i campi modifica profilo obbligatori.'; break; }
        if(!in_array($comp, $all_skills, true)) { $errors[]='Competenza non valida.'; break; }
        if(!ctype_digit($liv) || (int)$liv<0 || (int)$liv>5){ $errors[]='Livello deve essere 0..5.'; break; }
        $lvl=(int)$liv;
        $stmt=$mysqli->prepare('CALL ModificaProfilo(?,?,?,?,?)');
        if(!$stmt){ $errors[]='Prepare modifica profilo.'; break; }
        $stmt->bind_param('ssiss',$nome_p,$comp,$lvl,$user_email,$nome_progetto);
        try {
          if($stmt->execute()){
            $success[]='Profilo modificato.';
          } else {
            $errors[]='Errore modifica profilo: '.htmlspecialchars($stmt->error);
          }
        } catch (\mysqli_sql_exception $e) {
          $errors[] = 'Errore modifica profilo: '.htmlspecialchars($e->getMessage());
        } catch (\Throwable $e) {
          $errors[] = 'Errore inatteso durante modifica profilo: '.htmlspecialchars($e->getMessage());
        }
        $stmt->close(); drain($mysqli); break;
      }
      case 'fund_project': {
        if(!$project_open){ $errors[]='Progetto chiuso: non finanziabile.'; break; }
        $importo = $_POST['importo'] ?? '';
        $codice_reward = trim($_POST['codice_reward'] ?? '');
        if(!is_numeric($importo) || $importo<=0){ $errors[]='Importo non valido.'; break; }
        if($codice_reward===''){ $errors[]='Seleziona una reward.'; break; }
        if($user_email === $progetto['creatore_email']) { $errors[] = 'Non puoi finanziare il tuo stesso progetto.'; break; }
        $stmt=$mysqli->prepare('CALL FinanziaProgetto(?,?,?,?)');
        if(!$stmt){ $errors[]='Prepare finanziamento.'; break; }
        $imp=(float)$importo; $stmt->bind_param('ssds',$user_email,$nome_progetto,$imp,$codice_reward);
        if($stmt->execute()){ $success[]='Finanziamento registrato.'; } else {
          $err_msg = $stmt->error; $errno = $stmt->errno;
          if($errno === 1062 || stripos($err_msg,'duplicate')!==false) {
            $errors[] = 'Hai già effettuato un finanziamento per questo progetto in questa data.';
          } else {
            $errors[]='Errore finanziamento: '.htmlspecialchars($err_msg);
          }
        }
        $stmt->close(); drain($mysqli);
        // Aggiorna totale e stato
        $stmt=$mysqli->prepare('SELECT COALESCE(SUM(importo),0) FROM Finanziamento WHERE nome_progetto=?');
        $stmt->bind_param('s',$nome_progetto); $stmt->execute(); $stmt->bind_result($tot_fin); $stmt->fetch(); $stmt->close();
        $stmt=$mysqli->prepare('SELECT stato FROM Progetto WHERE nome=?');
        $stmt->bind_param('s',$nome_progetto); $stmt->execute(); $stmt->bind_result($st); $stmt->fetch(); $stmt->close();
        $progetto['stato']=$st; $project_open=($st==='aperto');
        break;
      }
      case 'apply_profile': {
        if($progetto['tipo_progetto']!=='software'){ $errors[]='Progetto non software.'; break; }
        if(!$project_open){ $errors[]='Progetto chiuso.'; break; }
        if($user_email === $progetto['creatore_email']) { $errors[] = 'Non puoi candidarti al tuo progetto.'; break; }
        $nome_prof_app = trim($_POST['nome_profilo'] ?? '');
        if($nome_prof_app===''){ $errors[]='Profilo mancante.'; break; }
        $stmt=$mysqli->prepare('CALL AggiungiCandidatura(?,?,?)');
        if(!$stmt){ $errors[]='Prepare candidatura: '.htmlspecialchars($mysqli->error); break; }
        $stmt->bind_param('sss',$user_email,$nome_progetto,$nome_prof_app);
        try {
          if($stmt->execute()){
            $success[]='Candidatura inviata.';
          } else {
            $err = $stmt->error ?: $mysqli->error;
            $errors[] = 'Errore candidatura: '.htmlspecialchars($err);
          }
        } catch (\mysqli_sql_exception $e) {
          $errors[] = 'Errore candidatura: '.htmlspecialchars($e->getMessage());
        } catch (\Throwable $e) {
          $errors[] = 'Errore inatteso durante candidatura: '.htmlspecialchars($e->getMessage());
        }
        $stmt->close(); drain($mysqli); break;
      }
      case 'manage_candidature': {
        if(!$can_manage){ $errors[]='Permessi insufficienti.'; break; }
        if(!$project_open){ $errors[]='Progetto chiuso.'; break; }
        $cand_id = $_POST['cand_id'] ?? '';
        $decision = $_POST['decision'] ?? '';
        if(!ctype_digit($cand_id) || !in_array($decision,['accettata','rifiutata'],true)) { $errors[]='Parametri candidatura non validi.'; break; }
        $cid = (int)$cand_id;
        $stmt=$mysqli->prepare('CALL GestisciCandidatura(?,?,?)');
        if(!$stmt){ $errors[]='Prepare gestione candidatura: '.htmlspecialchars($mysqli->error); break; }
        $stmt->bind_param('sis',$user_email,$cid,$decision);
        if($stmt->execute()){ $success[]='Candidatura '.$decision.'.'; } else {
            $err = $stmt->error ?: $mysqli->error;
            $errors[] = 'Errore gestione candidatura: '.htmlspecialchars($err);
        }
        $stmt->close(); drain($mysqli); break;
      }
      case 'add_comment': {
        if(!$project_open){ $errors[]='Progetto chiuso.'; break; }
        $contenuto = trim($_POST['contenuto'] ?? '');
        if($contenuto===''){ $errors[]='Contenuto commento vuoto.'; break; }
        $stmt=$mysqli->prepare('CALL InserisciCommento(?,?,?)');
        if(!$stmt){ $errors[]='Prepare commento: '.htmlspecialchars($mysqli->error); break; }
        $stmt->bind_param('sss',$user_email,$nome_progetto,$contenuto);
        if($stmt->execute()){ $success[]='Commento inserito.'; } else { $errors[]='Errore commento: '.htmlspecialchars($stmt->error); }
        $stmt->close(); drain($mysqli); break;
      }
      case 'reply_comment': {
        if(!$can_manage){ $errors[]='Permessi insufficienti.'; break; }
        if(!$project_open){ $errors[]='Progetto chiuso.'; break; }
        $comment_id = $_POST['comment_id'] ?? '';
        $testo = trim($_POST['testo'] ?? '');
        if(!ctype_digit($comment_id) || $testo===''){ $errors[]='Parametri risposta non validi.'; break; }
        $cid=(int)$comment_id;
        $stmt=$mysqli->prepare('CALL InserisciRisposta(?,?,?)');
        if(!$stmt){ $errors[]='Prepare risposta: '.htmlspecialchars($mysqli->error); break; }
        $stmt->bind_param('sis',$user_email,$cid,$testo);
        if($stmt->execute()){ $success[]='Risposta inserita.'; } else { $errors[]='Errore risposta: '.htmlspecialchars($stmt->error); }
        $stmt->close(); drain($mysqli); break;
      }
      default:
        $errors[]='Azione non riconosciuta.';
    }
  }
}


$rewards = [];
$stmt=$mysqli->prepare('SELECT r.codice, r.descrizione, r.foto FROM Reward r INNER JOIN RewardProgetto rp ON r.codice=rp.codice_reward WHERE rp.id_progetto=? ORDER BY r.codice');
$stmt->bind_param('s',$nome_progetto); $stmt->execute(); $rt=$stmt->get_result(); while($row=$rt->fetch_assoc()) $rewards[]=$row; $stmt->close();
$reward_list = array_map(function($r){ return ['codice'=>$r['codice'], 'descrizione'=>$r['descrizione']]; }, $rewards);

$componenti = [];
if($progetto['tipo_progetto']==='hardware') {
  $stmt=$mysqli->prepare('SELECT c.nome, c.descrizione, c.prezzo, c.quantità FROM Componenti c INNER JOIN ComponentiProgetto cp ON c.nome=cp.nome_componenti WHERE cp.nome_progetto=? ORDER BY c.nome');
  $stmt->bind_param('s',$nome_progetto); $stmt->execute(); $ct=$stmt->get_result(); while($row=$ct->fetch_assoc()) $componenti[]=$row; $stmt->close();
}

$profili = [];
if($progetto['tipo_progetto']==='software') {
  $stmt=$mysqli->prepare('SELECT pc.nome, pc.competenza, pc.livello FROM ProfiloCompetenze pc INNER JOIN Candidatura ca ON pc.nome=ca.nome_profilo WHERE ca.nome_progetto=? ORDER BY pc.nome');
  $stmt->bind_param('s',$nome_progetto); $stmt->execute(); $pt=$stmt->get_result(); while($row=$pt->fetch_assoc()) $profili[]=$row; $stmt->close();
}

$progress = $progetto['budget']>0 ? min(100, round(($tot_fin / $progetto['budget'])*100,2)) : 0;

// Commenti + risposte
$commenti = [];
$stmt=$mysqli->prepare('SELECT c.id, c.contenuto, DATE_FORMAT(IFNULL(c.data, NOW()), "%Y-%m-%d") AS data, c.id_utente, u.nickname, r.testo AS risposta, r.email_creatore FROM Commento c JOIN Utente u ON u.email=c.id_utente LEFT JOIN Risposta r ON r.id_commento=c.id WHERE c.nome_progetto=? ORDER BY c.id DESC');
$stmt->bind_param('s',$nome_progetto); $stmt->execute(); $cr=$stmt->get_result(); while($row=$cr->fetch_assoc()) $commenti[]=$row; $stmt->close();

// Candidature
$candidature = [];
if($progetto['tipo_progetto']==='software') {
  $stmt=$mysqli->prepare('SELECT ca.id, ca.email_utente, u.nickname, ca.nome_profilo, ca.stato FROM Candidatura ca JOIN Utente u ON u.email=ca.email_utente WHERE ca.nome_progetto=? ORDER BY ca.id DESC');
  $stmt->bind_param('s',$nome_progetto); $stmt->execute(); $cand_r=$stmt->get_result(); while($row=$cand_r->fetch_assoc()) $candidature[]=$row; $stmt->close();
}

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Dettagli Progetto - <?= htmlspecialchars($progetto['nome']) ?></title>
<style>
section { margin-bottom:2rem; }
table { border-collapse: collapse; }
th,td { border:1px solid #999; padding:4px 8px; }
form.inline-form { margin:1em 0; padding:0.5em; border:1px solid #ccc; }
.messages ul { margin:0; padding-left:1.2em; }
</style>
</head>
<body>
<?php include_once __DIR__ . '/../../includes/topbar.php'; ?>
<h1><?= htmlspecialchars($progetto['nome']) ?></h1>
<p><strong>Creatore:</strong> <?= htmlspecialchars($progetto['creatore_email']) ?><br>
<strong>Inserito il:</strong> <?= htmlspecialchars($progetto['data_inserimento']) ?><br>
<strong>Budget:</strong> €<?= number_format($progetto['budget'],2,',','.') ?><br>
<strong>Scadenza:</strong> <?= htmlspecialchars($progetto['data_limite']) ?><br>
<strong>Stato:</strong> <?= htmlspecialchars($progetto['stato']) ?><br>
<strong>Tipo:</strong> <?= htmlspecialchars($progetto['tipo_progetto']) ?></p>
<p><?= nl2br(htmlspecialchars($progetto['descrizione'])) ?></p>
<p><strong>Finanziato:</strong> €<?= number_format($tot_fin,2,',','.') ?> / €<?= number_format($progetto['budget'],2,',','.') ?> (<?= $progress ?>%)</p>
<div style="background:#eee;width:300px;height:14px;position:relative;border:1px solid #ccc;margin-bottom:1em;">
  <div style="background:#4caf50;height:100%;width:<?= $progress ?>%;"></div>
</div>

<?php if($project_open): ?>
<section id="funding">
  <h2>Finanzia questo progetto</h2>
  <?php if(empty($reward_list)) { echo '<p>Nessuna reward disponibile al momento.</p>'; } else { ?>
  <form method="post" class="inline-form" enctype="multipart/form-data">
    <input type="hidden" name="action" value="fund_project">
    <label>Importo (€)<br><input type="number" step="0.01" name="importo" required></label><br>
    <label>Reward<br>
      <select name="codice_reward" required>
        <option value="">-- scegli --</option>
        <?php foreach($reward_list as $rw): ?>
          <option value="<?= htmlspecialchars($rw['codice']) ?>"><?= htmlspecialchars($rw['codice']." - ".substr($rw['descrizione'],0,40)) ?></option>
        <?php endforeach; ?>
      </select>
    </label><br>
    <button type="submit">Finanzia</button>
  </form>
  <?php } ?>
</section>
<?php else: ?>
<p style="color:#c00;"><strong>Il progetto è chiuso e non è più finanziabile o modificabile.</strong></p>
<?php endif; ?>

<div class="messages">
<?php if($success){ echo '<ul style="color:green;">'; foreach($success as $m) echo '<li>'.htmlspecialchars($m).'</li>'; echo '</ul>'; }
      if($errors){ echo '<ul style="color:red;">'; foreach($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; echo '</ul>'; } ?>
</div>

<section id="rewards">
  <h2>Reward</h2>
  <?php if(!$rewards) echo '<p>Nessuna reward.</p>'; else { ?>
  <ul>
    <?php foreach($rewards as $r): ?>
      <li><strong><?= htmlspecialchars($r['codice']) ?></strong>: <?= nl2br(htmlspecialchars($r['descrizione'])) ?><?php if($r['foto']) echo '<br><img src="'.htmlspecialchars($r['foto']).'" alt="foto" style="max-width:120px;">'; ?></li>
    <?php endforeach; ?>
  </ul>
  <?php } ?>
  <?php if($can_manage && $project_open): ?>
  <form method="post" class="inline-form" enctype="multipart/form-data">
    <h3>Aggiungi Reward</h3>
    <input type="hidden" name="action" value="add_reward">
    <p><em>Il codice viene generato automaticamente.</em></p>
    <label>Descrizione<br><textarea name="descrizione" rows="3" required></textarea></label><br>
    <label>Foto (carica file)<br><input type="file" name="reward_foto_add" accept="image/*"></label><br>
    <button type="submit">Inserisci</button>
  </form>
  <form method="post" class="inline-form" enctype="multipart/form-data">
    <h3>Modifica Reward</h3>
    <input type="hidden" name="action" value="edit_reward">
    <label>Codice (esistente)<br><input name="codice" required></label><br>
    <label>Nuova Descrizione<br><textarea name="descrizione" rows="3" required></textarea></label><br>
    <label>Nuova Foto (carica file)<br><input type="file" name="reward_foto_edit" accept="image/*"></label><br>
    <button type="submit">Salva</button>
  </form>
  <?php endif; ?>
</section>

<?php if($progetto['tipo_progetto']==='hardware'): ?>
<section id="componenti">
  <h2>Componenti (Hardware)</h2>
  <?php if(!$componenti) echo '<p>Nessuna componente.</p>'; else { ?>
  <table>
    <tr><th>Nome</th><th>Descrizione</th><th>Prezzo</th><th>Quantità</th></tr>
    <?php foreach($componenti as $c): ?>
      <tr>
        <td><?= htmlspecialchars($c['nome']) ?></td>
        <td><?= nl2br(htmlspecialchars($c['descrizione'])) ?></td>
        <td>€<?= number_format($c['prezzo'],2,',','.') ?></td>
        <td><?= htmlspecialchars($c['quantità']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php } ?>
  <?php if($can_manage && $project_open): ?>
  <form method="post" class="inline-form">
    <h3>Aggiungi Componente</h3>
    <input type="hidden" name="action" value="add_component">
    <label>Nome<br><input name="nome_comp" required></label><br>
    <label>Descrizione<br><textarea name="descr_comp" rows="3" required></textarea></label><br>
    <label>Prezzo (€)<br><input type="number" step="0.01" name="prezzo" required></label><br>
    <label>Quantità<br><input type="number" name="quantita" required></label><br>
    <button type="submit">Inserisci</button>
  </form>
  <form method="post" class="inline-form">
    <h3>Modifica Componente</h3>
    <input type="hidden" name="action" value="edit_component">
    <label>Nome (esistente)<br><input name="nome_comp" required></label><br>
    <label>Nuova Descrizione<br><textarea name="descr_comp" rows="3" required></textarea></label><br>
    <label>Nuovo Prezzo (€)<br><input type="number" step="0.01" name="prezzo" required></label><br>
    <label>Nuova Quantità<br><input type="number" name="quantita" required></label><br>
    <button type="submit">Salva</button>
  </form>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if($progetto['tipo_progetto']==='software'): ?>
<section id="profili">
  <h2>Profili (Software)</h2>
  <?php if(!$profili) echo '<p>Nessun profilo.</p>'; else { ?>
  <table>
    <tr><th>Nome</th><th>Competenza</th><th>Livello</th></tr>
    <?php foreach($profili as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['nome']) ?></td>
        <td><?= htmlspecialchars($p['competenza']) ?></td>
        <td><?= htmlspecialchars($p['livello']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
  <?php } ?>
  <?php if($can_manage && $project_open): ?>
  <form method="post" class="inline-form">
    <h3>Aggiungi Profilo</h3>
    <input type="hidden" name="action" value="add_profile">
    <label>Nome<br><input name="nome_prof" required></label><br>
    <label>Competenza<br>
      <select name="competenza" required>
        <option value="">-- scegli competenza --</option>
        <?php foreach($all_skills as $sk): ?>
          <option value="<?= htmlspecialchars($sk) ?>"><?= htmlspecialchars($sk) ?></option>
        <?php endforeach; ?>
      </select>
    </label><br>
    <label>Livello (0-5)<br><input type="number" min="0" max="5" name="livello" required></label><br>
    <button type="submit">Inserisci</button>
  </form>
  <form method="post" class="inline-form">
    <h3>Modifica Profilo</h3>
    <input type="hidden" name="action" value="edit_profile">
    <label>Nome (esistente)<br><input name="nome_prof" required></label><br>
    <label>Nuova Competenza<br>
      <select name="competenza" required>
        <option value="">-- scegli competenza --</option>
        <?php foreach($all_skills as $sk): ?>
          <option value="<?= htmlspecialchars($sk) ?>"><?= htmlspecialchars($sk) ?></option>
        <?php endforeach; ?>
      </select>
    </label><br>
    <label>Nuovo Livello (0-5)<br><input type="number" min="0" max="5" name="livello" required></label><br>
    <button type="submit">Salva</button>
  </form>
  <?php endif; ?>

  <?php if($project_open): ?>
  <section id="candidature-utenti">
    <h3>Candidati ad un profilo</h3>
    <?php if(!$profili) { echo '<p>Nessun profilo per cui candidarsi.</p>'; } else { ?>
      <form method="post" class="inline-form">
        <input type="hidden" name="action" value="apply_profile">
        <label>Profilo<br>
          <select name="nome_profilo" required>
            <option value="">-- scegli --</option>
            <?php foreach($profili as $p): ?>
              <option value="<?= htmlspecialchars($p['nome']) ?>"><?= htmlspecialchars($p['nome'].' ('.$p['competenza'].' '.$p['livello'].')') ?></option>
            <?php endforeach; ?>
          </select>
        </label><br>
        <button type="submit">Invia candidatura</button>
      </form>
    <?php } ?>
  </section>
  <?php endif; ?>

  <section id="lista-candidature">
    <h3>Stato Candidature</h3>
    <?php
      $cand_visibili = array_filter($candidature, function($c) use ($progetto){ return $c['email_utente'] !== $progetto['creatore_email']; });
      if(!$cand_visibili) echo '<p>Nessuna candidatura.</p>'; else {
        echo '<table><tr><th>ID</th><th>Utente</th><th>Profilo</th><th>Stato</th>' . ($can_manage ? '<th>Azione</th>' : '') . '</tr>';
        foreach($cand_visibili as $cd){
          echo '<tr><td>'.htmlspecialchars($cd['id']).'</td><td>'.htmlspecialchars($cd['nickname']).'</td><td>'.htmlspecialchars($cd['nome_profilo']).'</td><td>'.htmlspecialchars($cd['stato']??'in attesa').'</td>';
          if($can_manage && $project_open && ($cd['stato']===NULL)){
            echo '<td><form method="post" style="display:inline"><input type="hidden" name="action" value="manage_candidature"><input type="hidden" name="cand_id" value="'.htmlspecialchars($cd['id']).'">'
               .'<button name="decision" value="accettata">Accetta</button> '
               .'<button name="decision" value="rifiutata">Rifiuta</button></form></td>';
          } elseif($can_manage) { echo '<td>-</td>'; }
          echo '</tr>';
        }
        echo '</table>';
      }
    ?>
  </section>
</section>
<?php endif; ?>

<section id="commenti">
  <h2>Commenti</h2>
  <?php if(!$commenti) echo '<p>Nessun commento.</p>'; else { ?>
    <ul>
      <?php foreach($commenti as $c): ?>
        <li>
          <strong><?= htmlspecialchars($c['nickname']) ?></strong> (<?= htmlspecialchars($c['data']) ?>):<br>
          <?= nl2br(htmlspecialchars($c['contenuto'])) ?>
          <?php if($c['risposta']): ?>
            <div style="margin:0.5em 0 0 1em; padding:0.5em; border-left:3px solid #4caf50;">
              <em>Risposta creatore:</em><br><?= nl2br(htmlspecialchars($c['risposta'])) ?>
            </div>
          <?php elseif($can_manage && $project_open): ?>
            <form method="post" class="inline-form" style="margin-left:1em;">
              <input type="hidden" name="action" value="reply_comment">
              <input type="hidden" name="comment_id" value="<?= htmlspecialchars($c['id']) ?>">
              <label>Rispondi<br><textarea name="testo" rows="2" required></textarea></label><br>
              <button type="submit">Invia risposta</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php } ?>
  <?php if($project_open): ?>
  <form method="post" class="inline-form">
    <h3>Inserisci Commento</h3>
    <input type="hidden" name="action" value="add_comment">
    <textarea name="contenuto" rows="3" required></textarea><br>
    <button type="submit">Invia</button>
  </form>
  <?php endif; ?>
</section>

<p><a href="search-view.php">Torna alla ricerca</a></p>
</body>
</html>
