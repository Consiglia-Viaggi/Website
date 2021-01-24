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
	function getTot($result) {
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
			echo '<a id="'.$previousPage.'" href="https://consigliaviaggi.altervista.org/statistics_users?_page=1">&laquo;</a>';
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
				echo '<a id="'.$i.'" href="https://consigliaviaggi.altervista.org/statistics_users?_page=' . $i .'">'. $i .'</a>';
		}
		if ($nextPage <= $maxPage)
			echo '<a id="'.$maxPage.'" href="https://consigliaviaggi.altervista.org/statistics_users?_page=' . $maxPage .'">&raquo;</a>';
		//echo '</div></div>';
		echo '</div>';
	}
	function showSearchBar() {
		?>
			<form onsubmit="return searchUser()">
			<input type="text" id="search" name="search" style="border-radius: 12px; outline: none; background-color: white; width:40%; padding: 10px;" placeholder="Cerca utente..."/>
			</form>
			<script>
				function searchUser() {
					var input = document.getElementById("search");
					input.value = input.value.replace(/[^a-z 0-9.,?]/gi , "" );
					input.value = ('<script />').text(input.value).html();
					input.value = ('<div />').text(input.value).html();
					if (input.value.indexOf("/") == 0)
						return false;
					window.location.href = 'https://consigliaviaggi.altervista.org/statistics_users?search=' + input.value;
					return false;
				}
			</script>
		<?php
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
		$query = "SELECT * FROM REVIEWS WHERE ID_USER = ". $id;
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
	function showGraph($array, $username, $year) {
		$title = "'Recensioni di " . $username ." nel ". $year ."'";
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
	function showUserStatistics($db) {
		$id = $_GET['id'];
		$query = "SELECT * FROM USERS WHERE ID = ". $id;
		if ($res = $db->query($query)) { // EXEC THE QUERY.
			$row = $res->fetchArray();
			$username = $row['USERNAME'];
			$date = $row['LASTSEEN_DATE'];
			$reviewsOfTheDay = getTotStatistics($db, "SELECT * FROM REVIEWS WHERE ID_USER = ". $id, "DATE_PUBLICATION");
			$totalReviews = getTotStatistics($db, "SELECT * FROM REVIEWS WHERE ID_USER = ". $id, NULL);
			$approvedReviews = getTotStatistics($db, "SELECT * FROM REVIEWS WHERE IS_APPROVED = 1 AND ID_USER = ". $id, NULL);
			$average = getAverage($db, "SELECT * FROM REVIEWS WHERE IS_APPROVED = 1 AND ID_USER = ". $id, "RATING");
			//$average = 4.5;
			echo '<h2>Statistiche di ' . $username . '</h2>';
			echo '<div style="display:inline-flex;">';
			echo '<div style="width:150%;">';
			echo '<p>Ultimo accesso: ' . $date . '<br><br>';
			echo 'Recensioni del giorno: '. $reviewsOfTheDay . '<br>';
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
			showGraph(getReviewsPoints($db, $year), $username, $year);
			addOnLoadFunction();
		}
	}
	function getSearchTerm() {
		if (!empty($_GET)) {
			if (isset($_GET['search'])) {
				$search = $_GET['search'];
				$search = trim($search);
				if ((strstr($search, "<div") != NULL) || (strstr($search, "</div>") != NULL) || (strstr($search, "script") != NULL))
					return "";
				return $search;
			}
		}
		return "";
	}
	function showUsers($db) {
		$query = "SELECT * FROM USERS";
		$isSearchMode = isset($_GET['search']);
		$valueToSearch = '';
		if ($isSearchMode) {
			$valueToSearch = getSearchTerm();
			$query = "SELECT * FROM USERS WHERE USERNAME LIKE '%{$valueToSearch}%'";
		}			
		$hasRows = FALSE;
		if ($res = $db->query($query)) { // EXEC THE QUERY.
			$tot = getTot($res);
			echo '<center>';
			echo '<h2>Statistiche per utente</h2>';
			if ($tot >  0) {
				showSearchBar();
				$rowsForPage = 10;
				$maxPages = calculatePages($tot, $rowsForPage);
				$page = 1;
				if (isset($_GET['_page']))
					$page = $_GET['_page'];
				$page = getCorrectPage($page, $maxPages);
				$whereToStart = 0;
				if ($page != 1)
					$whereToStart = ($page-1) * $rowsForPage;
				//echo "DEBUG DATA:<br>Ci sono ". $tot ." utenti, in ogni pagina dovrebbero esserci ". $rowsForPage ." utenti, la pagina attuale è la ". $page ." e la pagina massima è la ". $maxPages .".<br><br>";
				echo "<table style='table-layout: fixed; width: 40%;'>"; // START CREATING THE TABLE UI.
				echo "<tr>"; // START CREATING THE TABLE ROW UI.
				echo "<td style='background: #eeeeee; border: 1px solid #ddd; padding: 8px; -webkit-user-select: none; -khtml-user-select: none;-moz-user-select: none; -o-user-select: none; user-select: none;'>USERNAME</td>";
				echo "</tr>";
				$printedRows = 0;
				$j = 0;
				while ($row = $res->fetchArray()) {
					$j++;
					if ($j > $whereToStart) {
						$printedRows++;
						echo "<tr>"; // START CREATING THE TABLE ROW UI.
						$id = $row['ID'];
						foreach ($row as $key => $value) {
							if (!is_numeric($key) && (strcmp($key, "USERNAME") == 0)) {
								$hasRows = TRUE;
								echo '<td style="border: 1px solid #ddd; padding: 8px;"><a href="https://consigliaviaggi.altervista.org/statistics_users?id='. $id.'">'.$value.'</a></td>'; // PRINT THE VALUE.
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
			echo "<center><h2>Nessun utente registrato.</h4></center>";
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
			<?php
				$database = openDatabase();
				if ($database) {
					if (isset($_GET['id'])) {
						echo '<div class="container">';
						showUserStatistics($database);
						echo '</div>';
					}
					else
						showUsers($database);
				}
			?>
	</body>
</html><?php } ?>