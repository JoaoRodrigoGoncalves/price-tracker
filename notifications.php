<?php
if(!isset($_SESSION)){
	session_start();
}
if(!isset($_SESSION['userID'])){
	header("Location: ./login.php");
	exit();
}

function getNotificationsNumber(){
	require("./database.php");
	$userID = $_SESSION['userID'];
	$notifications = "SELECT * FROM userNotifications WHERE userID='$userID'";
	$get_notifications = mysqli_query($dbcon, $notifications);
	if($get_notifications){
		return mysqli_num_rows($get_notifications);
	}else{
		return mysqli_error($dbcon);
	}
}

function getNotificationsText(){
	require("./database.php");
	$userID = $_SESSION['userID'];
	$notifications = "SELECT * FROM userNotifications WHERE userID='$userID'";
	$get_notifications = mysqli_query($dbcon, $notifications);
	if($get_notifications){
		$userNotifications = array();
		while ($row = mysqli_fetch_array($get_notifications, MYSQLI_ASSOC)) {
			$userNotifications[$row['id']]['id'] = $row['id'];
			$userNotifications[$row['id']]['message'] = $row['message'];
			$userNotifications[$row['id']]['status'] = $row['status'];
		}
		return $userNotifications;
	}else{
		return mysqli_error($dbcon);
	}
}
?>