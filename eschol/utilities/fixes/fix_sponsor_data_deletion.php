#!/usr/bin/php
<?php

$sponsorsRestored = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// FIRST GET DATA FROM DEV (WHICH WAS RESTORED FROM BKUP)
//
//removed db credentials for security.
$conn = mysql_connect($db_host,$db_user,$db_password);
if(!$conn) {
	die("\nCould not connect to db: " . mysql_error() . "\n");
}
if(!mysql_select_db('ojs')) {
	die("\nCould not select db: " . mysql_error() . "\n");
} else {
	echo "\nConnected to $db_host\n";
} 

$articleSettingsInfo = array();
$selectQuery = "SELECT * FROM article_settings WHERE setting_name = 'sponsor' AND setting_value != '' AND setting_value IS NOT NULL";
echo "\nselectQuery: $selectQuery\n";
$selectResult = mysql_query($selectQuery);
if($selectResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nselectQuery: $selectQuery\n");
} else {
	while($articleSettingsRec = mysql_fetch_array($selectResult)) {
		$articleSettingsInfo[] = $articleSettingsRec;
	}	
}

mysql_close($conn); //close dev db

//echo "\narticleSettingsInfo:\n";
//print_r($articleSettingsInfo);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// COPY THAT DATA TO THE PROD DB
//
//removed db credentials for security.
$conn = mysql_connect($db_host,'ojsrw',$db_password);
if(!$conn) {
	die("\nCould not connect to db: " . mysql_error() . "\n");
}
if(!mysql_select_db('ojs')) {
	die("\nCould not select db: " . mysql_error() . "\n");
} else {
	echo "\nConnected to $db_host\n";
} 
foreach($articleSettingsInfo as $ackInfo) {
	$articleId = 0;
	$escholAcknowledgements = '';
	$articleId = $ackInfo['article_id'];
	$escholAcknowledgements = $ackInfo['setting_value'];
	$sponsorCheck = "SELECT * FROM article_settings WHERE article_id = $articleId AND setting_name = 'sponsor'";
	echo "\nsponsorCheck: $sponsorCheck\n";
	
	$sponsorResult = mysql_query($sponsorCheck);
	if($sponsorResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\nsponsorCheck: $sponsorCheck\n");
	} else {
		$numRecs = mysql_num_rows($sponsorResult);
		echo "numRecs: $numRecs\n";
		if($numRecs == 0) {
			$createSponsorQuery = "INSERT INTO article_settings (article_id, locale, setting_name, setting_value, setting_type) ";
			$createSponsorQuery .= "VALUES ($articleId, 'en_US', 'sponsor', '" . mysql_real_escape_string($escholAcknowledgements) . "', 'string')";
			echo "createSponsorQuery: $createSponsorQuery\n";
			$createSponsorResult = mysql_query($createSponsorQuery);
			if($createSponsorResult === FALSE) {
				die("\nInvalid query: " . mysql_error() . "\ncreateSponsorQuery: $createSponsorQuery\n");
			} else {
				$sponsorsRestored++;
 			}
		}
		
		//$deleteAcknowledgementsQuery = "DELETE FROM article_settings WHERE article_id = $articleId AND setting_name = 'eschol_acknowledgements'";
		//echo "deleteAcknowledgementsQuery: $deleteAcknowledgementsQuery\n";
		//$updateSponsorResult = mysql_query($deleteAcknowledgementsQuery);
		//if($deleteAcknowledgementsResult === FALSE) {
		//	die("\nInvalid query: " . mysql_error() . "\ndeleteAcknowledgementsQuery: $deleteAcknowledgementsQuery\n");
		//} else {
		//	$acknowledgementsDeleted++;
		//}
		
		echo "\n-------------------------------------------------------------------------------------\n";
	}
}

echo "\nsponsors restored: $sponsorsRestored\n";

?>
