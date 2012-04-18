#!/usr/bin/php
<?php

require './ojs_db_connect.php';
$usersFixed = 0;
$userSettingsFixed = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// FIX USERS
//
$usersInfo = array();
$usersQuery = "SELECT user_id, salutation, first_name, middle_name, last_name FROM users WHERE ";
$usersQuery .= "salutation NOT LIKE '%Dear%' "; 
$usersQuery .= "AND salutation NOT LIKE '%Dr%' ";
$usersQuery .= "AND salutation NOT LIKE '%Miss%' ";
$usersQuery .= "AND salutation NOT LIKE '%Mrs%' ";
$usersQuery .= "AND salutation NOT LIKE '%Mr%' ";
$usersQuery .= "AND salutation NOT LIKE '%Ms%' ";
$usersQuery .= "AND salutation NOT LIKE '%Prof%' ";
$usersQuery .= "AND salutation != '' AND salutation IS NOT NULL";

//echo "\nusersQuery: $usersQuery\n";

$usersQueryResult = mysql_query($usersQuery);
if($usersQueryResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nusersQuery: $usersQuery\n");
} else {
	while($usersRec = mysql_fetch_array($usersQueryResult)) {
		$usersInfo[] = $usersRec;
	}	
}

//echo "\nusersInfo:\n";
//print_r($usersInfo);

foreach($usersInfo as $userInfo) {
	$newSuffix = '';
	$newSuffix = $userInfo['salutation'];
	$newSuffix = trim($newSuffix);
	//echo "\nnewSuffix: $newSuffix\n";

	$userSettingCheckQuery = "SELECT * FROM user_settings WHERE user_id = " . $userInfo['user_id'] . " AND setting_name = 'eschol_suffix'";
	$userSettingCheckResult = mysql_query($userSettingCheckQuery);
	if($userSettingCheckResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\nuserSettingCheckQuery: $userSettingCheckQuery\n");
	} else {
		$numRecs = mysql_num_rows($userSettingCheckResult);
		if($numRecs > 1) {
			echo "\nanomaly! numRecs: $numRecs\n";
		} else {
			if($numRecs == 0) {
				$userSettingFixQuery = "INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) ";
				$userSettingFixQuery .= "VALUES (" . $userInfo['user_id'] . ", 'en_US', 'eschol_suffix', '$newSuffix', 'string')";
				echo "\nuserSettingFixQuery: $userSettingFixQuery\n";
				$userSettingFixResult = mysql_query($userSettingFixQuery);
				if($userSettingFixResult === FALSE) {
					die("\nInvalid query: " . mysql_error() . "\nuserSettingFixQuery: $userSettingFixQuery\n");
				} else {
					$userSettingsFixed++;
				}			
			}
			
			$userFixQuery = "UPDATE users SET salutation = NULL WHERE user_id = " . $userInfo['user_id'];
			echo "\nuserFixQuery: $userFixQuery\n";
			$userFixResult = mysql_query($userFixQuery);
			if($userFixResult === FALSE) {
				die("\nInvalid query: " . mysql_error() . "\nuserFixQuery: $userFixQuery\n");
			} else {
				$usersFixed++;
			}		
		}
	}
}

mysql_close($conn);

echo "\nuser records fixed: $usersFixed\n";
echo "\nuser_settings records fixed: $usersFixed\n";

?>