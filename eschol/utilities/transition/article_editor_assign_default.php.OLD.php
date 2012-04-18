#!/usr/bin/php
<?php
$editorId2 = 0;
$editorId3 = 0;
$editorId4 = 0;
$editorId5 = 0;

//$journalId = 6; //comitatus
//$editorId = 2482; //user sullivan@humnet.ucla.edu comitatus

//$journalId = 10; //jcgba
//$editorId = 2742; //hgillis@ucmerced.edu, jcgba 1st editor
//$editorId2 = 2739; //elin@ucmerced.edu, jcgba 2nd editor

//
// uclabiolchem/nutritionbytes
//
//$journalId = 13; //uclabiolchem/nutritionbytes
//$editorId = 2761; //jedmond@mednet.ucla.edu
//$editorId2 = 2745; //eulee@mednet.ucla.edu
//$editorId3 = 2760; //lrome@mednet.ucla.edu

//
// ucsb_ed/spaces
//
$journalId = 17;
$editorId = 2937;
$editorId2 = 2938;
$editorId3 = 2939;
$editorId4 = 2940;
$editorId5 = 2941;

require './ojs_db_connect.php';

echo "\n**** CREATING edit_assignments RECORDS FOR JOURNAL ID $journalId, EDITOR ID $editorId, 2nd EDITOR ID $editorId2 **** \n";
$editAssignmentsCreated = 0;
$editAssignments2Created = 0;
$editAssignments3Created = 0;
$editAssignments4Created = 0;
$editAssignments5Created = 0;

$existsCtr = 0;

$articleIds = array();
$articleIdQuery = "SELECT * from articles where journal_id = $journalId";
$articleIdQueryResult = mysql_query($articleIdQuery);
if(!$articleIdQueryResult) {
	die("\nInvalid query: " . mysql_error() . "\narticleIdQuery: $articleIdQuery");
} else {
	while($articlesRecord = mysql_fetch_object($articleIdQueryResult)) {
		$articleIds[] = $articlesRecord->article_id;
	}
}
//echo "\narticleIds:\n";
//print_r($articleIds);

foreach ($articleIds as $articleId) {
	$editAssignmentCheck = "SELECT * FROM edit_assignments WHERE article_id = $articleId";
	$editAssignmentCheckResult = mysql_query($editAssignmentCheck);
	if($$editAssignmentCheckResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\neditAssignmentCheck: $editAssignmentCheck\n");
	} else {
		if(mysql_num_rows($editAssignmentCheckResult)) {
			$existsCtr++;
		} else {
			//
			// insert record for first editor
			//
			$editAssignmentsQuery = "INSERT INTO edit_assignments ";
			$editAssignmentsQuery .= "(article_id, editor_id, can_edit, can_review, date_notified, date_underway)";
			$editAssignmentsQuery .= " VALUES ($articleId, $editorId, 1, 1, now(), now())";
			
			//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";
			
			$editAssignmentsResult = mysql_query($editAssignmentsQuery);
			if(!$editAssignmentsResult) {
				die("\nInvalid query: " . mysql_error() . "\n");
			} else {
				$editAssignmentsCreated++;
			}	
			
			//
			// insert record for 2nd editor
			//
			if($editorId2) {
				$editAssignmentsQuery2 = "INSERT INTO edit_assignments ";
				$editAssignmentsQuery2 .= "(article_id, editor_id, can_edit, can_review, date_notified, date_underway)";
				$editAssignmentsQuery2 .= " VALUES ($articleId, $editorId2, 1, 1, now(), now())";
				
				//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";
				
				$editAssignmentsResult2 = mysql_query($editAssignmentsQuery2);
				if(!$editAssignmentsResult2) {
					die("\nInvalid query: " . mysql_error() . "\n");
				} else {
					$editAssignments2Created++;
				}
			}
			
			if($editorId3) {
				$editAssignmentsQuery3 = "INSERT INTO edit_assignments ";
				$editAssignmentsQuery3 .= "(article_id, editor_id, can_edit, can_review, date_notified, date_underway)";
				$editAssignmentsQuery3 .= " VALUES ($articleId, $editorId3, 1, 1, now(), now())";
				
				//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";
				
				$editAssignmentsResult3 = mysql_query($editAssignmentsQuery3);
				if(!$editAssignmentsResult3) {
					die("\nInvalid query: " . mysql_error() . "\n");
				} else {
					$editAssignments3Created++;
				}
			}
			
			if($editorId4) {
				$editAssignmentsQuery4 = "INSERT INTO edit_assignments ";
				$editAssignmentsQuery4 .= "(article_id, editor_id, can_edit, can_review, date_notified, date_underway)";
				$editAssignmentsQuery4 .= " VALUES ($articleId, $editorId4, 1, 1, now(), now())";
				
				//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";
				
				$editAssignmentsResult4 = mysql_query($editAssignmentsQuery4);
				if(!$editAssignmentsResult4) {
					die("\nInvalid query: " . mysql_error() . "\n");
				} else {
					$editAssignments4Created++;
				}
			}
			
			if($editorId5) {
				$editAssignmentsQuery5 = "INSERT INTO edit_assignments ";
				$editAssignmentsQuery5 .= "(article_id, editor_id, can_edit, can_review, date_notified, date_underway)";
				$editAssignmentsQuery5 .= " VALUES ($articleId, $editorId5, 1, 1, now(), now())";
				
				//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";
				
				$editAssignmentsResult5 = mysql_query($editAssignmentsQuery5);
				if(!$editAssignmentsResult5) {
					die("\nInvalid query: " . mysql_error() . "\n");
				} else {
					$editAssignments5Created++;
				}
			}
		}
	}
	


}

echo "\nNumber of articles for which edit_assignments records already existed: $existsCtr\n";
echo "\nEdit Assignments records created: $editAssignmentsCreated\n";
echo "\nEdit Assignments records created for 2nd editor: $editAssignments2Created\n";
echo "\nEdit Assignments records created for 3rd editor: $editAssignments3Created\n";
echo "\nEdit Assignments records created for 4th editor: $editAssignments4Created\n";
echo "\nEdit Assignments records created for 5th editor: $editAssignments5Created\n";
echo "\nIMPORT OF EDITOR ASSIGNMENTS FOR $unpubString ARTICLES FINISHED.\n\n";

mysql_close($conn);
	
?>