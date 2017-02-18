<?php

// Connext to local database. I will have to change this to server database on upload
$db = mysqli_connect('localhost','root','Isthatmatt?','users')
or die('Error connecting to MySQL server.');

// Interpret the post variables and return the json that is required
$result_json = array('password' => $_POST);
if ($_POST["login"]){
	$result_json = login($db);
}elseif ($_POST["register"]) {
	$result_json = register($db);
}elseif ($_POST["init"]){
	$result_json = getShownDetails($db, $_POST['id']);
}elseif ($_POST["upload"]) {
	$result_json = addPhoto($db);
}elseif ($_POST["logout"]) {
	$result_json = logout();
}elseif ($_POST["deletePhoto"]) {
	$result_json = deletePhoto($db);
}elseif($_POST["shownDetails"]) {
	$result_json = getShownDetails($db, $_POST['id']);
}elseif ($_POST["photoRequest"]) {
	$result_json = getPhoto($db, $_POST['photoID'], $_POST['userID'], $_POST['oldStamp'], $_POST['number']);
}elseif ($_POST["setTrip"]) {
	$result_json = setTrip($db);
}elseif($_POST["search_text"]){
	$result_json = searchDB($db, $_POST['search_text']);
}elseif ($_POST["userToCheck"]) {
	$result_json = isVisibleUser($db, $_POST["userToCheck"]);
}elseif($_POST["followQuery"]){
	$result_json = isFollowing($db, $_COOKIE["userID"], $_POST["id"]);
}elseif($_POST["newFollow"]){
	$result_json = follow_user($db, $_POST['toFollow']);
}elseif($_POST["stopFollow"]){
	$result_json = unfollow_user($db, $_POST['toUnfollow']);
}elseif($_POST['coodRequest']){ 
	$result_json = getMapInfo($db, $_POST['forUser']);
}else{
	$result_json = $_POST;
}

// Do not cache any results
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// headers to tell that result is JSON
header('Content-type: application/json');

// send the result now
echo json_encode($result_json);
//echo $result_json;



// Authorise a login attempt
function login($db){

	// Check the database to see whether passwords match
	$query = "SELECT password,id FROM users WHERE username='" . $_POST["username"] ."' OR email='" . $_POST["username"]."'";
	$result = mysqli_query($db, $query) or die('Error querying database.');

	$row = mysqli_fetch_array($result);
	if ($row['password']){
		if ($_POST['password'] == $row['password']){
			$cookie = "userID";
			$cookie_value = $row['id'];
			setcookie($cookie, $cookie_value, time() + (86400 * 30), "/", null, null, true);
			$result_json = array('valid' => True);
		}else{
			// Return that the reason is invalid password
			$result_json = array('reason' => 'password');
		}
	}
	else{
		// Return that the reason is invalid username/email
		$result_json = array('reason' => 'username/email');
	}

	//$result_json = array('password' => $row['password'], 'email' => $row['email']);
	// headers for not caching the results

	return $result_json;

}


// Register a new user and create all databse records required
// TODO: Make sure that the passwords/Emails and stuff match. 
function register($db){
	$query = "SELECT MAX(id) FROM users";
	//return $query;
	$result = mysqli_query($db, $query) or die('Error querying database.');

	$max = mysqli_fetch_array($result);
	$max = $max['0'] + 1;
	//$query = "INSERT INTO users VAUES($max, $_POST['username'], $_POST['newPassword1'], $_POST['newEmail1'], CURDATE(), 0, NA)";
	if ($_POST["public"]){
		$privacy = 0;
	} else {
		$privacy = 1;
	}
	$query = "INSERT INTO users VALUES($max, '$_POST[newUsername]', '$_POST[newPassword1]', '$_POST[newEmail1]', CURDATE(), $privacy, 'N/A')";
	$result = mysqli_query($db, $query) or die('Error querying database.');

	$query = "CREATE TABLE photos_user_$max (
		photoID int,
		stamp timestamp,
		longitude real,
		latitude real,
		private boolean,
		title varchar(255),
		description text
		)";
	$result = mysqli_query($db, $query) or die('Error querying database.');

	$query = "CREATE TABLE follows_user_$max(
		idFollowed int,
		since date
	)";

	$result = mysqli_query($db, $query) or die('Error querying database.');

	$query = "CREATE TABLE trips_$max(
		continent varchar(255),
		country varchar(255),
		start date,
		end date
	)";

	$result = mysqli_query($db, $query) or die('Error querying database.');

	$result_json =array('success' => $result);
	$cookie = "userID";
	$cookie_value = $max;
	setcookie($cookie, $cookie_value, time() + (86400 * 30), "/", null, null, true);
	return $result_json;
}

