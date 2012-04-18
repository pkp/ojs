#!/usr/bin/php
<?php

require './import_set_parameters.php';
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import of editor decision files for published and unpublished articles
//
import_editor_decisions($importHome,$importParentDir,1,$baseUploadDir); //unpublished
import_editor_decisions($importHome,$importParentDir,0,$baseUploadDir); //published

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// CLOSE DB CONN
//
mysql_close($conn);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Function to import editor decision statuses and editor decisions emails/comments
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_editor_decisions($importHome,$importParentDir,$unpublished,$baseUploadDir) {

	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	
	echo "\n**** IMPORTING DECISIONS FOR $unpubString ARTICLES ***\n";
		
	//
	// LOOP THROUGH JOURNALS
	//
	$editDecisionsCreated = 0;
	$commentsCreated = 0;
	$decisionFilesUploaded = 0;
	$dupCtr = 0;
	$n = 0;
	foreach ($importParentDir as $dir) {
				
		$journalPath = $dir[0];
		$journalId = $dir[2]; //FIXME look this up in the database rather than passing as parameter
		
		//
		// GET ARRAY OF ARTICLE DIRS
		//
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

				$eschol_articleid = $eschol_articleid_begin . '_' . $dir;							
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//set full dir path
				//
				if($unpublished) {	
					$fullPath = $importHome . $journalPath . 'unpublished/' . $dir . '/';
				} else {
					$fullPath = $grandparentPath . $dir . '/';
				}
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				//
				// QUERY DB FOR article_id
				//
				$article_id = 0;
				$article_link = '';
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
				$articleIdResult = 0;
				$articleIdResult = mysql_query($articleid_query);
				if(!$articleIdResult) {
					die("\nInvalid query: " . mysql_error() . "\n");
				} 
				
				if (mysql_num_rows($articleIdResult)==0) {
					echo "\n**ERROR** No article_settings record exists with setting_name 'title' for this article.\nQuery: $articleid_query\n";
					continue;
				} else {
					$article_id = mysql_result($articleIdResult,0);	
				}
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				// 
				// QUERY DB FOR Author ID
				//
				$authorId = 0;
				$authorIdQuery = "SELECT user_id from articles WHERE article_id = $article_id";
				$authorIdResult = 0;
				$authorIdResult = mysql_query($authorIdQuery);
				if(!$authorIdResult) {
					die("\nInvalid query: " . mysql_error() . "\n");
				}
				if (mysql_num_rows($authorIdResult)==0) {
					die("\n**ERROR** No article_settings record exists with setting_name 'title' for this article.\nQuery: $authorIdQuery\n");
				} else {
					$authorId = mysql_result($authorIdResult,0);
				}		
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				//
				// IMPORT EDITOR DECISIONS AND BUILD ARRAY OF EDITOR DECISIONS FROM ARTICLE EVENT LOG
				//
				$editorId = 0;
				$dateLogged = ''; 
				$decisionNum = 0;
				$eventHistoryDecisions = array();
				$eventHistoryCtr = 0;
				
				$articleEventLogQuery = "SELECT * FROM article_event_log WHERE article_id = $article_id";
				//$articleEventLogQuery .= " AND (message LIKE '%Minor revisions required')";
				$articleEventLogQuery .= " AND (message LIKE '%Accepted' OR message LIKE '%Accepted with a request for minor revisions' OR message LIKE '%Major revisions required' OR message LIKE '%Minor revisions required' OR message LIKE '%Rejected')";
				//echo "\n$articleEventLogQuery\n";
				$articleEventLogResult = mysql_query($articleEventLogQuery);
				if(!$articleEventLogResult) {
					die("\nInvalid query: " . mysql_error() . "\n");
				} else {
					while($articleEventLogRow = mysql_fetch_object($articleEventLogResult)) {
						$decisionNum++;
						
						$eventLogId = $articleEventLogRow->log_id;
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
						//
						// GET EDITOR ID 
						//
						$editorId = $articleEventLogRow->user_id;
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
						//
						// GET DATE LOGGED
						//
						$dateLogged = $articleEventLogRow->date_logged;
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
						//
						// GET DECISION TYPE ID
						//
						$message = $articleEventLogRow->message;
						$round = 1;
						
						switch($message) {
							case '[bp event history] Accepted':
								$decisionId = 1; //1 = Accept Submission
								break;
							case '[bp event history] Accepted with a request for minor revisions':
								$decisionId = 2; //2 = Revisions Required
								break;
							case '[bp event history] Major revisions required':
								$decisionId = 3; //3 = resubmit for review
								break;
							case '[bp event history] Minor revisions required':
								$decisionId = 2; 
								break;								
							case '[bp event history] Rejected':
								$decisionId = 4; //4 = decline submission
								break;	
							default:
								$decisionId = 0;
						}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// GET EDITOR EMAIL ADDRESS FROM USERS TABLE
						//						
						$editorEmail = ''; //get from users table
						$editorEmailQuery = "SELECT * FROM users WHERE user_id = $editorId";
						$editorEmailResult = 0;
						$editorEmailResult = mysql_query($editorEmailQuery);
						if(!$editorEmailResult) {
							die("\nInvalid query: " . mysql_error() . "\n");
						} else {
							while($usersRow = mysql_fetch_object($editorEmailResult)) {
								$currEditorName = $usersRow->first_name . " " . $usersRow->last_name;
								$currEditorEmail = $usersRow->email;
								$editorEmail = '"' . $currEditorName . '" <' . $currEditorEmail . '>';
							}
						}
						if($editorEmail == '') {
							die("\nCould not get email address for user_id $editorId\n");
						}	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// BUILD ARRAY OF DATA FOR EACH EVENT FOR USE LATER IN IMPORTING DECISION TEXTS
						//
						$eventHistoryDecisions[] = array('decisionNum' => $decisionNum, 'eventLogId' => $eventLogId, 'editorId' => $editorId, 'editorEmail' => $editorEmail, 'dateLogged' => $dateLogged, 'decisionId' => $decisionId);
						$eventHistoryCtr++;
						//echo "\narticle_id: $article_id\neventHistoryCtr: $eventHistoryCtr\n";

						if($article_id && $round && $editorId && $decisionId && $dateLogged) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
							//
							// CHECK FOR DUPLICATES BEFORE IMPORTING
							//
							
							$editDecisionsDupStmt = "SELECT * FROM edit_decisions ";
							$editDecisionsDupStmt .= "WHERE article_id = $article_id AND round = $round AND editor_id = $editorId AND decision = $decisionId AND date_decided = '$dateLogged'";
							//echo "\ndup_check_stmt: $editDecisionsDupStmt\n";
							
							$editDecisionsDupResult = mysql_query($editDecisionsDupStmt);
							if(!$editDecisionsDupResult) {
								die("\nInvalid query: " . mysql_error() . "\nQuery: $editDecisionsDupStmt\n");
							}
							if(mysql_num_rows($editDecisionsDupResult) > 0) {
								$dupCtr++;
							} else {						
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
								//
								// IMPORT DECISIONS INTO edit_decisions table
								//						
								$editDecisionsQuery = "INSERT INTO edit_decisions ";
								$editDecisionsQuery .= "(article_id, round, editor_id, decision, date_decided)";
								$editDecisionsQuery .= " VALUES ($article_id, $round, $editorId, $decisionId, '$dateLogged')";
								//echo "\neditDecisionsQuery: $editDecisionsQuery\n";
								
								$edit_decisions_result = mysql_query($editDecisionsQuery);
								if(!$edit_decisions_result) {
									die("\nInvalid query: " . mysql_error() . "\n");
								} else {
									$editDecisionsCreated++;
								}
							}
						}
					}
				}
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
				//
				// GET LIST OF FILENAMES FOR EACH ARTICLE
				//
				$fileListing = scandir($grandparentPath . $dir);
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// GET ARRAY OF DECISION MESSAGE DATA
				//	

				$decisionTexts = array();
				$decisionTextCtr = 0;
				foreach($fileListing as $currImportFile) {
					$fileName = '';
					$filePath = '';
					$fileType = '';
					$fileLastModified = '';
					
					if(substr($currImportFile,0,9)=="decision-") {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// GET FILE NAME
						//	
						$fileName = $currImportFile;
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// GET FILE PATH
						//	
						$filePath = $grandparentPath . $dir . '/' . $fileName;
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// GET DECISION NUMBER
						//					
						$first_dot_pos = strpos($fileName,".",8); //10
						if($first_dot_pos == FALSE) {
							continue;
						}
						$decisionNum = substr($fileName,9,($first_dot_pos - 9));
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// GET FILE TYPE
						//						
						$fileType = substr($fileName,($first_dot_pos + 1));

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// GET FILE LAST MODIFIED DATE
						//						
						date_default_timezone_set('America/Los_Angeles');
						$fileLastModified = date('Y-m-d H:i:s',filemtime($filePath));

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// GET TEXT OF REVIEW IF TXT FILE
						//
						$message = '';
						if ($fileType == 'txt') {
							if(file_exists($filePath)) {
								//open file
								$handle = fopen($filePath, "r");
								//echo "\nOpened handle to $filePath successfully\n";
								$message = file_get_contents($filePath);
								//echo "\nreviewText: $message\n";
								fclose($handle);
								
								if($message == '') {
									echo "\n**ALERT** Txt file $filePath was empty.\n";
								}									
							} else {
								die("**ERROR** Txt file $filePath does not exist.\n");
							}
						} 						
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// BUILD ARRAY
						//			
						$decisionTexts[] = array('fileName' => $fileName, 'decisionNum' => $decisionNum, 'message' => $message, 'fileLastModified' => $fileLastModified);						
						$decisionTextCtr++;
					}					
				}
				
				//echo "\narticle_id: $article_id\neventHistoryCtr: $eventHistoryCtr\n";
				//echo "Num Event HistoryeventHistoryDecisions:";
				//print_r($eventHistoryDecisions);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
				//
				// IMPORT TEXT OF DECISIONS INTO EMAIL LOG
				//	
				foreach($decisionTexts as $decisionText) {
					$decisionNum = $decisionText['decisionNum'];
					$message = $decisionText['message'];
					$message = mysql_real_escape_string($message);
					$fileLastModified = $decisionText['fileLastModified'];
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
					//
					// GET EDITOR ID, EDITOR EMAIL, DATE LOGGED
					//	
					$editorId = 0;
					$dateLogged = '';
					$editorEmail = '';
					if($eventHistoryCtr == $decisionTextCtr) {
						$eventHistoryData = $eventHistoryDecisions[$decisionNum];
						//print_r($eventHistoryData);
						$editorId = $eventHistoryData['editorId'];
						$dateLogged = $eventHistoryData['dateLogged'];
						$editorEmail = $eventHistoryData['editorEmail'];
					} else {
						$dateLogged = $fileLastModified;
						//import these comments without certain pieces of data?
						echo "\nALERT: number of decision event histories != number of decision texts for:";
						echo "\narticleEventLogQuery: $articleEventLogQuery\n";
						echo "\narticleId: $article_id\n";
						echo "directory: $grandparentPath$dir\n";
						echo "eventHistoryCtr: $eventHistoryCtr\n";
						echo "decisionTextCtr: $decisionTextCtr\n";
						echo "Importing these decision texts into article_email_log without sender_id, date_sent, from_address\n";
						echo "eventHistoryDecision:\n ";
						print_r($eventHistoryDecisions);
						//echo "\ndecisionTexts:\n";
						//print_r($decisionTexts);						
					}
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
					//
					// SET SUBJECT
					//						
					$subject = '[bp editor decision msg]';
					$titleQuery = "SELECT * FROM article_settings where article_id = $article_id AND setting_name = 'title'";
					$titleResult = mysql_query($titleQuery);
					if(!$titleResult) {
						die("\nInvalid query: " . mysql_error() . "\n");
					} else {
						while($articleSettingsRow = mysql_fetch_object($titleResult)) {
							$title = $articleSettingsRow->setting_value;
						}
					}
					if(trim($title) != '') {
						$subject .= " $title";
					}
					$subject = mysql_real_escape_string($subject);
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
					//
					// GET AUTHOR EMAIL ADDRESS(ES) FROM AUTHORS TABLE
					//
					$authorEmails = '';
					$numAuthors = 0;
					$authorEmailQuery = "SELECT * FROM authors WHERE submission_id = $article_id AND email != ''";
					$authorEmailResult = mysql_query($authorEmailQuery);
					if(!$authorEmailResult) {
						die("\nInvalid query: " . mysql_error() . "\n");
					} else {
						while($authorsRow = mysql_fetch_object($authorEmailResult)) {
							$numAuthors++;
							$currAuthorName = $authorsRow->first_name . " " . $authorsRow->last_name;
							$currAuthorEmail = $authorsRow->email;
							if($numAuthors > 1) {
								$authorEmails .= ', ';
							}
							$authorEmails .= '"' . $currAuthorName . '" <' . $currAuthorEmail . '>';
						}
					}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// CHECK FOR DUPLICATES BEFORE CREATING article_email_log RECORD
					//
					$articleEmailLogDupStmt = "SELECT * FROM article_email_log ";
					if($dateLogged != '') {
						$articleEmailLogDupStmt .= "WHERE article_id = $article_id AND date_sent = '$dateLogged'";
					} else {
						$articleEmailLogDupStmt .= "WHERE article_id = $article_id AND (date_sent = '0000-00-00 00:00:00' OR date_sent IS NULL)";
					}
					//echo "\ndup_check_stmt: $articleEmailLogDupStmt\n";
					
					$articleEmailLogDupResult = mysql_query($articleEmailLogDupStmt);
					if(!$articleEmailLogDupResult) {
						die("\nInvalid query: " . mysql_error() . "\narticleEmailLogDupStmt: $articleEmailLogDupStmt\n");
					}
					if(mysql_num_rows($articleEmailLogDupResult) > 0) {
						$dupCtr++;
					} else {

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
						//
						// CREATE RECORD IN DATABASE
						//						
						$articleEmailLogQuery = "INSERT INTO article_email_log ";
						$articleEmailLogQuery .= "(article_id, sender_id, date_sent, from_address, recipients, subject, body)";
						$articleEmailLogQuery .= " VALUES ($article_id, $editorId, ";
						if($dateLogged != '') {
							$articleEmailLogQuery .= "'$dateLogged', ";
						} else {
							$articleEmailLogQuery .= "NULL, ";
						}
						$articleEmailLogQuery .= "'" . mysql_real_escape_string($editorEmail) . "', '" . mysql_real_escape_string($authorEmails) . "', '$subject', '$message')"; 
						//echo "\narticleEmailLogQuery: $articleEmailLogQuery\n";
						$articleEmailInsertResult = mysql_query($articleEmailLogQuery);
						if(!$articleEmailInsertResult) {
							die("\nInvalid query: " . mysql_error() . "\narticleEmailLogQuery: $articleEmailLogQuery\n");
						} else {
							$decisionFilesUploaded++;
						}
					}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// CHECK FOR DUPLICATES BEFORE CREATING article_comments RECORD
					// apparently we have to create records both in the email log and in the comments table!!
					$commentDupQuery = "SELECT * FROM article_comments where comment_type = 2 AND role_id = 256 AND article_id = $article_id AND assoc_id = $article_id";
					if($dateLogged != '') {
						$commentDupQuery .= " AND date_posted = '$dateLogged'";
					} else {
						$commentDupQuery .= " AND (date_posted = '0000-00-00 00:00:00' OR date_posted IS NULL)";
					}
					//echo "\ncommentDupQuery: $commentDupQuery\n";
					$dupCheckResult = mysql_query($commentDupQuery);
					if(!$dupCheckResult) {
						die("\nInvalid query: " . mysql_error() . "\ncommentDupQuery: $commentDupQuery\n");
					}
					if(mysql_num_rows($dupCheckResult) > 0) {
						$dupCtr++;
					} else {							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						//IMPORT TXT FILES INTO article_comments
						//
						$articleCommentsQuery = "INSERT INTO article_comments (comment_type, role_id, article_id, assoc_id, author_id, date_posted, comment_title, comments, viewable) ";
						$articleCommentsQuery .= "VALUES (2, 256, $article_id, $article_id, $authorId, ";
						if($dateLogged != '') {
							$articleCommentsQuery .= "'$dateLogged'";
						} else {
							$articleCommentsQuery .= "NULL";
						}
						$articleCommentsQuery .= ", '$subject', '$message', 1)";
						//echo "\narticleCommentsQuery: $articleCommentsQuery\n";
						$articleCommentsInsertResult = mysql_query($articleCommentsQuery);
						if(!$articleCommentsInsertResult) {
							die("\nInvalid query: " . mysql_error() . "\narticleCommentsQuery: $articleCommentsQuery\n");
						} else {
							$commentsCreated++;
						}
					}
				} //end foreach($decisionTexts as $decisionText)	
			}//end if(substr($dir,0,1) != '.')
		}//end foreach($parentDirs as $dir)
	} //end foreach ($importParentDir as $dir)
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of records that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	}
	
	echo "\nedit_decisions records created: $editDecisionsCreated\n";
	echo "\nDecision texts imported into email log: $decisionFilesUploaded\n";
	echo "\nDecision texts imported into article_comments: $commentsCreated\n";
	echo "\nIMPORT OF EDITOR DECISIONS FOR $unpubString ARTICLES FINISHED.\n\n";	
	
} //end function import_reviews
	
?>