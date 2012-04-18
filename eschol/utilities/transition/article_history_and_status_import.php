#!/usr/bin/php
<?php

require './import_set_parameters.php';
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_history($importHome,$importParentDir,1,"E"); //unpublished event history (including status)
import_history($importHome,$importParentDir,1,"R"); //unpublished review history
import_history($importHome,$importParentDir,0,"E"); //published event history
import_history($importHome,$importParentDir,0,"R"); //published review history


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Import event & review history. For unpublished articles, import current status.
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_history($importHome,$importParentDir,$unpublished,$historyType) {

	if($historyType == "R") {
		$fileName = 'revision_history.tsv';
	} elseif($historyType == "E") {
		$fileName = 'event_history.tsv';
	}
	
	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	$historyTypeString = ($historyType == "E" ? "EVENT" : "REVISION");
	echo "\n**** IMPORTING $unpubString $historyTypeString HISTORY ****\n";
	
	//
	// LOOP THROUGH JOURNALS
	//
	$recsCreated = 0;
	$statusesUpdated = 0;
	$dupCtr = 0;
	$n = 0;
	foreach ($importParentDir as $dir) {
		
		$journalPath = $dir[0];
	
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
						$num = count($data);
						
						if($row > 1) {
							
							//GET DATA VALUES
							if($historyType == "E") {
								$date_logged = $data[0];
								$origMessage = $data[1];
								$message = "[bp event history] " . $data[1];
								$message = mysql_real_escape_string($message);
								$adminEmail = $data[5];
								$forEmail = $data[9];
								$forLName = $data[6];
								$forFName = $data[7];
							} else if($historyType == "R") {
								$adminEmail = $data[0];
								$datestamp = $data[1];
								date_default_timezone_set('America/Los_Angeles');
								$date_logged = date('Y-m-d H:i:s',$datestamp);
								$origMessage = $data[2];
								$message = "[bp revision history] " . $data[2];	
								$message = mysql_real_escape_string($message);
							} else {
								die("\nERROR: historyType = $historyType. historyType must be 'E' or 'R'");
							}
							
							//CHECK FOR REQUIRED DATA VALUES
							if($adminEmail == '' or $adminEmail == 'NULL') {
								//echo "\nERROR: Admin Email not populated for $currImportFile Line: $row NumFields in Line: $num Not importing this line.\n";
								//continue;
								$adminEmail = 'help@escholarship.org'; //use help@escholarship.org by default.
							}
							if($origMessage == '' or $origMessage == 'NULL') {
								echo "\nERROR: Event Message not populated for $currImportFile Line: $row NumFields in Line: $num Not importing this line.\n";
								continue;
							}
							if($date_logged == '' or $date_logged == 'NULL') {
								echo "\nERROR: Event Message not populated for $currImportFile Line: $row NumFields in Line: $num Not importing this line.\n";
								continue;
							}
							
							//ADD RECIPIENT NAME & EMAIL TO EVENT MESSAGE
							if($forEmail != '') {
								$message .= " ($forLName $forFName, $forEmail)";
								//echo "\nMessage: $message\n";
							}

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
									die("\nInvalid query: " . mysql_error() . "\n");
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
							//QUERY DB FOR user_id
							//
							$userid_query = 'SELECT user_id FROM users ';
							$userid_query .= "WHERE email = '$adminEmail'";
							
							//echo "\nuserid_query: $userid_query\n";
							
							$userid_query_result = mysql_query($userid_query);
							
							if(!$userid_query_result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							} 
							
							$num_userid_rows = mysql_num_rows($userid_query_result); //not sure i need this
							if ($num_userid_rows > 0) {
								$user_id = mysql_result($userid_query_result, 0);
								//echo "\nuser_id after userid_query: $user_id\n";
							}							
							
							//echo "\nuser_id: $user_id\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
							//
							// QUERY DB FOR CURRENT ARTICLE STATUS
							//
							$currDbStatus = 0;
							if($unpublished && $historyType == "E") {
								$currStatusQuery = "SELECT status FROM articles WHERE article_id = $article_id";
								$currStatusResult = mysql_query($currStatusQuery);
								if(!$currStatusResult) {
									die("\nInvalid query: " . mysql_error() . "\ncurrStatusQuery: $currStatusQuery");
								} else {
									$currDbStatus = mysql_result($currStatusResult, 0);
								}
								//echo "article_id: $article_id\ncurrDbStatus: $currDbStatus\n";
							}
							
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
							//
							// UPDATE ARTICLE STATUS
							//
							if($unpublished && $historyType == "E") {
								if(trim($origMessage) == 'Rejected') {
									$status_id = 4; //declined
								} elseif(trim($origMessage) == 'Withdrawn') {
									$status_id = 0; //archived
								} elseif(trim($origMessage) == 'Reverted to pending') {
									$status_id = 1; //queued
								} else {
									if($currDbStatus != 4 && $currDbStatus != 0) {
										$status_id = 1; //queued
									}
								}
								
								$status_insert_stmt = "UPDATE articles SET status = $status_id WHERE article_id = $article_id";
								//echo "origMessage: $origMessage\nstatus_insert_stmt: $status_insert_stmt\n\n";
								$result = mysql_query($status_insert_stmt);
								if(!$result) {
									die("\nInvalid query: " . mysql_error() . "\n");
								}			
								$statusesUpdated++;
							}
		
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
							//CHECK FOR KEY DATA BEFORE RUNNING QUERY
							//
							if($article_id==0) {
								echo "\nERROR: No OJS article_id found. \nQuery: $articleid_query\nFile: $currImportFile, line: $row\nNot importing this line.\n";
								continue;
							}
							if($user_id==0) {
								//echo "\nALERT: No OJS article_id found for Admin Email: $adminEmail\nFile: $currImportFile, line: $row\nNot importing this line.\n";
								//continue;
								$user_id = 1; //use help@escholarship.org
							}	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// CHECK FOR DUPLICATES BEFORE CREATING RECORD
							//
							$dup_check_stmt = "SELECT * FROM article_event_log ";
							$dup_check_stmt .= "WHERE article_id = $article_id AND user_id = $user_id AND date_logged = '$date_logged' AND message LIKE '%" . mysql_real_escape_string($origMessage) . "%'";
							//echo "\ndup_check_stmt: $dup_check_stmt\n";
							
							$dup_check_result = mysql_query($dup_check_stmt);
							if(!$dup_check_result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							}							
							if(mysql_num_rows($dup_check_result) > 0) {
								$dupCtr++;
								continue;
							}	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							//INSERT RECORD INTO article_event_log
							//							
							$insert_stmt = 'INSERT INTO article_event_log ';
							$insert_stmt .= '(article_id, user_id, date_logged, log_level, event_type, assoc_type, assoc_id, message)';
							$insert_stmt .= " VALUES ($article_id, $user_id, '$date_logged', 'N', 0, 0, $user_id, '" . mysql_real_escape_string($message) . "')";
							
							//echo "\ndup_check_stmt: $dup_check_stmt\n";
							//echo "insert_stmt: $insert_stmt\n";
							
							$result = mysql_query($insert_stmt);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							} else {
								$recsCreated++;
							}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
						}
						$row++;

					}
					
					fclose($handle);
				}
			} //end if($dir != '.' && $dir != '..')
		} //end foreach($parentDirs as $dir)
	
	}
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of event histories that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	}
	
	echo "\nStatuses updated: $statusesUpdated\n";
	echo "\nRecords created: $recsCreated\n";
	echo "\nIMPORT OF $unpubString $historyTypeString HISTORY FINISHED.\n\n";
}

mysql_close($conn);
	
?>
