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
	$result_json = getShownDetails($db, $_COOKIE['userID']);
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
			setcookie($cookie, $cookie_value, time() + (86400 * 30), "/");
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
	setcookie($cookie, $cookie_value, time() + (86400 * 30), "/");
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
		'$description'
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
function follow_user($db){
	$query ="INSERT INTO follows_user_$_COOKIE[user] VALUES ($_POST[toFollow], CURDATE())";
	$result = mysqli_query($db, $query) or die('Error querying database.');
	return array("valid" => $result);
}

// Delete a user from the database of another
function unfollow_user($db){
	$query = "DELETE FROM follows_user_$_COOKIE[user] WHERE idFollowed=$_POST[toUnfollow]";
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

function setTrip($db){
	

	return array("trip" => $_POST['trip']);

}
?>