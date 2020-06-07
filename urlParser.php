<?php

function get_http_response_code($url){
	/* https://stackoverflow.com/questions/4358130/file-get-contents-when-url-doesnt-exist
	 * Funtion to get the http response code from the header of the http request
	 * by ynh (https://stackoverflow.com/users/511781/ynh)
	 * (edited by Jaylord Ferrer (https://stackoverflow.com/users/951033/jaylord-ferrer))
	 */

    if(@get_headers($url) != FALSE){	
    	$headers = get_headers($url);
    	return substr($headers[0], 9, 3);
    }else{
    	return "500";
    }
}

function getStore($url){
	/* This function will return the domain of the website
	 * based on the URL provided
	 */
	$supportedStores = array('chip7.pt', 'pcdiga.com', 'globaldata.pt');
	foreach ($supportedStores as $store) {
		if(strpos($url, $store)){
			return $store;
			break;
		}
	}
	return 0;
}

function getLastPrice($itemID){
	/* This function will return the last price
	 * of a specific item registed in the database
	 */
	require("database.php");
	$lastPrice = "SELECT * FROM prices WHERE itemID='$itemID' ORDER BY date ASC LIMIT 1";
	$get_lastPrice = mysqli_query($dbcon, $lastPrice);
	if($get_lastPrice){
		if($row = mysqli_fetch_array($get_lastPrice, MYSQLI_ASSOC)) {
			return $row['price'];
		}
	}else{
		return null;
	}
}

function nonExistentItem($url){
	/* This function must only be called in case of get_http_response_code()
	 * returns 404. This will handle the item details if the product page gets
	 * deleted.
	 * Other http errors should be handled straight
	 * on the respective store function.
	 */
	require("database.php");
	$OlderInfo = "SELECT * FROM items WHERE itemURL='$url' LIMIT 1";
	$getOlderInfo = mysqli_query($dbcon, $OlderInfo);
	if($getOlderInfo){
		if($row = mysqli_fetch_array($getOlderInfo, MYSQLI_ASSOC)) {
			$item['price'] = null;
			$item['id'] = $row['itemID'];
			$itemName = $row['itemName'];
		}
		$itemID = $item['id'];
		$affectedUsers = "SELECT userID FROM userItems WHERE itemID='$itemID'";
		$get_affectedUsers = mysqli_query($dbcon, $affectedUsers);
		if($get_affectedUsers){
			if(mysqli_num_rows($get_affectedUsers) != 0){
				//get affected users
				while($user = mysqli_fetch_array($get_affectedUsers, MYSQLI_ASSOC)){
					$affectedList[] = $user['userID'];
				}
				// notify the users that the item was removed
				$message = "O item <b>" . $itemName . "</b> foi removido na tua lista pois já não existe.";
				foreach ($affectedList as $affectedUser) {
					$notification = "INSERT INTO userNotifications (userID, message) VALUES ('$affectedUser', '$message')";
					$send_notification = mysqli_query($dbcon, $notification);
				}
				//remove the item from the database
				$listDelete = "DELETE FROM items WHERE itemID='$itemID' LIMIT 1";
				$run_listDelete = mysqli_query($dbcon, $listDelete);
				$priceDelete = "DELETE FROM prices WHERE itemID='$itemID'";
				$run_priceDelete = mysqli_query($dbcon, $priceDelete);
				$userListDelete = "DELETE FROM userItems WHERE itemID='$itemID'";
				$run_userListDelete = mysqli_query($dbcon, $userListDelete);

			}else{
				//no user has this item in their list. just remove the item form the database
				$listDelete = "DELETE FROM items WHERE itemID='$itemID' LIMIT 1";
				$run_listDelete = mysqli_query($dbcon, $listDelete);
				$priceDelete = "DELETE FROM prices WHERE itemID='$itemID'";
				$run_priceDelete = mysqli_query($dbcon, $priceDelete);
			}
			$item['price'] = -1;
		}else{
			echo "3" . mysqli_error($dbcon);
			exit();
		}
	}else{
		echo "2" . mysqli_error($dbcon);
		exit();
	}
	return $item;
}

