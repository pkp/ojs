#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$issuesDeleted = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// GET LIST OF 'UNPUBLISHED' ISSUES
//
$unpublishedIssues = array();
$issueQuery = "SELECT i.issue_id FROM issues AS i ";
$issueQuery .= "LEFT JOIN issue_settings AS i_s ON i.issue_id = i_s.issue_id ";
$issueQuery .= "WHERE i_s.setting_name = 'title' AND i_s.setting_value = 'Unpublished' AND i.published = 0 ";
$issueQuery .= "AND i.journal_id != 38"; //don't run for UEE.
echo "\nissueQuery: $issueQuery\n";
$issueResult = mysql_query($issueQuery);
if($issueResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nissueQuery: $issueQuery\n");
} else {
	while($issueRec = mysql_fetch_object($issueResult)) {
		$unpublishedIssues[] = $issueRec->issue_id;	
	}
}

//echo "\nunpublishedIssues:\n";
//print_r($unpublishedIssues);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// REMOVE ARTICLES FROM 'UNPUBLISHED' ISSUES, THEN REMOVE 'UNPUBLISHED' ISSUES
//
foreach($unpublishedIssues as $currIssueId) {
	// REMOVE ARTICLES
	$deletePubArticlesQuery = "DELETE FROM published_articles WHERE issue_id = $currIssueId";
	echo "\ndeletePubArticlesQuery: $deletePubArticlesQuery\n";
	$deletePubArticlesResult = mysql_query($deletePubArticlesQuery);
	if($deletePubArticlesResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\ndeletePubArticlesQuery: $deletePubArticlesQuery\n");
	}
	
	// DELETE ISSUES
	$deleteIssuesQuery = "DELETE FROM issues WHERE issue_id = $currIssueId";
	echo "\ndeleteIssuesQuery: $deleteIssuesQuery\n";
	$deleteIssuesResult = mysql_query($deleteIssuesQuery);
	if($deleteIssuesResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\ndeleteIssuesQuery: $deleteIssuesQuery\n");
	}
	
	// DELETE ISSUE_SETTINGS 
	$deleteIssueSettingsQuery = "DELETE FROM issue_settings WHERE issue_id = $currIssueId";
	echo "\ndeleteIssueSettingsQuery: $deleteIssueSettingsQuery\n";
	$deleteIssueSettingsResult = mysql_query($deleteIssueSettingsQuery);
	if($deleteIssueSettingsResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\ndeleteIssueSettingsQuery: $deleteIssueSettingsQuery\n");
	}
	
	$issuesDeleted++;
}

/*************************************************

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// GET LIST OF CURRENT ISSUES
//
$hasCurrentIssue = array();
$currentIssueQuery = "SELECT journal_id, issue_id FROM issues WHERE current = 1 ORDER BY journal_id, issue_id";
$currentIssueResult = mysql_query($currentIssueQuery);
if($currentIssueResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\ncurrentIssueQuery: $currentIssueQuery\n");
} else {
	while($currIssueRec = mysql_fetch_object($currentIssueResult)) {
		$hasCurrentIssue[] = $currIssueRec->journal_id;	
	}	
}

echo "\nhasCurrentIssue:\n";
print_r($hasCurrentIssue);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// GET LIST OF JOURNALS
//
$journalList = array();
$journalsQuery = "SELECT journal_id FROM journals ORDER BY journal_id";
$journalsResult = mysql_query($journalsQuery);
if($journalsResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\njournalsQuery: $journalsQuery\n");
} else {
	while($journalRec = mysql_fetch_object($journalsResult)) {
		$journalList[] = $journalRec->journal_id;	
	}	
}

echo "\njournalList:\n";
print_r($journalList);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// SET CURRENT ISSUE FOR EACH JOURNAL
//
foreach($journalList AS $journalId) {
	if(!(in_array($journalId,$hasCurrentIssue))) {
		//find most recent published issue
		$latestIssueQuery = "";
	}
}

*************************************************/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// CLOSE DB CONN
//
mysql_close($conn);

//
//PRINT RESULTS
//	
echo "\n'Unpublished' Issues Deleted: $issuesDeleted\n";

?>