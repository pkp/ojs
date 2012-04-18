#!/usr/bin/php
<?php

require_once './import_journals.php';
require_once './import_set_parameters.php';
require_once './ojs_db_connect.php';
require_once 'Mail/Mbox.php';

ini_set("memory_limit","200M"); //increasing memory limit from default 128M

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_emails($importHome,$importParentDir,1); //unpublished
import_emails($importHome,$importParentDir,0); //published

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 
// Import historical emails from the 'mail_received' file in the bepress data.
//
function import_emails($importHome,$importParentDir,$unpublished) {

	$emailLogRecsCreated = 0;
	$emailsProcessed = 0;
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// print what we're doing
	//
	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	echo "\n**** IMPORTING $unpubString EMAILS ****\n";
	
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
		$fileName = 'mail_received';
		
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
				// READ MAIL FILE (mbox format)
				//			
				if (file_exists($currImportFile)) {
					//echo "\nfile exists: $currImportFile\n";
					
					$mbox = new Mail_Mbox($currImportFile);
					$mbox->open();
					$numMsgs = $mbox->size();
					//echo "\nnumMsgs for $currImportFile: $numMsgs\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					// ITERATE THROUGH MESSAGES
					//					
					for ($n = 0; $n < $mbox->size(); $n++) {
					
						$message = $mbox->get($n);
						//echo "-------------------------------------------------------------------------\n";
						//echo "message # $n of $numMsgs for $currImportFile:\n";
						

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						// 	EXPLODE MESSAGE INTO AN ARRAY
						//						
						$messageLines = explode("\n", $message);
						$numLines = count($messageLines);
						//echo "numLines: $numLines\n";
						$fromAddress = '';
						$dateSent = '';
						$ipAddress = '';
						$recipients = '';
						$ccRecipients = '';
						$bccRecipients = '';
						$subject = '';
						$body = '';						
						$num = 1;
						$isHeader = 1;

						foreach ($messageLines as $line) {

							if($isHeader && trim($line) == '') {
								//echo "Setting end of header at line num $num\n";
								$isHeader = 0;
							}
							
							//echo "\nline num: $num\nisHeader: $isHeader\nline: $line\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//
							// 	PROCESS HEADER
							//							
							if($isHeader) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
								//
								// 	PROCESS 1ST LINE
								//							
								if($num == 1 && substr($line,0,5) == 'From ') {
									//echo "\nprocessing line # $num\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
									//
									// 	GET FROM EMAIL
									//
									//From pkeilty@gmail.com Mon May  2  3:01:25 2011 GMT
									//012345678901234567890123456789012345678901234567890
									//0         1         2         3         4         5
									//echo "line: $line\n";
									$firstSpacePos = strpos($line,' ',5); //22
									$fromAddress = substr($line,5,$firstSpacePos - 5); //17
									//echo "senderEmail: $fromAddress\n";
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
									//
									// 	GET DATE SENT
									//	
									$dateSent = substr($line,$firstSpacePos+1);
									date_default_timezone_set('America/Los_Angeles');
									$dateSent = strtotime($dateSent);
									$dateSent = date('Y-m-d H:i:s',$dateSent);
									//echo "dateSent: $dateSent\n";
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
									//
									// 	GET SENDER ID
									//
									$senderId = 0;
									if($fromAddress != '') {
										$userid_query = 'SELECT user_id FROM users ';
										$userid_query .= "WHERE email = '$fromAddress'";
										
										//echo "\nuserid_query: $userid_query\n";
										
										$userid_query_result = mysql_query($userid_query);
										
										if(!$userid_query_result) {
											die("\nInvalid query: " . mysql_error() . "\nuserid_query: $userid_query\n");
										} 
										
										$num_userid_rows = mysql_num_rows($userid_query_result); //not sure i need this
										if ($num_userid_rows > 0) {
											$senderId = mysql_result($userid_query_result, 0);
											
										}
										//echo "senderId: $senderId\n";
									}
								} elseif ($num == 0 && !(substr($line,0,5) == 'From ')) {
									echo "\n\n-------------------------------------------------------------------------\n";
									echo "currImportFile: $currImportFile\n";
									echo "ALERT: first line of message does not start with 'From '!!\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
								//
								// 	GET RECIPIENTS
								//									
								} elseif(substr($line,0,3) == "To:") {
									$recipients = trim(substr($line,3));
									//echo "recipients: $recipients\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
								//
								// 	CC
								//
								} elseif(substr($line,0,3) == "Cc:") {	
									$ccRecipients = trim(substr($line,3));
									//echo "ccRecipients: $ccRecipients\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
								//
								// 	BCC
								//
								} elseif(substr($line,0,4) == "Bcc:") {	
									$bccRecipients = trim(substr($line,4));
									//echo "bccRecipients: $bccRecipients\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
								//
								// 	SUBJECT
								//
								} elseif(substr($line,0,8) == "Subject:") {
									$subject = "[bp email log] " . trim(substr($line,8));
									//echo "subject: $subject\n";
								}
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//
							// 	BODY
							//
							} else {
								$body .= $line . "\n";
							}					
							$num++;
						}
						//echo "\nbody: $body\n";
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						//  CHECK FOR DUPLICATES BEFORE CREATING DB REC
						//	
						$emailLogDupQuery = "SELECT * FROM article_email_log WHERE ";
						$emailLogDupQuery .= "article_id = $article_id ";
						$emailLogDupQuery .=  "AND date_sent = '$dateSent' ";
						$emailLogDupQuery .=  "AND from_address = '" . mysql_real_escape_string($fromAddress) . "' ";
						$emailLogDupQuery .=  "AND recipients = '" . mysql_real_escape_string($recipients) . "' ";
						$emailLogDupQuery .=  "AND subject = '" . mysql_real_escape_string($subject) . "' ";
						$emailLogDupQuery .=  "AND body = '" . mysql_real_escape_string($body) . "'";
						
						//echo "\nemailLogDupQuery: $emailLogDupQuery\n";
						
						$emailLogDupResult = mysql_query($emailLogDupQuery);
						if($emailLogDupResult === FALSE) {
							die("\nInvalid query: " . mysql_error() . "\nemailLogDupQuery: $emailLogDupQuery\n");
						}
						
						if(mysql_num_rows($emailLogDupResult) > 0) {
							$dupCtr++;
						} else {
						
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//
							// 	CREATE article_email_log RECORD
							//					
							$emailLogQuery = "INSERT INTO article_email_log (article_id, sender_id, date_sent, from_address, recipients, ";
							if($ccRecipients != '') $emailLogQuery .= "cc_recipients, ";
							if($bccRecipients != '') $emailLogQuery .= "bcc_recipients, ";
							$emailLogQuery .= "subject, body) ";
							$emailLogQuery .= "VALUES($article_id, $senderId, '$dateSent', '" . mysql_real_escape_string($fromAddress) . "', '" . mysql_real_escape_string($recipients) . "', ";
							if($ccRecipients != '') $emailLogQuery .= "'" . mysql_real_escape_string($ccRecipients) . "', "; 
							if($bccRecipients != '') $emailLogQuery .= "'" . mysql_real_escape_string($bccRecipients) . "', ";
							$emailLogQuery .= "'" . mysql_real_escape_string($subject) . "', '" . mysql_real_escape_string($body) . "')";
							//echo "\nemailLogQuery: $emailLogQuery\n";
							
							$emailLogResult = mysql_query($emailLogQuery);
							if(!$emailLogResult) {
								die("\nInvalid query: " . mysql_error() . "\nemailLogQuery: $emailLogQuery\n");
							} else {
								$emailLogRecsCreated++;
							}
							$emailsProcessed++;
						}
					}				
					
					$mbox->close();
				} else {
					//it's OK -- not all articles have a mail_received file
					//echo "\nfile doesn't exist!: $currImportFile\n";
				}
			}
		}
	}
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of article_email_log records that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	}

	echo "\nTotal number of emails processed: $emailsProcessed\n";	
	echo "\narticle_email_log records created: $emailLogRecsCreated\n";
	echo "\nIMPORT OF EMAILS FOR $unpubString ARTICLES FINISHED.\n\n";
}

mysql_close($conn);
?>