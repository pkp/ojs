#!/usr/bin/php
<?php

require_once './import_journals.php';
require_once './import_set_parameters.php';
require_once './ojs_db_connect.php';

ini_set("memory_limit","200M"); //increasing memory limit from default 128M

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_ednotes($importHome,$importParentDir,1); //unpublished
import_ednotes($importHome,$importParentDir,0); //published

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 
// Import ednotes.txt files from bepress data
//
function import_ednotes($importHome,$importParentDir,$unpublished) {

	$ednotesImported = 0;
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// print what we're doing
	//
	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	echo "\n**** IMPORTING $unpubString ED NOTES ****\n";
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	// get array of directories to walk for each journal
	//
	foreach($importParentDir as $currJournalInfo) {
		//echo "\ncurrJournalInfo:";
		//print_r($currJournalInfo);
		$journalPath = $currJournalInfo[0];
		$eschol_articleid_begin = $currJournalInfo[1];
		$journalId = $currJournalInfo[2];
		
		$journalDirs = array();
		$journalDirs = get_import_dirs($journalPath,$journalId,$importHome,$unpublished);
		
		//echo "\njournalDirs:\n";
		//print_r($journalDirs);
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//
		// walk the directories & do the import
		//	
		$emailLogsCreated = 0;
		$dupCtr = 0;
		$n = 0;
		$fileName = 'ednotes.txt';
		
		foreach($journalDirs as $dir) {	
			$eschol_articleid = '';
			if(substr($dir,0,1) != '.') {
				//set full file path				
				if($unpublished) {
					$currImportFile = $importHome . $journalPath . 'unpublished/' . $dir . '/' . $fileName;
					$eschol_articleid = $eschol_articleid_begin . '_' . $dir;	
				} else {
					$currImportFile = $importHome . $grandparentPath . $dir . '/' . $fileName;
				}	
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// get OJS article ID
				//
				
				$article_id = get_article_id($unpublished,$eschol_articleid,$dir);
				//echo "\nOJSarticleID: $article_id\n";
				if(!$article_id) echo "\nALERT: Could not determine article ID for file: $currImportFile\n";
				
				if(!$article_id) continue;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				//
				// PROCESS EDNOTES FILE
				//	
				$notesText = '';
				$dateUploaded = '';
				if (file_exists($currImportFile)) {

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//					
					// OPEN FILE
					//					
					$handle = fopen($currImportFile, "r");
					$numRows = count(file($currImportFile));
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//					
					// ITERATE THROUGH LINES IN FILE
					//
					$firstDelimiter = '===-:';
					$midDelimiter = ':==:';
					$finalDelimiter = ':-===';
					$notesArray = array();
					$noteId = 0;
					while (($data = fgets($handle)) !== FALSE) {
						$midDelimiterStart = 0;
						
						//sample line:
						//===-:1088331:==:1234495033:-===
						if(substr($data,0,5) == $firstDelimiter) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//					
							// ADD DATA FOR PREVIOUS NOTE
							//
							if($noteId > 0) {
								$notesArray[] = array('ojsUserId' => $ojsUserId,'ojsUserName' => $ojsUserName,'dateCreated' => $dateCreated,'notesText' => $notesText);
							}
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//					
							// START NEW NOTE
							//
							$noteId++;
							$notesText = '';
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//					
							// GET NOTE DATE
							//		
							$dateCreated = '';
							$midDelimiterStart = strpos($data,$midDelimiter);
							$timestampBegin = $midDelimiterStart + strlen($midDelimiter);
							$finalDelimiterStart = strpos($data,$finalDelimiter);
							$timestampLength = $finalDelimiterStart - $timestampBegin;
							$timestamp = substr($data,$timestampBegin,$timestampLength);
							date_default_timezone_set('America/Los_Angeles');
							$dateCreated = date('Y-m-d H:i:s',$timestamp);
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//					
							// GET BP USER ID
							//	
							$bpUserId = 0;
							$bpUserIdBegin = strlen($firstDelimiter);
							$bpUserIdLength = $midDelimiterStart - $bpUserIdBegin;
							$bpUserId = substr($data,$bpUserIdBegin,$bpUserIdLength);
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//					
							// GET OJS USER ID
							//	
							$ojsUserId = 0;
							$ojsUserIdQuery = "SELECT user_id FROM user_settings ";
							$ojsUserIdQuery .= "WHERE setting_name = 'eschol_bpid' AND setting_value = '$bpUserId'";
							$ojsUserIdResult = mysql_query($ojsUserIdQuery);
					
							if($ojsUserIdResult === FALSE) {
								die("\nInvalid query: " . mysql_error() . "\nojsUserIdQuery: $ojsUserIdQuery\n");
							} elseif (mysql_num_rows($ojsUserIdResult)==0) {
								//echo "\n**ALERT** No user_settings record exists for this eschol_bpid.\nQuery: $ojsUserIdQuery\n";
								$ojsUserId = 0;
							} else {
								$ojsUserId = mysql_result($ojsUserIdResult,0);	
							}
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//					
							// IF USER ID NOT FOUND, SET TO 1 (help@escholarship.org)
							//							
							if($ojsUserId == 0) {
								$ojsUserId = 1;
								$ojsUserName = 'Unknown';
							} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
								//					
								// GET OJS USER NAME
								$ojsUserName = '';
								$ojsUserNameQuery = "SELECT CONCAT_WS(' ',first_name,middle_name,last_name) FROM users WHERE user_id = $ojsUserId";
								//echo "\nojsUserNameQuery: $ojsUserNameQuery\n";
								$ojsUserNameResult = mysql_query($ojsUserNameQuery);
								if($ojsUserNameResult === FALSE) {
									die("\nInvalid query: " . mysql_error() . "\nojsUserNameQuery: $ojsUserNameQuery\n");
								} elseif (mysql_num_rows($ojsUserNameResult)==0) {
									echo "\n**ALERT** No users record exists for this ID.\nQuery: $ojsUserNameQuery\n";
									$ojsUserName = 0;
								} else {
									$ojsUserName = mysql_result($ojsUserNameResult,0);
									$ojsUserName = str_replace('  ',' ',$ojsUserName);
									//echo "\nojsUserName: $ojsUserName\n";
								}							
								
							}
						} else {
							$notesText .= $data;
						}

					} //end while (($data = fgets($handle)) !== FALSE)

					//echo "\nNotesArray:\n";
					//print_r($notesArray);
					
					
					if($notesText == '') {
						//echo "\n**ALERT** Txt file $currImportFile was empty.\n";
					}	
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					//  ITERATE THROUGH NOTES AND IMPORT TO DB
					//
					foreach($notesArray as $noteArray) {
						$ojsUserId = 0;
						$dateCreated = '';
						$title = '';
						$notesText = '';
						$ojsUserId = $noteArray['ojsUserId'];
						$ojsUserName = $noteArray['ojsUserName'];
						$dateCreated = $noteArray['dateCreated'];
						$notesText = $noteArray['notesText'];

						//if userId couldn't be determined, use help@escholarship.org
						if($ojsUserId == 0) $userId = 1;
						
						//if date created couldn't be determined, use now
						if($dateCreated == '') $dateCreated = date('Y-m-d H:i:s');
						
						//create title
						$title = "[bp ednote] $ojsUserName for #$article_id";
						
						//echo "\n---------------------------------------------------------\n";
						//echo "ojsUserId: $ojsUserId\ndateCreated: $dateCreated\nnotesText:\ntitle: $title\n$notesText\n";
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						//  CHECK FOR DUPLICATES BEFORE CREATING DB REC
						//	
						$ednotesDupQuery = "SELECT * FROM notes ";
						$ednotesDupQuery .= "WHERE assoc_type = 257 AND assoc_id = $article_id AND context_id = $journalId AND contents = '" . mysql_real_escape_string($notesText) . "'";
						
						//echo "\nednotesDupQuery: $ednotesDupQuery\n";
						
						$ednotesDupResult = mysql_query($ednotesDupQuery);
						if($ednotesDupResult === FALSE) {
							die("\nInvalid query: " . mysql_error() . "\nednotesDupQuery: $ednotesDupQuery\n");
						}
						
						if(mysql_num_rows($ednotesDupResult) > 0) {
							$dupCtr++;
						} else {
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//
							// 	CREATE article_email_log RECORD
							//					
							$insertEdnoteQuery = "INSERT INTO notes ";
							$insertEdnoteQuery .= "(assoc_type, assoc_id, user_id, date_created, date_modified, title, context_id, contents) ";
							$insertEdnoteQuery .= "VALUES (257, $article_id, $ojsUserId, '$dateCreated', '$dateCreated', '$title', $journalId, '" . mysql_real_escape_string($notesText) . "')";
							
							//echo "\ninsertEdnoteQuery: $insertEdnoteQuery\n";
						
							$insertEdnoteResult = mysql_query($insertEdnoteQuery);
							if(!$insertEdnoteResult) {
								die("\nInvalid query: " . mysql_error() . "\ninsertEdnoteQuery: $insertEdnoteQuery\n");
							} else {
								$ednotesImported++;
							}

						}

					} //end foreach($notesArray as $noteArray)
				}
			}
		}
	}
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of duplicates: $dupCtr\n"; 
	}
	
	echo "\narticle_email_log records created: $ednotesImported\n";
	echo "\nIMPORT OF ED NOTES FOR $unpubString ARTICLES FINISHED.\n\n";
}

mysql_close($conn);
?>