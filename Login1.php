<?php
function getColumnsForTable($db, $table) {
	$query = "PRAGMA table_info(". $table .")"; // COMPOSE THE QUERY.
	$result = $db->query($query); // EXEC THE QUERY.
	$array = array(); // INIT THE ARRAY.
	if ($result) {
		while ($table = $result->fetchArray(SQLITE3_ASSOC)) { // ITERATE FOR COLUMNS
			array_push($array, $table['name']); // SAVE THE COLUMN NAME.
		}
	}
	return $array;
}
function showFullTable($db, $table) {
	$query = "SELECT * FROM ". $table; // COMPOSE THE QUERY.
	if ($res = $db->query($query)) { // EXEC THE QUERY.
		echo "<table>"; // START CREATING THE TABLE UI.
		echo "<tr"> // START CREATING THE TABLE ROW UI.
		$columns = getColumnsForTable($db, $table); // GET COLUMNS NAME FOR TABLE.
		$count = count($columns);
		for ($i = 0; $i < $count; $i++)
			echo "<td>". $columns[$i] ."</td>"; // PRINT COLUMNS NAME.
		echo "</tr>";
		while ($row = $res->fetchArray()) {
			echo "<tr>"; // START CREATING THE TABLE ROW UI.
			foreach ($row as $key => $value) {
				if (is_numeric($key)) {
					echo "<td>". $value."</td>"; // PRINT THE VALUE.
				}
			}
			echo "</tr>"; // CLOSE THE TABLE ROW.
		}
		echo "</table><br>"; // CLOSE THE TABLE.
	}
}
function getFieldFromQuery($db, $query, $field) {
	if ($res = $db->query($query)) // EXEC THE QUERY.
		while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
			return $row[$field]; // RETURN THE VALUE FOR THE FIELD.
	return NULL;
}
function isAdmin($checkTempPassword) {
	$mail  = trim($_POST['mail']);
	$password = md5(trim($_POST['password']));
	if (get_magic_quotes_gpc()) {
		$mail = stripslashes($mail);
		$password = stripslashes($password);
	}
	if (!$mail || !$password)
		return FALSE;
	$mail = filter_var($mail, FILTER_SANITIZE_STRING);
	$password = filter_var($password, FILTER_SANITIZE_STRING);
	$db = new SQLite3('wp-content/scripts/database.sqlite', SQLITE3_OPEN_READWRITE);
	if (!$db) {
		echo "Il database non esiste.";
		return FALSE;
	}
	$query = "SELECT * FROM USERS WHERE MAIL = '".$mail."' AND PASSWORD = '".$password."' AND ADMIN = 1";
	if ($checkTempPassword) { // Controllo su pasword temporanea.
		$temp_query = "SELECT * FROM USERS WHERE MAIL = '".$mail."' AND ADMIN = 1"; // Verifica se la mail corrisponde ad una degli admin.
		$user_exists = getFieldFromQuery($db, $temp_query, "ID"); // Esegui la query ed ottieni l'ID se esiste.
		if ($user_exists > 0) { // Se esiste...
			$temp_query = "SELECT * FROM TEMP_PASSWORDS WHERE ID = ".$user_exists." AND PASSWORD = '".$password."'"; // Verfica se esiste un utente con quell'ID che ha richiesto una password temporanea.
			$result = $db->query($temp_query); 
			if ($result) { // Se esiste quell'utente...
				$checkDate = TRUE;
				if ($checkDate) {
					$row = $result->fetchArray(); // Ottieni la riga.
					$date = $row['DATE']; // Estrai la data di generazione della password.
					$timestamp = strtotime($date); // Convertila in timestamp.
					$difference = time() - $timestamp; // Comprendi quanto tempo è passato dalla generazione della password.
					if ($difference > 86400) // Se sono passate 24 ore...
						return FALSE; // Per protezione, diciamo che la password generata è "scaduta", quindi non effettuo l'accesso. L'utente dovrà richiedere una nuova password ed effettuare l'accesso entro le 24 ore dalla richiesta.
				}
				$update_query = "UPDATE USERS SET PASSWORD = '". $password ."' WHERE ID = " . $user_exists; // Aggiorna la sua password (dimenticata) con quella temporanea, con cui sta tentando di accedere.
				$result = $db->query($update_query);
				if ($result) {
					$delete_query = "DELETE FROM TEMP_PASSWORDS WHERE ID = ".$user_exists;
					$result = $db->query($delete_query);
				}
				else
					return FALSE;
			}
			else
				return FALSE;
		}
		else
			return FALSE;
	}
	$result = $db->query($query); // EXEC THE QUERY.
	if (!$result)
		return FALSE;
	$id = NULL;
	$name = NULL;
	$surname = NULL;
	$username = NULL;
	$_mail = NULL; // Prendiamo la mail reale salvata nel database (questo perché l'utente potrebbe aver inserito la sua mail, per esempio, tutta maiuscola).
	while ($row = $result->fetchArray()) {
		$id = $row['ID'];
		$name = $row['NAME'];
		$surname = $row['SURNAME'];
		$username = $row['USERNAME'];
		$_mail = $row['MAIL'];
	}
	if (!$id)
		return FALSE;
	session_start();
	$_SESSION['id'] = $id;
	$_SESSION['name'] = $name;
	$_SESSION['surname'] = $surname;
	$_SESSION['username'] = $username;
	$_SESSION['mail'] = $mail;
	$_SESSION['password'] = $password;
	$_SESSION['isTemp'] = $checkTempPassword;
	$messaggio = urlencode('Login avvenuto con successo');
	header('Location: https://consigliaviaggi.altervista.org/home');
	return TRUE;
}

