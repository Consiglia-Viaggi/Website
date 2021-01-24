<?php
	function openDatabase() {
		$database = new SQLite3('wp-content/scripts/database.sqlite', SQLITE3_OPEN_READWRITE);
		return $database;
	}
	function getTot($db, $query, $column) {
		$count = 0;
		$res = $db->query($query);
		if ($res) {
			while ($row = $res->fetchArray()) {
				if ($column != NULL) {
					foreach ($row as $key => $value) {
						if ((!is_numeric($key)) && (strcmp($key, $column) == 0)) {
							$date = new DateTime();
							$match_date = new DateTime($value);
							$interval = $date->diff($match_date);
							if ($interval->days == 0)
								$count++;
						}
					}
				}
				else
					$count++;
			}
		}
		return $count;
	}
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
			<a href="https://consigliaviaggi.altervista.org/home">HOME</a>
			<a href="https://consigliaviaggi.altervista.org/reviews">RECENSIONI</a>
			<a class="active">STATISTICHE</a>
			<a class="last" href="https://consigliaviaggi.altervista.org" onclick="return logout();">Esci</a>
		</div>
		<div class="container">
			<?php
				$db = openDatabase();
				echo '<h2>Statistiche principali</h2>';
				echo '<p><strong>Visitatori del giorno: </strong>' . getTot($db, "SELECT * FROM USERS", "LASTSEEN_DATE") . '<br>';
				echo '<strong>Registrazioni del giorno: </strong>' . getTot($db, "SELECT * FROM USERS", "REGISTRATION_DATE") . '<br>';
				echo '<strong>Numero di recensioni del giorno: </strong>' . getTot($db, "SELECT * FROM REVIEWS", "DATE_PUBLICATION") . '<br>';
				echo '<strong>Numero di recensioni totali: </strong>' . getTot($db, "SELECT * FROM REVIEWS", NULL) . '</p>';
			?>
			<br>
			<br>
			<a href="https://consigliaviaggi.altervista.org/statistics_users">Statistiche per utente</a>
			<br><em>Visualizza tutte le statistiche per uno specifico utente registrato</em>
			<br>
			<br>
			<a href="https://consigliaviaggi.altervista.org/general_statistics">Statistiche generali</a>
			<br><em>Visualizza l'insieme di tutte le statistiche</em>
		</div>
	</body>
</html><?php }?>