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
	<script type="text/javascript" src="https://github.com/kvz/phpjs/raw/master/functions/xml/utf8_encode.js"></script>
	<script type="text/javascript" src="https://github.com/kvz/phpjs/raw/master/functions/strings/md5.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/md5.js"></script>
	<script type="text/javascript">
		<!--
		function encrypt(form) {
			if ((form.password.value) && (form.password.value != ''))
				form.password.value = md5(form.password.value);
		}
		//-->
	</script>
	<script type="text/javascript">
		function destroy_session(e) {	
			var xmlhttp = getXmlHttp();	
			var xmlhttp = new XMLHttpRequest();
			xmlhttp.open('GET','https://consigliaviaggi.altervista.org/wp-content/website/destroy_session.php', true);
			xmlhttp.onreadystatechange = function(){
				if (xmlhttp.readyState == 4){
					if (xmlhttp.status == 200)	
						alert(xmlhttp.responseText);
					}
				};
			xmlhttp.send(null);
		}
	</script>
	<script>
		function editmail(e) {
			var mail = document.getElementById("mail").value;
			var password = document.getElementById("password").value;
			$.ajax({
				url: 'https://consigliaviaggi.altervista.org/wp-content/website/script_editmail.php',
				type: 'post',
				data: { "mail": mail, "password" : CryptoJS.MD5(password).toString()},
				success: function(response) {
					if (response == "0") {
						alert("L'indirizzo mail è stato aggiornato correttamente.");
						location.reload();
					}
					else
						alert("Si è verificato un errore nell'aggiornamento dell'indirizzo mail.\nContatta l'amministratore segnalando l'errore: " + response);
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
				<h2>Modifica indirizzo email</h2>
				<strong>Indirizzo email attuale: </strong><?php echo $_SESSION['mail']; ?>
				<br>
				<br>
				<br>
				<form action="" method="post" onsubmit="return editmail(event);">
					<input type="text" id="mail" name="mail" placeholder="Nuovo indirizzo email...">
					<br>
					<br>
					<input type="password" id="password" name="password" placeholder="Conferma la tua password"><br><br>
					<br>
					<table>
						<tr>
							<td>
							<button type="button" onclick="window.history.back();">Annulla</button>
							</td>
							<td>
							<input type="submit" onclick="return editmail(event);" value="Conferma">
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