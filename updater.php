<?php
function fixIds(){
	require("database.php");
	$query = "SELECT * FROM items";
	$run = mysqli_query($dbcon, $query);
	if($run){
		while($row = mysqli_fetch_array($run, MYSQLI_ASSOC)){
			$item[$row['itemURL']]['itemID'] = $row['itemID'];
			$item[$row['itemURL']]['itemURL'] = $row['itemURL'];
		}
	}

	foreach ($item as $v) {
		$store = getStore($v['itemURL']);

		switch ($store) {
			case 'chip7.pt':
				$productInfof = chip7Product($v['itemURL']);
				break;

			case 'pcdiga.com':
				$productInfof = pcdigaProduct($v['itemURL']);
				break;

			case 'globaldata.pt':
				$productInfof = globaldataProduct($v['itemURL']);
				break;

			case 'worten.pt':
				$productInfof = wortenProduct($v['itemURL']);
				break;
			
			default:
				throw new Exception("Loja não suportada", 1);
				break;
		}

		$productInfo[$productInfof['itemURL']]['itemID'] = $productInfof['id'];
		$productInfo[$productInfof['itemURL']]['itemURL'] = $productInfof['itemURL'];
		$productInfo[$productInfof['itemURL']]['SKU'] = $productInfof['sku'];
		$productInfo[$productInfof['itemURL']]['gtin'] = $productInfof['gtin'];
	}

	foreach ($productInfo as $p) {
		$sku = $p['SKU'];
		$gtin = $p['gtin'];
		$currentID = $item[$p['itemURL']]['itemID'];
		mysqli_query($dbcon, "UPDATE items SET sku='$sku', gtin='$gtin' WHERE itemID='$currentID'");
		if($p['itemID'] != $item[$p['itemURL']]['itemID']){
			echo "Change actual key " . $item[$p['itemURL']]['itemID'] . " to " . $p['itemID'] . "<br>";
			$newID = $p['itemID'];
			$oldID = $item[$p['itemURL']]['itemID'];
			$update = "UPDATE items SET itemID='$newID' WHERE itemID='$oldID'";
			$runUpdate = mysqli_query($dbcon, $update);
			$updatePrices = "UPDATE prices SET itemID='$newID' WHERE itemID='$oldID'";
			$run_prices = mysqli_query($dbcon, $updatePrices);
			$updateIDs = "UPDATE userItems SET itemID='$newID' WHERE itemID='$oldID'";
			$run_updateIDs = mysqli_query($dbcon, $updateIDs);
		}
	}
	return 1;
}

?>