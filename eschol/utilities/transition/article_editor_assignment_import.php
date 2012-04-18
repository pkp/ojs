#!/usr/bin/php
<?php

require './import_set_parameters.php';
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_history($importHome,$importParentDir,1); //unpublished
import_history($importHome,$importParentDir,0); //published

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Import editor assignments for each article. This info is contained in the event_history.tsv file.
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_history($importHome,$importParentDir,$unpublished) {
	
	$fileName = 'event_history.tsv';
	
	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	echo "\n**** IMPORTING $unpubString ARTICLE EDITOR ASSIGNMENTS ****\n";
	
	//
	// LOOP THROUGH JOURNALS
	//
	$editAssignmentsCreated = 0;
	$rolesCreated = 0;
	$statusesUpdated = 0;
	$dupCtr = 0;
	$n = 0;
	foreach ($importParentDir as $dir) {
		
		$journalPath = $dir[0];
		$journalId = $dir[2]; //FIXME look this up in the database rather than passing as parameter?

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
		//
		// GET CHIEF EDITOR ID FOR THIS JOURNAL 
		//	
		$chiefEditorId = 0;
		$chiefEditorEmail = $dir[3];
		$chiefEditorLastName = $dir[4];
		$chiefEditorFirstName = $dir[5];
		$chiefEditorMiddleName = $dir[6];
		$chiefEditorIdQuery = "SELECT user_id FROM users WHERE username = '$chiefEditorEmail'";
		$chiefEditorIdResult = mysql_query($chiefEditorIdQuery);
		//echo "\nchiefEditorIdQuery: $chiefEditorIdQuery\n";
		if($chiefEditorIdResult === FALSE) {
			die("\nInvalid query: " . mysql_error() . "\nchiefEditorIdQuery: $chiefEditorIdQuery");
		} else {
			if(!$chiefEditorIdResult) {
				echo "\nuser with email $chiefEditorEmail doesn't exist in users table. Creating.\n";
				$editorInsertQuery = "INSERT INTO users username, first_name, middle_name, last_name, email) ";
				$editorInsertQuery = "VALUES ('$chiefEditorEmail', '$chiefEditorFirstName', '$chiefEditorMiddleName', '$chiefEditorLastName', '$chiefEditorEmail')";
				echo "\neditorInsertQuery: $editorInsertQuery\n";
				$editorInsertResult = mysql_query($editorInsertQuery);
				if(!$editorInsertResult) {
					die("\nInvalid query: " . mysql_error() . "\nneditorInsertQuery: $neditorInsertQuery\n");
				} else {
					$chiefEditorId = mysql_insert_id($editorInsertResult);
				}
			} else {
				$chiefEditorId = mysql_result($chiefEditorIdResult, 0);
			}
		}
		if($chiefEditorId == 0) {
			die("ERROR: Could not set chiefEditorId for Journal ID $dir[2]!");
		}
		//echo "\nchiefEditorId: $chiefEditorId\n";
		
		//OLD: get all of the editors for this journal as assigned in the DB
		/********
		$editorIds = array();
		$journalEditorsQuery = "SELECT roles.user_id, users.email FROM roles, users WHERE roles.journal_id = $journalId AND roles.role_id = 256 AND roles.user_id = users.user_id";
		$journalEditorsResult = mysql_query($journalEditorsQuery);
		if(!$journalEditorsResult) {
			die("\nInvalid query: " . mysql_error() . "\journalEditorsQuery: $journalEditorsQuery");
		} else {
			$edUserId = 0;
			$edEmail = '';
			while($editorRecord = mysql_fetch_array($journalEditorsResult)) {
			
				$edUserId = $editorRecord[0];
				$edEmail = $editorRecord[1];
				//echo "\nedUserId: $edUserId\n";
				//echo "\nedEmail: $edEmail\n";
				
				if($edUserId != 0 && $edUserId > 15 && substr($edEmail,-11) != 'bepress.com') {
					$editorIds[] = $edUserId;
				}
			}			
		}
		
		echo "\nEditors for journal $journalPath\n";
		print_r($editorIds);
		die();
		********/
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// GET ARRAY OF ARTICLE DIRS
		//
		// ucla_cjs/kheshbn/
		// vol80-81/iss1/art1
		// 
		// anrcs/californiaagriculture/
		// v1/n1/p1
		// 
		// italian_ucla/carteitaliane/
		// Series1/Vol1/1-1
		//
		// international/asc/ufahamu/
		// 35/1/1

		$parentDirs = array();
		if($unpublished == 1) {
			$grandparentPath = $importHome . $journalPath . 'unpublished/';	
			$parentDirs = scandir($grandparentPath); //array of directories
			$eschol_articleid_begin = $dir[1];
		} else {
			$grandparentPath = $importHome;
			$vols = scandir($importHome . $journalPath);
			$volDir = '';
			foreach($vols as $vol) {
				//if(substr($vol,0,3) == 'vol') {
				if(substr($vol,0,1) == 'v' OR substr($vol,0,6) == 'Series' OR is_int($vol)) {
					//echo "\nvol: $vol\n";
					$volDir = $importHome . $journalPath . $vol . '/';
					$issues = scandir($volDir);
					$issueDir = '';
					foreach($issues as $issue) {
						//if(substr($issue,0,3) == 'iss') {
						//if(substr($issue,0,3) == 'iss' OR substr($issue,0,1) == 'n' OR is_int($issue)) {
						if(substr($issue,0,3) == 'iss' OR substr($issue,0,1) == 'n' OR is_int($issue) OR substr($issue,0,3) == 'Vol') {
							//echo "\nissue: $issue\n";
							$issueDir = $volDir . $issue . '/';
							$arts = scandir($issueDir);
							foreach($arts as $art) {
								//if(substr($art,0,3) == 'art') {
								//if(substr($art,0,3) == 'art' OR substr($art,0,1) == 'p' OR is_int($art)) {
								if(substr($art,0,1) != '.' && $art != 'settings.tsv' && $art != 'templates') {
									//echo "\nart: $art\n";
									$artKey++;
									$parentDirs[$artKey] = $issueDir . $art . '/';
									$parentDirs[$artKey] = $journalPath . $vol . '/' . $issue . '/' . $art;
									//echo "\nparentDirs[$artKey]: $parentDirs[$artKey]\n";
								}
							}
						}
						
					}
				}
			}
		}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
		//
		// LOOP THROUGH ARTICLE DIRS
		//
		$article_settings_exists = 1;
		foreach($parentDirs as $dir) {
			
			if(substr($dir,0,1) != '.') {
				
				//set full file path
				if($unpublished) {
					$currImportFile = $importHome . $journalPath . 'unpublished/' . $dir . '/' . $fileName;
					$eschol_articleid = $eschol_articleid_begin . '_' . $dir;	
				} else {
					$currImportFile = $grandparentPath . $dir . '/' . $fileName;
				}
				
				//echo "\ncurrImportFile: $currImportFile\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				//
				//READ HISTORY FILE
				//
				$row = 1;
				$numRows = 0;				
				if (file_exists($currImportFile)) {
					//open file
					$handle = fopen($currImportFile, "r");
					
					//clear variables
					$article_id = 0;				
					$article_settings_exists = 1;
					$status_id = 1; //default 1, 'queued'
					$numRows = count(file($currImportFile));
					
					//echo "\n**** $journalPath$dir **** \n";
					//echo "\neschol_articleid: $eschol_articleid\n";
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
					//FOR EACH ROW IN THE HISTORY FILE
					while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
						
						if($article_settings_exists==0) continue;
						
						$user_id = 0;
						$date_logged = 'NULL';
						$origMessage = '';
						$message = 'NULL';
						$adminEmail = 'NULL';
						$forEmail = '';
						$editorEmail = '';
						$editorId = 0;
						
						$num = count($data);
						
						if($row > 1) {
							
							//GET DATA VALUES
							$date_logged = $data[0];
							$origMessage = $data[1];
							$message = "[bp event history] " . $data[1];
							$message = mysql_real_escape_string($message);
							$adminEmail = $data[5];
							$forEmail = $data[9];
							$forLName = $data[6];
							$forFName = $data[7];

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
							//
							//QUERY DB FOR article_id
							//
							$article_link = '';
							if($row==2) {
								if($unpublished) {
									$articleid_query = 'SELECT article_id FROM article_settings ';
									$article_link = $eschol_articleid;
									$articleid_query .= "WHERE setting_name = 'eschol_articleid' AND setting_value = '$article_link'";
								} else {
									$articleid_query = 'SELECT article_id FROM article_settings ';
									$article_link = $dir;
									$articleid_query .= "WHERE setting_name = 'eschol_submission_path' AND setting_value LIKE '%$article_link%'";
								}

								//echo "\narticleid_query: $articleid_query\n";
								$result = 0;
								$result = mysql_query($articleid_query);
								if(!$result) {
									die("\nInvalid query: " . mysql_error() . "\narticleid_query: $articleid_query");
								} 
								
								if (mysql_num_rows($result)==0) {
									echo "\n**ALERT** No article_settings record exists with eschol article ID or eschol_submission_path = '$article_link'.\nQuery: $articleid_query\nNo history created for this article.\n";
									$article_settings_exists = 0;
									continue;
								}
								
								$article_id = mysql_result($result,0);								
								//echo "\narticle_id: $article_id\n";
							}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
							//
							// ASSIGN EDITOR IN DB
							//				
		
							if(substr($origMessage,0,15) == 'Editor assigned') {
								
								if($forEmail != '') {
									$editorEmail = $forEmail;
								} elseif ($adminEmail != '') {
									$editorEmail = $adminEmail;
								}
								//echo "\norigMessage: $origMessage\nadminEmail: $adminEmail\nforEmail: $forEmail\neditorEmail: $editorEmail\n";
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
								//
								// QUERY DB FOR user_id
								//
									
								if($editorEmail != '') {
									if($editorEmail == 'bepress_editor@bepress.com') $editorEmail = 'help@escholarship.org';
									
									$userid_query = 'SELECT user_id FROM users ';
									$userid_query .= "WHERE email = '$editorEmail'";
									
									//echo "\nuserid_query: $userid_query\n";
									unset($userid_query_result);
									
									$userid_query_result = mysql_query($userid_query);
									
									if(!$userid_query_result) {
										die("\nInvalid query: " . mysql_error() . "\nuserid_query_result: $userid_query_result");
									} 
									
									$num_userid_rows = mysql_num_rows($userid_query_result);
									if ($num_userid_rows > 0) {
										$editorId = mysql_result($userid_query_result, 0);
										//echo "editorId after userid_query: $editorId\n";
									} else {
										echo "\nALERT: No users record found for email: $editorEmail in file $currImportFile on line $row. OJS article ID: $article_id\nWill assign chief editor if no other editor assignments in event history.";
									}
								} else {
									echo "\nALERT: No email for editor assignment in file $currImportFile on line $row. OJS article ID: $article_id\n\nWill assign chief editor if no other editor assignments in event history.";
								}
							}
						}

						if($editorId) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// CHECK FOR DUPLICATES BEFORE CREATING edit_assignments RECORD
							//
							$dupEditAssignCheck = "SELECT * FROM edit_assignments ";
							$dupEditAssignCheck .= "WHERE article_id = $article_id AND editor_id = $editorId";
							//echo "\ndupEditAssignCheck: $dupEditAssignCheck\n";
							
							$dupEditAssignResult = mysql_query($dupEditAssignCheck);
							if($dupEditAssignResult === FALSE) {
								die("\nInvalid query: " . mysql_error() . "\ndupEditAssignCheck: $dupEditAssignCheck\n");
							}
							if(mysql_num_rows($dupEditAssignResult) > 0) {
								$dupCtr++;
							} else {

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
								//
								// CREATE edit_assignments RECORD, UPDATE IF ONE ALREADY EXISTS FOR THIS ARTICLE
								//
								$existingEditId = 0;
								$editAssignmentsQuery = '';
								$editAssignCheckQuery = "SELECT edit_id FROM edit_assignments WHERE article_id = $article_id";
								
								$editAssignmentsCheckResult = mysql_query($editAssignCheckQuery);
								if(!$editAssignmentsCheckResult) {
									die("\nInvalid query: " . mysql_error() . "\neditAssignCheckQuery: $editAssignCheckQuery");
								} else {
									if(mysql_num_rows($editAssignmentsCheckResult) > 0) {
										$existingEditRow = mysql_fetch_array($editAssignmentsCheckResult);
										$existingEditId = $existingEditRow[0];
										$editAssignmentsQuery = "UPDATE edit_assignments SET editor_id = $editorId WHERE edit_id = $existingEditId";
										//echo "\neditAssignCheckQuery: $editAssignCheckQuery\n";
										//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";								
									} else {
										$editAssignmentsQuery = "INSERT INTO edit_assignments ";
										$editAssignmentsQuery .= "(article_id, editor_id, can_edit, can_review, date_notified, date_underway)";
										$editAssignmentsQuery .= " VALUES ($article_id, $editorId, 1, 1, '$date_logged', '$date_logged')";
									}
								}

								//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";
								$result = mysql_query($editAssignmentsQuery);
								if(!$result) {
									die("\nInvalid query: " . mysql_error() . "\neditAssignmentsQuery: $editAssignmentsQuery");
								} else {
									$editAssignmentsCreated++;
								}
							}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// CHECK FOR DUPLICATES BEFORE CREATING roles RECORD
							//
							$dup_check_stmt = "SELECT * FROM roles ";
							$dup_check_stmt .= "WHERE journal_id = $journalId AND user_id = $editorId AND role_id = 256";
							
							//echo "\ndup_check_stmt: $dup_check_stmt\n";
							
							$dup_check_result = mysql_query($dup_check_stmt);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\ndup_check_result: $dup_check_result");
							}
							if(mysql_num_rows($dup_check_result) > 0) {
								$dupCtr++;
							} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
								//
								// CREATE roles RECORD
								//
								$rolesQuery = "INSERT INTO roles ";
								$rolesQuery .= "(journal_id, user_id, role_id)";
								$rolesQuery .= " VALUES ($journalId, $editorId, 256)";
								
								//echo "\nrolesQuery: $rolesQuery\n";
								$result = mysql_query($rolesQuery);
								if(!$result) {
									die("\nInvalid query: " . mysql_error() . "\nrolesQuery: $rolesQuery");
								} else {
									$rolesCreated++;
								}	
							}
						}
						
						$row++;
					} //end if($row > 1)
				} //end while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE)
				
				fclose($handle);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				//
				// IF ARTICLE STILL DOESN'T HAVE AN EDITOR, ASSIGN THIS JOURNAL'S 1st EDITOR BY DEFAULT
				//
				$editAssignmentCheck = "SELECT * FROM edit_assignments WHERE article_id = $article_id";
				$editAssignmentCheckResult = mysql_query($editAssignmentCheck);
				if($editAssignmentCheckResult === FALSE) {
					die("\nInvalid query: " . mysql_error() . "\neditAssignmentCheck: $editAssignmentCheck\n");
				} else {
					if(!mysql_num_rows($editAssignmentCheckResult)) {
						$editorId = $editorIds[0];
						$editAssignmentsQuery = "INSERT INTO edit_assignments ";
						$editAssignmentsQuery .= "(article_id, editor_id, can_edit, can_review, date_notified, date_underway)";
						$editAssignmentsQuery .= " VALUES ($article_id, $chiefEditorId, 1, 1, now(), now())";
						
						//echo "\neditAssignmentsQuery: $editAssignmentsQuery\n";
						
						$editAssignmentsResult = mysql_query($editAssignmentsQuery);
						if(!$editAssignmentsResult) {
							die("\nInvalid query: " . mysql_error() . "\neditAssignmentsQuery: $editAssignmentsQuery\n");
						} else {
							$editAssignmentsCreated++;
						}	
					}
				}
			} //end if($dir != '.' && $dir != '..')
		} //end foreach($parentDirs as $dir)
	}
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of editor assignments that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	}
	
	echo "\nEdit Assignments records created: $editAssignmentsCreated\n";
	echo "\nRoles records created: $rolesCreated\n";
	echo "\nIMPORT OF EDITOR ASSIGNMENTS FOR $unpubString ARTICLES FINISHED.\n\n";
}

mysql_close($conn);
	
?>
