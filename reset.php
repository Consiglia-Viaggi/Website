<?php
	function getFieldFromQuery($db, $query, $field) {
		if ($res = $db->query($query)) // EXEC THE QUERY.
			while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
				return $row[$field]; // RETURN THE VALUE FOR THE FIELD.
		return NULL;
	}
	function getMail() {
		if ((!empty($_GET)) && (isset($_GET['mail'])))
			return $_GET['mail'];
		return "";
	}
	function createDatabase() {
		$dbExists = file_exists('database.sqlite');
		$database = $dbExists ?  new SQLite3('database.sqlite', SQLITE3_OPEN_READWRITE) : new SQLite3('database.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
		return $database;
	}
	function generateRandomString($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!£$%&()=?:.';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return md5($randomString);
	}
	$email = getMail();
	$database = createDatabase();
	header('Content-Type: application/json');
	if (strlen($email) > 1) {
		$query = "SELECT * FROM USERS WHERE MAIL = '" . $email . "'";
		$item = getFieldFromQuery($database, $query, "ID");
		if ($item > 0) {
			$id = getFieldFromQuery($database, "SELECT * FROM TEMP_PASSWORDS WHERE ID = '" . $item . "'", "ID");
			$updateQuery = "";
			$password = generateRandomString(10);
			$date = date("Y-m-d H:i:s");
			if ($id > 0)
				$updateQuery = "UPDATE TEMP_PASSWORDS SET PASSWORD = '". md5($password) ."', DATE = '" . $date ."' WHERE ID = " . $item;
			else
				$updateQuery = "INSERT INTO TEMP_PASSWORDS VALUES(" . $id . ", '". md5($password) ."', '" . $date ."')";
			$res = $database->query($updateQuery. $table);
			// Ricordarsi di eliminare i campi sensibili dalla riga successiva.
			$array = $res != NULL ? array("failed"=>0, "id"=>$id, "password"=>$password, "date"=>$date, "query"=>$updateQuery) : array("failed"=>1, "id"=>$id, "password"=>$password, "date"=>$date, "query"=>$updateQuery);
			echo json_encode($array, JSON_PRETTY_PRINT);
		}
		else
			echo json_encode(array("failed"=>2), JSON_PRETTY_PRINT);
	}
	else
		echo json_encode(array("failed"=>3), JSON_PRETTY_PRINT);
?>