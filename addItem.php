<?php
if(!isset($_SESSION)){
    session_start();
}
if(!isset($_SESSION['userID'])){
    header("Location: ./login.php");
    exit();
}
require("./database.php");
require("./urlParser.php");

$productURL = $_POST['productURL'];
if(empty($productURL)){
	header("Location: ./index.php");
}
$productURL = mysqli_real_escape_string($dbcon, $productURL);
$thisUserID = $_SESSION['userID'];

$store = getStore($productURL);

if($store == "chip7.pt"){
	$productInfo = chip7Product($productURL);
}elseif ($store == "pcdiga.com") {
	$productInfo = pcdigaProduct($productURL);
}elseif ($store == "globaldata.pt") {
	$productInfo = globaldataProduct($productURL);
}elseif ($store == "worten.pt"){
	$productInfo == wortenProduct($productURL);
}else{
	throw new Exception("Loja não suportada", 1);
	exit();
}
if($productInfo['price'] <= 0){
	echo "Item inixistente! <a href=\"./\">Voltar!</a>";
	exit();
}
$productID = $productInfo['id'];
$productName = $productInfo['name'];
$productPrice = $productInfo['price'];
$productStore = $productInfo['store'];
$productSKU = $productInfo['sku'];
$productGTIN = $productInfo['gtin'];

/*
 * This checks if the item that the user is trying to add is already on the user's list
 */

$currentItems = "SELECT * FROM userItems WHERE userID='thisUserID' AND itemID='$productID'";
$run_currentItems = @mysqli_query($dbcon, $currentItems);
if($run_currentItems){
	if(mysqli_num_rows($run_currentItems) != 0){
		header("Location: ./index.php");
	}
}else{
	echo mysqli_error($dbcon);
	exit();
}

/*
 * This checks if the product is already registed. If it is, we'll just add it to
 * the current user's list of products
 */

$checkItem = "SELECT * FROM items WHERE itemID='$productID'";
$run_check = @mysqli_query($dbcon, $checkItem);
if($run_check){
    if(mysqli_num_rows($run_check) != 0){
        // Item is already registed. Jusst adding it to the user's list
        $addtoList = "INSERT INTO userItems (userID, itemID) VALUES ($thisUserID, '$productID')";
        $run_add = @mysqli_query($dbcon, $addtoList);
        if($run_add){
            header("Location: ./index.php");
            exit();
        }else{
            echo mysqli_error($dbcon);
            exit();
        }
    } //Continue the script
}else{
    echo mysqli_error($dbcon);
    exit();
}

/*
 * This piece gets every date already registed on the system to be added later on to the new
 * product as if its cost was 0.
 */
$date = date("Y-m-d");
$getOldDates = "SELECT date FROM prices";
$runOldDates = @mysqli_query($dbcon, $getOldDates);
if($runOldDates){
	$oldDates = array();
	while($row = mysqli_fetch_array($runOldDates, MYSQLI_ASSOC)){
		if(!in_array($row['date'], $oldDates)){
			if($row['date'] != $date){
				$oldDates[] = $row['date'];
			}
		}
	}
}

/*
 * Adds a row for each date already registed on the system with the cost = 0
 */
$date = date("Y-m-d");
foreach ($oldDates as $oDate) {
	if($oDate != $date){
		$insertDate = "INSERT INTO prices (itemID, date, price) VALUES ('$productID', '$oDate', NULL)";
		$runInsert = @mysqli_query($dbcon, $insertDate);
		if(!$runInsert){
			echo mysqli_error($runInsert);
			exit();
		}
	}
}

/*
 * Now, we'll register the new product into the database and onto the user's list
 */

$insert = "INSERT INTO items (itemID, itemName, itemURL, r, g, b, store, sku, gtin) VALUES ('$productID', '$productName', '$productURL', '" . rand(0, 255) . "', '" . rand(0, 255) . "', '" . rand(0, 255) . "', '$productStore', '$productSKU', '$productGTIN')";
$run = @mysqli_query($dbcon, $insert);
if(!$run){
	echo mysqli_error($dbcon);
}else{
    /*
     * Here, we're adding the product to the user's list
     */
    $userList = "INSERT INTO userItems (userID, itemID) VALUES ('$thisUserID', '$productID')";
    $run_userList = @mysqli_query($dbcon, $userList);
    if($run_userList){
        $date = date("Y-m-d");
        /*
         * Now, we're adding the current value of the product to the database
         */
        $insertPrice = "INSERT INTO prices (itemID, date, price) VALUES ('$productID', '$date', '$productPrice')";
        $runPrice = @mysqli_query($dbcon, $insertPrice);
        if($runPrice){
            /*
             * Now, we'll add every other item again to today's date
             */
            $getProducts = "SELECT * FROM items";
            $run_getProducts = @mysqli_query($dbcon, $getProducts);
            if($run_getProducts){
                if(mysqli_num_rows($run_getProducts) != 0){
                    while ($row = mysqli_fetch_array($run_getProducts, MYSQLI_ASSOC)) {
                        /*
                         * Checks if the current item isn't the one we just added
                         */
                        if($row['itemID'] != $productID){
                            $currentItemID = $row['itemID'];
                            $getPrice = "SELECT price FROM prices WHERE itemID='$currentItemID' ORDER BY date DESC LIMIT 1";
                            $run_getPrices = @mysqli_query($dbcon, $getPrice);
                            if($run_getPrices){
                                while ($thisprice = mysqli_fetch_array($run_getPrices, MYSQLI_ASSOC)) {
                                    $currentPrice = $thisprice['price'];
                                    $delOtherRows = "DELETE FROM prices WHERE date='$date' AND itemID='$currentItemID'";
									$run_del = @mysqli_query($dbcon, $delOtherRows);
                                    $insertCurrent = "INSERT INTO prices (itemID, date, price) VALUES ('$currentItemID', '$date', '$currentPrice')";
                                    $run_insertCurrent = @mysqli_query($dbcon, $insertCurrent);
                                }
                            }
                        }
                    }
                }
                header("Location: ./");
            }else{
                /*
                 * An error! Removing previous inserted data
                 */
                echo mysqli_error($dbcon);
                $rm = "DELETE FROM items WHERE itemID='$productID'";
                $run_rum = @mysqli_query($dbcon, $rm);
                $rm = "DELETE FROM userItems WHERE itemID='$productID'";
                $run_rum = @mysqli_query($dbcon, $rm);
            }
        }else{
            /*
             * An error! Removing previous inserted data
             */
            echo mysqli_error($dbcon);
            $rm = "DELETE FROM items WHERE itemID='$productID'";
            $run_rum = @mysqli_query($dbcon, $rm);
        }
    }else{
        echo mysqli_error($dbcon);
        exit();
    }
}
?>