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
	function deleteReview() {
		$error = "Errore sconosciuto";
		if (isset($_POST['id'])) {
			$id_review = $_POST['id'];
			session_start();
			if (!empty($_SESSION["id"])) {
				$database = openDatabase();
				if (!$database)
					return "Impossibile aprire il database.";
				$id_review_v2 = getFieldFromQuery($database, "SELECT * FROM REVIEWS WHERE IS_APPROVED = 0 AND ID = ". $id_review, "ID");
				if ($id_review_v2 > 0) // La recensione esiste davvero e non è mai stata approvata.
					$updateQuery = "DELETE FROM REVIEWS WHERE ID = " . $id_review;
					$res = $database->query($updateQuery);
					if (!$res)
						return "Si è verificato un errore nell'elaborazione della richiesta.";
					return "0";
			}
			else
				$error = "Si è verificato un errore di autenticazione.";
		}
		else
			$error = "La richiesta è danneggiata.";
		return $error;
	}
	echo deleteReview();
?>