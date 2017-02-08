<?php

	if ($_POST["login"]){
		$loginDetails = http_build_query(
			array(
				'usernameEntered' => $_POST['username'],
				'passwordEntered' => $_POST['password'],
				'login' => True
			)
		);

		$options = array('http' =>
			array(
				'method' => 'POST',
				'content' => $loginDetails
			)
		);
		$context = stream_context_create($options);
		$json = file_get_contents("http://localhost/api.php", false, $context);
		$result = json_decode($json, true);
		if ($result['valid']){
			header('Location: Mymap.php');
			exit;
		}elseif ($result['reason'] == 'password'){
			$reason = "Incorrect Password";
		}else{
			$reason = "Username/Email is not Registered";
		}
	}

	if (isset($_COOKIE["userID"])){
		header('Location: Mymap.php');
		exit;
	}
 ?>

<!DOCTYPE html>
<html lang="en">


	<head>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
	<script>
		$(document).ready(function(){
			$("#login").submit(function(event){

				event.preventDefault();
				//alert("submitted");
				serializedData = $(this).serialize() + "&login=true";				

				apiRequest = $.ajax({
					url: "api.php",
					type: "post",
					data: serializedData
				}).done(function(response){
					if (response['valid']){

						console.log(response);
						window.location = "Mymap.php";
					}else if(response['reason'] == 'password'){
						//handle invalid password
						console.log("password");
						$("#loginFail").html("Password invalid");
					}else{
						//handle invalid username
						$("#loginFail").html("Username/Email does not exist");
					}
				});
			});

			$("#registerButton").click(function(){
				$("#registerNew").show();
			});

			$("#rexit").click(function(){
				$("#registerNew").hide();
			});

			$("#register").submit(function(event){
				event.preventDefault();

				fields = $(this).serializeArray();
				console.log(fields);
				if (!fields[0]["value"] || !fields[1]["value"] || !fields[2]["value"] || !fields[3]["value"] || !fields[4]["value"]|| !fields[5] || fields[6]){ 
					console.log("Damn");
					$("#afterReg").html("Please fill out all components of form and select only one of private and public");
				}else if (fields[0]["value"] != fields[1]["value"]){
					$("#afterReg").html("Emails do not match");
				}else if (fields[3]["value"] != fields[4]["value"]){
					$("#afterReg").html("Please ensure entered passwords match");
				}else{
					serializedData = $(this).serialize() + "&register=true";
					console.log(serializedData);

					apirequest = $.ajax({
						url: "api.php",
						type: "post",
						data: serializedData
					}).done(function(response){
						console.log(response);
						$("#afterReg").html(response);
						window.location = "Mymap.php";
					});
				}
			});
		});

	</script>

 	</head>
 	<body>
 	<form id="login">
 	<?php
 		$username = $_POST["username"];
 		$password = $_POST["password"];

 		
 		echo '<input type="text" name="username" value='."\"$username\"".' placeholder="Username">' ."\n\t";
 		echo '<input type="password" name="password" value='."\"$password\"".' placeholder="Password">' . "\n\t";
 		echo '<input type="submit" name="login" value="Login" ><br/>';

 	?>
 	
 	</form>
 	<div id="loginFail">
 	</div>
 	<button id="registerButton">Register</button>
 	<div id="registerNew" style="display: none">
 		<form id="register">
 			<input type="text" name="newEmail0" placeholder="Email">
 			<input type="text" name="newEmail1" placeholder="Confirm Email">
 			<br/>
 			<input type="text" name="newUsername" placeholder="Username">
 			<input type="password" name="newPassword0" placeholder="Password">
 			<input type="password" name="newPassword1" placeholder="Confirm Password">
 			<br/>
 			<input type="radio" name="private" value="private">Private
 			<input type="radio" name="public" value="public">Public
 			<input type="submit" name="register">
 		</form>
 		<button id="rexit">X</button>
 		<div id="afterReg">
 		</div>

 	</div>

 	
	</body>
</html>