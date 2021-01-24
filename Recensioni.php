<?php
	function openDatabase() {
		$database = new SQLite3('wp-content/scripts/database.sqlite', SQLITE3_OPEN_READWRITE);
		return $database;
	}
	function getCorrectPage($page, $maxPages) {
		if ($page > $maxPages)
			$page = 1;
		if ($page <= 0)
			$page = 1;
		return $page;
	}
	function getTotReviews($result) {
        $numRows = 0;
        while($rows = $result->fetchArray()){
            ++$numRows;
        }
        return $numRows;
	}
	function calculatePages($tot, $rowsForPage) {
		$i = 0;
		$pages = 0;
		for ($i = 0; $i < $tot; $i++) {
			if (($i % $rowsForPage) == 0)
				$pages++;
		}
		return $pages;	
	}
	function showPagination($currentPage, $maxPage) {
		//echo '<div class="center"><div class="pagination">';
		echo '<div class="pagination">';
		$previousPage = $currentPage - 1;
		$nextPage = $currentPage + 1;
		if ($previousPage > 0)
			echo '<a id="'.$previousPage.'" href="https://consigliaviaggi.altervista.org/reviews?_page=1">&laquo;</a>';
		$i = 1;
		$max = $maxPage;
		if ($currentPage > 9)
			$i = $currentPage - 5;
		if ($i + 10 < $max)
			$max = $i + 9;
		if ($max - 10 > 0 && $max - 10 < $currentPage)
			$i = $max - 10;
		for (; $i <= $max; $i++) {
			if ($currentPage == $i)
				echo '<a class="active">'. $i .'</a>';
			else
				//echo '<a href="https://consigliaviaggi.altervista.org/reviews?page='.$i.'" onclick="return openPage(this)">'. $i .'</a>';
				echo '<a id="'.$i.'" href="https://consigliaviaggi.altervista.org/reviews?_page=' . $i .'">'. $i .'</a>';
		}
		if ($nextPage <= $maxPage)
			echo '<a id="'.$maxPage.'" href="https://consigliaviaggi.altervista.org/reviews?_page=' . $maxPage .'">&raquo;</a>';
		//echo '</div></div>';
		echo '</div>';
	}
	function getPartialDescription($str) { // $str è la descrizione della recensione.
		if (strlen($str) > 45) { // Se la descrizione è maggiore di 45, allora devo troncare la stringa.
			$wrapped = wordwrap($str, 45); // Tronca al 45esimo carattere.
			$lines = explode("\n", $wrapped); // Splitta la stringa in un array. La prima cella di questo array conterrà la sottostringa fino al 45esimo caratter.
			$new_str = $lines[0] . '...'; // Alla prima cella dell'array di sottostringhe aggiungo i puntini sospensivi. 
			return $new_str; // Ritorno la stringa modificata.
		}
		return $str; // Ritorno la stringa originale in quanto la sua lunghezza è minore/uguale di 45.
	}
	function showReviews($db) {
		$query = "SELECT R.ID AS ID_REVIEW, USERNAME, TITLE, DESCRIPTION, R.RATING AS RATING FROM USERS AS U INNER JOIN REVIEWS AS R ON U.ID = R.ID_USER WHERE R.IS_APPROVED = 0";
		$hasRows = FALSE;
		if ($res = $db->query($query)) { // EXEC THE QUERY.
			$tot = getTotReviews($res);
			echo '<center><h2>Recensioni da approvare: ' . $tot .'</h2>';
			if ($tot >  0) {
				$rowsForPage = 10;
				$maxPages = calculatePages($tot, $rowsForPage);
				$page = 1;
				if (isset($_GET['_page']))
					$page = $_GET['_page'];
				$page = getCorrectPage($page, $maxPages);
				$whereToStart = 0;
				if ($page != 1)
					$whereToStart = ($page-1) * $rowsForPage;
				//echo "DEBUG DATA:<br>Ci sono ". $tot ." recensioni, in ogni pagina dovrebbero esserci ". $rowsForPage ." recensioni, la pagina attuale è la ". $page ." e la pagina massima è la ". $maxPages .".<br><br>";
				echo "<table>"; // START CREATING THE TABLE UI.
				echo "<tr>";// START CREATING THE TABLE ROW UI.
				$columns = array("Username", "Titolo", "Descrizione");
				$englishColumns = array("USERNAME", "TITLE", "DESCRIPTION");
				for ($i = 0; $i < 3; $i++)
					echo "<td style='background: #eeeeee; border: 1px solid #ddd; padding: 8px; -webkit-user-select: none; -khtml-user-select: none;-moz-user-select: none; -o-user-select: none; user-select: none;'>". $columns[$i] ."</td>"; // PRINT COLUMNS NAME.
				echo "</tr>";
				$printedRows = 0;
				$j = 0;
				while ($row = $res->fetchArray()) {
					$j++;
					if ($j > $whereToStart) {
						$printedRows++;
						echo "<tr>"; // START CREATING THE TABLE ROW UI.
						$id = $row['ID_REVIEW'];
						foreach ($row as $key => $value) {
							if ((!is_numeric($key)) && (in_array($key, $englishColumns))) {
								$hasRows = TRUE;
								$text = $value;
								if (strcmp($key, "DESCRIPTION") == 0)
									$text = getPartialDescription($value);
								if (strlen($text) == 0)
									$text = "<em>Recensione vuota</em>";
								if (strcmp($key, "USERNAME") == 0)
									echo '<td style="border: 1px solid #ddd; padding: 8px;"><a href="https://consigliaviaggi.altervista.org/statistics_users?id='. $id.'">'.$text.'</a></td>'; // PRINT THE VALUE.
								else
									echo '<td style="border: 1px solid #ddd; padding: 8px;"><a href="https://consigliaviaggi.altervista.org/reviews?id='. $id.'">'.$text.'</a></td>'; // PRINT THE VALUE.
							
							}
						}
						echo "</tr>"; // CLOSE THE TABLE ROW.
					}
					if ($printedRows >= $rowsForPage)
						break;
				}
				echo "</table><br>"; // CLOSE THE TABLE.
				showPagination($page, $maxPages);
				echo "</center>";
			}
		}
		if (!$hasRows)
			echo "<center><h2>Nessuna recensione da approvare.</h4></center>";
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
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/pagination.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/buttons.css" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<script>
			function approveReview(id) {
				$.ajax({
					url: 'https://consigliaviaggi.altervista.org/wp-content/website/script_approveReview.php',
					type: 'post',
					data: { "id": id},
					success: function(response) {
						if (response == "0")
							alert("La recensione è stata approvata.");
						else
							alert("Si è verificato un errore durante l'approvazione della recensione (" + response + ")");
						window.location.href = 'https://consigliaviaggi.altervista.org/reviews';
					}, error: function (jqXHR, exception) {
					}
				});
			}
		</script>
		<script>
			function deleteReview(id) {
				$.ajax({
					url: 'https://consigliaviaggi.altervista.org/wp-content/website/script_deleteReview.php',
					type: 'post',
					data: { "id": id},
					success: function(response) {
						if (response == "0")
							alert("La recensione è stata eliminata.");
						else
							alert("Si è verificato un errore durante l'eliminazione della recensione (" + response + ")");
						window.location.href = 'https://consigliaviaggi.altervista.org/reviews';
					}, error: function (jqXHR, exception) {
					}
				});
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
			<a href="https://consigliaviaggi.altervista.org/home">HOME</a>
			<?php
				if ((!empty($_GET)) && (isset($_GET['id']))) 
					echo '<a class="active"href="https://consigliaviaggi.altervista.org/reviews">RECENSIONI</a>';
				else	
					echo '<a class="active">RECENSIONI</a>';
			?>
			<a href="https://consigliaviaggi.altervista.org/statistics">STATISTICHE</a>
			<a class="last" href="https://consigliaviaggi.altervista.org" onclick="return logout();">Esci</a>
		</div>
			<?php
				if ((!empty($_GET)) && (isset($_GET['id']))) {
					$id = $_GET['id'];
					if ($id > 0) {
						$query = "SELECT R.ID AS ID_REVIEW, S.NAME AS STRUCTURE_NAME, USERNAME, TITLE, R.DESCRIPTION AS REV_DESCRIPTION, R.RATING AS RATING FROM USERS AS U INNER JOIN REVIEWS AS R ON U.ID = R.ID_USER INNER JOIN STRUCTURES AS S ON R.ID_ARTICLE = S.IDSTRUCTURE WHERE R.IS_APPROVED = 0 AND ID_REVIEW = ". $id;
						$db = openDatabase();
						$res = $db->query($query);
						if ($res) {
							$row = $res->fetchArray();
							$author = $row['USERNAME'];
							$structureName = $row['STRUCTURE_NAME'];
							$title = $row['TITLE'];
							$description = $row['REV_DESCRIPTION'];
							echo '<center><h2>Recensione di ' . $author . '</h2>';
							echo '<h3>Struttura: ' . $structureName .'</h3>';
							echo '<center><p>' . $title . '<br>'. $description .'</p></center>';
							?>
							<table style='margin-left: auto; margin-right: auto;'>
								<tr>
									<td>
										<button id="delete" type="button" onclick="return deleteReview(<?php echo $id; ?>);">ELIMINA</button>
									</td>
									<td>
										<button id="approve" type="button" onclick="return approveReview(<?php echo $id; ?>);">APPROVA</button>
									</td>
								</tr>
							</table>
							<?php
						}
					}
				}
				else {
					$database = openDatabase();
					if ($database)
						showReviews($database);
				}
			?>
	</body>
</html><?php } ?>