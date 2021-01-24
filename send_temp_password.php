<?
	function getFieldFromQuery($db, $query, $field) {
		if ($res = $db->query($query)) // EXEC THE QUERY.
			while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
				return $row[$field]; // RETURN THE VALUE FOR THE FIELD.
		return NULL;
	}
	function openDatabase() {
		$database = new SQLite3('../scripts/database.sqlite', SQLITE3_OPEN_READWRITE);
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
	function sendTempPassword() {
		$error = "Errore sconosciuto";
		if (isset($_POST['mail'])) {
			$mail = $_POST['mail'];
			if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
				$database = openDatabase();
				if (!$database)
					return "Impossibile aprire il database.";
				$id_prexisted_mail_user = getFieldFromQuery($database, "SELECT * FROM USERS WHERE ADMIN = 1 AND MAIL = '" . $mail . "'", "ID");
				if ($id_prexisted_mail_user > 0) 
					$password = generateRandomString(10);
					$id = getFieldFromQuery($database, "SELECT * FROM TEMP_PASSWORDS WHERE ID = '" . $id_prexisted_mail_user . "'", "ID");
					$updateQuery = "";
					$date = date("Y-m-d H:i:s");
					if ($id > 0)
						$updateQuery = "UPDATE TEMP_PASSWORDS SET PASSWORD = '". md5($password) ."', DATE = '" . $date ."' WHERE ID = " . $id_prexisted_mail_user;
					else
						$updateQuery = "INSERT INTO TEMP_PASSWORDS VALUES(" . $id_prexisted_mail_user . ", '". md5($password) ."', '" . $date ."')";
					$res = $database->query($updateQuery);
					if (!$res)
						return "Si è verificato un errore nell'elaborazione della richiesta.";
					$message = "E' stata richiesta una password temporanea per accedere a https://consigliaviaggi.altervista.org.\nLa password temporanea è " . $password ."\n\nSe non hai richiesto tu la password, ignora semplicemente questa mail.";
					$response = mail($mail, 'Consiglia Viaggi: recupero password', $message, 'From: "Consiglia Viaggi" <consigliaviaggi.altervista@gmail.com>');
					return $response;
			}
			else
				$error = "L'indirizzo inserito non è un valido indirizzo mail.";
		}
		else
			$error = "La richiesta è danneggiata.";
		return $error;
	}
	$error = sendTempPassword();
	if ((isset($_POST['app'])) && (strcmp($_POST['app'], "1") == 0))
		echo json_encode(array("temp_password"=>"0"), JSON_PRETTY_PRINT);
	else
		echo $error;

?>