<?php
if(!isset($_SESSION)){
    session_start();
}
if(!isset($_SESSION['userID'])){
    header("Location: ./login.php");
    exit();
}
require("./database.php");

$productID = $_GET['productID'];
$productID = mysqli_real_escape_string($dbcon, $productID);
$userID = $_SESSION['userID'];

$rmItem = "DELETE FROM userItems WHERE userID='$userID' AND itemID='$productID'";
$run_rmitem = @mysqli_query($dbcon, $rmItem);
if($run_rmitem){
	header("Location: ./index.php");
}else{
	echo mysqli_error($dbcon);
}

?>