// MAIN
	/*session_start();
	if (!empty($_SESSION["id"]))
		header('Location: https://consigliaviaggi.altervista.org/home');
	else
		session_destroy();*/
	$isLogged = false;
	$hasPost = false;
	if ($_POST) {
		$isLogged = isAdmin(FALSE);
		if (!$isLogged)
			$isLogged = isAdmin(TRUE);
		$hasPost = true;
	}
	if (!$isLogged) {
?>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/links.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/body.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/buttons.css" />
	<script type="text/javascript" src="https://github.com/kvz/phpjs/raw/master/functions/xml/utf8_encode.js"></script>
	<script type="text/javascript" src="https://github.com/kvz/phpjs/raw/master/functions/strings/md5.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script type="text/javascript">
		<!--
		function encrypt(e) {
			var mail = document.getElementById("mail").value;
			if (mail == '') {
				alert("Inserisci l'indirizzo email per accedere all'account.");
				e.preventDefault();
				return false;
			}
			var password = document.getElementById("password").value;
			if (password == '')	 {
				alert("Inserisci la password per accedere all'account.");
				e.preventDefault();
				return false;
			}
			if ((form.password.value) && (form.password.value != ''))
				form.password.value = md5(form.password.value);
		}
		//-->
	</script>
	<script type="text/javascript">
		function showForgottenPasswordAlert(e) {
			var mail = document.getElementById("mail").value;
			if (mail) {
				if ((mail == "Indirizzo email..") || (mail == '')) {
					alert("Inserisci prima l'indirizzo email per il quale non ricordi la password.");
					e.preventDefault();
					return false;
				}
				if ((mail.includes("@")) && (mail.includes("."))) {
					$.ajax({
						url: 'https://consigliaviaggi.altervista.org/wp-content/website/send_temp_password.php',
						type: 'post',
						data: { "mail": mail},
						success: function(response) {
							if (response)
								alert("Se l'indirizzo fornito e' valido e corrisponde ad un account gia' esistente, sara' inviata una mail.");
							else
								alert("Si è verificato un errore nell'inoltro della password temporanea. Segnala l'errore all'amministratore.");
							e.preventDefault();
							return false;
						}, error: function (jqXHR, exception) {
							var msg = '';
							if (jqXHR.status === 0)
								msg = 'Verifica che ci sia una connessione ad internet attiva.';
							else
							if (jqXHR.status == 404)
								msg = 'La pagina richiesta non è stata trovata.';
							else
							if (jqXHR.status == 500)
								msg = 'Internal Server Error [500].';
							else
							if (exception === 'parsererror')
								msg = 'Si è verificato un errore nel parsering del JSON.';
							else
							if (exception === 'timeout')
								msg = 'Time out error.';
							else
							if (exception === 'abort')
								msg = 'La richiesta è scaduta. Aggiorna la pagina e riprova.';
							else
								msg = 'Errore non riconosciuto.\n' + jqXHR.responseText;
							alert(msg);
						}
					});
				}
				else
					alert("Il formato inserito non e' stato riconosciuto. Ricontrolla l'input.");
			}
			else
				alert("Inserisci prima l'indirizzo email per il quale non ricordi la password.");
			e.preventDefault();
			return false;
		}
	</script>
	</head>
	<body>
		<div class="container">
			<center>
				<h2>Consiglia Viaggi</h2>
				<h3>Pannello amministrazione</h3>
				<?php if ($hasPost)
						echo '<h4 style="color: red">I dati inseriti sono errati. Riprova.</h4>';
				?>
				<form action="" method="post" onsubmit="return encrypt(event);">
					<input type="text" id="mail" name="mail" placeholder="Indirizzo email.."><br>
					<br>
					<input type="password" id="password" name="password" placeholder="Password"><br><br>
					<a href="" onclick="return showForgottenPasswordAlert(event);">Password dimenticata?</a>
					<br>
					<br>
					<input type="submit" value="ACCEDI">
				</form> 
			</center>
		</div>
	</body>
</html>
	<? }
?>