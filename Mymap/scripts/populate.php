<?php

// Connext to local database. I will have to change this to server database on upload
$db = mysqli_connect('localhost','root','Isthatmatt?','users')
or die('Error connecting to MySQL server.');

$users = ["Matthew", "Mark", "Luke", "John", "Judas", "Walter", "Jesse", "Gus", "Hank", "David", "WalterJr", "Bruce", "Isaac", "Taylor", "Davis", "Asha", "aSHa"];


foreach ($users as $single){
	$query = "SELECT MAX(id) FROM users";
	//return $query;
	$result = mysqli_query($db, $query) or die('Error querying database.?');

	$max = mysqli_fetch_array($result);
	$max = $max['0'] + 1;

	$privacy = 0;
	echo "??$max\n";
	$query = "CREATE TABLE photos_user_$max(
		photoID int,
		stamp timestamp,
		longitude real,
		latitude real,
		private boolean,
		title varchar(255),
		description text
		)";
	$result = mysqli_query($db, $query) or die('Error querying database.???');

	$query = "INSERT INTO users VALUES($max, '$single', '$single', '$single@hotmail.com', CURDATE(), $privacy, 'N/A')";

	$result = mysqli_query($db, $query) or die('Error querying database.??');

	

	$query = "CREATE TABLE follows_user_$max(
		idFollowed int,
		since date
	)";

	$result = mysqli_query($db, $query) or die('Error querying database.????');

	$query = "CREATE TABLE trips_$max(
		continent varchar(255),
		country varchar(255),
		start date,
		end date
	)";

	$result = mysqli_query($db, $query) or die('Error querying database.?????');
}

?>