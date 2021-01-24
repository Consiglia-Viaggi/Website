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
?><html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/navigation_bar.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/links.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/body.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/table_AdminPanel.css" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
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
			<a class="active">HOME</a>
			<a href="https://consigliaviaggi.altervista.org/reviews">RECENSIONI</a>
			<a href="https://consigliaviaggi.altervista.org/statistics">STATISTICHE</a>
			<a class="last" href="https://consigliaviaggi.altervista.org" onclick="return logout();">Esci</a>
		</div>
		<div class="container">
			<h2>Benvenuto, <? echo $_SESSION['name']; ?></h2>
			<?php if ($_SESSION['isTemp'])
					echo "<br><em>Hai effettuato l'accesso mediante la password inviata tramite mail. Adesso è la tua nuova password.</em><br><em>Se vuoi, puoi cambiarla in seguito.</em><br>"; 
					$_SESSION['isTemp'] = FALSE;
			?>
			<h3>Gestione account</h3>
			<table>
				<tr>
					<td><strong>Nome: </strong></td>
					<td class="tableInfo"><?php echo $_SESSION['name'] . ' ' . $_SESSION['surname']; ?></td>
				</tr>
				<tr>
					<td><strong>Nickname: </strong></td>
					<td class="tableInfo"><?php echo $_SESSION['username']; ?></td>
				</tr>
				<tr>
					<td><strong>Informazioni di contatto: </strong></td>
					<td class="tableInfo"><?php echo $_SESSION['mail']; ?></td>
				</tr>
			</table>
			<br>
			<br>
			<a href="https://consigliaviaggi.altervista.org/editmail">Modifica indirizzo email</a>
			<br><em>Assicurati di avere sempre accesso al tuo indirizzo email</em>
			<br>
			<br>
			<a href="https://consigliaviaggi.altervista.org/editpassword">Modifica password</a>
			<br><em>Ti consigliamo di usare una password sicura che non utilizzi altrove</em>
		</div>
	</body>
</html><?php } ?>