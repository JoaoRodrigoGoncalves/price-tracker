<?php
function passwordEncryptor($password){
	$options = [
		'cost' => 14,
	];
	return password_hash($password, PASSWORD_BCRYPT, $options);
}
?>