<?php
	function isStillLoggedIn() {
		$mail = $_SESSION['mail'];
		$password = $_SESSION['password'];
		$id = $_SESSION['id']; // Lo usiamo nel caso in cui l'utente attuale cambi mail e password e le stesse mail e password vengono prese da un altro admin. L'identità è diversa quindi va controllato l'ID!
		$query = "SELECT * FROM USERS WHERE ID = '".$id."' AND MAIL = '".$mail."' AND PASSWORD = '".$password."' AND ADMIN = 1";
		$db = new SQLite3('wp-content/scripts/database.sqlite', SQLITE3_OPEN_READWRITE);
		$result = $db->query($query); // EXEC THE QUERY.
		$isLogged = false;
		if ($result != null) {
			while ($row = $result->fetchArray()) {
				$isLogged = true;
				break;
			}
		}
		if (!$isLogged) {
			 $_SESSION = array();
			if (ini_get("session.use_cookies")) {
			   $params = session_get_cookie_params();
			   setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			   );
			}
			$_SESSION['id'] = NULL;
			$_SESSION['name'] = NULL;
			$_SESSION['surname'] = NULL;
			$_SESSION['username'] = NULL;
			$_SESSION['mail'] = NULL;
			$_SESSION = NULL;
			session_destroy();
			session_unset();
		}
		return $isLogged;
	}
	session_start();
	if ((empty($_SESSION["id"])) || (!isStillLoggedIn()))
		header('Location: https://consigliaviaggi.altervista.org');
	else {
?>
<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/navigation_bar.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/links.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/body.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/buttons.css" />
	<script type="text/javascript" src="http://github.com/kvz/phpjs/raw/master/functions/xml/utf8_encode.js"></script>
	<script type="text/javascript" src="http://github.com/kvz/phpjs/raw/master/functions/strings/md5.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/md5.js"></script>
	<script type="text/javascript">
		<!--
		function encrypt(e) {
			var password0 = document.getElementById("password0").value;
			var password1 = document.getElementById("password1").value;
			var password2 = document.getElementById("password2").value;
			if ((password0 == '') && (password1 == '') || (password2 == '')) {
				alert("La password non può essere vuota.");
				e.preventDefault();
				return false;
			}
			if (password1 != password2) {
				alert("La password da confermare deve essere uguale alla password scelta.");
				e.preventDefault();
				return false;	
			}
			if (password0 == password1) {
				alert("La nuova password non può essere uguale alla vecchia.");
				e.preventDefault();
				return false;	
			}
			var maxLength = 15;
			if (password1.length > maxLength) {
				alert("La password è troppo lunga. E' accettata una password di lunghezza massima " + maxLength + " caratteri.");
				e.preventDefault();
				return false;	
			}
			if  (/[A-Z]/ .test(password1) && /[a-z]/ .test(password1) && /[0-9]/ .test(password1) && /[^A-Za-z0-9]/.test(password1) && password1.length > 7) {
				$.ajax({
					url: 'https://consigliaviaggi.altervista.org/wp-content/website/script_editpassword.php',
					type: 'post',
					data: { "password": CryptoJS.MD5(password1).toString()},
					success: function(response) {
						if (response == "0")
							alert("La password è stata aggiornata correttamente.");
						else
							alert("Si è verificato un errore nell'aggiornamento della tua password.\nContatta l'amministratore segnalando l'errore: " + response);
						location.reload();
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
				e.preventDefault();
				return false;	
			}
			alert("La password non è sicura. Deve contenere almeno un carattere maiuscolo, uno minuscolo, un numero e deve essere almeno di 8 caratteri.");
			e.preventDefault();
			return false;
		}
		//-->
	</script>
	<script>
		function logout() {	
			$.ajax({
				url:'https://consigliaviaggi.altervista.org/wp-content/website/destroy_session.php',
				complete: function (response) {
					
				}, error: function () {
					
				}
			});
		}
	</script>
	</head>
	<body>
		<div class="topnav noselect">
			<a class="active" href="https://consigliaviaggi.altervista.org/home">HOME</a>
			<a href="https://consigliaviaggi.altervista.org/reviews">RECENSIONI</a>
			<a href="https://consigliaviaggi.altervista.org/statistics">STATISTICHE</a>
			<a class="last" href="https://consigliaviaggi.altervista.org" onclick="return logout();">Esci</a>
		</div>
		<div class="container">
			<center>
				<h2>Modifica password</h2>
				<br>
				<br>
				<form action="" method="post" onsubmit="return encrypt(event);">
					<input type="password" id="password0" name="password0" placeholder="Vecchia password">
					<br>
					<br>
					<input type="password" id="password1" name="password1" placeholder="Nuova password">
					<br>
					<br>
					<input type="password" id="password2" name="password2" placeholder="Conferma la tua nuova password"><br><br>
					<br>
					<table>
						<tr>
							<td>
							<button type="button" onclick="window.history.back();">Annulla</button>
							</td>
							<td>
							<input type="submit" value="Conferma">
							</td>
						</tr>
					</table>
				</form> 
				<br>
				<br>
			</center>
		</div>
	</body>
</html><?php } ?>