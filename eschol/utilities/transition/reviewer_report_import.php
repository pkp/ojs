#!/usr/bin/php
<?php

require './import_set_parameters.php';
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_reviewer_rpt($importHome,$importParentDir);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Function to import reviewer report
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_reviewer_rpt($importHome,$importParentDir) {
	echo "\n**** IMPORTING REVIEWER REPORTS ****\n";
	
	//
	// LOOP THROUGH JOURNALS
	//
	$recsCreated = 0;
	$reviewersCreated = 0;
	$rolesCreated = 0;
	$reviewsFixed = 0;
	$dupCtr = 0;
	$n = 0;
	foreach ($importParentDir as $dir) {
		
		$journalPath = $dir[0];
		$eschol_article_id_begin = $dir[1];
		$journalId = $dir[2];
	
		$currImportFile = $importHome . $journalPath . 'reviewer_report.tsv';
		//echo "Curr Import File: $currImportFile\n";
		
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		//READ HISTORY FILE
		//
		$row = 1;
		$numRows = 0;				
		if (file_exists($currImportFile)) {
			//open file
			$handle = fopen($currImportFile, "r");
			$numRows = count(file($currImportFile));
			//echo "\nnum of rows in file: $numRows\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
			//FOR EACH ROW IN THE HISTORY FILE
			//

			while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {

				$user_id = 0;
				$article_id = 0;
				$bp_name = '';
				$bp_suggested = '';
				$bp_requested = '';
				$bp_committed = '';
				$bp_abrogated_bool = 0;
				$bp_declined_bool = 0;
				$bp_reviewed = '';
				$bp_due_date = '';
				$firstName = '';
				$middleName = '';
				$lastName = '';
				$nameElements = array();
				
				if($row > 1) {
					$bp_userid = $data[0];	
					$bp_name = $data[1];	
					$bp_email = $data[2];
					$bp_title = $data[3];
					$bp_manuscriptid = $data[4];	
					$bp_reviewer_number = $data[5];	
					$bp_state = $data[6];
					$bp_suggested = $data[7];	
					$bp_requested = $data[8];	
					$bp_declined = $data[9];	
					$bp_tooslow = $data[10];	
					$bp_committed = $data[11];	
					$bp_abrogated = $data[12];	
					$bp_gave_up = $data[13];
					$bp_reviewed = $data[14];	
					$bp_due_date = $data[15];	
					$bp_uploaded = $data[16];	
					$bp_rereview = $data[17];
					
					//
					//TRANSLATE VALUES AS NECESSARY
					//
					$bp_suggested = ($bp_suggested == '0000:00:00' OR $bp_suggested == '') ? 'NULL': $bp_suggested;
					$bp_requested = ($bp_requested == '0000:00:00' OR $bp_requested == '') ? 'NULL': $bp_requested;
					$bp_committed = ($bp_committed == '0000:00:00' OR $bp_committed == '') ? 'NULL': $bp_committed;
					$bp_reviewed = ($bp_reviewed == '0000:00:00' OR $bp_reviewed == '') ? 'NULL': $bp_reviewed;
					$bp_due_date = ($bp_due_date == '0000:00:00' OR $bp_due_date == '') ? 'NULL': $bp_due_date;
					
					$bp_declined_bool = ($bp_declined == '0000:00:00' OR $bp_declined == '') ? 0 : 1;
					$bp_abrogated_bool = ($bp_abrogated == '0000:00:00' OR $bp_abrogated == '') ? 0 : 1;
					
					//echo "\nbp_declined: $bp_declined bp_declined_bool: $bp_declined_bool\n";
					//echo "\bp_abrogated: $bp_abrogated bp_abrogated_bool: $bp_abrogated_bool\n";
					
					//
					// If review was declined but is also completed, set declined to 0 (zero).
					//
					if($bp_declined_bool && $bp_reviewed != 'NULL') {
						$bp_declined_bool = 0;
					}
					
					//CHECK FOR REQUIRED DATA VALUES
					if($bp_email == '' or $bp_email == 'NULL') {
						echo "\nERROR: Email not populated for $currImportFile Line: $row NumFields in Line: $num Not importing this line.\n";
						continue;
					}
					if($bp_manuscriptid == '' or $bp_manuscriptid == 'NULL') {
						echo "\nERROR: BP Manuscript ID not populated for $currImportFile Line: $row NumFields in Line: $num Not importing this line.\n";
						continue;
					}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
					//
					//QUERY DB FOR article_id
					//
					$article_link = $eschol_article_id_begin . '_' . $bp_manuscriptid;
					$articleid_query = 'SELECT article_id FROM article_settings ';
					$articleid_query .= "WHERE setting_name = 'eschol_articleid' AND setting_value = '$article_link'";
	
	
					//echo "\narticleid_query: $articleid_query\n";
					$articleIdResult = 0;
					$articleIdResult = mysql_query($articleid_query);
					if(!$articleIdResult) {
						die("\nInvalid query: " . mysql_error() . "\n");
					} 
					
					if (mysql_num_rows($articleIdResult)==0) {
						echo "\n**ALERT** No article_settings record exists with eschol article ID or eschol_submission_path = '$article_link'.\nQuery: $articleid_query\nNo history created for this article.\n";
						$article_settings_exists = 0;
						continue;
					}
					
					$article_id = mysql_result($articleIdResult,0);								
					//echo "\narticle_id: $article_id\n";
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
					//
					//get OJS user_id for reviewer
					//
					$userid_query = 'SELECT user_id FROM users ';
					$userid_query .= "WHERE email = '$bp_email'";
					
					//echo "\nuserid_query: $userid_query\n";
					unset($userid_query_result);
					
					$userid_query_result = mysql_query($userid_query);
					
					if(!$userid_query_result) {
						die("\nInvalid query: " . mysql_error() . "\n");
					} 
					
					if (mysql_num_rows($userid_query_result) > 0) {
						$user_id = mysql_result($userid_query_result,0);
					} else {
						///////////////////////
						// CREATE USERS REC
						//////////////////////
						$usersCreateQuery = "INSERT INTO users (username, first_name, last_name, email) ";
						$usersCreateQuery .= "VALUES('$bp_email', '[FIXME]', '" . mysql_real_escape_string($bp_name) . "', '$bp_email')";
						
						//echo "\nusersCreateQuery: $usersCreateQuery\n";
						$usersCreateResult = mysql_query($usersCreateQuery);
						if(!$usersCreateResult) {
							die("\nInvalid query: " . mysql_error() . "\nusersCreateQuery: $usersCreateQuery\n");
						} else {
							$user_id = mysql_insert_id();
							$reviewersCreated++;
						}
						echo "\nNames need fixing for new users record with user_id: $user_id\nusersCreateQuery was: $usersCreateQuery";
						
						if($user_id == 0) {
							die("ERROR: Could not get user_id after query: $usersCreateQuery\n");
						}	

						//////////////////////////////////////////////
						// FIX EXISTING review_assignments RECORD
						/////////////////////////////////////////////	
						$findFixRecordQuery = "SELECT * FROM review_assignments ";
						$findFixRecordQuery .= "WHERE submission_id = $article_id AND date_assigned = '$bp_suggested'";
						
						//echo "\nfindFixRecordQuery: $findFixRecordQuery\n";
						
						$findFixRecordResult = mysql_query($findFixRecordQuery);
						if($findFixRecordResult === FALSE) {
							die("\nInvalid query: " . mysql_error() . "\nfindFixRecordQuery: $findFixRecordQuery\n");
						} else {
							if(mysql_num_rows($findFixRecordResult) > 0) {
								$fixReviewQuery = "UPDATE review_assignments SET reviewer_id = $user_id ";
								$fixReviewQuery .= "WHERE submission_id = $article_id AND date_assigned = '$bp_suggested'";
								echo "\nfixReviewQuery: $fixReviewQuery\n";
								
								$fixReviewResult = mysql_query($fixReviewQuery);
								if(!$fixReviewResult) {
									die("\nInvalid query: " . mysql_error() . "\nfixReviewQuery: $fixReviewQuery\n");
								} else {
									$reviewsFixed++;
								}
							}
						}
						
 					}
					//echo "\nuser_id after userid_query: $user_id\n";
					
					if($article_id && $user_id) {					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// CHECK FOR DUPLICATES BEFORE CREATING RECORD
						//
						$dup_check_stmt = "SELECT * FROM review_assignments ";
						$dup_check_stmt .= "WHERE 
						reviewer_id = $user_id 
						AND submission_id = $article_id 
						AND date_assigned = '$bp_suggested'	
						";
						//echo "\ndup_check_stmt: $dup_check_stmt\n";
						
						$dup_check_result = mysql_query($dup_check_stmt);
						if(!$dup_check_result) {
							die("\nInvalid query: " . mysql_error() . "\n");
						}
						if(mysql_num_rows($dup_check_result) != 0) {
							$dupCtr++;
						} else {
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							//INSERT RECORD INTO review_assignments
							//
							$insert_stmt = "
							INSERT INTO review_assignments
							(
							review_assignments.reviewer_id,
							review_assignments.submission_id,
							review_assignments.date_assigned,
							review_assignments.date_notified,
							review_assignments.declined,
							review_assignments.date_confirmed,
							review_assignments.cancelled,
							review_assignments.date_completed,
							review_assignments.date_due
							)
							VALUES
							(
							$user_id,
							$article_id,";
							
							if($bp_suggested == 'NULL') {
								$insert_stmt .= "NULL, ";
							} else {
								$insert_stmt .= "'$bp_suggested', ";
							}
							
							if($bp_requested == 'NULL') {
								$insert_stmt .= "NULL, ";
							} else {
								$insert_stmt .= "'$bp_requested', ";
							}
							
							$insert_stmt .= "$bp_declined_bool, ";
							
							if($bp_committed == 'NULL') {
								$insert_stmt .= "NULL, ";
							} else {
								$insert_stmt .= "'$bp_committed', ";
							}
							
							$insert_stmt .= "$bp_abrogated_bool, ";
							
							if($bp_reviewed == 'NULL') {
								$insert_stmt .= "NULL, ";
							} else {
								$insert_stmt .= "'$bp_reviewed', ";
							}
							
							if($bp_due_date == 'NULL') {
								$insert_stmt .= "NULL";
							} else {
								$insert_stmt .= "'$bp_due_date'";
							}
							
							$insert_stmt .= ")";				
							//echo "\ninsert_stmt: $insert_stmt\n";
							$reviewInsertResult = mysql_query($insert_stmt);
							if(!$reviewInsertResult) {
								die("\nInvalid query: " . mysql_error() . "\ninsert_stmt: $insert_stmt");
							} else {
								$recsCreated++;
							}
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// CHECK FOR DUPLICATES BEFORE CREATING roles RECORD
							//
							$roleDupQuery = "SELECT * FROM roles ";
							$roleDupQuery .= "WHERE journal_id = $journalId AND user_id = $user_id AND role_id = 4096";
							
							//echo "\nroleDupQuery: $roleDupQuery\n";
							
							$roleDupResult = mysql_query($roleDupQuery);
							if(!$roleDupResult) {
								die("\nInvalid query: " . mysql_error() . "\nroleDupQuery: $roleDupQuery");
							}
							if(mysql_num_rows($roleDupResult) > 0) {
								$dupCtr++;
							} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
								//
								// CREATE roles RECORD
								//
								$rolesQuery = "INSERT INTO roles ";
								$rolesQuery .= "(journal_id, user_id, role_id)";
								$rolesQuery .= " VALUES ($journalId, $user_id, 4096)";
								
								//echo "\nrolesQuery: $rolesQuery\n";
								$rolesCreateResult = mysql_query($rolesQuery);
								if(!$rolesCreateResult) {
									die("\nInvalid query: " . mysql_error() . "\nrolesQuery: $rolesQuery\n");
								} else {
									$rolesCreated++;
								}	
							}
						}
						
					} 
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				} //end if($row > 1)
				$row++;
			} //while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE)

			fclose($handle);
		} else {
			echo "\nALERT: $currImportFile could not be opened\n";
		}
	}
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of review_assignments recs that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	}

	echo "\nReviewers created as ojs users: $reviewersCreated\n";
	echo "\nReview assignments fixed: $reviewsFixed\n";
	echo "\nRecords created: $recsCreated\n";
	echo "\nRoles records created: $rolesCreated\n";
	echo "\nIMPORT OF REVIEWER REPORTS FINISHED.\n\n";
	
}

mysql_close($conn);


?>