function chip7Product($url){
	$productInfo['itemURL'] = $url;
	$productInfo['sku'] = "0";
	$productInfo['gtin'] = "0";
	$productInfo['store'] = "Chip7";
	/* Chip7 url parser.
	 * The only webstore that doesn't use "schema.org script"
	 * This function will get the price from the specified url.
	 * It will also handle non-404 errors.
	 */
	list($lixo, $lixo1, $websiteDomain, $lixo3, $productID) = explode("/", $url);

	if((is_null($productID)) || (empty($productID)) || ($productID == "")){
		list($productID, $lixo) = explode("-", $lixo3);
	}else{
		list($productID, $lixo) = explode("-", $productID);
	}
	
	require("database.php");

	$productInfo['id'] = "CHP7" . $productID;
	$http_code = get_http_response_code($url);
	if(($http_code != "200") && ($http_code != "302")){
		if($http_code != "404"){
			$thisID = $productInfo['id'];
			$lastPrice = "SELECT * FROM prices WHERE itemID='$thisID' ORDER BY date ASC LIMIT 1";
			$get_lastPrice = mysqli_query($dbcon, $lastPrice);
			if($get_lastPrice){
				while ($row = mysqli_fetch_array($get_lastPrice, MYSQLI_ASSOC)) {
					$productInfo['price'] = $row['price'];
				}
			}
		}else{
			$productInfo = nonExistentItem($url);
		}
	}else{
		$text = file_get_contents($url);
		$doc = new DOMDocument('1.0');
		$internalErrors = libxml_use_internal_errors(true);
		$doc->loadHTML($text);

		foreach($doc->getElementsByTagName('h1') AS $name) {
		    $itemprop = $name->getAttribute('itemprop');
		    if(strpos($itemprop, 'name') !== FALSE) {
		    	$productInfo['name'] = mysqli_real_escape_string($dbcon, $name->nodeValue);
		    }
		}

		foreach($doc->getElementsByTagName('span') AS $span) {
		    $spanid = $span->getAttribute('id');
		    if(strpos($spanid, 'our_price_display') !== FALSE) {
		        if($span->getAttribute('content')) {
		            $productInfo['price'] = mysqli_real_escape_string($dbcon, $span->getAttribute('content'));
		        }else{
		        	$thisID = $productInfo['id'];
					$lastPrice = "SELECT * FROM prices WHERE itemID='$thisID' ORDER BY date ASC LIMIT 1";
					$get_lastPrice = mysqli_query($dbcon, $lastPrice);
					if($get_lastPrice){
						while ($row = mysqli_fetch_array($get_lastPrice, MYSQLI_ASSOC)) {
							$productInfo['price'] = $row['price'];
						}
					}
		        }
		    }
		    $itemprop = $span->getAttribute('itemprop');
		    if(strpos($itemprop, 'sku')){
		    	$productInfo['sku'] = mysqli_real_escape_string($dbcon, $span->getAttribute('content'));
		    }
		}
	}
	return $productInfo;
}

function pcdigaProduct($url){
	/* PCDiga url parser
	 * This function will get the price from the specified url.
	 * It will also handle non-404 errors.
	 */
	require("database.php");
	$http_code = get_http_response_code($url);
	if($http_code != "200"){
		if($http_code != "404"){
			$OlderInfo = "SELECT itemID FROM items WHERE itemURL='$url' LIMIT 1";
			$getOlderInfo = mysqli_query($dbcon, $OlderInfo);
			if($getOlderInfo){
				while ($row = mysqli_fetch_array($getOlderInfo, MYSQLI_ASSOC)) {
					$productInfo['id'] = $row['itemID'];
				}
				$productInfo['price'] = getLastPrice($productInfo['id']);
			}else{
				echo "1" . mysqli_error($dbcon);
				exit();
			}
		}else{
			$productInfo = nonExistentItem($url);
		}
	}else{
		$text = file_get_contents($url);
		$doc = new DOMDocument('1.0');
		$internalErrors = libxml_use_internal_errors(true);
		$doc->loadHTML($text);
		$productInfo = array();

		/*
		 * Get product ID and price
		 */

		foreach($doc->getElementsByTagName('script') AS $script) { //get "script" elements
		    $type = $script->getAttribute('type');
		    if(strpos($type, 'application/ld+json') !== FALSE) { // narrow down the options only for the scripts that are of type "application/ld+json"
		    	if(strpos($script->nodeValue, '"@type": "Product"') !== FALSE){ // narrow down to the script that has the informations about the product we're looking for
		    		$thisproductInfo = json_decode($script->nodeValue, true);
		    		$productInfo['id'] = "PCDG" . $thisproductInfo['productID'];
		    		$productInfo['name'] = $thisproductInfo['name'];
		    		$productInfo['sku'] = $thisproductInfo['sku'];
		    		$productInfo['gtin'] = $thisproductInfo['gtin12'];
		    		$productInfo['price'] = round($thisproductInfo['offers']['price'], 2);
		    	}
		    }
		}
	}
	if(($productInfo['price'] == "") || (is_null($productInfo['price']))){
		$productInfo['price'] = getLastPrice($productInfo['id']);
	}
	$productInfo['store'] = "PCDiga";
	$productInfo['itemURL'] = $url;
	return $productInfo;
}

