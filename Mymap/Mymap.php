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
	<script>
		// TODO: Separate the different JS scripts into individual files to clean
		$(document).ready(function(){
			// Get the current trip details
			apirequest0 = $.ajax({
				url: "api.php",
				type: "post",
				data: "init=true"
			}).done(function(response){
				console.log(response);
				$('h1').html(response['username'] + " " + response['trip']);
			});

			<?php
				// Check the url to see what form to display the data in
				if ($_GET["display"] == "Feed"){
					echo "makePhotoRequest(1, 1, '2019-12-18 02:15:54', 3, false);";
				}else{
					// Default is full screen map
					echo "$('#body_content').html(\"<canvas class=\\\"full_screen_map\\\" id=\\\"myCanvas\\\" width=500 height=500></canvas>\");";
					echo "fillLargeCanvas();";
				}
			?>


			// AJAX request to upload a file
			$('#upload').submit(function(event){
				event.preventDefault();

				serializedData = $(this).serialize() + "&upload=true";
				file = $('#fileToUpload').prop('files')[0];
				formData = new FormData($(this)[0]);
				formData.append('longitude', -25.2744);
				formData.append('latitude', 133.7751);
				formData.append('description', $('#description').val());
				formData.append('upload', true);
				for(var pair of formData.entries()) {
					console.log(pair[0]+ ', '+ pair[1]); 
				}
				console.log(serializedData);
				apirequest = $.ajax({
					url: "api.php",
					type: "post",
					data: formData,
					cache: false,
                	contentType: false,
                	processData: false
                 
				}).done(function(response){
					if (response["valid"]){
						$('#title').val("");
						$('#fileToUpload').val("");
						$('#description').val("");
						$('#private').prop('checked', false);
						$('#uploadStatus').html("Uploading Complete!");
						$('#uploadSection').hide();
						// This is only in feed moode
						makePhotoRequest(1, 1, '2019-12-18 09:15:54', 1, true);
						
					}else if(response["file"] == "invalid"){
						$('#uploadStatus').html("Please Upload an image file only");
					}
					console.log(response);
				});
				
			});

			$('#uploadInit').click(function(){
				$('#uploadSection').show();
			});

			$('#uploadCancel').click(function(){
				$('#uploadSection').hide();
			});

			$('#display_options').click(function(){
				$('#options').slideToggle('fast'); 
			});

			$('#settings_button').click(function(){
				$('#settings').slideToggle('fast');
			});

			$("#logout").click(function(event){
				event.preventDefault();
				serializedData = "logout=true";
				apirequest = $.ajax({
					url: "api.php",
					type: "post",
					data: serializedData
				}).done(function(response){
					window.location = "login.php";
				});

			});

			$("#body_content").on('click', '.deletePhoto', function() {
				
				item = $(this).parent();
				line = $(this).attr('id');
				line = line + "_br";
				apirequest =$.ajax({
					url: "api.php",
					type: "post",
					data: "deletePhoto=true&button=" + $(this).attr('id')
				}).done(function(response){
					console.log(response);
					console.log(item);
					$(item).remove();
					$("#" + line).remove();

				});


			});

			$("#Trip").click(function(){
				$("#trip_form").show();
				$("#settings").hide();
			});

			$("#cancel_trip").click(function(){
				$("#trip_form").hide();
			});

			$("#set_trip").submit(function(event){
				event.preventDefault();
				serializedData = $(this).serialize() + "&"
			});


			// Makes an AJAX request to the API, which returns the photo info
			function makePhotoRequest(pID, uID, oldStamp, numNeeded, prepend){

				apirequest = $.ajax({
					url: "api.php",
					type: "post",
					data: "photoRequest=true&photoID="+ pID + "&userID=" + uID + "&oldStamp=" + oldStamp + "&number=" + numNeeded
				}).done(function(response){

					console.log(response);
					
					for (photo in response){
						newHTML = "<div class=\"photo_wrapper\" id=\"" + response[photo]['title'] + "_image\">" +
									"<div class=\"photo_title\" id=\"" + response[photo]['title'] + "_title\">" +
									response[photo]['title'] + 
									"</div>"  +
									"<img src=" + response[photo]['path'] + " class=\"photo\">" +
									"<div class=\"photo_description\" id=\"" + response[photo]['title'] + "_desc\">" +
									response[photo]['description'] +
									"</div>" +
									"<button class=\"deletePhoto\" id=\"" + response[photo]['id'] + "\">X</button>" +
								"</div><br id=\"" + response[photo]['id'] + "_br\"/>";
						if (prepend){
							$("#body_content").prepend(
								newHTML
							);
						}else{
							$("#body_content").append(
								newHTML
							);
						}
					}
						
				});
			}

			// Fit the large map into the canvas
			function fillLargeCanvas(){
				canvas = document.getElementById('myCanvas');
      			context = canvas.getContext('2d');
      			canvas.width = $(window).width() - 128;
      			canvas.height = $(window).height() - 128;
      			imageObj = new Image();

      			offScreen = document.createElement('canvas');
      			osContext = offScreen.getContext('2d');

      			imageObj.onload = function() {
      				i = 1;
      				offScreen.width = imageObj.width * 0.5;
	      			offScreen.height = imageObj.height * 0.5;
	      			osContext.drawImage(imageObj, 0, 0, offScreen.width, offScreen.height);
      				while (i < 0){

	      				osContext.drawImage(offScreen, 0, 0, offScreen.width * 0.5, offScreen.height * 0.5);
	      				i += 1;
	      			}
        			//context.drawImage(offScreen, 0, 0, offScreen.width, offScreen.height);
        			context.drawImage(imageObj, 0, 0, canvas.width, canvas.height);
        			console.log(context.getImageData(800, 300, 1,1).data);
        			fillCountries(canvas,context);
        			
      			};
      			imageObj.src = "images/stock/Simple_world_map.png";
			}

			// Algorithm for filling in a country in the map. 
			// Currently a bit slow. 
			// TODO: improve performance
			function fillCountries(canvas, context){
				newdata = context.createImageData(1,1);
				newdata.data[0] = 255;
				newdata.data[1] = 128;
				newdata.data[2] = 276;
				newdata.data[3] = 255;
				pixelStack = [[1000, 150]];
				imageData = context.getImageData(pixelStack[0][0], pixelStack[0][1], 1, 1).data;
				while(pixelStack.length){
					cood = pixelStack.pop();
					//console.log(pixelStack.length);
					y = cood[1];
					x = cood[0];
					while (y >= 0 && sameColour(x, y, imageData, context)){
						y--;
					}
					//console.log("found boundary");
					//console.log(y);
					//console.log(x);
					++y;
					//console.log(y);
					addedLeft = false;
					addedRight = false;

					while (y <= canvas.height && sameColour(x, y, imageData, context)){
						context.putImageData(newdata, x, y++);
						
						if (!addedRight){
							if (sameColour(x+1, y, imageData, context)){
								pixelStack.push([x+1, y]);
								addedRight = true;
							}
						}else if(!sameColour(x+1, y, imageData, context)){
							addedRight = false;
						}
						if (!addedLeft){
							if (sameColour(x-1, y, imageData, context)){
								pixelStack.push([x-1, y]);
								addedLeft = true;
							}
						}else if(!sameColour(x-1, y, imageData, context)){
							addedLeft = false;
						}

						
					}
					//console.log(y);

					
				}
			}
			
			// Check if a pixel is the same colour as another next to it
			function sameColour(X, Y, initData, context){

				checkImageData = context.getImageData(X, Y, 1, 1).data;
				//console.log("compare to ");
				//console.log(initData);
				newR = checkImageData[0];
				newG = checkImageData[1];
				newB = checkImageData[2];

				//console.log(newR);
				//console.log(newG);
				//console.log(newB);


				return(newR == initData[0] && newG == initData[1] && newB == initData[2]);
			}
			
		});
	</script>
 	</head>
 	<body>
 	<div class="top_bar">
 		<span class="basic_profile">
 			Place Holder for info
 		</span>
 		<form class="search_bar" id="search_bar">
 			<input type="text" name="search_text" id="search_text" class="entry_bar" placeholder="Search">
 			<input type="submit" name="search_button" id="search_button" style="display: none;">
 			By
 			<select>
 				<option value="username_search">Username</option>
 				<option value="destination_search">Destination</option>
 			</select>
 		</form>
 	
 		<button id="display_options" class="menu_button">Menu</button>
	 	<button id="uploadInit" class="menu_button">Upload Photo</button>
	 	<button id="settings_button" class="menu_button">Settings</button>
 	</div>
	 	<div id="uploadSection" style="display: none">
		 	<form id="upload" enctype="multipart/form-data">
		 		<input type="file" name="fileToUpload" id="fileToUpload" accept="image/jpeg, image/png">
		 		<input type="text" name="title" id="title" placeholder="Title (optional)">
		 		<select>
		 			<option value="Australia">Australia</option>
		 		</select>
		 		<input type="radio" name="private" id="private">Private
		 		<textarea value="description" id="description" placeholder="Description (optional)"></textarea>
		 		<input type="submit" name="upload" id="uploadButton">
	 		</form>
	 		<button class="close_button" id="uploadCancel">X</button>
	 	</div>
	 	<div id="uploadStatus">
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