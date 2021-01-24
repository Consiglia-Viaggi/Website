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
	function openDatabase() {
		$database = new SQLite3('database.sqlite', SQLITE3_OPEN_READWRITE);
		return $database;
	}
	function getError($error) {
		if ($error == 1)
			return "Si è verificato un errore nel server. Contattare l'amministratore (Error: 1).";
		if ($error == 2)
			return "La password è errata. Riprova.";	
		if ($error == 3)
			return "La mail inserita non è in un formato valido.";
		if ($error == 4)
			return "L'indirizzo mail inserito è già associato ad un altro account.";
		if ($error == 5)
			return "Hai confermato una mail diversa da quella che hai scelto. Riprova.";
		if ($error == 6)
			return "La mail inserita è uguale alla precedente.";
		return "Si è verificato un errore generico.";
	}
	$mail = getField("newMail");
	$confirmMail = getField("confirmMail");
	$password = getField("password");
	$oldMail = getField("oldMail");
	$database = openDatabase();
	header('Content-Type: application/json');
	$error = 0;
	if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
		if (strcmp($mail, $oldMail) != 0) {
			if (strcmp($mail, $mail) == 0) { // Non ci serve confrontarlo con $confirmMail perché lo abbiamo già fatto sull'app.
				$query = "SELECT * FROM USERS WHERE MAIL = '" . $oldMail . "'";
				$userPassword = getFieldFromQuery($database, $query, "PASSWORD");
				if (strcmp($userPassword, $password) == 0) {
					$id_prexisted_mail_user = getFieldFromQuery($database, "SELECT * FROM USERS WHERE MAIL = '" . $mail . "'", "ID");
					if ($id_prexisted_mail_user > 0)
						$error = 4;
					else {
						$id = getFieldFromQuery($database, "SELECT * FROM USERS WHERE MAIL = '" . $oldMail . "'", "ID");
						$query = "UPDATE USERS SET MAIL = '". $mail ."' WHERE ID = " . $id;
						$res = $database->query($query);
						if (!$res)
							$error = 1;
					}
				}
				else
					$error = 2;
			}
			else
				$error = 5;
		}
		else
			$error = 6;
	}
	else
		$error = 3;
	if ($error == 0)
		echo json_encode(array("failed"=>"0", "newMail"=>$mail), JSON_PRETTY_PRINT);
	else
		echo json_encode(array("failed"=>getError($error)), JSON_PRETTY_PRINT);
?>