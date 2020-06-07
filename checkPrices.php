<?php
require("database.php");
require("urlParser.php");
require("updater.php");

function array_key_last($array) {
	/* http://php.net/manual/en/function.array-key-last.php#123016
	 * Function to get the last key of an array since we're not in PHP 7.3
	 * by Anyvie at devlibre dot fr
	 */
    if (!is_array($array) || empty($array)) {
        return NULL;
    }
    return array_keys($array)[count($array)-1];
}

$get_Items = "SELECT * FROM items";
$run_Items = mysqli_query($dbcon, $get_Items);
if($run_Items){
	$num = mysqli_num_rows($run_Items);
	while ($row = mysqli_fetch_array($run_Items, MYSQLI_ASSOC)) {
		$itemsInformation[$row['itemID']]['itemID'] = $row['itemID'];
		$itemsInformation[$row['itemID']]['itemURL'] = $row['itemURL'];
		$itemID = $row['itemID'];
		$get_Prices = "SELECT * FROM prices WHERE itemID='$itemID' ORDER BY date ASC LIMIT 1";
		$run_Prices = mysqli_query($dbcon, $get_Prices);
		if($run_Prices){
			if($row = mysqli_fetch_array($run_Prices, MYSQLI_ASSOC)){
				$itemsInformation[$itemID]['price'] = $row['price'];
				$itemsInformation[$itemID]['date'] = $row['date'];
			}
		}else{
			echo mysqli_error($dbcon);
		}
	}
}else{
	echo mysqli_error($dbcon);
}

/*
 * Progress Bar to display server’s script execution: using JQuery/JQueryUI
 * http://www.hellothupten.com/2011/07/20/progress-bar-to-display-servers-script-execution-using-jqueryjqueryui/
 */

//create jason file and put default 0 values. we will update these values later 
$fp = fopen('checkStatus.json', "w");
$arr = array('total'=>'0', 'current'=>'0');
fwrite($fp, json_encode($arr));  
fclose($fp);
//update the total  
$arr['total'] = $num;  
$currentStep = 0;

foreach ($itemsInformation as $item) {
	// ------ Code for the progress bar --------
	$currentStep++;
	$arr['current'] = $currentStep;
	$fp = fopen("checkStatus.json", "w");  
	fwrite($fp, utf8_encode(json_encode($arr)));  
	fclose($fp);

	// ------- End of the code for the progress bar -----

	$store = getStore($item['itemURL']);

	switch ($store) {
		case 'chip7.pt':
			$productInfo = chip7Product($item['itemURL']);
			break;

		case 'pcdiga.com':
			$productInfo = pcdigaProduct($item['itemURL']);
			break;

		case 'globaldata.pt':
			$productInfo = globaldataProduct($item['itemURL']);
			break;

		case 'worten.pt':
			$productInfo = wortenProduct($item['itemURL']);
			break;
		
		default:
			throw new Exception("Loja não suportada", 1);
			break;
	}


	if($productInfo['price'] == "-1"){
		unset($itemsInformation[$productInfo['id']]);
	}elseif(($productInfo['price'] == "") || (is_null($productInfo['price']))){
		$productPrice[$productInfo['id']] = "";
	}else{
		$productPrice[$productInfo['id']] = $productInfo['price'];
	}
}

print_r($productPrice);

/*$currentDate = date("Y-m-d");
if($itemsInformation[array_key_last($itemsInformation)]['date'] == $currentDate){
	$delOtherRows = "DELETE FROM prices WHERE date='$currentDate'";
	$run_del = mysqli_query($dbcon, $delOtherRows);
	if(!$run_del){
		echo mysqli_error($dbcon) . "<br>Query: " . $delOtherRows . "";
		exit();
	}
}

foreach ($itemsInformation as $item) {
	if(!isset($productPrice[$item['itemID']])){
		fixIds();
		header("Location: ./checkPrices.php");
	}
}

foreach ($itemsInformation as $itemInfo) {
	$currentItem = $itemInfo['itemID'];
	$price = $productPrice[$currentItem];
	if(($price != null) || ($price != "")){
		$updatePrices = "INSERT INTO prices (itemID, date, price) VALUES ('$currentItem', '$currentDate', '$price')";
	}else{
		$updatePrices = "INSERT INTO prices (itemID, date, price) VALUES ('$currentItem', '$currentDate', NULL)";
	}
	$runUpdate = mysqli_query($dbcon, $updatePrices);
	if(!$runUpdate){
		echo "Erro: " . mysqli_error($dbcon) . "<br>Query: " . $updatePrices . "";
	}
}
echo "1";
*/
?>