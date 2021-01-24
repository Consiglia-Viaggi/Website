<?php
	function getFieldFromQuery($db, $query, $field) {
		if ($res = $db->query($query)) // EXEC THE QUERY.
			while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
				return $row[$field]; // RETURN THE VALUE FOR THE FIELD.
		return NULL;
	}
	function getField($field) {
		if ((!empty($_GET)) && (isset($_GET[$field])))
			return $_GET[$field];
		return "";
	}
	function openDatabase() {
		$database = new SQLite3('../scripts/database.sqlite', SQLITE3_OPEN_READWRITE);
		return $database;
	}
	function editPassword() {
		$error = "0";
		session_start();
		if (empty($_SESSION["id"]))
			return "Utente non autorizzato";
		if (isset($_POST['password'])) {
			$newPassword = $_POST['password']; // md5 effettuato da javascript con JQuery.
			$database = openDatabase();
			if (!$database)
				return "Impossibile aprire il database.";
			$query = "SELECT * FROM USERS WHERE ID = '" . $_SESSION['id'] . "'";
			$userPassword = getFieldFromQuery($database, $query, "PASSWORD");
			if (strcmp($userPassword, $_SESSION['password']) == 0) {
				$query = "UPDATE USERS SET PASSWORD = '". $newPassword ."' WHERE ID = " . $_SESSION["id"];
				$res = $database->query($query);
				if (!$res)
					$error = "Errore database: impossibile aggiornare la password.";
				else
					$_SESSION["password"] = $newPassword;
			}
			else
				$error = "Si è verificato un errore verificando la tua identità.";
		}
		else
			$error = "La richiesta è danneggiata.";
		return $error;
	}
	echo editPassword();
?>