#!/usr/bin/php
<?php

require './ojs_db_connect.php';
$sponsorsUpdated = 0;
$sponsorsCreated = 0;
$acknowledgementsDeleted = 0;
$numAckRecs = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// GET LIST OF IMPORTED ACKNOWLEDGEMENTS VALUES
//
$acknowledgementsInfo = array();
$acknowledgeQuery = "SELECT * FROM article_settings WHERE setting_name = 'eschol_acknowledgements' AND trim(setting_value) != '' AND setting_value IS NOT NULL ORDER BY article_id";
$acknowledgeResult = mysql_query($acknowledgeQuery);
if($acknowledgeResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nacknowledgeQuery: $acknowledgeQuery\n");
} else {
	while($acknowledgeRec = mysql_fetch_array($acknowledgeResult)) {
		$acknowledgementsInfo[] = $acknowledgeRec;
		$numAckRecs++;
	}	
}


//echo "\nacknowledgementsInfo:\n";
//print_r($acknowledgementsInfo);
//echo "\nnumAckRecs: $numAckRecs\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// LOOP THROUGH ARTICLES AND COPY ACKNOWLEDGEMENTS INTO SPONSORS
//
foreach($acknowledgementsInfo as $ackInfo) {
	$articleId = 0;
	$escholAcknowledgements = '';
	$articleId = $ackInfo['article_id'];
	$escholAcknowledgements = $ackInfo['setting_value'];
	$sponsorCheck = "SELECT * FROM article_settings WHERE article_id = $articleId AND setting_name = 'sponsor'";
	//echo "\nsponsorCheck: $sponsorCheck\n";
	$sponsorResult = mysql_query($sponsorCheck);
	if($sponsorResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\nsponsorCheck: $sponsorCheck\n");
	} else {
		$numRecs = mysql_num_rows($sponsorResult);
		echo "numRecs: $numRecs\n";
		if($numRecs == 1) {
			$updateSponsorQuery = "UPDATE article_settings SET setting_value = '" . mysql_real_escape_string($escholAcknowledgements) . "' WHERE setting_name = 'sponsor'";
			echo "updateSponsorQuery: $updateSponsorQuery\n";
			$updateSponsorResult = mysql_query($updateSponsorQuery);
			if($updateSponsorResult === FALSE) {
				die("\nInvalid query: " . mysql_error() . "\nupdateSponsorQuery: $updateSponsorQuery\n");
			} else {
				$sponsorsUpdated++;
 			}
		} else if($numRecs == 0) {
			$createSponsorQuery = "INSERT INTO article_settings (article_id, locale, setting_name, setting_value, setting_type) ";
			$createSponsorQuery .= "VALUES ($articleId, 'en_US', 'sponsor', '" . mysql_real_escape_string($escholAcknowledgements) . "', 'string')";
			echo "createSponsorQuery: $createSponsorQuery\n";
			$createSponsorResult = mysql_query($createSponsorQuery);
			if($createSponsorResult === FALSE) {
				die("\nInvalid query: " . mysql_error() . "\ncreateSponsorQuery: $createSponsorQuery\n");
			} else {
				$sponsorsCreated++;
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


mysql_close($conn);

echo "\nsponsors updated: $sponsorsUpdated\n";
echo "\nsponsors created: $sponsorsCreated\n";
echo "\nacknowledgements deleted: $acknowledgementsDeleted\n";


?>