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

			$("#body_content").css("padding-top", $(".top_bar").outerHeight());
			// Get the current trip details
			function getBasicInfo(id){
				apirequest0 = $.ajax({
					url: "api.php",
					type: "post",
					data: "init=true&id=" + id
				}).done(function(response){
					console.log(response);
					$('#basic_profile').append(response['username'] + " " + response['trip']);
				});
			}

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
				
				


				if ($_GET["display"] == "Feed"){
			?>
					makePhotoRequest(1, 1, '2019-12-18 02:15:54', 3, false);
			<?php
				}elseif($_GET["search_button"] == "Submit"){
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


				}elseif ($_GET['guest'] == 'true') {
					if (checkIfVisible($_GET["visiting"])){
						echo "god";
					}
					if (isset($_GET["visiting"]) and checkIfVisible($_GET["visiting"])){
						?> 
						getBasicInfo(<?php echo $_GET["visiting"]?>);						
						fillLargeCanvas(<?php echo $_GET['visiting']?>);
						dropPins();
						<?php	
					}else{
						?> 
						getBasicInfo(<?php echo $_COOKIE["userID"]?>);
						fillLargeCanvas(<?php echo $_COOKIE['userID']?>);
						dropPins();
						<?php
					}
				
				}else{
				?>
					// Default is full screen map
					getBasicInfo(<?php echo $_COOKIE["userID"]?>);
					fillLargeCanvas(<?php echo $_COOKIE['userID']?>);
					dropPins();
				<?php
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
				$('#body_content').html("<div id=\"map\">\n" + 
						"<canvas class=\"full_screen_map\" id=\"myCanvas\" width=500 height=500></canvas>\"\n" + 
						"\"</div>\"");
				canvas = document.getElementById('myCanvas');
      			context = canvas.getContext('2d');
      			canvas.width = $(window).width() - 200;
      			canvas.height = $(window).height() - 168;
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
				//newdata = context.createImageData(1,1);
				offScreen = document.createElement('canvas');
      			osContext = offScreen.getContext('2d');
      			newdata = osContext.createImageData(1,1);
				newdata.data[0] = 4;
				newdata.data[1] = 128;
				newdata.data[2] = 80;
				newdata.data[3] = 255;
				red = 4;
				green = 128;
				blue = 80;
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
					yInit = ++y;
					height = 0;
					//console.log(y);
					addedLeft = false;
					addedRight = false;

					while (y <= canvas.height && sameColour(x, y, imageData, context)){
						//context.putImageData(newdata, x, y++);
						++y;
						height += 1;
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
					
					newdata = osContext.createImageData(1,height);
					for (i = 0; i < newdata.data.length; ++i){
						switch(i%4){
							case 0:
								newdata.data[i] = 4;
								break;
							case 1:
								newdata.data[i] = 128;
								break;
							case 2:
								newdata.data[i] = 80;
								break;
							case 3:
								newdata.data[i] = 255;
						}
					}
					//context.putImageData(newdata, x, y++)
					context.putImageData(newdata, x, yInit);

					
				}
			}
			/*

			function fillCountriesRecursive(canvas, context){

			pixelStack = [[1000, 150]];
			imageData = context.getImageData(pixelStack[0][0], pixelStack[0][1], 1, 1).data;
			y = pixelStack[0][1];
			x = pixelStack[0][0];

			while (pixelStack.length){
				cood = pixelStack.pop();
				y = cood[1];
				x = cood[0];

				addedTop = false;
				addedBottom = false;
				addedLeft = false;
				addedRight = false;

				while (y >= 0 && sameColour(x, y, imageData, context)){
					--y;
				}
				++y;
				highest = y;
				
				/*
				startY = y;
				while (y <= canvas.height && sameColour(x, y, imageData, context)){
					++y;
				}
				--y;
				height = y - startY;
				area = height;
				maxArea = area;
				
				while (x >= 0 && sameColour(x, y, imageData, context)){
					--x;
					if (!addedTop){
						if (sameColour(x, y-1, imageData, context)){
							pixelStack.push([x, y-1]);
							addedTop = true;
						}
					}else if(!sameColour(x, y-1, imageData, context)){
							addedTop = false;
					}

				}
				++x;
				farLeft = x;
				x = cood[0]
				addedTop = false;
				while (x <= canvas.width && sameColour(x, y, imageData, context)){
					++x;
					if (!addedTop){
						if (sameColour(x, y-1, imageData, context)){
							pixelStack.push([x, y-1]);
							addedTop = true;
						}
					}else if(!sameColour(x, y-1, imageData, context)){
						addedTop = false;
					}
				}
				--x;
				farRight = x;
				x = cood[0];

				while (y <= canvas.height && sameColour(x, y, imageData, context)){
					++y;
				}
				--y;
				lowest = y;

				while (x >= 0 && sameColour(x, y, imageData, context)){
					--x;
					if (!addedBottom){
						if (sameColour(x, y+1, imageData, context)){
							pixelStack.push([x, y+1]);
							addedBottom = true;
						}
					}else if(!sameColour(x, y+1, imageData, context)){
							addedBottom = false;
					}
				}
				++x;
				if (x > farLeft){
					farLeft = x;
				}
				addedBottom = false;
				x = cood[0];
				while (x <= canvas.width && sameColour(x, y, imageData, context)){
					++x;
					if (!addedBottom){
						if (sameColour(x, y+1, imageData, context)){
							pixelStack.push([x, y+1]);
							addedBottom = true;
						}
					}else if(!sameColour(x, y+1, imageData, context)){
							addedBottom = false;
					}
				}
				--x;
				if (x < farRight){
					farRight = x;
				}

				

				

				x = farLeft;
				while (y >= highest){
					if (!addedLeft){
						if (sameColour(x-1, y, imageData, context)){
							pixelStack.push([x-1,y]);
							addedLeft = true;
						}
					}else if(!sameColour(x-1, y, imageData, context)){
						addedLeft = false;
					}
					--y;
				}

				x = farRight;
				while (y <= lowest){
					if (!addedRight){
						if (sameColour(x+1, y, imageData, context)){
							pixelStack.push([x+1, y]);
							addedRight = true;
						}else if(!sameColour(x+1, y, imageData, context)){
							addedRight = false;
						}
					}
					++y;
				}

				console.log("Left " + farLeft);
				console.log("Right " + farRight);
				console.log("Highest " + highest);
				console.log("Lowest " + lowest);
				newdata = context.createImageData(farRight - farLeft + 1, lowest - highest + 1);
				for (i = 0; i < newdata.data.length; ++i){
					switch(i%4){
						case 0:
							newdata.data[i] = 4;
							break;
						case 1:
							newdata.data[i] = 128;
							break;
						case 2:
							newdata.data[i] = 80;
							break;
						case 3:
							newdata.data[i] = 255;
					}
				}
				//context.putImageData(newdata, x, y++)
				context.putImageData(newdata, farLeft, highest);
			}


		}
		*/
			
			
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

			function dropPins(){
				style = "style=\"position: absolute; left: 70%; top: 14%; z-index: 2;\">";
				$("#map").append("<img src=\"images/stock/pin.png\" height=\"50\" width=\"40\" " + style);
				style = "style=\"position: absolute; left: 50%; top: 40%; z-index: 2;\">";
				$("#map").append("<img src=\"images/stock/pin.png\" height=\"50\" width=\"40\" " + style);
			}

			
		});
		

		
		
	</script>
 	</head>
 	<body>
 	<div class="top_bar">
 		<span id="basic_profile" class="basic_profile">
 		<img src="images/stock/prof_pic.png" width="50" height="50">
 			
 		</span>
 		<form class="search_bar" id="search_bar" onsubmit="search()">
 			<input type="text" name="search_text" id="search_text" class="entry_bar" placeholder="Search">
 			<input type="submit" name="search_button" id="search_button" style="display: none;">
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