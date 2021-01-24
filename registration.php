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
	function isAlphabetic($string, $min) {
		$length = strlen($string); // Ottieni la lunghezza della stringa.
		if ($length >= $min && $length < 20) // Se la lunghezza della stringa è compresa tra quella prefissata...
			return ctype_alpha(str_replace(' ', '', $string)); // Controlla se è alfabetica (gli spazi sono consentiti perché una persona può avere anche più di un nome).
		return FALSE;
	}
	function isAlphanumeric($string, $min) {
		$length = strlen($string); // Ottieni la lunghezza della stringa.
		if ($length >= $min && $length < 20) // Se la lunghezza della stringa è compresa tra quella prefissata...
			return ctype_alnum($string); // Controlla se è alfanumerica.
		return FALSE;
	}
	function getError($error) {
		if ($error == 1)
			return "Questo indirizzo mail è già associato ad un account";
		if ($error == 2)
			return "Questo username è già associato ad un account,";
		if ($error == 3)
			return "La password non è abbastanza sicura.";
		if ($error == 4 || $error == 5)
			return "Errore server. Contattare l'amministratore.";
		if ($error == 6)
			return "Controllare gli input di Nome, Cognome e Username.";
		if ($error == 7)
			return "L'indirizzo mail inserito non è un valido indirizzo mail.";
		if ($error == 8)
			return "La password deve essere lunga almeno 8 caratteri.";
		if ($error == 9)
			return "La password deve includere almeno un numero";
		if ($error == 10)
			return "La password deve includere almeno una lettera minuscola!";
		if ($error == 11)
			"La password deve includere almeno una lettera maiuscola!";
		if ($error == 12)
			return "La password deve includere almeno un simbolo!";
		return "0";
	}
	function checkPassword($pwd) {
		if (strlen($pwd) < 8) // La password deve essere lunga almeno 8 caratteri.
			return 8;
		if (!preg_match("#[0-9]+#", $pwd)) // Deve includere almeno un numero
			return 9;
		if (!preg_match("#[a-z]+#", $pwd)) // Deve includere almeno una lettera minuscola!
			return 10;
		if (!preg_match("#[A-Z]+#", $pwd)) // Deve includere almeno una lettera maiuscola!
			return 11;
		if (!preg_match("/[\'^£$%&*()}{@#~!?><>,|=_+¬-]/", $pwd)) // Deve includere almeno un simbolo!
			return 12;
		return 0;
	} 
	// Di seguito ottengo tutti i campi necessari per realizzare la registrazione.
	$user_id = 0;
	$name = getField("name");
	$surname = getField("surname");
	$username = getField("username");
	$password = getField("password");
	$repeatPassword = getField("repeatPassword");
	$mail = getField("mail");
	$database = createDatabase(); // Ottengo il database.
	header('Content-Type: application/json');
	$error = 0;
	if (filter_var($mail, FILTER_VALIDATE_EMAIL)) { // Se è un indirizzo mail valido...
		if (isAlphabetic($name, 3) && isAlphabetic($surname, 3) && isAlphanumeric($username, 4)) { // Se questi campi sono validi...
			$item = getFieldFromQuery($database, "SELECT * FROM USERS WHERE MAIL = '" . $mail . "'", "ID"); // Verifico se esiste almeno una riga che contenga quell'indirizzo mail.
			if ($item > 0) // Se la riga esiste...
				$error = 1; // Questo indirizzo mail è già associato ad un account.
			else {
				$item = getFieldFromQuery($database, "SELECT * FROM USERS WHERE USERNAME = '" . $USERNAME . "'", "ID"); // Verifico se esiste almeno una riga che contenga quell'username.
				if ($item > 0) // Se esiste...
					$error = 2; // Questo username è già associato ad un account.
				else {
					if (strcmp($password, $repeatPassword) != 0) // Se le password non sono uguali...
						$error = 3; // Le password non coincidono.
					else {
						$item = checkPassword($password); // Verifica se la password è abbastanza sicura. Ritorna 0 se è sicura, altrimenti un numero di errore che riporteremo al client.
						if ($item > 0)
							$error = $item; // La password non è abbastanza sicura.
						else {
							$id = getFieldFromQuery($database, "SELECT MAX(ID) AS ID FROM USERS", "ID"); // Estrai il massimo ID.
							if ($id == 0) // Se è 0 (non è possibile) vuol dire che la query è fallita per qualche motivo.
								$error = 4; // Errore server.
							else {
								$id = $id + 1; // Incrementa l'ID massimo.
								$user_id = $id;
								$date = date("Y-m-d H:i:s");
								$encryptedPassword = md5($password); // Effettua la crittografia della password. Siccome con checkPassword ci siamo assicurati di utilizzare una password sicura, è altamente impossibile risalire ad un md5 della stessa, quindi possiamo utilizzare questo algoritmo.
								$query = "INSERT INTO USERS(ID, NAME, SURNAME, USERNAME, PASSWORD, MAIL, ADMIN, REGISTRATION_DATE, LASTSEEN_DATE) VALUES(".$id.", '".$name."', '".$surname."', '".$username."', '".$encryptedPassword."', '".$mail."', 0 , '".$date."', '".$date."')";
								$result = $database->query($query); // Esegui la query.
								if (!$result) // Se la query ha dato errore...
									$error = 5; // Errore server.
							}
						}
					}
				}
			}
		}
		else
			$error = 6; // Controllare gli input di Nome, Cognome e Username.
	}
	else
		$error = 7; // Questo non è un indirizzo mail valido.
	echo json_encode(array("failed"=>getError($error), "id"=>$user_id), JSON_PRETTY_PRINT); // Mostra la rappresentazione JSON dei dati.
?>