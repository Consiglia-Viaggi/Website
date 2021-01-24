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
	$password = getField("password");
	$mail = getField("mail");
	$database = createDatabase();
	header('Content-Type: application/json');
	$query = "SELECT * FROM USERS WHERE MAIL = '" . $mail . "'";
	$userPassword = getFieldFromQuery($database, $query, "PASSWORD");
	if (strcmp($userPassword, $password) == 0) {
		$query = "DELETE FROM USERS WHERE ID = ". $id;
		$res = $database->query($updateQuery. $table);
		if (!$res)
			echo json_encode(array("failed"=>"Si è verificato un errore nell'eliminazione del tuo account. Riprova più tardi."), JSON_PRETTY_PRINT);
	}
	else
		echo json_encode(array("failed"=>"La password non corrisponde. Riprova."), JSON_PRETTY_PRINT);
?>