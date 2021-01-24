<?php
	$isDebugMode = 0;
	function openHtml() {
		?>
			<!DOCTYPE html>
			<html>
				<head>
					<style>
						table {
							border-collapse: collapse;
						}
						table, th, td {
							border: 1px solid black;
							padding: 5px;
						}
					</style>
				</head>
			<body>
		<?php
	}
	function closeHtml() {
		?>
			</body>
		</html>
		<?php
	}
	function messagesWithCondition($text1, $text2, $condition) {
		if ($GLOBALS['isDebugMode'] == 1) { // IF I'M USING THE DEBUG MODE
			if ($condition) // IF THE CONDITION IS TRUE
				echo $text1 . "<br>"; // SHOW THE FIRST TEXT
			else if (($text2) && (strlen($text2))) // OTHERWISE IF THE SECOND TEXT IS NOT NULL AND IT'S NOT EMPTY..
				echo $text2 . "<br>"; // SHOW THE SECOND TEXT.
		}
		return $condition;
	}
	function message($text) {
		if ($GLOBALS['isDebugMode'] == 1) // IF I'M USING THE DEBUG MODE
			echo $text . "<br>"; // SHOW THE TEXT.
	}
	function getFieldFromQuery($db, $query, $field) {
		if ($res = $db->query($query)) // EXEC THE QUERY.
			while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
				return $row[$field]; // RETURN THE VALUE FOR THE FIELD.
		return NULL;
	}
	function getStringFromArray($array) {
		$count = count($array); // GET THE SIZE OF THE ARRAY.
		if ($count == 0) // IF THE ARRAY IS EMPTY..
			return "empty array"; // RETURN THIS STRING.
		$str = ""; // INIT THE STRING.
		for ($i = 0; $i < $count; $i++) {
			$str = $str . $array[$i];
			if ($i + 1 == $count) // IF THIS IS THE LAST STRING..
				$str = $str . "."; // SET A DOT.
			else // OTHERWISE
				$str = $str . ", "; // SET A COMMA
		}
		return $str; // RETURN THE STRING.
	}
	function updateValueInTableForID($db, $table, $field, $value, $id) {
		$query = "UPDATE ". $table ." SET ". $field ." = ". $value ." WHERE ID = ". $id; // COMPOSE THE QUERY
		$result = $db->query($query); // EXEC THE QUERY
		messagesWithCondition("The update operation was correctly executed.", "An error occurred while executing the update operation.", $result);
		return $result;
	}
	function getMoreFieldsFromQuery($db, $query, $field) {
		$array = array(); // INIT THE ARRAY.
		if ($res = $db->query($query)) // EXEC THE QUERY.
			while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
				$array[] = $row[$field]; // SAVE THE VALUE FOR THE SAME COLUMN.
		return $array;
	}
	function createDatabase() {
		$dbExists = file_exists('database.sqlite');
		$database = $dbExists ?  new SQLite3('database.sqlite', SQLITE3_OPEN_READWRITE) : new SQLite3('database.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
		//messagesWithCondition("Database created/opened.", "Impossible to create/open the database.", $database);
		return $database;
	}
	function getColumnsFromResult($res) {
		$array = array(); // INIT THE ARRAY
		$row = $res->fetchArray(SQLITE3_ASSOC); // GET METADATA
		foreach($row as $key => $value)
			$array[] = $key; // SAVE THE COLUMN NAME.
		return $array; // RETURN ALL COLUMNS NAMES.
	}
	function showTableUsingQuery($db, $query) {
		if ($res = $db->query($query)) { // EXEC THE QUERY..
			$columns = getColumnsFromResult($res); // GET ALL COLUMNS NAMES FOR THE QUERY.
			$res = $db->query($query); // THE CURSOR IS CHANGED! WE NEED TO REPEAT THE QUERY.
			echo "<table>";
			echo "<tr>";
			$count = count($columns);
			for ($i = 0; $i < $count; $i++)
				echo "<td>". $columns[$i] ."</td>"; // PRINT COLUMNS NAME.
			echo "</tr>";
			while ($row = $res->fetchArray()) {
				echo "<tr>";
				foreach ($row as $key => $value) {
					if (is_numeric($key)) {
						echo "<td>". $value."</td>";
					}
				}
				echo "</tr>";
			}
			echo "</table><br>";
		}
	}
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
	function removeNotNumericIndexes($array) {
		foreach ($array as $key => $value) {
			if (is_numeric($key))
				unset($array[$key]);
		}
		return $array;
	}
	function getArrayFromQuery($db, $query) {
		$array = array();
		$result = $db->query($query); // EXEC THE QUERY.
		if ($result) {
			while ($row = $result->fetchArray())
				$array[] = removeNotNumericIndexes($row);
		}
		return $array;
	}
	function tableExists($db, $table) {
		$query = "SELECT name FROM sqlite_master WHERE type='table' AND name='".$table."';"; // COMPOSE THE TABLE.
		$result = $db->query($query); // EXEC THE QUERY.
		if ($result)
			while ($row = $result->fetchArray()) // IF I'M ITERATING THE RESULTS..
				return TRUE; // IT MEANS THE TABLE EXISTS, RETURN TRUE.
		return FALSE; // IF I'M HERE, IT MEANS THE TABLE DOESN'T EXIST. RETURN FALSE.
	}
	function isTableEmpty($db, $table) {
		if (tableExists($db, $table)) { // IF THE TABLE IS EMPTY..
			if ($res = $db->query("SELECT * FROM " . $table)) // EXEC THE QUERY TO GET ALL ITS CONTENT.
				while ($row = $res->fetchArray()) // IF I'M ITERATING THE RESULTS..
					return FALSE; // IT MEANS THE TABLE IS NOT EMPTY, RETURN FALSE.
		}
		return TRUE; // IF THE TABLE DOES NOT EXIST OR I DIDN'T ITERATE THE RESULTS, IT MEANS THE TABLE DOESN'T EXIST OR IT IS EMPTY: RETURN TRUE.
	}
	function createReviewsTable($db) {
		message("The table REVIEWS does not exist. Starting to populate the database..");
		$result = $db->query("CREATE TABLE REVIEWS(ID INTEGER UNIQUE, ID_USER INTEGER NOT NULL, ID_ARTICLE INTEGER NOT NULL, TITLE TEXT NOT NULL, DESCRIPTION TEXT NOT NULL, RATING INT NOT NULL, DATE_PUBLICATION DATE NOT NULL, IS_APPROVED INT NOT NULL, PRIMARY KEY(ID))"); // CREATE THE TABLE.
		if ($result) { // IF THE TABLE WAS SUCCESSFULLY CREATED, INSERT TWO ROWS.
			$result1 = $db->query("INSERT INTO REVIEWS VALUES(1, 1, 1, 'Good!', 'I liked this! I will choose it again soon.', 5, '2020-05-11 10:22:34', 0)"); // year / month / day
			$result2 = $db->query("INSERT INTO REVIEWS VALUES(2, 2, 1, 'Amazing!', 'I had no idea it was so funny! But the waiter was arrogant.', 4, '2020-05-10 16:31:59', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(3, 2, 2, 'So bad', 'I do not recommend this place. Really.', 1, '2020-05-04 22:12:37', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(4, 2, 3, 'Non male', 'Posto accoglievole, ci ritornerei per un weekend.', 3, '2020-06-10 10:30:50', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(5, 2, 3, 'Il paradiso!', 'Sicuro sarà la mia meta per la prossima estate!', 5, '2020-08-10 16:31:59', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(6, 2, 4, 'Sconsigliatissimo', 'Norme igieniche totalmente assenti, ho beccato il covid19 proprio qui.', 1, '2020-06-03 21:11:57', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(7, 4, 4, 'Consigliato', 'Cibo ok.', 4, '2020-04-04 14:17:27', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(8, 4, 5, 'Mah', 'Sinceramente mi aspettavo di meglio. Le foto mi hanno illuso parecchio.', 2, '2020-05-06 23:22:40', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(9, 3, 6, 'NON ANDATECI.', 'Il cibo ha causato dei problemi intestinali a tutta la famiglia!', 1, '2020-03-03 15:30:23', 0)");
			$result3 = $db->query("INSERT INTO REVIEWS VALUES(10, 4, 7, 'Posto pulito', 'Ero abbastanza preoccupato inizialmente, ma poi mi sono subito ambientato. Bel posto dove trascorrere le vacanze.', 1, '2020-08-04 19:10:33', 0)");
			return messagesWithCondition("The Reviews table was populated.", "An error occurred while populating the database.", $result1 && $result2 && $result3);
		}
		message("Impossible to create the table REVIEWS.");
		return FALSE;
	}
	function populateDatabase($db) {
		message("The table USERS is empty. Starting to populate the database..");
		$result = $db->query("CREATE TABLE USERS(ID INTEGER AUTO_INCREMENT, NAME TEXT NOT NULL, SURNAME TEXT NOT NULL, USERNAME TEXT NOT NULL UNIQUE, PASSWORD TEXT NOT NULL, MAIL TEXT NOT NULL, ADMIN INT NOT NULL, REGISTRATION_DATE NOT NULL, LASTSEEN_DATE NOT NULL, PRIMARY KEY(ID))"); // CREATE THE TABLE.
		if ($result) { // IF THE TABLE WAS SUCCESSFULLY CREATED, INSERT TWO ROWS.
			$date = date("Y-m-d H:i:s");
			//Query for admin. Type: $result1 = $db->query("INSERT INTO USERS(ID, NAME, SURNAME, USERNAME, PASSWORD, MAIL, ADMIN, REGISTRATION_DATE, LASTSEEN_DATE) VALUES(1, '', '', '', '', '', 1, '". $date ."', '". $date."')");
			
			return messagesWithCondition("The database was populated", "An error occurred while populating the database.", $result1 && $result2 && $result3 && $result4 && $result5 && $result6);
		}
		message("Impossible to create the table USER.");
		return FALSE;
	}
	function createServices($db) {
		$result = $db->query("CREATE TABLE SERVICES(IDSTRUCTURE INTEGER NOT NULL, ANIMALS INT NOT NULL, PARKING INT NOT NULL, WIFI INT NOT NULL, DISABLE_ACCESS INT NOT NULL, SWIMMING_POOL INT NOT NULL, VIEW INT NOT NULL, SMOKING_AREA INT NOT NULL, CHILD_AREA INT NOT NULL, PRIMARY KEY(IDSTRUCTURE))"); // CREATE THE TABLE.
		if ($result) { // IF THE TABLE WAS SUCCESSFULLY CREATED...
			$result1 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(1, 1, 1, 1, 1, 0, 1, 1, 0)"); // ALBERGO		
			$result2 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(2, 0, 1, 1, 0, 0, 0, 1, 1)"); // RISTORANTE DA PEPPE
			$result3 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(3, 0, 1, 0, 1, 1, 0, 0, 0)"); // RELAX DA SONIA
			$result4 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(4, 1, 1, 0, 1, 0, 0, 0, 1)"); // LA NATURA
			$result5 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(5, 0, 1, 1, 1, 0, 0, 0, 0)"); // DORMI BENE
			$result6 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(6, 1, 1, 0, 1, 1, 1, 0, 1)"); // LA PLAYA DEL SOL
			$result7 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(7, 0, 1, 1, 0, 0, 0, 1, 0)"); // AMMAZZACAFE		
			$result8 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(8, 1, 1, 1, 0, 0, 0, 1, 1)"); // Hyatt Caffe		
			$result9 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(9, 1, 0, 1, 1, 1, 1, 0, 0)"); // Moxy Caffe		
			$result10 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(10, 1, 0, 1, 0, 1, 0, 1, 1)"); // Fairfield Camping		
			$result11 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(11, 0, 0, 0, 1, 0, 1, 1, 0)"); // Embassy Hotel		
			$result12 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(12, 1, 0, 0, 1, 1, 1, 0, 1)"); // Holiday Park		
			$result13 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(13, 0, 0, 0, 1, 0, 0, 1, 0)"); // Sheraton Hotel		
			$result14 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(14, 0, 1, 0, 0, 1, 1, 0, 1)"); // Villaggio Robinson		
			$result15 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(15, 0, 1, 0, 1, 0, 0, 1, 0)"); // Villaggio Rosa Del Sole		
			$result16 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(16, 0, 0, 1, 0, 0, 1, 0, 1)"); // Villaggio Torre Ruffa		
			$result17 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(17, 0, 0, 1, 1, 1, 0, 1, 0)"); // Centro Benessere Roby		
			$result18 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(18, 1, 0, 1, 0, 0, 1, 1, 1)"); // Relax e Relex		
			$result19 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(19, 0, 1, 1, 1, 1, 1, 0, 1)"); // Ippodromo	
			
			$result20 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(20, 0, 1, 1, 1, 1, 1, 0, 1)");
			$result21 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(21, 0, 1, 1, 1, 1, 1, 0, 0)");
			$result22 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(22, 1, 1, 0, 0, 0, 1, 0, 1)");
			$result23 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(23, 1, 1, 0, 0, 0, 1, 1, 1)");
			$result24 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(24, 0, 1, 0, 1, 0, 1, 1, 0)");
			$result25 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(25, 0, 1, 1, 0, 0, 0, 0, 0)");
			$result26 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(26, 1, 1, 1, 1, 1, 0, 0, 1)");
			$result27 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(27, 1, 1, 1, 0, 1, 0, 0, 1)");
			$result28 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(28, 0, 0, 1, 0, 1, 1, 1, 0)");
			$result29 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(29, 1, 1, 1, 0, 0, 0, 0, 0)");
			$result30 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(30, 0, 1, 1, 1, 0, 0, 0, 0)");
			$result31 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(31, 0, 1, 1, 1, 1, 0, 0, 1)");
			$result32 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(32, 0, 0, 0, 1, 0, 0, 0, 0)");
			
			$result33 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(33, 0, 1, 1, 1, 1, 0, 0, 1)"); // I commenti sono odiosi
			$result34 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(34, 0, 0, 1, 1, 1, 0, 0, 1)"); 
			$result35 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(35, 1, 1, 0, 0, 1, 1, 0, 0)"); 
			$result36 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(36, 1, 1, 1, 0, 1, 1, 0, 1)"); 
			$result37 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(37, 1, 0, 1, 0, 1, 0, 0, 1)"); 
			$result38 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(38, 0, 0, 0, 1, 1, 1, 0, 0)"); 
			$result39 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(39, 1, 0, 1, 0, 1, 0, 0, 1)"); 
			$result40 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(40, 0, 1, 1, 1, 1, 1, 0, 1)"); 
			$result41 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(41, 1, 1, 0, 1, 1, 0, 0, 0)"); 
			$result42 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(42, 0, 0, 0, 1, 1, 0, 0, 1)"); 
			$result43 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(43, 0, 1, 1, 0, 1, 1, 0, 1)"); 
			$result44 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(44, 0, 0, 0, 1, 1, 0, 0, 0)"); 
			$result45 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(45, 1, 1, 1, 1, 1, 1, 0, 0)"); 
			$result46 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(46, 0, 0, 0, 1, 1, 0, 0, 1)"); 
			$result47 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(47, 1, 0, 1, 0, 1, 1, 0, 1)"); 
			$result48 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(48, 0, 0, 0, 1, 1, 0, 0, 1)"); 
			$result49 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(49, 1, 0, 0, 1, 1, 1, 0, 0)"); 
			$result50 = $db->query("INSERT INTO SERVICES(IDSTRUCTURE, ANIMALS, PARKING, WIFI, DISABLE_ACCESS, SWIMMING_POOL, VIEW, SMOKING_AREA, CHILD_AREA) VALUES(50, 0, 1, 1, 1, 1, 1, 0, 0)"); 

			return messagesWithCondition("The database was populated", "An error occurred while populating the database.", $result1 && $result2 && $result3 && $result4 && $result5 && $result6 && $result7);
		}
		message("Impossible to create the table USER.");
		return FALSE;
	}
	function createStructures($db) {
		$result = $db->query("CREATE TABLE STRUCTURES(IDSTRUCTURE INTEGER NOT NULL, NAME TEXT NOT NULL, TYPE INT NOT NULL, DESCRIPTION TEXT NOT NULL, ADDRESS TEXT NOT NULL, PHONENUMBER TEXT NOT NULL, PRICE_LEVEL INT, LATITUDE DECIMAL(10, 8) NOT NULL, LONGITUDE DECIMAL(11, 8) NOT NULL, PRIMARY KEY(IDSTRUCTURE))"); // CREATE THE TABLE.
		if ($result) { // IF THE TABLE WAS SUCCESSFULLY CREATED...
			$result1 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(1, 'Albergo Il Geraneo', 1, 'Il geraneo è il posto migliore dove trascorrere le tue vacanze.', 'Via Luigi Pareyson, 13', '0817000001', 3, 40.904825, 14.238832)");
			$result2 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(2, 'Ristorante Da Peppe', 3, 'Da Peppe solo cibo di alta qualità.', 'Via Fratelli Cervi', '0817000002', 2, 40.907279, 14.239916)");
			$result3 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(3, 'Relax da Sonia', 6, 'Il Relax da Sonia è una struttura unica nel suo genere. Il posto migliore dove rilassarti per allontanare i pensieri.', 'Corso Europa, 425', '0817000003', 1, 40.912342, 14.234698)");
			$result4 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(4, 'La Natura', 2, 'Campeggio assortito di servizi', 'Via Giacomo Brodolini', '0817000004', 1, 40.909593, 14.221716)");
			$result5 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(5, 'DormiBene', 5, 'Pulizia e convenienza sono le caratteristiche che descrivono il nostro B&B.', '80018 Mugnano di Napoli NA', '0817000005', 2, 40.912610, 14.218025)");
			$result6 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(6, 'La Playa Del Sol', 4, 'Divertiti e passa una giornata diversa nella nostra struttura grazie al nostro assortimento di piscine!', '80018 Mugnano di Napoli NA', '0817000006', 2, 40.910461, 14.207833)");
			$result7 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(7, 'AmmazzaCafe', 0, 'Luogo confortevole dove trascorrere del tempo con amici e parenti.', '80018 Mugnano di Napoli NA', '0817000007', 1, 40.909239, 14.203664)");	

			$result8 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(8, 'Hyatt Caffe', 0, 'Le migliori degustazioni che potete mai assaggiare. Il nostro personale è sempre pronto per soddisfare ogni vostra richiesta. Venite a trovarci per ulteriori informazioni. Disponibili anche per feste.', '73048, Sant Isidoro LE', '0846574433', 3, 40.213705, 17.924638)");
			$result9 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(9, 'Moxy Caffe', 0, 'Perché fermarsi a bere solo un semplice caffè, Moxy Caffè va ben oltre con una serie di pietanze da leccarsi i baffi. Provare per credere!', '84043 Agropoli SA', '3334754643', 1, 40.351594, 14.986384)");
			$result10 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(10, 'Fairfield Camping', 2, 'Niente di meglio della nostra accoglienza al nostro Fairfield. Ogni camera è strutturata su un pezzo di storia architettonica. Siamo aperti 24h per il servizio bar e ristoro.', '73048 Nardo LE', '3374612323', 2, 40.237567, 17.962708)");
			$result11 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(11, 'Embassy Hotel', 1, 'Il nostro Hotel è a tua disposizione per soste veloci o intere settimane. Vedrai che non riuscirai più ad andartene. La caratteristica delle nostre camere sono la pulizia, amore a prima vista.', 'Via Per Uggiano, 19, 74024 Manduria TA', '0818493743', 3, 40.397473, 17.635626)");
			$result12 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(12, 'Holiday Park', 0, 'Per quanto strutturato vicino ad una città sempre in pieno ritmo, Holiday Park dispone di una sua armonia interiore. Al suo interno numerosi giochi per bambini e scoiattoli giocherelloni.', 'Vecchia Comunale Manduria, Oria, 74024 Manduria TA', '0818490909', 2, 40.407855, 17.637514)");
			$result13 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(13, 'Sheraton Hotel', 1, 'Hotel raffinato nel cuore della città. È possibile prenotare per feste o cene a lume di candela. Sorprendi la tua metà con il nostro menù dei piatti locali più prelibati.', 'Strada Provinciale Andriace, 75023 Montalbano Jonico MT', '3339343234', 2, 40.302277, 16.632623)");
			$result14 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(14, 'Villaggio Robinson', 4, 'Il villaggio vacanze più folle del mondo. Per bambini, ragazzi e genitori che vogliono rilassarsi. Le nostre piscine sono sempre aperte ed ogni camera gode di una vista mozzafiato. Nel mese di Agosto si organizzano feste e cene in spiaggia.', '75015 Pisticci MT', '0907435756', 1, 40.289566, 16.776866)");
			$result15 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(15, 'Villaggio Rosa Del Sole', 4, 'Villaggio dotato di tutte le attrezzature sportive per chi è amante degli sport. Oltre alle piscine e al centro messaggi ogni mese uno chef stellato verrà a proporre le sue pietanze più prelibate.', '87067 Centrale Enel CS', '0937463423', 1, 39.624102, 16.604432)");
			$result16 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(16, 'Villaggio Torre Ruffa', 4, 'Il nostro è un villaggio di maestose costruzioni in legno. Ognuno più totalmente isolarsi per godersi la vacanza nella più totale tranquillità. Ogni sera nella piazza principale del villaggio saranno presenti animazioni e intrattenimenti per tutti.', ' Don Antonio Marra, 24, 88050 Sellia Marina CZ', '3334546784', 2, 38.891545, 16.761946)");
			$result17 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(17, 'Centro Benessere Roby', 6, 'Il relax è ciò di cui tutti abbiamo bisogno. Qui potrai staccare la mente e lasciarsi andare nella calma e pace. Dedica del tempo a te stesso e prova le nostre cure per il tuo corpo.', 'Via Roma, 75-61, 87018 San Marco Argentano CS', '0815866333', 3, 39.557901, 16.117274)");
			//$result18 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(18, 'Relax e Relex', 6, 'Il posto adatto per trascorrere momenti di pace e senerità.', 'Via Vittorio Emanuele II, 157, 87017 Roggiano Gravina CS', '3458743645', 39.616453, 16.162146)");
			$result19 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(19, 'Ippodromo', 0, 'Ippodromo riqualificato nel 2008.', 'Via G. Marconi, 104, 85100 Potenza PZ', '3473465789', 1, 40.635395, 15.803986)");
			
			$result20 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(20, 'Hotel Weber Ambassador', 1, 'Weber Ambassador, hotel a 4 stelle, si affaccia sulla baia di Marina Piccola e sui Faraglioni e offre gratuitamente un servizio navetta da-per il centro di Capri, compreso completo accesso alle sue 3 piscine, a 2 vasche idromassaggio e al suo centro fitness.', 'Via Marina Piccola 118, 80073 Capri, Italia', '0817000020', 3, 40.5457244,14.2333733)");
			$result21 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(21, 'Hotel Continental', 1, 'Dotato di vista panoramica sul Golfo di Napoli e sul Vesuvio, Hotel Continental a 4 stelle occupa una posizione centrale a 50 metri dal lungomare di Sorrento e vanta una piscina esterna e la connessione Wi-Fi gratuita in tutta la struttura. Tutte spaziose, le camere includono aria condizionata, una TV LCD, un bagno interno e, nella maggior parte dei casi, un balcone o una terrazza, talvolta con vista sul Mar Tirreno. La struttura mette a vostra disposizione la connessione WiFi. Le camere climatizzate del Continental Hotel sono arredate con mobili in legno e comprendono una TV satellitare a schermo piatto e un bagno interno con accappatoi e asciugacapelli. La maggior parte vantano un balcone e alcune si affacciano sul Mar Mediterraneo.', 'Piazza Della Vittoria, 4, 80067 Sorrento', '0817000021', 3, 40.6271031,14.3681022)");
			$result22 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(22, 'LA VOLPE DI AGROPOLI', 2, 'Situato ad Agropoli, il bed & breakfast LA VOLPE DI AGROPOLI offre un bar e la connessione WiFi gratuita. Al mattino vi attende una colazione italiana. Potrete inoltre rilassarvi nel giardino. Salerno dista 42 km dal campeggio. 84 km da Aeroporto Internazionale di Napoli, lo scalo più vicino.', 'Via belvedere 136 133, 84043 Agropoli', '0817000022', 1, 40.3412164,14.9780686)");
			$result23 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(23, 'Vena dei Corvi Ranch', 2, 'Situato a Roccagloriosa, il Vena dei Corvi Ranch offre un bar. Potrete rilassarvi nel giardino della struttura. Agropoli dista 50 km dal campeggio.', 'Via dei Pioppi snc Presso Oasi degli Scudieri, 84060 Roccagloriosa', '0817000023', 1, 40.669871,14.826591)");
			$result24 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(24, 'Villa Riflesso', 3, 'In riva al lago Fusaro, nel cuore dei Campi Flegrei, una splendida location per ogni evento speciale. Immersa nella terra del mito, perfetto per rendere indimenticabile il vostro giorno più bello.Circondati dalle meraviglie della natura Flegrea, con la Casina Vanvitelliana sullo sfondo, celebrare le nozze in penisola renderà ancora più magico il vostro matrimonio.', 'Via Cuma 97 80070, Bacoli', '0817000024', 2, 40.8302688,14.0526542)");
			$result25 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(25, 'Agriturismo Ulivo', 3, 'Situato a Padula, a 36 km da Viggiano, Agriturismo Ulivo offre un giardino. Le sistemazioni sono dotate di patio, TV a schermo piatto e bagno privato con doccia e asciugacapelli. Ogni mattina vi attende una colazione a buffet. Il nostro agriturismo ospita un ristorante di cucina italiana. In loco troverete anche una terrazza. Presso il nostro agriturismo troverete un barbecue e un salotto in comune.', 'Via Provinciale 51b, 84034 Padula', '0817000025', 1, 40.32727,15.6617753)");
			$result26 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(26, 'Resort Baia del Silenzio', 4, 'Il Resort Baia del Silenzio di Pisciotta, circondato dal Parco Nazionale del Cilento, Vallo di Diano e Alburni, offre accesso gratuito alla spiaggia privata, una piscina e un centro fitness. Ogni mattina gusterete una colazione dolce a buffet, a base di torte, biscotti e bevande calde e fredde, e il ristorante propone un menù alla carta di specialità locali.', 'Via Palinuro 2 , 84066 Pisciotta', '0817000026',2, 40.083022,15.2597523)");
			$result27 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(27, 'Nautilus village', 4, 'Situato a Castel Volturno, a 27 km da Pozzuoli, il Nautilus Village offre un ristorante. Il bagno privato è completo di bidet, asciugacapelli e set di cortesia. Al mattino vi attende una colazione continentale o a buffet. Il Nautilus Village ospita anche area giochi per bambini. La struttura vanta una piscina esterna, un miniclub e un salone in comune.', 'Mezzagni, Castel Volturno CE, 81030 Castel Volturno','0817000027',2, 41.0034835,13.9732731)");
			$result28 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(28, 'Relais Della Porta', 5, 'Situato a Napoli, a pochi passi dalle famose attrazioni, tra cui il Teatro San Carlo, il Molo Beverello, San Gregorio Armeno e il Palazzo Reale, il Relais Della Porta dista 9 minuti a piedi dal Maschio Angioino, e dispone di un salone in comune. Tutte le camere del Relais Della Porta presentano un bagno privato con set di cortesia e asciugacapelli e una TV a schermo piatto.', 'Via Toledo 368,Centro storico di Napoli, 80134 Napoli','0817000028',2, 40.8449902,14.2467317)");
			$result29 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(29, 'Royal Gardens', 5, 'Situato a pochi passi dalla Reggia di Caserta e a 1,1 km dalla Seconda Università degli Studi di Napoli, il ROYAL GARDENS offre sistemazioni con connessione WiFi gratuita, aria condizionata e TV a schermo piatto.', 'Via Raffaele Gasparri 6, 81100 Caserta','0817000029',3,41.0724239,14.3274817)");
			$result30 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(30, 'Il Tesoro Smart Suite & SPA', 6, 'Situato nel centro storico di Napoli, Il Tesoro Smart Suite & SPA si trova a 1,1 km da San Gregorio Armeno, a 1,3 km dal Museo Archeologico Nazionale di Napoli e a 1,5 km dalle Catacombe di San Gaudioso. Dotato di un salone in comune, questo hotel a 4 stelle dispone di camere climatizzate con connessione WiFi gratuita e bagno privato. La struttura offre una reception aperta 24 ore su 24, il servizio in camera e organizzazione di visite guidate.', '228 Via Duomo, 80138 Napoli','0817000030',2,40.8504964,14.2577777)");
			$result31 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(31, 'Villa Araucária', 6, 'Situata a metà strada tra Casamicciola Terme e Ischia, a 5 km dalla Baia di San Montano, la Villa Araucária offre una piscina esterna, un bar. Le Terme di Castiglione sono raggiungibili in 10 minuti a piedi. Tutte le camere sono dotate di TV satellitare a schermo piatto e bagno privato con doccia idromassaggio, set di cortesia e asciugacapelli.', 'Via Castiglione 43, Casamicciola Terme, 80074 Ischia','0817000031',3,40.5481669,13.8287113)");
			$result32 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(32, 'Gran Cafe Gambrinus', 0, 'Storico bar del centro. Potete gustare tutte le specialità dolciare di Napoli. Gusto unico e location storica. ', 'Via Chiaia, 80132 Napoli NA','0817000032',1,40.8298969,14.2408221)");
			
			$result33 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(33, 'La Casaccia', 1, 'Disponiamo di un insieme di camera per chi deve fermasi in città per un paio di giorni. È consigliato chiamare il numero fornito se si soggiorna per più di una settimana in maniera tale da verificare la disponibilità.', 'Via Govone 42, Sempione, 20155 Milano', '0834756478', 2, 45.490597, 9.165926)");
			$result34 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(34, 'Matilde Boutique Hotel', 1, 'Dal 1980 il Matilde Hotel detiene il primato di migliore struttura accogliente. Ogni camera è stata costruita secondo dettami specifici per rispecchiare l’architettura della città intorno.', 'Via Spadari, 1, Milano Centro, 20123 Milano', '4736489098', 2, 45.463826, 45.463826)");
			$result35 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(35, 'Seven', 5, 'Seven dispone di un numero limitato di camera ma tutte complete di ogni servizio. Indipendentemente dalla prenotazione offriamo sempre la colazione gratuita ed un kit di benvenuto contenente i nostri sali del luogo.', 'Via Nazionale 249, Stazione Termini, 00184 Roma', '2384759403', 1, 41.901637, 12.497465)");
			$result36 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(36, 'Camping In Town', 2, 'Il campeggio avanzato a portata di mano. Disposto in un luogo del tutto privo di rumori di città potrai totalmente rilassarti e dedicarti alle tue passioni come la pesca, trekking, barbecue e tanti altri', 'Via Aurelia 831, Aurelio, 00165 Roma', '3334343212', 2, 41.887077, 12.404866)");
			$result37 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(37, 'Residence Alpinum', 0, 'Vicino alle montagne il Residence Alpinum gode di una vista mozzafiato, sia nei periodi estivi che invernali. Qui si possono assaggiare tutte le specialità del posto.', 'SP57, 86010 Ferrazzano CB', '9489037933', 1, 41.523097, 14.661081)");
			$result38 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(38, 'La vie Deluxe', 3, 'Ti catturiamo il palato con i sapori della nostra cucina. Non esiste un menu fisso ma ogni giorno propiniamo un’insieme di nuovi piatti che ti faranno innamorare.', 'SP54, 86010 Cercemaggiore CB', '3849082234', 1, 41.460566, 14.686477)");
			$result39 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(39, 'Roanerhof', 6, 'Le nostre camere dispongono di sauna idromassaggio di ultima tecnologia pronte per ogni tua esigenza. Puoi scegliere tra numerose camere con stili diversi: da architetture moderne al totale legno.', 'Via Filetta, 81035 Vairano Patenora CE', '0858392712', 3, 41.329783, 14.083746)");
			$result40 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(40, 'Appartement Wasserer', 0, 'Il complesso dispone di un unico appartamento costruito per ospitare dino a una ventina di persone. È espressamente richiesto contattare telefonicamente almeno due mesi prima per prenotare.', 'SP283, 81037 Sessa Aurunca CE', '8303749012', 2, 41.198648, 13.882717)");
			$result41 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(41, 'Terme di Simone', 6, 'Aperto durante il periodo invernale, la nostra struttura è pronta ad accogliervi e mettervi subito a vostro agio con i migliori massaggi per il corpo e un ampia scelta di piatti gourmet.', '81030 Falciano del Massico CE', '1107373289', 3, 41.147934, 13.958877)");
			$result42 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(42, 'B&B Magic', 5, 'B&B in centro città. Le camere dispongono di ogni servizio compresa culla per neonati. Grazie alla sua locazione i mezzi sono accessibili a pochi metri.', 'Viale Michelangelo, 204, 81030 Castel Volturno CE', '8390487033', 3, 41.058102, 13.919426)");
			$result43 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(43, 'Cutolo House', 0, 'La struttura piena di storia. Qui puoi distaccarti totalmente dalla città ed ascoltare il suono delle onde.', '81030 Castel Volturno CE', '4290874209', 2, 41.021262, 13.930810)");
			$result44 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(44, 'Dimora Del Dragone', 0, 'La dimora del dragone gode di una vasta scelta di vini per soddisfare ogni palato. Possibile anche ammirare la maestosità della pietra di agata e mangiare in tutta tranquillità.', 'SP289, 81053 Riardo CE', '4809275978', 3, 41.265536, 14.158904)");
			$result45 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(45, 'Il Trappeto', 6, 'Il relax è ha portata di mano. Immergiti in acque termali nella nostra struttura costruita dentro la più antica grotta delle città.', 'SP3, 71035 Celenza Valfortore FG', '0482073245', 1, 41.587540, 14.983634)");
			$result46 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(46, 'La conchiglia', 5, 'La conchiglia è rappresenta un gruppo di strutture costruite come una reale conchiglia. Ideale per soggiorni con amici e parenti.', 'Foggia FG', '8094389080', 1, 41.546897, 15.443415)");
			$result47 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(47, 'Casa Giulia', 0, 'Camera padronali per rendere ogni soggiorno il più bello e confortevole possibile. Fatti coccolare dal nostro staff e prova le delizie del giorno cucinate dai nostri migliori chef.', 'SP240, 618, 06024 Gubbio PG', '4890270328', 2, 43.287258, 12.614219)");
			$result48 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(48, 'Villa Merina', 0, 'Villa per feste e cerimonie. Disponiamo di un intero cast per organizzare anche il più complesso dei matrimoni.', '20270 Aleria, Francia', '8290738095', 3, 42.100836, 9.514942)");
			$result49 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(49, 'Hilton Niagara Falls', 1, 'Eccellenza invidiata da tutti. Ogni camera nasconde una storia, lo staff è disponibile h24 per qualsiasi esigenza e forniamo supporto completo per prenotazioni verso le meraviglie della città.', '6440-6386 Stanley Ave, Niagara Falls, ON L2G 3Y6, Canada', '9582178935', 3, 43.082666, 79.084242)");
			$result50 = $db->query("INSERT INTO STRUCTURES(IDSTRUCTURE, NAME, TYPE, DESCRIPTION, ADDRESS, PHONENUMBER, PRICE_LEVEL, LATITUDE, LONGITUDE) VALUES(50, 'Camping Anelli Booleani', 2, 'Il Camping Anelli Booleani sorge da profonde radici. La sua struttura originale resta tra le più belle di tutta la regione. Ogni camera nasconde una storia, da quale cominciare?', 'Suourland, Islanda', '0982617485', 2, 64.515657, 18.474327)");

			
			return messagesWithCondition("The database was populated", "An error occurred while populating the database.", $result1 && $result2 && $result3 && $result4 && $result5 && $result6 && $result7);
		}
		message("Impossible to create the table USER.");
		return FALSE;
	}
	function createTempPasswordsTable($db) {
		message("The table USERS is empty. Starting to populate the database..");
		$result = $db->query("CREATE TABLE TEMP_PASSWORDS(ID INTEGER NOT NULL, PASSWORD NOT NULL, DATE NOT NULL, PRIMARY KEY(ID))"); // CREATE THE TABLE.
		if ($result) { // IF THE TABLE WAS SUCCESSFULLY CREATED...
			//$result1 = $db->query("INSERT INTO TEMP_PASSWORDS(ID, PASSWORD, DATE) VALUES(1, '', '2020-05-10 16:31:59')");
			return messagesWithCondition("The database was populated", "An error occurred while populating the database.", $result1 && $result2);
		}
		message("Impossible to create the table USER.");
		return FALSE;
	}
	function incrementRatingForID($database, $id) {
		$value = getFieldFromQuery($database, "SELECT * FROM USERS WHERE ID = ". $id, "RATING"); // GET THE VALUE OF RATING FOR THE ID.
		$ratingValue = intval($value); // GET ITS INT VALUE.
		$ratingValue = $ratingValue + 1; // INCREMENT THE VALUE.
		updateValueInTableForID($database, "USERS", "RATING", $ratingValue, $id); // UPDATE THE VALUE IN TABLE FOR THE FIELD FOR ID.
	}
	function test_extractInfo($database) {
		echo "<br>ID FOR 'Tweety' IS: " . getFieldFromQuery($database, "SELECT * FROM USERS WHERE NAME = 'Tweety'", "ID");
		echo "<br>ALL IDs ARE: " . getStringFromArray(getMoreFieldsFromQuery($database, "SELECT * FROM USERS", "ID"));
	}
	function isDebug() {
		if ((!empty($_GET)) && (isset($_GET['token'])))
			return $_GET['token'] == 440;
		return FALSE;
	}
	function getAction() {
		if ((!empty($_GET)) && (isset($_GET['action'])))
			return $_GET['action'];
		return 0;
	}
	return; // NON DEVE MOSTRARE NULLA!
	$action = getAction();
	$database = createDatabase();
	if (isTableEmpty($database, "USERS")) {
		populateDatabase($database);
		createTempPasswordsTable($database);
	}
	createStructures($database);
	createTempPasswordsTable($database);
	createServices($database);
	createReviewsTable($database);
	if ($action == 0) { // ADMIN
		openHtml();
		echo "<br>";
		//$database->query("DROP TABLE REVIEWS");
		echo "TABLE USERS:<br>";
		showFullTable($database, "USERS");
		echo "TABLE TEMP_PASSWORDS:<br>";
		showFullTable($database, "TEMP_PASSWORDS");

		
		echo "TABLE STRUCTURES:<br>";
		showFullTable($database, "STRUCTURES");
		echo "TABLE SERVICES:<br>";
		showFullTable($database, "SERVICES");
		echo "TABLE REVIEWS:<br>";
		showFullTable($database, "REVIEWS");
		echo "JOINING USERS-REVIEWS:<br>";
		showTableUsingQuery($database, "SELECT R.ID AS ID_REVIEW, USERNAME, TITLE, DESCRIPTION, R.RATING AS RATING FROM USERS AS U INNER JOIN REVIEWS AS R ON U.ID = R.ID_USER");
		closeHtml();
	}
	else
	if ($action == 1) { // REQUESTED REVIEWS JSON
		header('Content-Type: application/json');
		$array = getArrayFromQuery($database, "SELECT R.ID AS ID_REVIEW, USERNAME, TITLE, DESCRIPTION, R.RATING AS RATING, DATE_PUBLICATION FROM USERS AS U INNER JOIN REVIEWS AS R ON U.ID = R.ID_USER");
		echo json_encode($array, JSON_PRETTY_PRINT);
	}
?>