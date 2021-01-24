<?php
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
function getFieldFromQuery($db, $query, $field) {
	if ($res = $db->query($query)) // EXEC THE QUERY.
		while ($row = $res->fetchArray()) // ITERATE FOR COLUMNS
			return $row[$field]; // RETURN THE VALUE FOR THE FIELD.
	return NULL;
}

function getError($error) {
	if ($error == 1)
		return "L'indirizzo mail o la password non sono settati.";
	if ($error == 2)
		return "Si è verificato un errore nel server. Contattare l'amministratore (Error: 2).";
	if ($error == 3)
		return "La password temporanea inserita è scaduta. Richiedine un'altra ed effettua l'accesso entro le prime 24 ore.";
	if ($error == 4)
		return "Si è verificato un errore nel server. Contattare l'amministratore (Error: 4).";
	if ($error == 5)
		return "Si è verificato un errore nel server. Contattare l'amministratore (Error: 5).";	
	if ($error == 6)
		return "La mail inserita non corrisponde ad alcun account.";
	if ($error == 7)
		return "Si è verificato un errore nel server. Contattare l'amministratore (Error: 7).";
	if ($error == 8)
		return "I dati di accesso sono errati. Riprova.";
	return "Si è verificato un errore generico.";
	
}

function isAdmin($checkTempPassword) {
	$mail  = trim($_POST['mail']);
	$password = md5(trim($_POST['password']));
	if (get_magic_quotes_gpc()) {
		$mail = stripslashes($mail);
		$password = stripslashes($password);
	}
	if (!$mail || !$password) 
		return 1;
	$mail = filter_var($mail, FILTER_SANITIZE_STRING);
	$password = filter_var($password, FILTER_SANITIZE_STRING);
	$db = new SQLite3('database.sqlite', SQLITE3_OPEN_READWRITE);
	if (!$db) {
		echo "Il database non esiste.";
		return 2;
	}
	$query = "SELECT * FROM USERS WHERE MAIL = '".$mail."' AND PASSWORD = '".$password."'";
	if ($checkTempPassword) { // Controllo su pasword temporanea.
		$temp_query = "SELECT * FROM USERS WHERE MAIL = '".$mail."'"; // Verifica se la mail corrisponde ad una degli admin.
		$user_exists = getFieldFromQuery($db, $temp_query, "ID"); // Esegui la query ed ottieni l'ID se esiste.
		if ($user_exists > 0) { // Se esiste...
			$temp_query = "SELECT * FROM TEMP_PASSWORDS WHERE ID = ".$user_exists." AND PASSWORD = '".$password."'"; // Verfica se esiste un utente con quell'ID che ha richiesto una password temporanea.
			$result = $db->query($temp_query); 
			if ($result) { // Se esiste quell'utente...
				$checkDate = TRUE;
				if ($checkDate) {
					$row = $result->fetchArray(); // Ottieni la riga.
					$date = $row['DATE']; // Estrai la data di generazione della password.
					$timestamp = strtotime($date); // Convertila in timestamp.
					$difference = time() - $timestamp; // Comprendi quanto tempo è passato dalla generazione della password.
					if ($difference > 86400) // Se sono passate 24 ore...
						return 3; // Per protezione, diciamo che la password generata è "scaduta", quindi non effettuo l'accesso. L'utente dovrà richiedere una nuova password ed effettuare l'accesso entro le 24 ore dalla richiesta.
				}
				$update_query = "UPDATE USERS SET PASSWORD = '". $password ."' WHERE ID = " . $user_exists; // Aggiorna la sua password (dimenticata) con quella temporanea, con cui sta tentando di accedere.
				$result = $db->query($update_query);
				if ($result) {
					$delete_query = "DELETE FROM TEMP_PASSWORDS WHERE ID = ".$user_exists;
					$result = $db->query($delete_query);
				}
				else
					return 4;
			}
			else
				return 5;
		}
		else
			return 6;
	}
	$result = $db->query($query); // EXEC THE QUERY.
	if (!$result)
		return 7;
	$id = NULL;
	$name = NULL;
	$surname = NULL;
	$username = NULL;
	$_mail = NULL; // Prendiamo la mail reale salvata nel database (questo perché l'utente potrebbe aver inserito la sua mail, per esempio, tutta maiuscola).
	while ($row = $result->fetchArray()) {
		$id = $row['ID'];
		$name = $row['NAME'];
		$surname = $row['SURNAME'];
		$username = $row['USERNAME'];
		$_mail = $row['MAIL'];
	}
	if (!$id)
		return 8;
	$_SESSION['id'] = $id;
	$_SESSION['name'] = $name;
	$_SESSION['surname'] = $surname;
	$_SESSION['username'] = $username;
	$_SESSION['mail'] = $mail;
	$_SESSION['password'] = $password;
	$_SESSION['isTemp'] = $checkTempPassword;
	$date = date("Y-m-d H:i:s");
	$update_query = "UPDATE USERS SET LASTSEEN_DATE = '". $date ."' WHERE ID = " . $id;
	$result = $db->query($update_query);
	if (!$result)
		return 9;
	echo json_encode(array("failed"=>"0", "id"=>$id, "name"=>$name, "surname"=>$surname, "username"=>$username, "isLoginProcedure"=>"1"), JSON_PRETTY_PRINT);
	return 0;
}
	header('Content-Type: application/json');
	$value = isAdmin(FALSE);
	$otherValue = 0;
	if ($value > 0)
		$otherValue = isAdmin(TRUE);
	if (($value > 0) && ($otherValue > 0))
		echo json_encode(array("failed"=>getError($value), "isLoginProcedure"=>"0"), JSON_PRETTY_PRINT);
?>