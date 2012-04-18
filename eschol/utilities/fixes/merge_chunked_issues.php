#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$journalPath = 'nelc_uee';
$targetIssueName = 'Unpublished';
$originalIssueNamePattern = 'Unpublished%';

$articlesUpdated = 0;
$issuesDeleted = 0;
$issueSettingsDeleted = 0;

echo "***** Updating issue IDs for journal: $journalPath *****\n\n";
///////////////////////
//get journal ID
///////////////////////
$journalId = 0;
$journalIdQuery = "SELECT journal_id FROM journals WHERE path = '$journalPath'";
$journalIdResult = mysql_query($journalIdQuery);
if(!$journalIdResult) {
	die("\nInvalid query: " . mysql_error() . "\njournalIdQuery: $journalIdQuery\n");
} else {
	$journalId = mysql_result($journalIdResult,0);
}

echo "journalId: $journalId\n";

///////////////////////
//get target issue ID
///////////////////////
$targetIssueId = 0;
$targetIssueQuery = "SELECT issue_settings.issue_id FROM issue_settings ";
$targetIssueQuery .= "LEFT JOIN issues ON issue_settings.issue_id = issues.issue_id ";
$targetIssueQuery .= "WHERE issues.journal_id = $journalId AND issue_settings.setting_name = 'title' AND issue_settings.setting_value = '$targetIssueName'";
$targetIssueResult = mysql_query($targetIssueQuery);
if(!$targetIssueResult) {
	die("\nInvalid query: " . mysql_error() . "\ntargetIssueQuery: $targetIssueQuery\n");
} else {
	$targetIssueId = mysql_result($targetIssueResult,0);
}

echo "targetIssueId: $targetIssueId\n";

/////////////////////////////////
//get IDs of issues to move from
/////////////////////////////////
$originIssueIds = array();
$originIssueQuery = "SELECT issue_settings.issue_id FROM issue_settings ";
$originIssueQuery .= "LEFT JOIN issues ON issue_settings.issue_id = issues.issue_id ";
$originIssueQuery .= "WHERE issues.journal_id = $journalId AND issue_settings.setting_name = 'title' ";
$originIssueQuery .=  "AND issue_settings.setting_value LIKE '$originalIssueNamePattern' AND issue_settings.setting_value != '$targetIssueName'";
$originIssueResult = mysql_query($originIssueQuery);
//echo "originIssueQuery: $originIssueQuery\n";
if(!$originIssueResult) {
	die("\nInvalid query: " . mysql_error() . "\noriginIssueQuery: $originIssueQuery\n");
} else {
	while($issueSettingsRec = mysql_fetch_array($originIssueResult)) {
		$origIssueId = $issueSettingsRec[0];
		$origIssueIds[] = $origIssueId;		
	}
}

echo "origIssueIds:\n";
print_r($origIssueIds);

////////////////////////////////////////////////////////////////
//get largest seq # for target issue from published_articles
////////////////////////////////////////////////////////////////
$currSeq = 0;
$seqQuery = "SELECT seq FROM published_articles WHERE seq=(SELECT MAX(seq) from published_articles WHERE issue_id = $targetIssueId) AND issue_id = $targetIssueId";
$seqResult = mysql_query($seqQuery);
if(!$seqResult) {
	die("\nInvalid query: " . mysql_error() . "\nseqQuery: $seqQuery\n");
} else {
	$currSeq = mysql_result($seqResult,0);
}

//echo "currSeq: $currSeq\n";

//////////////////////////////////////////
//iterate through issues & update articles 
//////////////////////////////////////////
foreach($origIssueIds as $origIssueId) {	
	$articlesQuery = "SELECT article_id FROM published_articles WHERE issue_id = $origIssueId ORDER BY seq";
	$articlesResult = mysql_query($articlesQuery);
	if(!$articlesResult) {
		die("\nInvalid query: " . mysql_error() . "\narticlesQuery: $articlesQuery\n");
	} else {
		while($articlesRec = mysql_fetch_array($articlesResult)) {
			$currArticleId = 0;
			$currArticleId = $articlesRec[0];
			if($currArticleId) {
				$currSeq++;
				//$articlesArray[] = array('issueId' => $origIssueId, 'articleId' => $articlesRec[0], 'newSeq' => $currSeq);
				$articleUpdateQuery = "UPDATE published_articles ";
				$articleUpdateQuery .= "SET issue_id = $targetIssueId, seq = $currSeq ";
				$articleUpdateQuery .= "WHERE article_id = $currArticleId AND issue_id = $origIssueId";
				echo "articleUpdateQuery: $articleUpdateQuery\n";
				
				unset($articleUpdateResult);
				$articleUpdateResult = mysql_query($articleUpdateQuery);
				if(!$articleUpdateResult) {
					die("\nInvalid query: " . mysql_error() . "\narticleUpdateQuery: $articleUpdateQuery\n");
				} else {
					$articlesUpdated++;
				}
			}
		}
		
		///////////////////////////////////////////
		//delete old issue and issue_settings rec
		///////////////////////////////////////////
		$deleteIssueQuery = "DELETE FROM issues WHERE issue_id = $origIssueId AND journal_id = $journalId";
		echo "deleteIssueQuery: $deleteIssueQuery\n";
		unset($deleteIssueResult);
		$deleteIssueResult = mysql_query($deleteIssueQuery);
		if(!$deleteIssueResult) {
			die("\nInvalid query: " . mysql_error() . "\ndeleteIssueQuery: $deleteIssueQuery\n");
		} else {
			$issuesDeleted++;
		}
		
		$deleteIssueSettingQuery = "DELETE FROM issue_settings WHERE issue_id = $origIssueId";
		echo "deleteIssueSettingQuery: $deleteIssueSettingQuery\n";
		unset($deleteIssueSettingResult);
		$deleteIssueSettingResult = mysql_query($deleteIssueSettingQuery);
		if(!$deleteIssueSettingResult) {
			die("\nInvalid query: " . mysql_error() . "\ndeleteIssueSettingQuery: $deleteIssueSettingQuery\n");
		} else {
			$issueSettingsDeleted++;
		}		
	}
}




mysql_close($conn);

echo "\npublished_articles records updated: $articlesUpdated\n";
echo "\nissues deleted: $issuesDeleted\n";
echo "\nissue_settings deleted: $issueSettingsDeleted\n";
echo "\nDONE\n\n";
	
?>