<?php
if(!isset($_SESSION)){
	session_start();
}
if(!isset($_SESSION['userID'])){
	header("Location: ./login.php");
	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Price Tracker</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link href="./assets/test_icon.png" rel="shortcut icon">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
	<style type="text/css">
		#loading {
			width: 100%;
			height: 100%;
			top: 0;
			left: 0;
			position: fixed;
			display: block;
			opacity: 0.9;
			background-color: #fff;
			z-index: 99;
			text-align: center;
		}
	</style>
</head>
<body>
	<div id="loading">
		<div class="text-center">
			<div class="spinner-border" role="status" style="width: 6rem; height: 6rem; top: 100px; position: absolute;">
				<span class="sr-only">A carregar...</span>
			</div>
		</div>
	</div>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>
	<?php
	require("./database.php");
	require("./notifications.php");
	if(!function_exists('array_key_last')){
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
	}
    if (!function_exists('array_key_first')) {
    	/* https://www.php.net/manual/en/function.array-key-first.php#123775
    	 * Function to get the first key of an array since we're not in PH  7.3
    	 * by divinity76+spam at gmail dot com
    	 */
	    function array_key_first(array $arr){
	        foreach($arr as $key=>$unused){
	            return $key;
	        }
	        return NULL;
	    }
	}
	// ----- Get Items ----------
	/*
	 * This block will create and add information about a product to
	 * an array. It will be added the itemID, itemName, itemURL, r, g and b to the
	 * array under the key with the value of the current itemID.
	 */
	$userID = $_SESSION['userID'];
	$itemsInformation = array();
	$getUserItems = "SELECT * FROM userItems WHERE userID=$userID ORDER BY isImportant DESC";
	$run_User = @mysqli_query($dbcon, $getUserItems);
	if($run_User){
		while ($userItemsRow = mysqli_fetch_array($run_User, MYSQLI_ASSOC)) {
			$isImportant = $userItemsRow['isImportant'];
			$currentItemID = $userItemsRow['itemID'];
			$getItems = "SELECT * FROM items WHERE itemID='$currentItemID' ORDER BY id ASC";
			$runItems = @mysqli_query($dbcon, $getItems);
			if($runItems){
				while ($row = mysqli_fetch_array($runItems, MYSQLI_ASSOC)) {
					$thisItemID = $row['itemID'];
					$itemsInformation[$thisItemID]['itemID'] = $thisItemID;
					$itemsInformation[$thisItemID]['itemName'] = $row['itemName'];
					$itemsInformation[$thisItemID]['itemURL'] = $row['itemURL'];
					$itemsInformation[$thisItemID]['store'] = $row['store'];
					$itemsInformation[$thisItemID]['isImportant'] = $isImportant;

					/*
					 * What the heck is r, g and b?
					 *
					 * r, g and b are the values of the 3 main colors that will be displayed on the chart
					 * (red, green and blue). They will be used to create the color that will represent each
					 * product on the chart.
					 */

					$itemsInformation[$thisItemID]['r'] = $row['r'];
					$itemsInformation[$thisItemID]['g'] = $row['g'];
					$itemsInformation[$thisItemID]['b'] = $row['b'];
				}

				/*
				 * From now til the end of this "block", we'll add the price of each product
				 * and the date when such price was registed.
				 */

				foreach ($itemsInformation as $item) {
					$ID = $item['itemID'];
					$todaysDate = date("Y-m-d");
					$thisYear = date("Y");
					$thisMonth = date("m")-1;
					if($thisMonth < 10){
						$thisMonth = "0" . $thisMonth;
					}
					if($thisMonth == 12){
						$thisYear--;
					}
					$formatedDate = $thisYear . "-" . $thisMonth . "-" . date("d");
					if($_SERVER['REQUEST_METHOD'] == "POST"){
						if(isset($_POST['datepicker'])){
							$startDate = mysqli_real_escape_string($dbcon, $_POST['datepicker']);
							$getPrices = "SELECT * FROM prices WHERE itemID='$ID' AND date BETWEEN '$startDate' AND '$todaysDate'";
						}else{
							$getPrices = "SELECT * FROM prices WHERE itemID='$ID' AND date BETWEEN '$formatedDate' AND '$todaysDate'";
						}
					}else{
						$getPrices = "SELECT * FROM prices WHERE itemID='$ID' AND date BETWEEN '$formatedDate' AND '$todaysDate'";
					}
					$runPrices = @mysqli_query($dbcon, $getPrices);
					if($runPrices){
						while($row = mysqli_fetch_array($runPrices, MYSQLI_ASSOC)){
							if((!is_null($row['price'])) || ($row['price'] != "")){
								$itemsInformation[$ID]['price'][$row['date']] = $row['price'];	
							}else{
								$itemsInformation[$ID]['price'][$row['date']] = "null";
							}
							if((!isset($itemsInformation[$ID]['dates'])) || !in_array($row['date'], $itemsInformation[$ID]['dates'])){
								$itemsInformation[$ID]['dates'][] = $row['date'];
							}
						}
					}else{
						echo mysqli_error($dbcon);
					}
				}
			}else{
				echo mysqli_error($dbcon);
			}
		}
	}else{
		echo mysqli_error($dbcon);
	}
	// --------- End of Get Items block ---------

	/* "Clear the clutter"
	 * If the $itemsInformation array is not empty, we'll remove dates common to all produts
	 * where the price is "null"
	 */

	if(!empty($itemsInformation)){
		$ocurr = 0;
		foreach ($itemsInformation[array_key_last($itemsInformation)]['dates'] as $itemDate) {
			foreach ($itemsInformation as $item) {
				if((isset($item['price'][$itemDate])) && ($item['price'][$itemDate] != "null")){
					$ocurr++;
				}
			}
			if($ocurr != 0){
				$ocurr = 0;
			}else{
				foreach ($itemsInformation as $item) {
					unset($itemsInformation[$item['itemID']]['price'][$itemDate]);
					$key = array_search($itemDate, $item['dates']);
					unset($itemsInformation[$item['itemID']]['dates'][$key]);	
				}
				$ocurr = 0;
			}
		}
	
		/*
		 * Get dates for the datepicker
		 */

		$dateList = array();
		$getDates = "SELECT date FROM prices WHERE itemID='$ID'";
		$run_getDates = mysqli_query($dbcon, $getDates);
		if($run_getDates){
			while ($row = mysqli_fetch_array($run_getDates, MYSQLI_ASSOC)) {
				if(!in_array($row['date'], $dateList)){
					$dateList[] = $row['date'];
				}
			}
		}

		$key = array_key_first($dateList);
		
		list($year, $month, $day) = explode("-", $dateList[$key]);
	
		$month = $month - 1;
		if($month < 10){
			$month = "0" . $month;
		}

		$thisMonth = date("m")-1;
		if($thisMonth < 10){
			$thisMonth = "0" . $thisMonth;
		}

		/*
		 *
		 */

		$storeList = array();
		foreach ($itemsInformation as $item) {
			if(!in_array($item['store'], $storeList)){
				$storeList[] = $item['store'];
			}
		}
	}
	?>
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
		<a class="navbar-brand" href="./">Price Tracker</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item active">
					<a class="nav-link" href="./">Inicio <span class="sr-only">(atual)</span></a>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Opções
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdown">
						<?php
						if($_SESSION['isAdmin']){
							echo '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#updateModal" onclick="updatePrices()">Recarregar Preços!</a>';
							echo '<a class="dropdown-item" href="./adminTools/">Painel de Administração</a>';
						}
						?>
						<a class="dropdown-item" href="#" data-toggle="modal" data-target="#deleteModal">Remover Produto</a>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="./logout.php">Terminar Sessão!</a>
					</div>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Notificações<span class="badge badge-light"><?php echo getNotificationsNumber(); ?></span>
					</a>
					<div class="dropdown-menu p-4" aria-labelledby="navbarDropdown" style="width: 300px;white-space: normal;">
						<?php
							$userNotifications = getNotificationsText();
							if(!is_array($userNotifications)){
								echo $userNotifications;
							}else{
								if(!empty($userNotifications)){
									foreach ($userNotifications as $notification) {
										echo $notification['message'] . "<div class='dropdown-divider'></div>";
									}
								}else{
									echo "Nada. Ainda.";
								}
							}
						?>
					</div>
				</li>
			</ul>
			<form class="form-inline my-2 my-lg-0" method="POST" action="./addItem.php">
				<input class="form-control mr-sm-2" type="text" placeholder="Endereço" aria-label="Endereço" name="productURL" autocomplete="off" required>
				<button class="btn btn-outline-success my-2 my-sm-0" type="submit" onclick="startLoading();">Adicionar</button>
			</form>
		</div>
	</nav>
	<div class="container-fluid">
		<?php
		if($_SESSION['isAdmin']){
			echo '<!-- Modal -->
		<div class="modal fade" id="updateModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="exampleModalLabel"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>&nbsp;A recarregar preços...</h5>
		      </div>
		      <div class="modal-body">
		        A página irá recarregar assim que o processo esteja completo!
		        <hr>
		        <div class="progress">
					<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%" id="checkPricesProgressBar">0%</div>
				</div>
		      </div>
		    </div>
		  </div>
		</div>';
		}
		?>
		<!-- Modal -->
		<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		  <div class="modal-dialog" role="document">
		    <div class="modal-content">
		      <div class="modal-header">
		        <h5 class="modal-title" id="exampleModalLabel">Remover Produto</h5>
		        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      </div>
		      <div class="modal-body">
		        <?php
		        if(!empty($itemsInformation)){
					echo "<table class=\"table\"><thead><tr><th scope=\"col\">Loja</th><th scope=\"col\">Produto</th><th scope=\"col\"></th></tr></thead><tbody>";
					foreach ($itemsInformation as $item) {
						echo "<tr><td>" . $item['store'] . "</td><td><a href=\"" . $item['itemURL'] . "\" style=\"color: black;\" target=\"_blank\">" . $item['itemName'] . "</a></td><td><a href=\"./removeItem.php?productID=" . $item['itemID'] . "\" onclick=\"startLoading();\"><i class=\"far fa-times-circle\"></i></a></td></tr>";
					}
					echo "</tbody></table>";
		        }else{
		        	echo "<center><h4><b>A tua lista está vazia! Adiciona produtos colando o seu endereço na barra de endereços do cabeçalho!</b></h4></center>";
		        }
		        ?>
		      </div>
		    </div>
		  </div>
		</div>
		<div class="row">
			<div class="col d-block d-sm-block d-md-block d-lg-none d-xl-none">
				<center>Para ver o gráfico, tenta <b>rodar o ecrã</b> ou trocar para <b>modo Desktop</b>!<br>
				Recomendo a aplicação para dispositivos móveis! (Ainda não disponível)</center>
			</div>
			<div class="col d-none d-sm-none d-md-none d-lg-block d-xl-block">
				<?php
					if(!empty($itemsInformation)){
						echo '
							<form method="POST" action="./index.php">
								<input type="text" name="month" hidden value="' . $month . '">
								<input type="text" name="day" hidden value="' . $day . '">
								<p class="form-inline p-3">Data de inicio:&nbsp;
								<input class="form-control" type="text" id="datepicker" name="datepicker" required autocomplete="off" value="';
								if(isset($startDate)){
									echo $startDate;
								}
						echo '">&nbsp;<button class="btn btn-primary" type="submit">Filtrar</button></p>
						</form>';
					}
				?>
				<canvas id="priceChart"></canvas>
				<?php
					if(empty($itemsInformation)){
						echo "<center><h4><b>A tua lista está vazia! Adiciona produtos colando o seu endereço na barra de endereços do cabeçalho!</b></h4></center>";
					}
				?>
				<script type="text/javascript">
					var ctx = document.getElementById('priceChart').getContext('2d');
					var chart = new Chart(ctx, {
					    // The type of chart we want to create
					    type: 'line',

					    // The data for our dataset
					    data: {

					    	<?php
					     	//----------------"label maker"-----------

					    	// labels: ["label1", "label2", "label3"],

					        echo "labels: [";
					        $listDates = $itemsInformation[array_key_last($itemsInformation)]['dates'];
					        foreach ($listDates as $date) {
					        	echo "\"" . $date . "\", ";
					        }
					        echo "],";
					        //---------------------------------------

					        echo "datasets: [";

					        foreach ($itemsInformation as $item) {
					        	echo "{";
								echo "label: \"" . "[" . $item['store'] . "] " . htmlspecialchars($item['itemName']) . "\",";
								echo "backgroundColor: 'rgb(" . $item['r'] . ", " . $item['g'] . ", " . $item['b'] . ")',
							            borderColor: 'rgb(" . $item['r'] . ", " . $item['g'] . ", " . $item['b'] . ")',";
							    echo "data:";
							    //----------- data
							    $itemPrice = $item['price'];

							    echo "[";
							    foreach ($itemPrice as $price) {
							     	echo "\"" . $price . "\",";
							    }
							    echo "],";

							    //-------------------------------
								echo "fill: false,
						},";
							}
					        ?>
					    ]},

					    // Configuration options go here
					    options: {legend:{position: 'bottom'}}
					});
				</script>
				<hr>
			</div>
		</div>
		<div class="row">
			<?php
			function priceYesterday($array){
				return array_keys($array)[count($array)-2];
			}
			?>
			<div class="col d-block d-sm-block d-md-block d-lg-none d-xl-none">
				<hr>
				<?php
					if(!empty($itemsInformation)){
						echo '<ul class="nav nav-tabs" id="storeTabMobile" role="tablist">';
						$a = "active";
						$a2 = "true";
						foreach($storeList as $store){
							$lowerPrices = 0;
							echo '
							<li class="nav-item">
							    <a class="nav-link ' . $a . '" id="' . $store . '-tabMobile" data-toggle="tab" href="#' . $store . 'Mobile" role="tab" aria-controls="' . $store . '" aria-selected="' . $a2 . '">' . $store;
						   	foreach ($itemsInformation as $item) {
								if($item['store'] == $store){
									if(count($itemsInformation) != 1){
										if(count($item['price']) != 1){
											if($item['price'][priceYesterday($item['price'])] != "null"){	
												if($item['price'][priceYesterday($item['price'])] > $item['price'][array_key_last($item['price'])]) {
													$lowerPrices++;
												}
											}
										}
									}
								}
							}
							if($lowerPrices != 0){
								echo '<span class="badge badge-light">' . $lowerPrices . '</span>';
							}
							echo '</a></li>
							';
							$a = "";
							$a2 = "false";
						}
						echo '</ul>';
						echo '<div class="tab-content" id="storeTabContentMobile">';
						$a = "show active";
						foreach ($storeList as $store) {
							echo '<div class="tab-pane fade ' . $a . '" id="' . $store . 'Mobile" role="tabpanel" aria-labelledby="' . $store . '-tabMobile">';
							$a = "";
							//div content
							echo "<table class=\"table\"><thead><tr><th scope=\"col\"></th><th scope=\"col\">Produto</th><th scope=\"col\">Preço</th></tr></thead><tbody>";
							foreach ($itemsInformation as $item) {
								if($item['store'] == $store){
									if(count($itemsInformation) != 1){
										if(count($item['price']) == 1){
											$state = "red";
										}else{
											if($item['price'][priceYesterday($item['price'])] != "null"){
												if($item['price'][priceYesterday($item['price'])] < $item['price'][array_key_last($item['price'])]){
													$state = "red";
												}elseif ($item['price'][priceYesterday($item['price'])] > $item['price'][array_key_last($item['price'])]) {
													$state = "green";
												}else{
													$state = "black";
												}
											}else{
												$state = "black";
											}
										}
									}else{
										$state = "red";
									}
									if($item['isImportant'] == 1){
										$color = "#CFB53B";
										$star = "starFilled.png";
										$starInverse = "star.png";
									}else{
										$color = "transparent";
										$star = "star.png";
										$starInverse = "starFilled.png";
									}
									echo "<tr style=\"background-color: " . $color . ";\">
									<td>
										<img src=\"./assets/" . $star . "\" width=\"32\" height=\"32\" id=\"MisImportanticon" . $item['itemID'] . "\" onclick=\"changeImportantState('" . $item['itemID'] . "', '" . $item['isImportant'] . "');\" onmouseover=\"$(this).attr('src','./assets/" . $starInverse . "')\" onmouseout=\"$(this).attr('src','./assets/" . $star . "')\" alt=\"Adicionar/Remover da lista de favoritos.\">
									</td>
									<td>
										<a href=\"" . $item['itemURL'] . "\" style=\"color: black;\" target=\"_blank\">
											" . $item['itemName'] . "
										</a>
									</td>
									<td>
										<span style=\"color: " . $state . ";\">" . $item['price'][array_key_last($item['price'])] . "€</span>
									</td>
								</tr>";
								}
							}
							echo '</table></div>';
						}
						echo '</div>';
					}
				?>
			</div>
			<div class="col d-none d-sm-none d-md-none d-lg-block d-xl-block">
				<hr>
				<?php
				/* Desktop View */
					if(!empty($itemsInformation)){
						echo '<ul class="nav nav-tabs" id="storeTab" role="tablist">';
						$a = "active";
						$a2 = "true";
						foreach($storeList as $store){
							$lowerPrices = 0;
							echo '
							<li class="nav-item">
							    <a class="nav-link ' . $a . '" id="' . $store . '-tab" data-toggle="tab" href="#' . $store . '" role="tab" aria-controls="' . $store . '" aria-selected="' . $a2 . '">' . $store;
						   	foreach ($itemsInformation as $item) {
								if($item['store'] == $store){
									if(count($itemsInformation) != 1){
										if(count($item['price']) != 1){
											if($item['price'][priceYesterday($item['price'])] != "null"){	
												if($item['price'][priceYesterday($item['price'])] > $item['price'][array_key_last($item['price'])]) {
													$lowerPrices++;
												}
											}
										}
									}
								}
							}
							if($lowerPrices != 0){
								echo '<span class="badge badge-light">' . $lowerPrices . '</span>';
							}
							echo '</a></li>
							';
							$a = "";
							$a2 = "false";
						}
						echo '</ul>';
						echo '<div class="tab-content" id="storeTabContent">';
						$a = "show active";
						foreach ($storeList as $store) {
							echo '<div class="tab-pane fade ' . $a . '" id="' . $store . '" role="tabpanel" aria-labelledby="' . $store . '-tab">';
							$a = "";
							//div content
							echo "<table class=\"table\"><thead><tr><th scope=\"col\"></th><th scope=\"col\">Produto</th><th scope=\"col\">Preço</th><th scope=\"col\"></th></tr></thead><tbody>";
							foreach ($itemsInformation as $item) {
								if($item['store'] == $store){
									if(count($itemsInformation) != 1){
										if(count($item['price']) == 1){
											$state = "red.png";
										}else{
											if($item['price'][priceYesterday($item['price'])] != "null"){	
												if($item['price'][priceYesterday($item['price'])] < $item['price'][array_key_last($item['price'])]){
													$state = "red.png";
												}elseif ($item['price'][priceYesterday($item['price'])] > $item['price'][array_key_last($item['price'])]) {
													$state = "green.png";
												}else{
													$state = "yellow.png";
												}
											}else{
												$state = "yellow.png";
											}
										}
									}else{
										$state = "red.png";
									}
									if($item['isImportant'] == 1){
										$color = "#CFB53B";
										$star = "starFilled.png";
										$starInverse = "star.png";
									}else{
										$color = "transparent";
										$star = "star.png";
										$starInverse = "starFilled.png";
									}
									echo "<tr style=\"background-color: " . $color . ";\">
									<td>
										<img src=\"./assets/" . $star . "\" width=\"32\" height=\"32\" id=\"DisImportanticon" . $item['itemID'] . "\" onclick=\"changeImportantState('" . $item['itemID'] . "', '" . $item['isImportant'] . "');\" onmouseover=\"$(this).attr('src','./assets/" . $starInverse . "')\" onmouseout=\"$(this).attr('src','./assets/" . $star . "')\" alt=\"Adicionar/Remover da lista de favoritos.\">
									</td>
									<td>
										<a href=\"" . $item['itemURL'] . "\" style=\"color: black;\" target=\"_blank\">
											" . $item['itemName'] . "
										</a>
									</td>
									<td>
										" . $item['price'][array_key_last($item['price'])] . "€
									</td>
									<td>
										<img class=\"img-fluid\" width=\"32\" height=\"32\" src=\"./assets/" . $state . "\">
									</td>
								</tr>";
								}
							}
							echo '</table></div>';
						}
						echo '</div>';
					}

				?>
			</div>
		</div>
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript">
		$(function(){
			$.datepicker.setDefaults($.datepicker.regional['pt']);
			$.datepicker.setDefaults({ dateFormat: 'yy-mm-dd' });
			$.datepicker.setDefaults({ minDate: new Date(<?php echo $year; ?>,<?php echo $month; ?>,<?php echo $day; ?>) });
			$.datepicker.setDefaults({ maxDate: new Date(<?php echo date("Y"); ?>,<?php echo $thisMonth; ?>,<?php echo date("d"); ?>) });
			$("#datepicker").datepicker();
		});
		
		function changeImportantState($item, $state){
			if($state == 0){
				$.ajax({
					url: "./addImportant.php?item=" + $item,
					success: function(response){
						if(response != "1"){
							alert(response);
						}else{
							$('#MisImportanticon' + $item).parent().parent().css("background-color", "#CFB53B");
							$('#MisImportanticon' + $item).attr("onclick", "changeImportantState('" + $item + "', 1)");
							$('#MisImportanticon' + $item).attr("onmouseover", "$(this).attr('src','./assets/star.png')");
							$('#MisImportanticon' + $item).attr("onmouseout", "$(this).attr('src','./assets/starFilled.png')");
							$('#MisImportanticon' + $item).attr('src', "./assets/starFilled.png");
							$('#DisImportanticon' + $item).parent().parent().css("background-color", "#CFB53B");
							$('#DisImportanticon' + $item).attr("onclick", "changeImportantState('" + $item + "', 1)");
							$('#DisImportanticon' + $item).attr("onmouseover", "$(this).attr('src','./assets/star.png')");
							$('#DisImportanticon' + $item).attr("onmouseout", "$(this).attr('src','./assets/starFilled.png')");
							$('#DisImportanticon' + $item).attr('src', "./assets/starFilled.png");
						}
					}
				})
			}else{
				$.ajax({
				url: "./removeImportant.php?item=" + $item,
					success: function(response){
						if(response != "1"){
							alert(response);
						}else{
							$('#MisImportanticon' + $item).parent().parent().css("background-color", "transparent");
							$('#MisImportanticon' + $item).attr("onclick", "changeImportantState('" + $item + "', 0)");
							$('#MisImportanticon' + $item).attr("onmouseover", "$(this).attr('src','./assets/starFilled.png')");
							$('#MisImportanticon' + $item).attr("onmouseout", "$(this).attr('src','./assets/star.png')");
							$('#MisImportanticon' + $item).attr("src", "./assets/star.png");
							$('#DisImportanticon' + $item).parent().parent().css("background-color", "transparent");
							$('#DisImportanticon' + $item).attr("onclick", "changeImportantState('" + $item + "', 0)");
							$('#DisImportanticon' + $item).attr("onmouseover", "$(this).attr('src','./assets/starFilled.png')");
							$('#DisImportanticon' + $item).attr("onmouseout", "$(this).attr('src','./assets/star.png')");
							$('#DisImportanticon' + $item).attr("src", "./assets/star.png");
						}
					}
				})
			}
		};

		<?php
		if($_SESSION['isAdmin']){
			echo "
			/*
			 * Progress Bar to display server’s script execution: using JQuery/JQueryUI
			 * http://www.hellothupten.com/2011/07/20/progress-bar-to-display-servers-script-execution-using-jqueryjqueryui/
			 */

			function updateStatus(){ 
				$.getJSON('./checkStatus.json', function(data){ 
					var items = []; 
					pbvalue = 0; 
					if(data){ 
						var total = data['total']; 
						var current = data['current'];  
						var pbvalue = Math.floor((current / total) * 100);  
						if(pbvalue > 0){  
							$('#checkPricesProgressBar').attr(\"aria-valuenow\", pbvalue);
							$('#checkPricesProgressBar').css(\"width\", pbvalue + \"%\");
							$('#checkPricesProgressBar').text(pbvalue + \"%\");
						}else{
							$('#checkPricesProgressBar').attr(\"aria-valuenow\", \"0\");
							$('#checkPricesProgressBar').css(\"width\", \"0%\");
						}
					}  
					if(pbvalue < 100){  
						t = setTimeout(\"updateStatus()\", 3000);  
					}
				});
			}

			function updatePrices(){
				$('#updateModal').modal({
					keyboard: false
				})
				t = setTimeout(\"updateStatus()\", 3000);
				$.ajax({
					url: \"./checkPrices.php\",
					success: function(response){
						if(response != \"1\"){
							alert(response);
						}else{
							location.reload();
						}
					}
				});
			}
			";
		}
		?>
	</script>
	<script language="javascript" type="text/javascript">
		$(window).load(function() {
			$('#loading').hide();
		});
		function startLoading(){
			$('#loading').show();
		}
	</script>
</body>
</html>