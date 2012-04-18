#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$articlesUpdated = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// GET EDIKIT DATES SUBMITTED
//
$escholArticleInfo = array();

$escholDateQuery = "SELECT article_id, setting_value FROM article_settings WHERE setting_name = 'eschol_dateSubmitted' ORDER BY article_id";
//echo "\nescholDateQuery: $escholDateQuery\n";

$escholDateResult = mysql_query($escholDateQuery);
if($escholDateResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nescholDateQuery: $escholDateQuery\n");
} else {
	while($articleSettingsRec = mysql_fetch_object($escholDateResult)) {
		$articleId = $articleSettingsRec->article_id;
		$escholDateSubmitted = $articleSettingsRec->setting_value;
		//2011-01-03T12:48:44-08:00
		if(substr($escholDateSubmitted,10,1) == 'T') {
			$escholDateSubmitted = substr($escholDateSubmitted,0,10) . ' ' . substr($escholDateSubmitted,11,8);
			
		} elseif(strlen($escholDateSubmitted) == 10) {
			$escholDateSubmitted = $escholDateSubmitted . ' 00:00:00';
		} else {
			die("\neschol_dateSubmitted malformed! articleId: $articleId, escholDateSubmitted: $escholDateSubmitted\n");
		}
		$escholArticleInfo[] = array($articleId,$escholDateSubmitted);
	}
}

//echo "\nescholArticleInfo:\n";
//print_r($escholArticleInfo);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// UPDATE ARTICLE SUBMIT DATE
//
foreach($escholArticleInfo as $currArticleInfo) {
	$articleId = 0;
	$escholSubmitDate = '';
	$articleId = $currArticleInfo[0];
	$escholSubmitDate = $currArticleInfo[1];
	$articleUpdateQuery = "UPDATE articles SET date_submitted = '$escholSubmitDate' WHERE article_id = $articleId";
	//echo "\narticleUpdateQuery: $articleUpdateQuery\n";
	$articleUpdateResult = mysql_query($articleUpdateQuery);
	if($articleUpdateResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\narticleUpdateQuery: $articleUpdateQuery\n");
	} else {
		$articlesUpdated++;
	}
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// CLOSE DB CONN
//
mysql_close($conn);

//
//PRINT RESULTS
//	
echo "\nArticle submit dates updated: $articlesUpdated\n";

?>