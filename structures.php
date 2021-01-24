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
	function distance($lat1, $lon1, $lat2, $lon2, $unit) {
		$theta = $lon1 - $lon2;
		$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist = acos($dist);
		$dist = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;
		$unit = strtoupper($unit);
		if ($unit == "K")
			return ($miles * 1.609344);
		if ($unit == "N")
			return ($miles * 0.8684);
		 return $miles;
	}
	function getNearnessLevel($lat1, $lon1, $lat2, $lon2) {
		$km = distance($lat1, $lon1, $lat2, $lon2, "K");
		if ($km < 10)
			return 1;
		if ($km < 20)
			return 2;
		return 3;
	}
	function getNumberOfRows($result) {
        $numRows = 0;
        while($rows = $result->fetchArray()){
            ++$numRows;
        }
        return $numRows;
	}
	function getImagesForStructureID($id, $numberOfImages) {
		$dir = "../images/" . $id . "/";
		$array = array();
		if (is_dir($dir)) {
			if ($dh = opendir($dir)){
				while (($file = readdir($dh)) !== false) {
					if ((strcmp($file, ".") == 0) || (strstr($file, "..") != NULL))
						continue;
					$path = "../images/" . $id . "/" . $file;
					$image = file_get_contents($path);
					$encode = base64_encode($image);
					array_push($array, $encode);
					if ($numberOfImages == 1)
						break;
					//echo $path . ": codificato con: " . $encode ."<br><br>";
				}
				closedir($dh);
			}
		}
		return $array;
	}
	function getReviewsForStructureID($id, $database) {
		$query = "SELECT REVIEWS.ID AS ID_REVIEW, REVIEWS.ID_USER, REVIEWS.TITLE, REVIEWS.DESCRIPTION, REVIEWS.RATING, REVIEWS.DATE_PUBLICATION, REVIEWS.IS_APPROVED, USERS.USERNAME FROM REVIEWS INNER JOIN USERS ON REVIEWS.ID_USER = USERS.ID WHERE ID_ARTICLE = " . $id;
		//echo $query;
		$onlyApproved = !false;
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
	function injectFakeServices() {
		 $_POST["WIFI"] = "1";
	}
	$action = getField("action");
	$database = createDatabase();		
	header('Content-Type: application/json');
	$isDebugMode = getGetField("debug");
	if ($isDebugMode > 0)
		$action = 1;
	if (isset($_POST['getMainImageByID'])) {
		$id = $_POST['getMainImageByID'];
		$index = $_POST['index'];
		$array = getImagesForStructureID($id, 1);
		echo json_encode(array("failed"=>0, "index"=>$index, "image"=>$array[0]), JSON_PRETTY_PRINT);
	}
	else
	if ($action == 3) {
		$id = getIntField('id');
		$query = "SELECT * FROM STRUCTURES WHERE IDSTRUCTURE = " . $id;
		$res = $database->query($query);
		$array = getImagesForStructureID($id, 2);
		echo json_encode(array("failed"=>0, "id"=>$id, "images"=>$array), JSON_PRETTY_PRINT);
	}
	else
	if ($action == 1) {
		$query = "SELECT * FROM STRUCTURES";
		$includeServices = strlen(getField("services")) > 0;
		/*if ($isDebugMode == 1) {
			$includeServices = true;
			injectFakeServices();
		}*/
		if ($includeServices)
			$query = $query . " INNER JOIN SERVICES ON STRUCTURES.IDSTRUCTURE = SERVICES.IDSTRUCTURE";
		$cat = getField('cat');
		$id_post = getIntField('id');
		if ($isDebugMode == 1)
			$cat = "";
		// NOTA : LA CATEGORIA E L'ID NON VENGONO MAI INVIATI INSIEME, QUINDI NON CI SARANNO MAI PROBLEMI NELLA CONCATENAZIONE DELLA QUERY (OVVERO NON CORRO IL RISCHIO CHE $cat > 0 && %id_post > 0).
		if (strlen($cat) > 0)
			$query = $query . " WHERE TYPE = " . $cat;
		if ($id_post > 0)
			$query = $query . " WHERE IDSTRUCTURE = " . $id_post;
		$res = $database->query($query);
		if (!$res)
			echo json_encode(array("failed"=>"Si è verificato un errore nel server. Contattare l'amministratore."), JSON_PRETTY_PRINT);
		else {
			$tot = getNumberOfRows($res);
			$limit = getIntField("start");
			$shouldIncludeImages = getIntField("includeImages");
			$shouldUseLimit = $limit > 0;
			$array = array();
			$index = 0;
			$search = getField("search");
			$search = " ";
			$price_level_requested = getIntField("price");
			$shouldUseSearch = strlen($search) > 0;
			$nearness = getIntField("nearness");
			$user_lat = getIntField("lat");
			$user_lon = getIntField("lon");
			$quality = getIntField("quality");
			$shouldGetReviews = getIntField("getReviews");
			$shouldGetAllReviews = getIntField("getAllReviews");
			$res_reviews = $database->query("SELECT ID_ARTICLE, RATING FROM REVIEWS WHERE IS_APPROVED = 1");
			$array_reviews = array_fill(0, $tot+1, 0);
			$array_reviews_count = array_fill(0, $tot+1, 0);
			//$array[0] = $tot;
			$reviews_average = array();
			if ($isDebugMode == 1) {
				$shouldIncludeImages = 1; // 1 per una sola foto, 2 per tutte le foto.
				$shouldIncludeImages = 0;
				$shouldGetAllReviews = TRUE;
			}
			else
			if ($isDebugMode == 2) {
				$user_lat = 40.905733;
				$user_lon = 14.243251;
				$nearness = 1;
			}
			if (($quality > 0) || ($shouldGetReviews)) {
				while ($row = $res_reviews->fetchArray()) {
					$id = $row['ID_ARTICLE'];
					$rating = $row['RATING'];
					//$isApproved = $row['IS_APPROVED'];
					//if ($isApproved) {
						$array_reviews[$id] = $array_reviews[$id] + $rating;
						$array_reviews_count[$id] = $array_reviews_count[$id] + 1;
					//}
				}
				for ($i = 1; $i <= $tot; $i++) {
					if ($array_reviews_count[$i] > 0) {
						$average = $array_reviews[$i] / $array_reviews_count[$i];
						$reviews_average[$i] = $average;
						if ($average < 1.7)
							$array_reviews[$i] = 1;
						else
						if ($average < 3.4)
							$array_reviews[$i] = 2;
						else
							$array_reviews[$i] = 3;
					}
					else {
						$array_reviews_count[$i] = 1;
						$array_reviews[$i] = 1;
						$reviews_average[$i] = 1;
					}
				}
			}
			$limit_start_index = 0;
			$service_names = array("ANIMALS", "PARKING", "WIFI", "DISABLE_ACCESS", "SWIMMING_POOL", "VIEW", "SMOKING_AREA", "CHILD_AREA");
			while ($row = $res->fetchArray()) {
				if ($shouldUseLimit && $limit_start_index < $limit) {
					$limit_start_index++;
					continue;
				}
				if ($shouldUseLimit && $index > 10)
					break;
				$subArray = array();
				$id = $row['IDSTRUCTURE'];
				$name = $row['NAME'];
				$price_level = $row['PRICE_LEVEL'];
				$lat = $row['LATITUDE'];
				$lot = $row['LONGITUDE'];
				//echo "Nome struttura: ".$name. ": " . getNearnessLevel($lat, $lon, $user_lat, $user_lot)."\n";
				if ($shouldUseSearch && strstr(strtolower($name), strtolower($search)) == NULL) // Se bisogna usare la ricerca e il nome della struttura non contiene il nome desiderato dall'utente (low both string)
					continue; // passa al prossimo.
				if ($price_level_requested > 0 && $price_level != $price_level_requested) // Se l'utente ha impostato un livello di prezzo e questo è diverso da quello di questa struttura...
					continue; // passa al prossimo.
				if ($nearness > 0 && $user_lat != 0 && $user_lon != 0 && getNearnessLevel($lat, $lon, $user_lat, $user_lot) != $nearness)  // Se l'utente ha impostato la vicinanza e il grado di vicinanza è diverso da quello espresso dall'utente...
					continue;
				if ($quality > 0 && $array_reviews_count[$id] > 0 && $array_reviews[$id] != $quality) // Se l'utente ha espresso la qualità della struttura e per quella struttura ci sono recensioni (per le quali viene valutata la struttura) e il grado di recensione è diverso dalla qualità della struttura...
					continue;
				if ($includeServices) {
					$shouldContinue = false;
					for ($j = 0; $j < count($service_names); $j++) {
						$field = $service_names[$j];
						if (isset($_POST[$field]) && strcmp($_POST[$field], "1") == 0 && intval($_POST[$field]) != $row[$field]) {
							$shouldContinue = true;
							break;
						}
					}
					if ($shouldContinue)
						continue;
				}
				$subArray[0] = $id;
				$subArray[1] = $name;
				$subArray[2] = $lat;
				$subArray[3] = $lot;
				$subArray[4] = $price_level;
				$subArray[5] = $reviews_average[$index+1];
				if ($subArray[5] == null) // no reviews_average
					$subArray[5] = 1; // base value
				if ($shouldIncludeImages > 0)
					$subArray[6] = getImagesForStructureID($id , $shouldIncludeImages);
				else
					$subArray[6] = array();
				if ($shouldGetAllReviews)
					$subArray[7] = getReviewsForStructureID($id, $database);
				else
					$subArray[7] = array();
				$subArray[8] = $row['DESCRIPTION'];
				$subArray[9] = $row['PHONENUMBER'];
				$array[$index] = $subArray;
				$index++;
			}
			echo json_encode(array("failed"=>0, "action"=>$action, "places"=>$array), JSON_PRETTY_PRINT);
		}
	}
	else
	if ($action == 2) {
		$query = "SELECT IDSTRUCTURE, NAME, LATITUDE, LONGITUDE, DESCRIPTION, PHONENUMBER FROM STRUCTURES";
		$cat = getField('cat');
		if ($isDebugMode)
			$cat = 1;
		if (strlen($cat) > 0)
			$query = $query . " WHERE TYPE = " . $cat;
		$res = $database->query($query);
		if ($res) {
			$array = array();
			$index = 0;
			$shouldIncludeImages = getIntField("includeImages") > 0;
			while ($row = $res->fetchArray()) {
				$subArray = array();
				$id = $row['IDSTRUCTURE'];
				$subArray[0] = $row['IDSTRUCTURE'];
				$subArray[1] = $row['NAME'];
				$subArray[2] = $row['LATITUDE'];
				$subArray[3] = $row['LONGITUDE'];
				if ($shouldIncludeImages)
					$subArray[4] = getImagesForStructureID($id);
				else
					$subArray[4] = array();
				$subArray[5] = $row['DESCRIPTION'];
				$subArray[6] = $row['PHONENUMBER'];
				$array[$index] = $subArray;
				$index++;
			}
			echo json_encode(array("failed"=>0, "action"=>$action, "places"=>$array), JSON_PRETTY_PRINT);
		}
		else	
			echo json_encode(array("failed"=>"Si è verificato un errore nel server. Contattare l'amministratore."), JSON_PRETTY_PRINT);
	}
	else
		echo json_encode(array("failed"=>"Si è verificato un errore nell'inserimento della richiesta."), JSON_PRETTY_PRINT);
?>