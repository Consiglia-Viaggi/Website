<?php
	function openDatabase() {
		$database = new SQLite3('wp-content/scripts/database.sqlite', SQLITE3_OPEN_READWRITE);
		return $database;
	}
	function getCorrectPage($page, $maxPages) { // $page equivale alla pagina attuale che l'utente sta per visitare. $maxPage il numero di pagine totali.
		if ($page > $maxPages) // Se il numero di pagina che l'utente vuole visitare è maggiore di quello disponibile...
			$page = 1; // Imposta la pagina ad 1.
		if ($page <= 0) // Se l'utente vuole visitare una pagina negativa o 0...
			$page = 1; // Imposta la pagina ad 1.
		return $page; // Ritorna la pagina fixata.
	}
	function getTot($result) {
        $numRows = 0;
        while($rows = $result->fetchArray()){
            ++$numRows;
        }
        return $numRows;
	}
	function calculatePages($tot, $rowsForPage) { // $tot corrisponde al numero di recensioni totali. $rowsForPage corrisponde al numero di recensioni che dovrebbero esserci in una sola pagina.
		$i = 0;
		$pages = 0;
		for ($i = 0; $i < $tot; $i++) {
			if (($i % $rowsForPage) == 0) // Se la divisione è uguale a 0...
				$pages++; // Ho una nuova pagina.
		}
		return $pages; // Ritorno il numero di pagine totali necessarie per ospitare $tot recensioni, dove in ogni pagina ci sono $rowsForPage recensioni.
	}
	function getTotStatistics($db, $query, $column) {
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
	function getAverage($db, $query, $column) {
		$count = 0;
		$average = 0;
		$res = $db->query($query);
		if ($res) {
			while ($row = $res->fetchArray()) {
				foreach ($row as $key => $value) {
					if ((!is_numeric($key)) && (strcmp($key, $column) == 0)) {
						$count++;
						$average = $average + $value;
					}
				}
			}
		}
		if ($count == 0)
			return 0;
		return $average / $count;
	}
	function getReviewsPoints($db, $year) {
		$id = $_GET['id'];
		$query = "SELECT * FROM REVIEWS";
		$points = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
		if ($res = $db->query($query)) {
			while ($row = $res->fetchArray()) {
				$date = $row['DATE_PUBLICATION'];
				$review_year = date("Y",strtotime($date));
				if ($review_year == $year) { 
					$month = date("n",strtotime($date));
					$month--;
					$points[$month]++;
				}
			}
		}
		return $points;
	}
	function showPieGraph($approved, $notApproved) {
		?>
		<script>
		var config = {
			type: 'pie',
			data: {
				datasets: [{
					data: [
						<?php echo $approved; ?>, <?php echo $notApproved; ?>,
					],
					backgroundColor: [
						window.chartColors.green,
						window.chartColors.red
					],
					label: 'Recensioni'
				}],
				labels: [
					'Approvate',
					'Non approvate'
				]
			},
			options: {
				responsive: true
			}
		};
	</script>
	<?php
	}
	function showGraph($array, $year) {
		$title = "'Grafico delle recensioni dell\'anno ". $year ."'";
		?>
		<script>
		var MONTHS = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
		var color = Chart.helpers.color;
		var barChartData = {
			labels: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
			datasets: [{
				label: <?php echo $title; ?>,
				backgroundColor: color(window.chartColors.blue).alpha(0.5).rgbString(),
				borderColor: window.chartColors.blue,
				borderWidth: 1,
				data: [
					<?php echo ($array[0]); ?>, <?php echo ($array[1]); ?>, <?php echo ($array[2]); ?>, <?php echo ($array[3]); ?>, <?php echo ($array[4]); ?>, <?php echo ($array[5]); ?>, <?php echo ($array[6]); ?>, <?php echo ($array[7]); ?>, <?php echo ($array[8]); ?>, <?php echo ($array[9]); ?>, <?php echo ($array[10]); ?>, <?php echo ($array[11]); ?>
					]
			}]
		};
		
		</script>
		<?php
	}
	function addOnLoadFunction() {
		?>
		<script>
		window.onload = function() {
			var ctxPie = document.getElementById('chart-area').getContext('2d');
			window.myPie = new Chart(ctxPie, config);
			var ctx = document.getElementById('canvas').getContext('2d');
			window.myBar = new Chart(ctx, {
				type: 'bar',
				data: barChartData,
				options: {
					responsive: true,
					legend: {
						position: 'top',
					}
				}
			});
		};
		</script>
		<?php
	}
	function showGeneralStatistics($db) {
		$query = "SELECT * FROM USERS";
		if ($res = $db->query($query)) { // EXEC THE QUERY.
			$row = $res->fetchArray();
			$date = $row['LASTSEEN_DATE'];
			$reviewsOfTheDay = getTotStatistics($db, "SELECT * FROM REVIEWS", "DATE_PUBLICATION");
			$totalReviews = getTotStatistics($db, "SELECT * FROM REVIEWS", NULL);
			$approvedReviews = getTotStatistics($db, "SELECT * FROM REVIEWS WHERE IS_APPROVED = 1", NULL);
			$average = getAverage($db, "SELECT * FROM REVIEWS WHERE IS_APPROVED = 1", "RATING");
			//$average = 4.5;
			echo '<h2>Statistiche generali</h2>';
			echo '<div style="display:inline-flex;">';
			echo '<div style="width:150%;">';
			echo '<p>Recensioni del giorno: '. $reviewsOfTheDay . '<br>';
			echo 'Recensioni totali: '. $totalReviews . '<br>';
			echo 'Recensioni approvate: '. $approvedReviews . '<br>';
			echo 'Media recensioni: ';
			if ($average >=1) {
				echo '<span class="rating">';
				$printed = 0;
				for ($i = 1; $i < $average; $i++) {
					if ($i + 1 > $average)
						echo '◐';
					else
						echo '●';
					$printed++;
				}
				if ($printed < 5)
					for (;$printed < 5; $printed++)
						echo '○';
				echo '</span></p>';
			}
			else
			if ($average == 0)
				echo '<span class="rating">○○○○○</span></p>';
			else
				echo '<span class="rating">◐○○○○</span></p>';
			echo "</div>";
			echo '<div style="float: right; width:60%;">';
			echo '<canvas id="chart-area"></canvas>';
			echo '</div>';
			echo '</div>';
			showPieGraph($approvedReviews, $totalReviews - $approvedReviews);
			echo '<div id="container">';
			echo '<canvas id="canvas"></canvas>';
			echo '</div>';
			$date = new DateTime();
			$year = $date->format('Y');
			showGraph(getReviewsPoints($db, $year), $year);
			addOnLoadFunction();
		}
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
		<style>
			@font-face {
				font-family:star-rating;
				src:url(data:application/x-font-ttf;base64,AAEAAAALAIAAAwAwT1MvMigiLIYAAAC8AAAAYGNtYXAmCyZNAAABHAAAAFRnYXNwAAAAEAAAAXAAAAAIZ2x5ZlNxiKoAAAF4AAABFGhlYWQBHDApAAACjAAAADZoaGVhA+IB6AAAAsQAAAAkaG10eAcAAAAAAALoAAAAHGxvY2EAjADoAAADBAAAABBtYXhwAAoAGAAAAxQAAAAgbmFtZYWP6p0AAAM0AAABaXBvc3QAAwAAAAAEoAAAACAAAwIAAZAABQAAAUwBZgAAAEcBTAFmAAAA9QAZAIQAAAAAAAAAAAAAAAAAAAABAAAgAAAAAAAAAAAAAAAAAABAAAAl0AHg/+D/4AHgACAAAAABAAAAAAAAAAAAAAAgAAAAAAACAAAAAwAAABQAAwABAAAAFAAEAEAAAAAMAAgAAgAEAAEAICXLJdD//f//AAAAAAAgJcslz//9//8AAf/j2jnaNgADAAEAAAAAAAAAAAAAAAAAAQAB//8ADwABAAAAAAAAAAAAAgAANzkBAAAAAAEAAAAAAAAAAAACAAA3OQEAAAAAAQAAAAAAAAAAAAIAADc5AQAAAAACAAD/7QIAAdMACgAVAAABLwEPARcHNxcnNwUHNyc/AR8BBxcnAgCxT0+xgB6enh6A/wBwFlt9ODh9WxZwARkaoKAafLBTU7B8sjp8WBJxcRJYfDoAAAAAAQAA/+0CAAHTAAoAAAEvAQ8BFwc3Fyc3AgCxT0+xgB6enh6AARkaoKAafLBTU7B8AAAAAAIAAP/tAgAB0wAKABIAAAEvAQ8BFwc3Fyc3BTERHwEHFycCALFPT7GAHp6eHoD/ADh9WxZwARkaoKAafLBTU7B8sgEdcRJYfDoAAAABAAAAAQAA1qooUl8PPPUACwIAAAAAAM/+d7YAAAAAz/53tgAA/+0CAAHTAAAACAACAAAAAAAAAAEAAAHg/+AAAAIAAAAAAAIAAAEAAAAAAAAAAAAAAAAAAAAHAAAAAAAAAAAAAAAAAQAAAAIAAAACAAAAAgAAAAAAAAAACgAUAB4ASgBkAIoAAQAAAAcAFgACAAAAAAACAAAAAAAAAAAAAAAAAAAAAAAAAA4ArgABAAAAAAABABYAAAABAAAAAAACAA4AYwABAAAAAAADABYALAABAAAAAAAEABYAcQABAAAAAAAFABYAFgABAAAAAAAGAAsAQgABAAAAAAAKADQAhwADAAEECQABABYAAAADAAEECQACAA4AYwADAAEECQADABYALAADAAEECQAEABYAcQADAAEECQAFABYAFgADAAEECQAGABYATQADAAEECQAKADQAhwBzAHQAYQByAC0AcgBhAHQAaQBuAGcAVgBlAHIAcwBpAG8AbgAgADEALgAwAHMAdABhAHIALQByAGEAdABpAG4AZ3N0YXItcmF0aW5nAHMAdABhAHIALQByAGEAdABpAG4AZwBSAGUAZwB1AGwAYQByAHMAdABhAHIALQByAGEAdABpAG4AZwBGAG8AbgB0ACAAZwBlAG4AZQByAGEAdABlAGQAIABiAHkAIABJAGMAbwBNAG8AbwBuAC4AAAAAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==) format('truetype'),url(data:application/font-woff;base64,d09GRk9UVE8AAAUgAAoAAAAABNgAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAABDRkYgAAAA9AAAAWMAAAFjbsVsoU9TLzIAAAJYAAAAYAAAAGAoIiyGY21hcAAAArgAAABUAAAAVCYLJk1nYXNwAAADDAAAAAgAAAAIAAAAEGhlYWQAAAMUAAAANgAAADYBHDApaGhlYQAAA0wAAAAkAAAAJAPiAehobXR4AAADcAAAABwAAAAcBwAAAG1heHAAAAOMAAAABgAAAAYAB1AAbmFtZQAAA5QAAAFpAAABaYWP6p1wb3N0AAAFAAAAACAAAAAgAAMAAAEABAQAAQEBDHN0YXItcmF0aW5nAAECAAEAOvgcAvgbA/gYBB4KABlT/4uLHgoAGVP/i4sMB4tr+JT4dAUdAAAAjg8dAAAAkxEdAAAACR0AAAFaEgAIAQEMFxkbHiMoLXN0YXItcmF0aW5nc3Rhci1yYXRpbmd1MHUxdTIwdTI1Q0J1MjVDRnUyNUQwAAACAYkABQAHAQEEBwoNVn29/JQO/JQO/JQO+5QO+JT3rRX7RaU89zQ8+zT7RXH3FPsQbftE9zLe9zI4bfdE9xT3EAX7lPtGFfsEUaH3EDDj9xGdw/cFw/sF9xF5MDOh+xD7BMUFDviU960V+0WlPPc0PPs0+0Vx9xT7EG37RPcy3vcyOG33RPcU9xAFDviU960V+0WlPPc0PPs0+0Vx9xT7EG37RPcy3vcyOG33RPcU9xAF+5T7RhWLi4v3scP7BfcReTAzofsQ+wTFBQ74lBT4lBWLDAoAAAMCAAGQAAUAAAFMAWYAAABHAUwBZgAAAPUAGQCEAAAAAAAAAAAAAAAAAAAAAQAAIAAAAAAAAAAAAAAAAAAAQAAAJdAB4P/g/+AB4AAgAAAAAQAAAAAAAAAAAAAAIAAAAAAAAgAAAAMAAAAUAAMAAQAAABQABABAAAAADAAIAAIABAABACAlyyXQ//3//wAAAAAAICXLJc///f//AAH/49o52jYAAwABAAAAAAAAAAAAAAAAAAEAAf//AA8AAQAAAAEAAOJjA1tfDzz1AAsCAAAAAADP/ne2AAAAAM/+d7YAAP/tAgAB0wAAAAgAAgAAAAAAAAABAAAB4P/gAAACAAAAAAACAAABAAAAAAAAAAAAAAAAAAAABwAAAAAAAAAAAAAAAAEAAAACAAAAAgAAAAIAAAAAAFAAAAcAAAAAAA4ArgABAAAAAAABABYAAAABAAAAAAACAA4AYwABAAAAAAADABYALAABAAAAAAAEABYAcQABAAAAAAAFABYAFgABAAAAAAAGAAsAQgABAAAAAAAKADQAhwADAAEECQABABYAAAADAAEECQACAA4AYwADAAEECQADABYALAADAAEECQAEABYAcQADAAEECQAFABYAFgADAAEECQAGABYATQADAAEECQAKADQAhwBzAHQAYQByAC0AcgBhAHQAaQBuAGcAVgBlAHIAcwBpAG8AbgAgADEALgAwAHMAdABhAHIALQByAGEAdABpAG4AZ3N0YXItcmF0aW5nAHMAdABhAHIALQByAGEAdABpAG4AZwBSAGUAZwB1AGwAYQByAHMAdABhAHIALQByAGEAdABpAG4AZwBGAG8AbgB0ACAAZwBlAG4AZQByAGEAdABlAGQAIABiAHkAIABJAGMAbwBNAG8AbwBuAC4AAAAAAwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA==) format('woff');
				font-weight:normal;
				font-style:normal;
			}
			.rating {
				font-family:star-rating;
				color: orange;
			}
			chart-area {
				-moz-user-select: none;
				-webkit-user-select: none;
				-ms-user-select: none;
			}
			canvas {
				-moz-user-select: none;
				-webkit-user-select: none;
				-ms-user-select: none;
			}
		</style>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/navigation_bar.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/links.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/body.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/pagination.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/buttons.css" />
		<link rel = "stylesheet" type = "text/css" href = "https://consigliaviaggi.altervista.org/wp-content/styles/graph.css" />
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
		<script src="https://www.chartjs.org/dist/2.9.3/Chart.min.js"></script>
		<script src="https://www.chartjs.org/samples/latest/utils.js"></script>
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
			<a class="active" href="https://consigliaviaggi.altervista.org/statistics">STATISTICHE</a>
			<a class="last" href="https://consigliaviaggi.altervista.org" onclick="return logout();">Esci</a>
		</div>
		<div class="container">
			<?php
				$database = openDatabase();
				if ($database)
					showGeneralStatistics($database);
			?>
		</div>
	</body>
</html><?php } ?>