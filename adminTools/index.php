<?php
if(!isset($_SESSION)){
	session_start();
}
if(!isset($_SESSION['userID'])){
	header("Location: ../login.php");
	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Admin Tools - Price Tracker</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="shortcut icon" type="image/x-icon" href="../assets/test_icon.png" />
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>
<body>
	<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.js"></script>
	<?php
	require("../database.php");
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
	?>
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
		<a class="navbar-brand" href="./">Admin Tools - Price Tracker</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item active">
					<a class="nav-link" href="../">Inicio</a>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Opções
					</a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdown">
						<?php
						if($_SESSION['isAdmin']){
							echo '<a class="dropdown-item" href="#" data-toggle="modal" data-target="#updateModal" onclick="updatePrices()">Recarregar Preços!</a>';
							echo '<a class="dropdown-item" href="#">Painel de Administração</a>';
						}
						?>
						<div class="dropdown-divider"></div>
						<a class="dropdown-item" href="../logout.php">Terminar Sessão!</a>
					</div>
				</li>
				<li class="nav-item dropdown">
					<a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Notificações<span class="badge badge-light"><?php echo getNotificationsNumber(); ?></span>
					</a>
					<div class="dropdown-menu p-4" aria-labelledby="navbarDropdown">
						<?php
							$userNotifications = getNotificationsText();
							if(!is_array($userNotifications)){
								echo $userNotifications;
							}else{
								if(!empty($userNotifications)){
									foreach ($userNotifications as $notification) {
										echo $notification . "<div class='dropdown-divider'></div>";
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
			<button class="btn btn-outline-success my-2 my-sm-0" type="submit">Adicionar</button>
			</form>
		</div>
	</nav>
	<div class="container-fluid">
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
						echo "<tr><td>" . $item['store'] . "</td><td><a href=\"" . $item['itemURL'] . "\" style=\"color: black;\" target=\"_blank\">" . $item['itemName'] . "</a></td><td><a href=\"./removeItem.php?productID=" . $item['itemID'] . "\"><i class=\"far fa-times-circle\"></i></a></td></tr>";
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
			<div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6">
				<div class="progress">
					<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%" id="checkPricesProgressBar">0%</div>
				</div>
				<button onclick="updateStatus(); this.attr('disabled');">Atualizar</button>
			</div>
			<div class="col d-block d-sm-block d-md-block d-lg-none d-xl-none">
				<!-- mobile -->				
			</div>
			<div class="col d-none d-sm-none d-md-none d-lg-block d-xl-block">
				<!-- pc -->
			</div>
		</div>
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.js"></script>
	<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
	<script type="text/javascript">
		/*
		 * Progress Bar to display server’s script execution: using JQuery/JQueryUI
		 * http://www.hellothupten.com/2011/07/20/progress-bar-to-display-servers-script-execution-using-jqueryjqueryui/
		 */

		function updateStatus(){ 
			$.getJSON('../checkStatus.json', function(data){ 
				var items = []; 
				pbvalue = 0; 
				if(data){ 
					var total = data['total']; 
					var current = data['current'];  
					var pbvalue = Math.floor((current / total) * 100);  
					if(pbvalue > 0){  
						$('#checkPricesProgressBar').attr("aria-valuenow", pbvalue);
						$('#checkPricesProgressBar').css("width", pbvalue + "%");
						$('#checkPricesProgressBar').text(pbvalue + "%");
					}else{
						$('#checkPricesProgressBar').attr("aria-valuenow", "0");
						$('#checkPricesProgressBar').css("width", "0%");
					}
				}  
				if(pbvalue < 100){  
					t = setTimeout("updateStatus()", 3000);  
				}
			});
		}

		function updatePrices(){
			$('#updateModal').modal({
				keyboard: false
			})
			t = setTimeout("updateStatus()", 3000);
			$.ajax({
				url: "../checkPrices.php",
				success: function(response){
					if(response != "1"){
						alert(response);
					}else{
						location.reload();
					}
				}
			});
		}
	</script>
</body>
</html>