#!/usr/bin/php
<?php

require './ojs_db_connect.php';
$articlesFixed = 0;
$issueCount = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// GET ISSUE IDS
//
$issueIds = array();
$issueIdQuery = "SELECT issue_id FROM issues ORDER BY issue_id";
$issueIdResult = mysql_query($issueIdQuery);
if($issueIdResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nissueIdQuery: $issueIdQuery\n");
} else {
	while($issueRec = mysql_fetch_array($issueIdResult)) {
		$issueIds[] = $issueRec[0];
	}
}

//echo "\nissueIds:\n";
//print_r($issueIds);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// GET ARTICLE ORDERING INFO
//
$issueInfo = array();
foreach($issueIds as $issueId) {
	$issueInfoQuery = "SELECT DISTINCT pa.pub_id, pa.article_id, pa.issue_id, pa.seq, a.section_id, a.status,
	COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
	COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
	COALESCE(o.seq, s.seq) AS section_seq,
	pa.seq
	FROM published_articles pa, articles a 
	LEFT JOIN sections s ON s.section_id = a.section_id
	LEFT JOIN custom_section_orders o ON (a.section_id = o.section_id AND o.issue_id = $issueId)
	LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = 'title' AND stpl.locale = 'en_US')
	LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = 'title' AND stl.locale = 'en_US')
	LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = 'abbrev' AND sapl.locale = 'en_US')
	LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = 'abbrev' AND sal.locale = 'en_US')
	WHERE	pa.article_id = a.article_id
	AND pa.issue_id = $issueId
	AND a.status <> 0
	ORDER BY section_seq ASC, pa.seq ASC";
	
	$issueInfoResult = mysql_query($issueInfoQuery);
	if($issueInfoResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\nissueInfoQuery: $issueInfoQuery\n");
	} else {
		while($articleSectionRec = mysql_fetch_array($issueInfoResult)) {
			$issueInfo[] = $articleSectionRec;
		}
	}	
}

//print_r($issueInfo);

$articleSeq = 1; //articleSeq starts at 1
$sectionSeq = 0; //sectionSeq, on the other hand, starts at 0!
$currSectionId = 0;
$currIssueId = 0;
$hasCustomSequencing = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// ITERATE THROUGH ISSUES/SECTIONS/ARTICLES
//
foreach($issueInfo as $info) {
	$pubId = $info['pub_id'];
	$sectionId = $info['section_id'];
	$articleId = $info['article_id'];
	$issueId = $info['issue_id'];
	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
	//
	// CHECK TO SEE IF WE'VE REACHED A NEW ISSUE AND/OR SECTION
	//	
	if($currIssueId != $issueId || $currSectionId != $sectionId) {
		if($issueId != $currIssueId) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
			//
			// RESET VALUES AT BEGINNING OF ISSUE
			//
			$sectionSeq = 0;
			$currIssueId = $issueId;
			$issueCount++;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
			//
			// SEE IF CUSTOM SECTION SEQUENCING EXISTS FOR THIS ISSUE
			//
			/***
			$hasCustomSequencing = 0;
			$customSeqExistsQuery = "SELECT count(*) FROM custom_section_orders WHERE issue_id = $issueId";
			$customSeqExistsResult = mysql_query($customSeqExistsQuery);
			if($customSeqExistsResult === FALSE) {
				die("\nInvalid query: " . mysql_error() . "\ncustomSeqExistsQuery: $customSeqExistsQuery\n");
			} else {
				$customSectionCount = mysql_fetch_row($customSeqExistsResult);
				$hasCustomSequencing = $customSectionCount[0];
			}
			echo "\nissue_id: $issueId\thasCustomSequencing: $hasCustomSequencing\n";
			***/
		}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		// RESET VALUES AT BEGINNING OF SECTION
		//
		$articleSeq = 1;
		$currSectionId = $sectionId;
	}
	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
	//
	// UPDATE CUSTOM SECTION SEQUENCING
	//
	if($articleSeq == 1) {		
		//if($hasCustomSequencing) {
			$sectionSeqUpdated = 0;
			$customSectionQuery = "SELECT * FROM custom_section_orders WHERE issue_id = $issueId AND section_id = $sectionId";
			$customSectionResult = mysql_query($customSectionQuery);
			if($customSectionResult === FALSE) {
				die("\nInvalid query: " . mysql_error() . "\ncustomSectionQuery: $customSectionQuery\n");
			} else {
				while($customSectionRec = mysql_fetch_array($customSectionResult)) {
					$sectionSeqUpdateQuery = "UPDATE custom_section_orders SET seq = $sectionSeq WHERE issue_id = $issueId AND section_id = $sectionId";
					echo "\nsectionSeqUpdateQuery: $sectionSeqUpdateQuery\n";
					$sectionSeqUpdateResult = mysql_query($sectionSeqUpdateQuery);
					if($sectionSeqUpdateResult === FALSE) {
						die("\nInvalid query: " . mysql_error() . "\nsectionSeqUpdateQuery: $sectionSeqUpdateQuery\n");
					}
					$sectionSeqUpdated = 1;
				}
				if(!$sectionSeqUpdated) {
					$customSectionOrderCreate = "INSERT INTO custom_section_orders (seq, issue_id, section_id) VALUES ($sectionSeq, $issueId, $sectionId)";
					$customSectionOrderResult = mysql_query($customSectionOrderCreate);
					echo "\ncustomSectionOrderCreate: $customSectionOrderCreate\n";
					if($customSectionOrderResult === FALSE) {
						die("\nInvalid query: " . mysql_error() . "\ncustomSectionOrderCreate: $customSectionOrderCreate\n");
					}
				}			
				$sectionSeq++;
			}
		//}
	}


	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
	//
	// UPDATE ARTICLE SEQUENCING
	//
	$seqUpdateQuery = "UPDATE published_articles SET seq = $articleSeq WHERE pub_id = $pubId AND article_id = $articleId AND issue_id = $issueId";
	echo "\nseqUpdateQuery: $seqUpdateQuery\n";
	$seqUpdateResult = mysql_query($seqUpdateQuery);
	if($seqUpdateResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\nseqUpdateQuery: $seqUpdateQuery\n");
	} else {
		$articlesFixed++;
	}
	// increment
	$articleSeq++;
	
}


mysql_close($conn);

echo "\nArticle sequences reset: $articlesFixed\n";
echo "\nIssues Processed: $issueCount\n";

?>