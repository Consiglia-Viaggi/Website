<?php
	function getFieldFromQuery($db, $query, $field) {
		if ($res = $db->query($query)) // EXEC THE QUERY.
			while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
				return $row[$field]; // RETURN THE VALUE FOR THE FIELD.
		return NULL;
	}
	function getField($field) {
		if ((!empty($_POST)) && (isset($_POST[$field])))
			return $_POST[$field];
		return "";
	}
	function createDatabase() {
		$dbExists = file_exists('database.sqlite');
		$database = $dbExists ?  new SQLite3('database.sqlite', SQLITE3_OPEN_READWRITE) : new SQLite3('database.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
		return $database;
	}
	$password = getField("oldPassword");
	$newPassword = getField("newPassword");
	$mail = getField("mail");
	$database = createDatabase();
	header('Content-Type: application/json');
	$query = "SELECT * FROM USERS WHERE MAIL = '" . $mail . "'";
	$userPassword = getFieldFromQuery($database, $query, "PASSWORD");
	if (strcmp($userPassword, $password) == 0) {
		$query = "UPDATE USERS SET PASSWORD = '". $newPassword ."' WHERE MAIL = '" . $mail."'";
		$res = $database->query($query);
		if (!$res)
			echo json_encode(array("failed"=>"Si è verificato un errore nell'aggiornamento della tua password. Riprova più tardi."), JSON_PRETTY_PRINT);
		else
			echo json_encode(array("failed"=>"0"), JSON_PRETTY_PRINT);
		//echo json_encode(array("failed"=>$query), JSON_PRETTY_PRINT);
	}
	else
		echo json_encode(array("failed"=>"La vecchia password inserita è errata. Riprova."), JSON_PRETTY_PRINT);	
?>