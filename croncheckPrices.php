<?php
echo "Update Started at " . date("Y-m-d H:i:s") . "...";
require("database.php");
require("urlParser.php");
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
// ----- Get Items ----------
/*
 * This block will create and add information about a product to
 * an array. It will be added the itemID, itemName, itemURL, r, g and b to the
 * array under the key with the value of the current itemID.
 */
$getItems = "SELECT * FROM items ORDER BY id ASC";
$runItems = @mysqli_query($dbcon, $getItems);
if($runItems){
	$num = mysqli_num_rows($runItems); //this will be used later for the progress bar
	while ($row = mysqli_fetch_array($runItems, MYSQLI_ASSOC)) {
		$thisItemID = $row['itemID'];
		$itemsInformation[$thisItemID]['itemID'] = $thisItemID;
		$itemsInformation[$thisItemID]['itemURL'] = $row['itemURL'];
	}

	/*
	 * From now til the end of this "block", we'll add the price of each product
	 * and the date when such price was registed.
	 */

	foreach ($itemsInformation as $item) {
		$ID = $item['itemID'];
		$getPrices = "SELECT * FROM prices WHERE itemID='$ID' ORDER BY date DESC LIMIT 1";
		$runPrices = @mysqli_query($dbcon, $getPrices);
		if($runPrices){
			while($row = mysqli_fetch_array($runPrices, MYSQLI_ASSOC)){
				$itemsInformation[$ID]['price'][$row['date']] = $row['price'];
				$itemsInformation[$ID]['date'] = $row['date'];
			}
		}else{
			echo mysqli_error($dbcon) . "Q: " . $getPrices;
		}
	}
}else{
	echo mysqli_error($dbcon) . "Q:" . $getItems;
}

/*
 * Progress Bar to display server’s script execution: using JQuery/JQueryUI
 * http://www.hellothupten.com/2011/07/20/progress-bar-to-display-servers-script-execution-using-jqueryjqueryui/
 */

//create json file and put default 0 values. we will update these values later 
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

	if($store == "chip7.pt"){
		$productInfo = chip7Product($item['itemURL']);
	}elseif ($store == "pcdiga.com") {
		$productInfo = pcdigaProduct($item['itemURL']);
	}elseif ($store == "globaldata.pt") {
		$productInfo = globaldataProduct($item['itemURL']);
	}elseif ($store == "worten.pt"){
		$productInfo = wortenProduct($item['itemURL']);
	}else{
		throw new Exception("Loja não suportada", 1);
		exit();
	}

	if($productInfo['price'] == -1){
		unset($itemsInformation[$productInfo['id']]);
	}else{
		$productPrice[$productInfo['id']] = $productInfo['price'];
	}
}

$currentDate = date("Y-m-d");
if($itemsInformation[array_key_last($itemsInformation)]['date'] == $currentDate){
	$delOtherRows = "DELETE FROM prices WHERE date='$currentDate'";
	$run_del = mysqli_query($dbcon, $delOtherRows);
	if(!$run_del){
		echo mysqli_error($dbcon) . "Q: " . $delOtherRows;
	}
}
foreach ($itemsInformation as $itemInfo) {
	$currentItem = $itemInfo['itemID'];
	$price = $productPrice[$currentItem];
	if(($price != null) || ($price != "")){
		$updatePrices = "INSERT INTO prices (itemID, date, price) VALUES ('$currentItem', '$currentDate', '$price')";
	}else{
		$price = getLastPrice($currentItem);
		if(($price != null) || ($price != "")){
			$updatePrices = "INSERT INTO prices (itemID, date, price) VALUES ('$currentItem', '$currentDate', '$price')";
		}else{
			$updatePrices = "INSERT INTO prices (itemID, date, price) VALUES ('$currentItem', '$currentDate', NULL)";
		}
	}
	$runUpdate = mysqli_query($dbcon, $updatePrices);
	if(!$runUpdate){
		echo mysqli_error($dbcon) . "Q: " . $updatePrices;
	}
}
echo "Update Finhished at " . date("Y-m-d H:i:s") . "...";
?>