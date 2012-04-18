#!/usr/bin/php
<?php

require './import_set_parameters.php';
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_reviews_multiple($importHome,$importParentDir,1,$baseUploadDir); //unpublished
import_reviews_multiple($importHome,$importParentDir,0,$baseUploadDir); //published

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// CLOSE DB CONN
//
mysql_close($conn);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Function to import individual reviewer recommendations (reviews.tsv files from bepress)
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_reviews_multiple($importHome,$importParentDir,$unpublished,$baseUploadDir) {

	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	
	echo "\n**** IMPORTING REVIEWS FOR $unpubString ARTICLES ***\n";
		
	//
	// LOOP THROUGH JOURNALS
	//
	$reviewAssignmentsCtr = 0;
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
				
				//
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
				$result = 0;
				$result = mysql_query($articleid_query);
				if(!$result) {
					die("\nInvalid query: " . mysql_error() . "\n");
				} 
				
				if (mysql_num_rows($result)==0) {
					echo "\n**ALERT** No article_settings record exists for this article.\nQuery: $articleid_query\n";
					continue;
				} else {
					$article_id = mysql_result($result,0);	
				}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				//
				// LOOP THROUGH FILES AND ACT ON 'reviews.tsv'
				//
				$fileListing = scandir($grandparentPath . $dir);
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// GET ARRAY OF FILENAMES & FILE PATHS
				//	
				$fullFileListing = array();
				foreach($fileListing as $fileOrDir) {
					$fileName = '';
					$filePath = '';
					
					//we only want files named 'reviews.tsv'
					if($fileOrDir=='reviews.tsv') {		
						$fileName = $fileOrDir;
						$filePath = $grandparentPath . $dir . '/' . $fileName;
						//echo "\nfileName: $fileName\nfilePath: $filePath\n";
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						// READ IN REVIEWS FILE
						//
						$row = 1;
						$numRows = 0;				
						if (file_exists($filePath)) {
							//open file
							$handle = fopen($filePath, "r");
							$numRows = count(file($filePath));
							//echo "numRows: $numRows\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
							//
							//FOR EACH ROW IN THE REVIEWS FILE
							//
							while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {	
								if($row > 1) {								
									//GET DATA VALUES
									$reviewerEmail = $data[0];
									$reviewerBpUserId = $data[1];
									$reviewerLastName = $data[2];
									$reviewerFirstName = $data[3];
									$reviewerMName = $data[4];
									$timestamp = $data[5];
									date_default_timezone_set('America/Los_Angeles');
									$bpReviewDate = date('Y-m-d H:i:s',$timestamp);
									$bpRecommendation = $data[6];
									$bpFiles = $data[7];


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
									//									
									// GET REVIEWER'S OJS USERID
									//
									$reviewerOjsId = 0;
									$reviewerIdQuery = "SELECT user_id FROM user_settings WHERE setting_name = 'eschol_bpid' AND setting_value = $reviewerBpUserId";
									//echo "\nreviewerIdQuery: $reviewerIdQuery\n";
									$reviewerIdResult = mysql_query($reviewerIdQuery);
									if($reviewerIdResult === FALSE) {
										die("\nInvalid query: " . mysql_error() . "\nreviewerIdQuery: $reviewerIdQuery\n");
									} else {
										while($userSettingsRec = mysql_fetch_object($reviewerIdResult)) {	
											$reviewerOjsId = $userSettingsRec->user_id;
										}										
									}
									
									if(!$reviewerOjsId) {
										"\nALERT: OJS user_id for reviewerBpId $reviewerBpId could not be determined. Cannot assign this review.\nrow: $row\nfilePath: $filePath\n";
									}	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// GET RECOMMENDATION ID
									//
									// OJS reviewer recommendation types as stored in review_assignments.recommendation:
									// Accept Submission = 1
									// Revisions Required = 2
									// Resubmit for Review = 3
									// Resubmit Elsewhere = 4
									// Decline Submission = 5
									// See Comments = 6
									
									switch($bpRecommendation) {
										case "Accept":
											$recommendationId = 1;
											break;
										case "Accept After Revision":
											$recommendationId = 2;
											break;
										case "Major revisions recommended":
											$recommendationId = 2; 
											break;
										case "Accept with minor revisions":
											$recommendationId = 1;
											break;
										case "Reject":
											$recommendationId = 5;
											break;
										case "Major Revision/Resubmit for review":
											$recommendationId = 3;
											break;
										default:
											$recommendationId = 0;
									}
									
									if(!$recommendationId) {
										echo "\nALERT: Could not determine recommendation ID for line $row file $filePath\n";
										echo "bpRecommendation: $bpRecommendation\n";
									}									
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
									//									
									//LOOK TO SEE IF REVIEW ASSIGNMENTS RECORD EXISTS FOR THIS REVIEW
									//
									$reviewId = 0;
									$reviewIdQuery = "SELECT * FROM review_assignments WHERE submission_id = $article_id AND date_completed = '$bpReviewDate'";
									
									$result = mysql_query($reviewIdQuery);
									if(!$result) {
										die("\nInvalid query: " . mysql_error() . "\n");
									} else {
										while($review_assignments_rec = mysql_fetch_object($result)) {	
											$reviewId = $review_assignments_rec->review_id;
										}
									}
									
									if(!$reviewId) {
										//echo "\nNo review_assignments record exists for row $row file $filePath\nQuery: $reviewIdQuery\n";
										//continue;
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
										//									
										//LOOK TO SEE IF ARTICLE_FILES RECORD EXISTS FOR THIS REVIEW
										//
										$articleFileId = 0;
										$articleFileCheckQuery = "SELECT * FROM article_files WHERE article_id = $article_id AND original_file_name LIKE 'review-%' AND date_uploaded = '$bpReviewDate'";
										$articleFileCheckResult = mysql_query($articleFileCheckQuery);
										if($articleFileCheckResult === FALSE) {
											die("\nInvalid query: " . mysql_error() . "\narticleFileCheckQuery: $articleFileCheckQuery\n");
										} else {
											while($articleFileRec = mysql_fetch_object($articleFileCheckResult)) {
												$articleFileId = $articleFileRec->file_id;
											}
										}
										
										if(!$articleFileId) {
											echo "\nALERT: No article_files record exists for row $row file $filePath\nQuery: $articleFileCheckQuery\n";
										} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
											//									
											// LOOK FOR EXISTING REVIEW ASSIGNMENTS REC FOR THIS REVIEWER
											//
											$reviewAssignCheckQuery = "SELECT * FROM review_assignments WHERE submission_id = $article_id AND reviewer_id = $reviewerOjsId";
											//echo "\nreviewAssignCheckQuery: $reviewAssignCheckQuery\n";
											$reviewAssignCheckResult = mysql_query($reviewAssignCheckQuery);
											if($reviewAssignCheckResult === FALSE) {
												die("\nInvalid query: " . mysql_error() . "\nreviewAssignCheckQuery: $reviewAssignCheckQuery\n");
											} else {
												while($reviewAssignRec = mysql_fetch_object($reviewAssignCheckResult)) {
													$existingDateAssigned = $reviewAssignRec->date_assigned;
													$existingDateNotified = $reviewAssignRec->date_notified;
													$existingDeclined = $reviewAssignRec->declined;
													$existingDateConfirmed = $reviewAssignRec->date_confirmed;
													$existingCancelled = $reviewAssignRec->cancelled;
													$existingDateDue = $reviewAssignRec->date_due;
												}
											}
											
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
											//									
											//CREATE REVIEW ASSIGNMENTS RECORD
											//
											$reviewAssignInsertQuery = "
											INSERT INTO review_assignments
											(review_assignments.submission_id,
											review_assignments.reviewer_id,
											review_assignments.recommendation,
											review_assignments.date_assigned,
											review_assignments.date_notified,
											review_assignments.declined,
											review_assignments.date_confirmed,
											review_assignments.cancelled,
											review_assignments.date_completed,
											review_assignments.date_due,
											reviewer_file_id
											)
											VALUES
											(
											$article_id,
											$reviewerOjsId,
											$recommendationId,
											'$existingDateAssigned',
											'$existingDateNotified',
											$existingDeclined,
											'$existingDateConfirmed',
											$existingCancelled,
											'$bpReviewDate',
											'$existingDateDue',
											$articleFileId
											)
											";	
											echo "\nreviewAssignInsertQuery: $reviewAssignInsertQuery\n";
											$reviewAssignInsertResult = mysql_query($reviewAssignInsertQuery);
											if($reviewAssignInsertResult === FALSE) {
												die("\nInvalid query: " . mysql_error() . "\nreviewAssignInsertQuery: $reviewAssignInsertQuery\n");
											} else {
												$reviewId = mysql_insert_id();
												$reviewAssignmentsCtr++;
											}
											
											if(!$reviewId) {
												die("ERROR: Could not get reviewId after INSERT INTO review_assignments for file: $filePath");
											} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
												//
												// UPDATE article_event_log
												//
												$articleEventLogQuery = "UPDATE article_event_log SET assoc_type = 3 AND assoc_id = $reviewId ";
												$articleEventLogQuery .= "WHERE article_id = $article_id AND date_logged = '$bpReviewDate'";
												//echo "\narticleEventLogQuery: $articleEventLogQuery\n";
												$articleEventLogResult = mysql_query($articleEventLogQuery);
												if($articleEventLogResult === FALSE) {
													die("\nInvalid query: " . mysql_error() . "\narticleEventLogQuery: $articleEventLogQuery\n");
												}	
											}
										}
									}									
								} //end if($row > 1)
								
								
								$row++;
							} //end while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE)
						}// end if (file_exists($filePath))
					}//end if($fileOrDir=='reviews.tsv')
				} //end foreach($fileListing as $fileOrDir)
			}//end if(substr($dir,0,1) != '.')
		}//end foreach($parentDirs as $dir)
	} //end foreach ($importParentDir as $dir)
	
	//
	//PRINT RESULTS
	//	
	echo "\nReviews imported (review_assignments created): $reviewAssignmentsCtr\n";
	echo "\nIMPORT OF REVIEWS FOR $unpubString ARTICLES FINISHED.\n\n";
} //end function

?>