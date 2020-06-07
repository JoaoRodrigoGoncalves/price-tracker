<?php
	if(!defined('db_host')){
		define('db_host', 'localhost');
	}
	if(!defined('db_user')){
		define('db_user', 'pricechecker');
	}
	if(!defined('db_password')){
		define('db_password', 'J7yKuQk4sjeoPlLh');
	}
	if(!defined('db_name')){
		define('db_name', 'priceChecker');
	}
	
	//faz a ligação
	
	$dbcon = @mysqli_connect (db_host, db_user, db_password, db_name) or die ('Não foi possivel ligar á database: '. mysqli_connect_error() );
	
	//charset
	
	mysqli_set_charset($dbcon, 'utf8');
	
?>