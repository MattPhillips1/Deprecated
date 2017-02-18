<?php
	// Matthew Phillips 15/12/2016
	// If the user is not logged in, go to the login page
	if (!isset($_COOKIE["userID"])){
		header('Location: login.php');
		exit;
	}
?>

<!DOCTYPE html>
<html lang="en">


	<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<link href="styles/main.css" rel="stylesheet"> 
	<script src="scripts/shared.js"></script>
	<script src="scripts/maps.js"></script>
	<script>

	

		// TODO: Separate the different JS scripts into individual files to clean
		$(document).ready(function(){

			$("#body_content").css("padding-top", $(".top_bar").outerHeight());
			$("#body_content").css("height", $(window).height()-$(".top_bar").outerHeight());
			

			<?php
			
			function checkIfVisible($id){

				$url = "http://localhost/Mymap/api.php";
				$data = array("userToCheck" => $id);
				$options = array(
						'http' => array(
							'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
							'method' => 'POST',
							'content' => http_build_query($data)
						)
				);
				$context = stream_context_create($options);
				$result = json_decode(file_get_contents($url, false, $context));
				
				return $result->valid;
			}

				// Check the url to see what form to display the data in
				if ($_GET['guest'] == 'true'){
					if (!checkIfVisible($_GET['visiting'])){
						header('Location: Mymap.php');
						exit;
					}else{
						?>
						getBasicInfo(<?php echo $_GET['visiting']?>);
						addFollowButton(<?php echo $_GET['visiting']?>);						
						fillLargeCanvas(<?php echo $_GET['visiting']?>);
						//dropPins();
						<?php
					}

				}elseif($_GET["search_button"] == "Submit"){
					?>
					getBasicInfo(<?php echo $_COOKIE["userID"]?>);
					<?php
					$url = "http://localhost/Mymap/api.php";
					$data = array("search_text" => $_GET['search_text']);
					$options = array(
						'http' => array(
							'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
							'method' => 'POST',
							'content' => http_build_query($data)
						)

					);
					$context = stream_context_create($options);
					$result = json_decode(file_get_contents($url, false, $context));
					if (count($result) != 0){

						foreach ($result as $user){
							
							if (is_null($user->profpic)){
								$pic = "stock/prof_pic.png";
							}else{
								$pic = "stock/prof_pic.png";
							}
							
							$html = "<div class='search_result' id='" . $user->id . "_link'><a href='Mymap.php?guest=true&visiting=" . $user->id . "' class='lol'><img src=images/" . $pic . " width='50' height='50'></a><a href='Mymap.php?guest=true&visiting=" . $user->id . "' class='lol'>" . $user->username . "</a></div>";
							echo "$('#body_content').append(\"$html\");\n\t\t\t";
						}	

					}else{
						?>
						$('#body_content').html("No matches/No Search Query");
						<?php
					}

				}else{ 
					?>
					getBasicInfo(<?php echo $_COOKIE["userID"]?>);
					<?php

					switch($_GET["display"]){
						case "Feed":
							?>
							makePhotoRequest(1, 1, '2019-12-18 02:15:54', 3, false);
							<?php
							break;
						case "mapFeed":
							?>
							makePhotoRequest(1, 1, '2019-12-18 02:15:54', 3, false);
							<?php
							break;
						default:
							?>
							// Default is full screen map
							fillLargeCanvas(<?php echo $_COOKIE['userID']?>);
							//dropPins();
							<?php
					}
				}
			?>
  
			
		});


		
		
	</script>
 	</head>
 	<body>
 	<div class="top_bar">
 		<span id="basic_profile" class="basic_profile">
 			<img src="images/stock/prof_pic.png" width="50" height="50">
 			<div id="profile_text"></div>
 		</span>
 		<form class="search_bar" id="search_bar" onsubmit="search()">
 			<input type="text" name="search_text" id="search_text" class="entry_bar" placeholder="Search">
 			<input type="submit" name="search_button" id="search_button" style="display: none;">
 		</form>
 	
 		<button id="display_options" class="menu_button">Menu</button>
	 	<button id="uploadInit" class="menu_button">Upload Photo</button>
	 	<button id="settings_button" class="menu_button">Settings</button>
 	</div>
	 	<div class="upload_section" id="uploadSection" style="display: none">
	 		<button class="close_button" id="uploadCancel">X</button>

		 	<form id="upload" enctype="multipart/form-data">
		 		<input type="file" name="fileToUpload" id="fileToUpload" accept="image/jpeg, image/png">
		 		<input type="text" class="entry_bar" name="title" id="title" placeholder="Title (optional)">
		 		<br/>
		 		<select>
		 			<option value="Australia">Australia</option>
		 		</select>
		 		<input type="radio" name="private" id="private">Private
		 		<textarea value="description" id="description" placeholder="Description (optional)" rows="20" cols="75"></textarea>
		 		<br/>
		 		<input type="submit" name="upload" id="uploadButton">
	 		</form>
	 		<div id="uploadStatus">
	 		</div>
	 	</div>
	 	
 	
	 	<div class="options" id="settings">
	 		<?php
	 			$options = ['Trip', 'Follows','Privacy', 'Logout'];
	 			foreach ($options as $option){
	 				echo "<button class=\"option\" id=\"$option\">$option</button>";
	 			}
	 		?>
	 	</div>
 	<div class="options" id="options">
 		<?php
 			$options = ['Home', 'Feed', 'Maps'];
 			foreach ($options as $option){
 				echo "<form>";
 				echo "<input type=\"hidden\" name=\"display\" value=\"$option\">";
 				echo "<input type=\"submit\" class=\"option\" value=\"$option\">";
 				echo "</form>";
 			}
 		?>
 	</div>
 	<div class="trip_form" id="trip_form" style="display: none">
 		<form method="post" id="set_trip">
 			From
 			<input type="date" name="from_date" class="date_entry" id="from_date">
 			To
 			<input type="date" name="to_date" class="date_entry" id="to_date">
 			<input type="submit" name="submit_trip" value="Set">
 		</form>
 		<button class="close_button" id="cancel_trip">X</button>
 	</div>
 	<div class="body_content" id="body_content">
 	</div>

	</body>
</html>