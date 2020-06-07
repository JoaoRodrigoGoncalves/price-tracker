<?php
if(!isset($_SESSION)){
	session_start();
}
if(!isset($_SESSION['userID'])){
	header("Location: ./login.php");
	exit();
}
require("./database.php");
$productID = mysqli_real_escape_string($dbcon, $_GET['item']);
$userID = $_SESSION['userID'];

$update = "UPDATE userItems SET isImportant='1' WHERE itemID='$productID' AND userID='$userID'";
$run_Update = @mysqli_query($dbcon, $update);
if($run_Update){
	echo "1";
	exit();
}else{
	echo mysqli_error($dbcon);
	exit();
}
?>