// Upload a new photo to the server and store it sequentially
// TODO: Instead of sequentia, make the storage a hash of the username/email and the sequence number?
function addPhoto($db){
	$imageData = getimagesize($_FILES["fileToUpload"]["tmp_name"]);

	if(!$imageData){
		return array("file"=>"invalid");
	}

	$result = mysqli_query($db, "SELECT MIN(id) FROM deleted_slots");
	$min = mysqli_fetch_array($result);
				

	if (isset($min[0])){
		$picID = $min[0];
		$num = floor($picID/10000);
		$dir = "images/user/$num";
		mysqli_query($db, "DELETE FROM deleted_slots WHERE id=$picID");

	}else{
		$picturePath = "images/user/*";
		$directories = glob($picturePath , GLOB_ONLYDIR);

		$num = sizeof($directories) - 1;


		$dir = "images/user/$num";
		$fi = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
		$fileCount = iterator_count($fi);

		if ($fileCount >= 10000){
			$fileCount = 0;
			$num++;
			mkdir("images/user/$num");
			chmod("images/user/$num", 0755);
			$query = "INSERT INTO photos_dir VALUES($num)";
			$result = mysqli_query($db, $query) or die('Error querying database.');
			$query = "CREATE TABLE $num (photoID int, userID int, stamp timestamp)";
			$result = mysqli_query($db, $query) or die('Error querying database.');
		}
		// Save the image to images/Mymap/$max/$filecount.format
		$picID = $num * 10000 + $fileCount;
	}

	if ($_POST['private']){
		$privacy = 1;
	}else{
		$privacy = 0;
	}
	


	$query = "INSERT INTO dir_$num VALUES(
		$picID,
		$_COOKIE[userID],
		NOW()
		)";
	
	$result = mysqli_query($db, $query) or die('Error querying database.');
	
	
	

	$title = mysqli_real_escape_string($db, $_POST[title]);	

	$description = mysqli_real_escape_string($db, $_POST[description]);


	$query = "INSERT INTO photos_user_$_COOKIE[userID] VALUES(
		$picID,
		NOW(),
		$_POST[longitude],
		$_POST[latitude],
		$privacy,
		'$title',
		'$description',
	);";
	$result = mysqli_query($db, $query) or die('Error querying database??.');
	
	$query = "CREATE TABLE photo_$_COOKIE[userID]_$picID(
		id int,
		userActing int,
		stamp timestamp,
		content varchar(255)
	)";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$moveTo = $_SERVER['DOCUMENT_ROOT'] . "/Mymap/images/user/$num/" . $picID;
	$result = move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $moveTo);
	chmod($moveTo, 0755);
	$result_json = array("valid" =>  $result);
	
	return $result_json;


}

// Remove the cookie in the browser that indicates logged in
function logout(){
	if (isset($_COOKIE['userID'])){
		unset($_COOKIE['userID']);
		setcookie("userID", '', 0, '/');
	}
	return array("valid" => True);
}

// Delete a photo from the server
function deletePhoto($db){

	$query = "DROP TABLE photo_$_COOKIE[userID]_$_POST[button]";
	$result = mysqli_query($db, $query) or die('Error querying database.???');

	$query = "DELETE FROM photos_user_$_COOKIE[userID] WHERE photoID = $_POST[button]";
	$result = mysqli_query($db, $query) or die('Error querying database.??');

	$dir = floor($_POST[button]/10000);
	$query = "DELETE FROM dir_$dir WHERE picID=$_POST[button]";
	$result = mysqli_query($db, $query) or die('Error querying database.????');

	// Add a record of a spare spot that the next uploaded photo will fill
	// Assumption: There will be more uploads than deletes
	$query = "INSERT INTO deleted_slots VALUES($_POST[button])";
	$result = mysqli_query($db, $query) or die('Error querying database.');

	//Remove the actual file from the database
	$path = getPhotoPath($_POST["button"]);
	unlink($path);


	return array("valid" => $result);
}

function getShownDetails($db, $id){
	$query = "SELECT username, trip FROM users WHERE id=$id";
	
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$row = mysqli_fetch_array($result);
	$result_json = array("username" => $row['username'], "trip" => $row['trip']);
	return $result_json;
}

// Add a user into the follows database of another
function follow_user($db, $toFollow){
	$id = getIdFromUsername($db, $toFollow);
	$currUser = $_COOKIE['userID'];
	$query ="INSERT INTO follows_user_$currUser VALUES ($id, CURDATE())";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	return array("valid" => $result);
}

