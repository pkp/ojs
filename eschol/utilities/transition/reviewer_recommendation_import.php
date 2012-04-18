#!/usr/bin/php
<?php

require './import_set_parameters.php';
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_reviewer_recommendations($importHome,$importParentDir,1,$baseUploadDir); //unpublished
import_reviewer_recommendations($importHome,$importParentDir,0,$baseUploadDir); //published

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// CLOSE DB CONN
//
mysql_close($conn);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Function to import individual reviewer recommendations (reviews.tsv files from bepress)
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_reviewer_recommendations($importHome,$importParentDir,$unpublished,$baseUploadDir) {

	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	
	echo "\n**** IMPORTING REVIEWER RECOMMENDATIONS FOR $unpubString ARTICLES ***\n";
		
	//
	// LOOP THROUGH JOURNALS
	//
	$reviewerRecsCtr = 0;
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
					echo "\n**ALERT** No article_settings record exists with setting_name 'title' for this article.\nQuery: $articleid_query\n";
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
									
									//echo "\nfileName: $fileName\nfilePath: $filePath\n";
									//echo "bpRecommendation: $bpRecommendation\n";
									//echo "recommendationId: $recommendationId\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// GET REVIEW ID
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
									
									//look in article_files
									if(!$reviewId) {
										echo "\nALERT: Could not determine review ID for line $row file $filePath\nQuery: $reviewIdQuery\n";
										continue;
									}
									
									//echo "\nreviewIdQuery: $reviewIdQuery\nreviewId: $reviewId\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
									//
									// MAKE SURE RECOMMENDATION DOESN'T ALREADY EXIST BEFORE IMPORTING
									//
									$dup_check_stmt = "SELECT * FROM review_assignments WHERE review_id = $reviewId AND recommendation IS NOT NULL AND recommendation != 0";			
									$dup_check_result = mysql_query($dup_check_stmt);
									if(mysql_num_rows($dup_check_result) > 0) {
										//echo "dup_check_stmt: $dup_check_stmt\n";
										$dupCtr++;
									} else {									
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
										//
										// IMPORT RECOMMENDATIONS INTO DB
										//
										$recImportQuery = "UPDATE review_assignments SET recommendation = $recommendationId ";
										$recImportQuery .= "WHERE review_id = $reviewId AND (recommendation IS NULL OR recommendation = 0)";	
										//echo "row: $row\nrecImportQuery: $recImportQuery\n";
										$result = mysql_query($recImportQuery);
										if(!$result) {
											die("\nInvalid query: " . mysql_error() . "\n");
										} else {
											$reviewerRecsCtr++;
										}
										
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
										//
										// UPDATE article_event_log
										//
										$articleEventLogQuery = "UPDATE article_event_log SET assoc_type = 3 AND assoc_id = $reviewId ";
										$articleEventLogQuery .= "WHERE article_id = $article_id AND date_logged = '$bpReviewDate'";
										//echo "\narticleEventLogQuery: $articleEventLogQuery\n";
										$result = mysql_query($articleEventLogQuery);
										if(!$result) {
											die("\nInvalid query: " . mysql_error() . "\n");
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
	if($dupCtr > 0) {
		echo "\nCount of reviewer recommendations that were already recorded and therefore not overwritten by this import: $dupCtr\n"; 
	}
	
	echo "\nReviewer recommendations imported: $reviewerRecsCtr\n";
	echo "\nIMPORT OF REVIEWER RECOMMENDATIONS FOR $unpubString ARTICLES FINISHED.\n\n";
} //end function

?>