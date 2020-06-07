<?php
if(!isset($_SESSION)){
	session_start();
}
if(isset($_SESSION['userID'])){
	header("Location: ./index.php");
	exit();
}

if($_SERVER['REQUEST_METHOD'] == "POST"){
	require("./database.php");
	require("./required/passwordEncrypt.php");

	/*
	 * Checks if the email or password fields are empty/null
	 */

	if(is_null($_POST['email']) || empty($_POST['email'])){
		$errors["specific"]["email"] = 1;
	}

	if(is_null($_POST['password']) || empty($_POST['password'])){
		$errors["specific"]["password"] = 1;
	}

	if(!isset($errors)){ // Checks if there are any errors

		$email = mysqli_real_escape_string($dbcon, $_POST['email']);
		$password = $_POST['password'];

		/*
	     * As we're using BCrypt, we'll just search for the email
	     * and compare the given password with the one on the database
	     * using the password_verify() function.
		 */

		$searchEmail = "SELECT * FROM users WHERE email='$email' LIMIT 1";
		$runSearch = @mysqli_query($dbcon, $searchEmail);
		if($runSearch){
			if(mysqli_num_rows($runSearch) != 0){
				while($row = mysqli_fetch_array($runSearch, MYSQLI_ASSOC)){
					if(password_verify($password, $row['password'])){
						// Password is valid!
						$_SESSION['userID'] = $row['id'];
						$_SESSION['isAdmin'] = $row['isAdmin'];
						header("Location: ./index.php");
						exit();
					}else{
						// Given passowrd is not correct.
						$errors["general"] = "Email ou Palavra-Passe incorretos!";
						break;
					}
				}
			}else{
				// Email not founded in the database
				$errors["general"] = "Email ou Palavra-Passe incorretos!";
			}
		}else{
			$errors["general"] = "Error: " . mysqli_error($dbcon);
		}
	}
}


?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
	<title>Price Tracker - Login</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link href="./assets/test_icon.png" rel="shortcut icon">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body style="background-image: url(./assets/web_bg.png); background-size: 100%">
	<div class="container-fluid">
		<div class="row">
			<div class="col-0 col-sm-1 col-md-2 col-lg-4 col-xl-4"></div>
			<div class="col-12 col-sm-10 col-md-8 col-lg-4 col-xl-4">
				<div style="background-color: #fff2cc; border-radius: 5px;">
					<form method="POST" action="./login.php" class="p-3">
						<?php
						/*
						 * Checks if there are any errors to be printed
						 * to the user. If there are, print them in red
						 */
						if(isset($errors["general"])){
							foreach ($errors as $error) {
								echo "<h6 style=\"color: red;\">" . $error . "</h6>";
							}
						}
						?>
						<div class="form-group">
							<label for="email">Email</label>
	    					<input type="email" class="form-control <?php if(isset($errors["specific"]["email"])){echo "is-invalid";} ?>" id="email" name="email" aria-describedby="emailHelp" placeholder="Endereço de Email">
	    					<div class="invalid-feedback">
	    						Por favor, insere o teu email!
	    					</div>
	    					<small id="emailHelp" class="form-text text-muted">O endereço de email que usaste quando criaste a tua conta no Price Tracker</small>
						</div>
						<div class="form-group">
							<label for="currentPassword">Palavra-Passe</label>
	    					<input type="password" class="form-control <?php if(isset($errors["specific"]["password"])){echo "is-invalid";} ?>" id="currentPassword" name="password" placeholder="Palavra-Passe">
	    					<div class="invalid-feedback">
	    						Por favor, insere a tua Palavra-Passe!
	    					</div>
						</div>
						<button type="submit" class="btn btn-primary">Iniciar Sessão!</button>
						<button class="btn btn-secondary" onclick="event.preventDefault(); document.location.href = './registar.php';">Registar?</button>
					</form>
				</div>
			</div>
			<div class="col-0 col-sm-1 col-md-2 col-lg-4 col-xl-4"></div>
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/jquery-3.3.1.js" integrity="sha256-2Kok7MbOyxpgUVvAk/HJ2jigOSYS2auK4Pfzbm7uH60=" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>