// Delete a user from the database of another
function unfollow_user($db, $toUnfollow){
	$id = getIdFromUsername($db, $toUnfollow);
	$currUser = $_COOKIE['userID'];
	$query = "DELETE FROM follows_user_$currUser WHERE idFollowed=$id";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	return array("valid" => $result);
}

// Get a list of photos and their paths etc to send back to the client
function getPhoto($db, $photoID, $userID, $oldStamp, $numNeeded){
	
	$query = "SELECT * FROM photos_user_$userID WHERE stamp < '$oldStamp' ORDER BY stamp DESC LIMIT $numNeeded";
	
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$photoNum = 0;
	while($row = mysqli_fetch_array($result)){
	
		$return[$photoNum]["path"] = getPhotoPath($row['photoID']);
		$return[$photoNum]["id"] = $row['photoID'];
		$return[$photoNum]["title"] = $row["title"];
		$return[$photoNum]["timestamp"] = $row["stamp"];
		$return[$photoNum]["description"] = $row["description"];
		$return[$photoNum]["latitude"] = $row["latitude"];
		$return[$photoNum]["longitude"] = $row["longitude"];
		$photoNum++;
	}

	//return array("lol" => $result);
	return $return;
}

// Given the id of a photo, return the directory path for it
function getPhotoPath($photoID){
	$dir = floor($photoID/10000);
	$fileNum = $photoID%10000;
	return "images/user/$dir/$fileNum";
}
/*
// Get the number of likes and the number of comments for a certain photo
function getPhotoStats($db, $photoID, $userID){
	$query = "SELECT * FROM photo_$userID_$photoID";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$numlikes = 0;
	$numcomments = 0;
	while($row = mysqli_fetch_array($result)){
		if (!$row['content']){
			$numlikes += 1;
		} else {
			$numcomments += 1;
		}
	}
	return array("likes" => $numlikes, "comments" => $numcomments)
}
/*
function getPhotoLikes($db, $photoID, $userID){
	$query = "SELECT * FROM photo_$userID_$photoID";
	$result = mysqli_query($db, $query) or die('Error querying database.');	
	//$row = mysqli_fetch_array($result);
	$numlikes = 0;
	$comments = array();
	while($row = mysqli_fetch_array($result)){
		if (!$row['content']){
			$comments[$row[getUsername($db, $row['userActing'])]] = 1;
		}else{
			$comments[$row[getUsername($db, $row['userActing'])]] = $row['content'];
		}
	}

	$comments['likes'] = $numlikes;
	return $comments;

}

/*function getUsername($db, $id){
	$query = "SELECT username FROM users WHERE id=$id";
	$result = mysqli_query($db, $query) or die('Error querying database.');	
	$row = mysqli_fetch_array($result);
	return $row['username'];
}
*/
/*
function setTrip($db){
	

	return array("trip" => $_POST['trip']);

}
/*
function getPhotoInArea($db, $photoID, $userID, $oldest, $newest, $numNeeded){
	
	$query = "SELECT * FROM photos_user_$userID WHERE stamp > '$oldest' AND stamp < '$newest' ORDER BY stamp DESC LIMIT $numNeeded";
	
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$photoNum = 0;
	while($row = mysqli_fetch_array($result)){
	
		$return[$photoNum]["path"] = getPhotoPath($row['photoID']);
		$return[$photoNum]["id"] = $row['photoID'];
		$return[$photoNum]["title"] = $row["title"];
		$return[$photoNum]["timestamp"] = $row["stamp"];
		$return[$photoNum]["description"] = $row["description"];
		$return[$photoNum]["latitude"] = $row["latitude"];
		$return[$photoNum]["longitude"] = $row["longitude"];
		$photoNum++;
	}

	//return array("lol" => $result);
	return $return;
}

// Return the continent of a given country
function getContinent($db, $country){
	$query = "SELECT continent FROM locations WHERE contry = '$country'";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$row = mysqli_fetch_array($result);
	return $row['continent'];
}

// TODO
// Need to be able to get a country from only the latitude and the londitude
function getCountry($db, $lat, $long){
	// Query should be find the two points that are on roughly same latitude/longitude that encase the point.
	// The point is also in the same country as those two.
	// May fail if taken right on the border? Not sure how accurate the data source is at the moment
	// Inherent inaccuracy 
	return 0;
}
*/

