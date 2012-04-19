#!/usr/bin/php
<?php

require_once '../ojs_db_connect.php';

$revisionNumbersUpdated = 0;

//
// PURPOSE OF SCRIPT: correct the version # in review_rounds table. We had globally set this 1 when we imported data to OJS, so it's now off in some cases
// 
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// GET LIST OF CURRENT VERSION NUMBER FOR EACH ARTICLE ACCORDING TO VARIOUS TABLES
//
$reviewRoundRecs = array();
$reviewRoundRecsQuery = "SELECT rr.submission_id, rr.round, rr.review_revision, a.review_file_id, MAX(af.revision) ";
$reviewRoundRecsQuery .= "FROM review_rounds AS rr, articles AS a, article_files AS af ";
$reviewRoundRecsQuery .= "WHERE a.article_id = rr.submission_id AND af.file_id = a.review_file_id AND af.round = rr.round AND af.type = 'submission/review'";
$reviewRoundRecsQuery .= "GROUP BY af.article_id ";
$reviewRoundRecsQuery .= "ORDER BY af.article_id, revision";
//echo "\nreviewRoundRecsQuery: $reviewRoundRecsQuery\n";
$reviewRoundRecsResult = mysql_query($reviewRoundRecsQuery);
if($reviewRoundRecsResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nreviewRoundRecsQuery: $reviewRoundRecsQuery\n");
} else {
	while($reviewRound = mysql_fetch_array($reviewRoundRecsResult)) {
		$reviewRoundRecs[] = $reviewRound;
	}
}

//echo "\nreviewRoundRecs:\n";
//print_r($reviewRoundRecs);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// UPDATE VERSION NUMBER IN REVIEW_ROUNDS
//
foreach($reviewRoundRecs as $reviewRound) {
	$reviewRoundRevNum = $reviewRound['review_revision'];
	$articleFileRevNum = $reviewRound['MAX(af.revision)'];
	$submissionId = $reviewRound['submission_id'];
	$round = $reviewRound['round'];
	$reviewRevision = $reviewRound['review_revision'];
	
	if($reviewRoundRevNum != $articleFileRevNum) {
		$reviewVersionUpdateQuery = "UPDATE review_rounds SET review_revision = $articleFileRevNum ";
		$reviewVersionUpdateQuery .= "WHERE submission_id = $submissionId AND round = $round AND review_revision = $reviewRevision";
		echo "\nreviewVersionUpdateQuery: $reviewVersionUpdateQuery\n";
		$reviewVersionUpdateResult = mysql_query($reviewVersionUpdateQuery);
		if($reviewVersionUpdateResult === FALSE) {
			die("\nInvalid query: " . mysql_error() . "\nreviewVersionUpdateQuery: $reviewVersionUpdateQuery\n");
		} else {
			$revisionNumbersUpdated++;
		}		
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
echo "\nRevision numbers updated: $revisionNumbersUpdated\n";

?>