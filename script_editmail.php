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
	
	function editMail() {
		$error = "0";
		session_start();
		if (empty($_SESSION["id"]))
			return "Utente non autorizzato";
		if (isset($_POST['mail'])) {
			$mail = $_POST['mail'];
			if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				if (strcmp($mail, $_SESSION["mail"]) == 0)
					return "La mail inserita è uguale a quella già associata al tuo account.";
				$database = openDatabase();
				if (!$database)
					return "Impossibile aprire il database.";
				$query = "SELECT * FROM USERS WHERE ID = '" . $_SESSION['id'] . "'";
				$userPassword = getFieldFromQuery($database, $query, "PASSWORD");
				$id_prexisted_mail_user = getFieldFromQuery($database, "SELECT * FROM USERS WHERE MAIL = '" . $mail . "'", "ID");
				if ($id_prexisted_mail_user > 0)
					return "L'indirizzo mail inserito è già associato ad un altro account.";
				if ((strcmp($userPassword, $_SESSION['password']) == 0) && (strcmp($userPassword, $_POST['password']) == 0)) {
					$query = "UPDATE USERS SET MAIL = '". $mail ."' WHERE ID = " . $_SESSION["id"];
					$res = $database->query($query);
					if (!$res)
						$error = "Errore database: impossibile aggiornare l'indirizzo.";
					else
						$_SESSION["mail"] = $mail;
				}
				else
					$error = "Si è verificato un errore con l'autenticazione della tua identità.";
			}
			else
				$error = "L'indirizzo inserito non è un valido indirizzo mail.";
		}
		else
			$error = "La richiesta è danneggiata.";
		return $error;
	}
	echo editMail();
?>