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
	function getGetField($field) {
		if ((!empty($_GET)) && (isset($_GET[$field])))
			return $_GET[$field];
		return 0;
	}
	function getIntField($field) {
		if ((!empty($_POST)) && (isset($_POST[$field])))
			return $_POST[$field];
		return -1;
	}
	function createDatabase() {
		$dbExists = file_exists('database.sqlite');
		$database = $dbExists ?  new SQLite3('database.sqlite', SQLITE3_OPEN_READWRITE) : new SQLite3('database.sqlite', SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE);
		return $database;
	}
	function getReviewsForUserID($id, $database) {
		$query = "SELECT REVIEWS.ID AS ID_REVIEW, REVIEWS.ID_USER, REVIEWS.TITLE, REVIEWS.DESCRIPTION, REVIEWS.RATING, REVIEWS.DATE_PUBLICATION, REVIEWS.IS_APPROVED, USERS.USERNAME FROM REVIEWS INNER JOIN USERS ON REVIEWS.ID_USER = USERS.ID WHERE ID_USER = " . $id;
		$onlyApproved = !false;
		if (isset($_POST['includeNotApproved'])) {
			$onlyApproved = !strcmp($_POST['includeNotApproved'], "1") == 0;
		}
		if ($onlyApproved)
			$query = $query . " AND IS_APPROVED = 1";
		$res = $database->query($query);
		$array = array();
		if ($res != null) {
			while ($row = $res->fetchArray()) {
				$subArray = array();
				$subArray[0] = $row['ID_REVIEW'];
				$subArray[1] = $row['ID_USER'];
				$subArray[2] = $row['USERNAME'];
				$subArray[3] = $row['RATING'];
				$subArray[4] = $row['DATE_PUBLICATION'];
				$subArray[5] = $row['TITLE'];
				$subArray[6] = $row['DESCRIPTION'];
				$subArray[7] = $row['IS_APPROVED'];
				array_push($array, $subArray);
			}
		}
		return $array;
	}
	function fast_isLogged($database) {
		$mail = $_POST['mail'];
		$password = $_POST['password'];
		$id_user = getFieldFromQuery($database, 'SELECT * FROM USERS WHERE MAIL = "'. $mail .'" AND PASSWORD = "'. $password .'"', "ID");
		return $id_user > 0;
	}
	$action = getField("action");
	$database = createDatabase();
	header('Content-Type: application/json');
	if (!fast_isLogged($database))
		echo json_encode(array("failed"=>"-1"), JSON_PRETTY_PRINT);
	else
	if ($action == 3) {
		$id_review = $_POST['id_review'];
		$pos = $_POST['pos'];
		$query = "DELETE FROM REVIEWS WHERE ID = " . $id_review;
		$res = $database->query($query);
		if ($res != null)
			echo json_encode(array("failed"=>"0", "pos"=>$pos), JSON_PRETTY_PRINT);
		else
			echo json_encode(array("failed"=>"Si è verificato un errore nell'eliminazione della recensione. Riprova."), JSON_PRETTY_PRINT);
	}
	else
	if ($action == 1) {
		$id_review = getFieldFromQuery($database, "SELECT MAX(ID) AS ID FROM REVIEWS", "ID");
		$id_review++;
		$mail = getField("mail");
		$id_user = getFieldFromQuery($database, 'SELECT * FROM USERS WHERE MAIL = "'. $mail .'"', "ID");
		if ($id_user > 0) {
			$id_structure = getField("id_structure");
			$title = getField("title");
			$description = getField("description");
			$rating = getIntField("rating");
			$date = date("Y-m-d H:i:s");
			//$title = addslashes($title);
			//$description = addslashes($description);
			$title = str_replace("\"", "'", $title);
			$description = str_replace("\"", "'", $description);
			//$query = "INSERT INTO REVIEWS VALUES(". $id_review .", " . $id_user .", " . $id_structure .", '". addslashes($title) ."', '" . addslashes($description) ."', ". $rating.", '". $date."', 0)";
			$query = "INSERT INTO REVIEWS VALUES(". $id_review .", " . $id_user .", " . $id_structure .", \"". $title ."\", \"" . $description ."\", ". $rating.", '". $date."', 0)";
			$res = $database->query($query);
			if ($res != null)
				echo json_encode(array("failed"=>0), JSON_PRETTY_PRINT);
			else
				//echo json_encode(array("failed"=>"Si è verificato un errore lato server. Si prega di contattare lo sviluppatore."), JSON_PRETTY_PRINT);
			echo json_encode(array("failed"=>$query), JSON_PRETTY_PRINT);
		}
		else
			echo json_encode(array("failed"=>"Si è verificato di autenticazione. Questo succede quando la mail viene cambiata da un altro dispositivo. Riprova."), JSON_PRETTY_PRINT);
	}
	else
	if ($action == 2) {
		$mail = getField("mail");
		$id_user = getFieldFromQuery($database, 'SELECT * FROM USERS WHERE MAIL = "'. $mail .'"', "ID");
		if ($id_user > 0) {
			$array = getReviewsForUserID($id_user, $database);
			echo json_encode(array("failed"=>0, "reviews"=>$array), JSON_PRETTY_PRINT);
		}
		else
			echo json_encode(array("failed"=>"Si è verificato di autenticazione. Questo succede quando la mail viene cambiata da un altro dispositivo. Riprova."), JSON_PRETTY_PRINT);

	}
	else
		echo json_encode(array("failed"=>"Si è verificato un errore nell'inserimento della richiesta."), JSON_PRETTY_PRINT);
?>