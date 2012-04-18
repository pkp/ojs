#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$proposedFix = '';

$usersToFixSQL = "select * FROM users where first_name = '[FIXME]'";
$usersToFixResult = mysql_query($usersToFixSQL);
if($usersToFixResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nusersToFixSQL: $usersToFixSQL\n");
} else {
	while($usersRow = mysql_fetch_array($usersToFixResult)) {
		//echo "\nusersRow:";
		//print_r($usersRow);
		$nameElements = array();
		$elementsCount = 0;
		$first_name = '';
		$middle_name = '';
		$last_name = '';
		
		$currName = $usersRow[6];
		$nameElements = explode(" ", $currName);
		$elementsCount = count($nameElements);
		if($elementsCount > 0) {
			$first_name = $nameElements[0];
			
			if($elementsCount==2) {
				$last_name = $nameElements[1];
			}
			if($elementsCount==3) {
				$middle_name = $nameElements[1];
				$last_name = $nameElements[2];
			}
		}

		$proposedFix .= "UPDATE users SET first_name = '" . mysql_real_escape_string($first_name) . "', middle_name = '" . mysql_real_escape_string($middle_name) . "', last_name = '" . mysql_real_escape_string($last_name) . "' WHERE user_id = $usersRow[0];\n";
	}
}

mysql_close($conn);

echo "\nproposedFix (copy and paste to mysql prompt if you want to run it):\n$proposedFix\n";

?>