function globaldataProduct($url){
	$productInfo['itemURL'] = $url;
	/* GlobalData url parser
	 * This function will get the price from the specified url.
	 * It will also handle non-404 errors.
	 */
	require("database.php");
	$http_code = get_http_response_code($url);
	if($http_code != "200"){
		if($http_code != "404"){
			$OlderInfo = "SELECT itemID FROM items WHERE itemURL='$url' LIMIT 1";
			$getOlderInfo = mysqli_query($dbcon, $OlderInfo);
			if($getOlderInfo){
				while ($row = mysqli_fetch_array($getOlderInfo, MYSQLI_ASSOC)) {
					//$productInfo['price'] = null;
					$productInfo['id'] = $row['itemID'];
				}
				$thisID = $productInfo['id'];
				$lastPrice = "SELECT * FROM prices WHERE itemID='$thisID' ORDER BY date ASC LIMIT 1";
				$get_lastPrice = mysqli_query($dbcon, $lastPrice);
				if($get_lastPrice){
					while ($row = mysqli_fetch_array($get_lastPrice, MYSQLI_ASSOC)) {
						$productInfo['price'] = $row['price'];
					}
				}
			}else{
				echo mysqli_error($dbcon);
				exit();
			}
		}else{
			$productInfo = nonExistentItem($url);
		}
	}else{
		$text = file_get_contents($url);
		$doc = new DOMDocument('1.0');
		$internalErrors = libxml_use_internal_errors(true);
		$doc->loadHTML($text);

		/*
		 * Get product ID, name and price
		 */

		foreach($doc->getElementsByTagName('script') AS $script) { //get "script" elements
		    $type = $script->getAttribute('type');
		    if(strpos($type, 'application/ld+json') !== FALSE) { // narrow down the options only for the scripts that are of type "application/ld+json"
		    	if(strpos($script->nodeValue, '"@type":"Product"') !== FALSE){ // narrow down to the script that has the informations about the product we're looking for
		    		$thisproductInfo = json_decode($script->nodeValue, true);
		    		$productInfo['name'] = $thisproductInfo['name'];
		    		$productInfo['sku'] = $thisproductInfo['sku'];
		    		$productInfo['gtin'] = $thisproductInfo['gtin13'];
		    		$productInfo['price'] = round($thisproductInfo['offers']['price'], 2);
		    	}
		    }
		}

		foreach($doc->getElementsByTagName('div') AS $div) { //get "div" elements
		    $class = $div->getAttribute('class');
		    if(strpos($class, 'MagicToolboxContainer selectorsBottom minWidth') !== FALSE) { // narrow down the options only for the divs that are the class "MagicToolboxContainer selectorsBottom minWidth"
		    	$attr = json_decode($div->getAttribute('data-mage-init'), true);
		    	$productInfo['id'] = "GLBDT" . $attr['magicToolboxThumbSwitcher']['productId'];
		    }
		}
	}
	$productInfo['store'] = "Globaldata";
	return $productInfo;
}

function wortenProduct($url){
	$productInfo['itemURL'] = $url;
	/* Worten url parser
	 * This function will get the price from the specified url.
	 * It will also handle non-404 errors.
	 */
	require("database.php");
	$http_code = get_http_response_code($url);
	echo $http_code;
	if(($http_code != "200") || ($http_code != "304")){
		if($http_code != "404"){
			$OlderInfo = "SELECT itemID FROM items WHERE itemURL='$url' LIMIT 1";
			$getOlderInfo = mysqli_query($dbcon, $OlderInfo);
			if($getOlderInfo){
				while ($row = mysqli_fetch_array($getOlderInfo, MYSQLI_ASSOC)) {
					//$productInfo['price'] = null;
					$productInfo['id'] = $row['itemID'];
				}
				$thisID = $productInfo['id'];
				$lastPrice = "SELECT * FROM prices WHERE itemID='$thisID' ORDER BY date ASC LIMIT 1";
				$get_lastPrice = mysqli_query($dbcon, $lastPrice);
				if($get_lastPrice){
					while ($row = mysqli_fetch_array($get_lastPrice, MYSQLI_ASSOC)) {
						$productInfo['price'] = $row['price'];
					}
				}
			}else{
				echo mysqli_error($dbcon);
				exit();
			}
		}else{
			$productInfo = nonExistentItem($url);
		}
	}else{
		$text = file_get_contents($url);
		$doc = new DOMDocument('1.0');
		$internalErrors = libxml_use_internal_errors(true);
		$doc->loadHTML($text);

		/*
		 * Get product ID, name and price
		 */

		foreach($doc->getElementsByTagName('script') AS $script) { //get "script" elements
		    $type = $script->getAttribute('type');
		    if(strpos($type, 'application/ld+json') !== FALSE) { // narrow down the options only for the scripts that are of type "application/ld+json"
		    	if(strpos($script->nodeValue, '"@type":"Product"') !== FALSE){ // narrow down to the script that has the informations about the product we're looking for
		    		$thisproductInfo = json_decode($script->nodeValue, true);
		    		$productInfo['name'] = $thisproductInfo['name'];
		    		$productInfo['id'] = $thisproductInfo['sku'];
		    		$productInfo['gtin'] = $thisproductInfo['gtin13'];
		    		$productInfo['price'] = round($thisproductInfo['offers']['price'], 2);
		    	}
		    }
		}
	}
	$productInfo['store'] = "Worten";
	return $productInfo;
}

?>