// TODO: Not return self. Or return self but just send to home. Or return self and send to what profile looks like to followers?
function searchDB($db, $searchString){
	$searchString = mysqli_real_escape_string($db, $searchString);
	$query = "SELECT * FROM users WHERE username OR email LIKE '%$searchString%' AND private != 1";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$i = 0;
	$users = array();
	//return array("data" => mysqli_fetch_array($result)["id"]);
	while ($row = mysqli_fetch_array($result) and $i < 20){
		$users[$i] = array('id' => $row["id"], 'username' => $row['username'], 'email' => $row['email']);
		if ($row['trip'] != "N/A"){
			$users[$i]['trip'] = $row['trip'];
		}
		if (isset($row['profpic'])){
			$dir = floor($row['profpic']/10000);
			$fileNum = $row['profpic']%10000;
			$users[$i]['profpic'] = $dir . "/" . $fileNum;
		}
		$i += 1;
	}

	return $users;
}

// Checks whether or not a certain user is visible to the public
// Returns false if the user either does not exist or is set to private
function isVisibleUser($db, $user){
	$user = mysqli_escape_string($db, $user);
	$query = "SELECT private FROM users WHERE id=$user";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	if (mysqli_num_rows($result) == 0){
		$valid = false;
	}else{
		$row = mysqli_fetch_array($result);
		if ($row[0] == 0){
			$valid = true;
		}else{
			$valid = false;
		}
	}
	return array("valid" => $valid);
}

// Checks whether or not the follower is following the userToCheck

function isFollowing($db, $follower, $userToCheck){
	$query = "SELECT * FROM follows_user_$follower WHERE idFollowed=$userToCheck";
	$result = mysqli_query($db, $query) or die('Error querying database. isFollowing');
	if (mysqli_num_rows($result) == 0){
		$follows = false;
	}else{
		$follows = true;
	}
	return array("status" => $follows);
}

// Returns the user ID given a username.
// General user function

function getIdFromUsername($db, $username){
	$query = "SELECT id FROM users WHERE username='$username'";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	$row = mysqli_fetch_array($result);
	return $row['id'];
}

// Called when a map is needed to be filled in
// TODO: Make it possible to specify a trip/time range for the map to be filled in for
function getMapInfo($db, $userID){
	$query = "SELECT latitude, longitude FROM photos_user_$userID ORDER BY stamp";
	$result = mysqli_query($db, $query) or die('Error querying database');
	$countries = array();
	while ($row = mysqli_fetch_array($result)){
		$country = getCountryFromLatLong($db, $row['latitude'], $row['longitude']);
		if (is_null($countries[$country])){
			$countries[$country]['id'] = $row['photoID'];
			$percentages = getcanvasPercentage($db, $country);
			if (isset($percentages[0]['x'])){
				for ($i=0; $i < count($percentages); $i++) { 
					$country = $country + "$i";
					$countries[$country]['x'] = $percentages[$i]['x'];
					$countries[$country]['y'] = $percentages[$i]['y'];
				}
			}else{
				$countries[$country]['x'] = $percentages['x'];
				$countries[$country]['y'] = $percentages['y'];
			}
		}
	}
	$countries["china"]['x'] = 78;
	$countries["china"]['y'] = 33.5;
	$countries["russia"]['x'] = 73;
	$countries["russia"]['y'] = 16;
	$countries["australia"]['x'] = 87;
	$countries['australia']['y'] = 73;
	$countries['india']['x'] = 71.5;
	$countries['india']['y'] = 42.5;
	$countries['canada']['x'] = 18;
	$countries['canada']['y'] = 18;
	$countries['usa0']['x'] = 18;
	$countries['usa0']['y'] = 30;
	$countries['usa1']['x'] = 10;
	$countries['usa1']['y'] = 14;
	return $countries;
}

// Uses latitude and longitude to determine the name of the country that a photo was taken in
function getCountryFromLatLong($db, $lat, $long){
	$query = "SELECT country FROM latLong WHERE latitude AND longitude";
	$result = mysqli_query($db, $query) or die('Error querying database');
	$row = mysqli_fetch_array($result);
	return $row['country'];
}

// Returns the coordinates on the chosen map image of a country to be filled in as a percentage of the image width and height
// Need to update the table pixelCoords if the map image is to be changed
function getcanvasPercentage($db, $country){
	$query = "SELECT x, y FROM pixelCoords WHERE country='$country'";
	$result = mysqli_query($db, $query) or die('Error querying database');
	$percentages = array();
	$i = 0;
	while ($row = mysqli_fetch_array($result)){
		$percentages[$i]['x'] = $row['x'];
		$percentages[$i]['y'] = $row['y'];
		$i += 1;
	}
	return array("lol" => "haha");
}

?>