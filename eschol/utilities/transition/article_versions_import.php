#!/usr/bin/php
<?php

require './import_set_parameters.php';
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// RUN IMPORT
//
import_article_versions($importHome,$importParentDir,1,$baseUploadDir); //unpublished
import_article_versions($importHome,$importParentDir,0,$baseUploadDir); //published

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// BEGIN FUNCTION TO IMPORT VARIOUS ARTICLE VERSIONS
//
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_article_versions($importHome,$importParentDir,$unpublished,$baseUploadDir) {

	$publishedString = $unpublished ? 'UNPUBLISHED' : 'PUBLISHED';
	echo "\n**** IMPORTING VERSIONS FOR $publishedString ARTICLES ****\n";
		
	//
	// LOOP THROUGH JOURNALS
	//
	$recsCreated = 0;
	$typeOrigRecsCreated = 0;
	$typeReviewRecsCreated = 0;
	$typeGalleyRecsCreated = 0;
	$typeEditorRecsCreated = 0;
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
				
				//
				//get eschol_articleid value (stored in article_settings table)
				//				
				$eschol_articleid = $eschol_articleid_begin . '_' . $dir;
				
				//
				//set full dir path
				//
				if($unpublished) {	
					$fullPath = $importHome . $journalPath . 'unpublished/' . $dir . '/';
				} else {
					$fullPath = $grandparentPath . $dir . '/';
				}
				
				//echo "\nFull Path: $fullPath\n";
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				//
				//QUERY DB FOR article_id
				//
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
					echo "\n**ALERT** No article_settings record exists with eschol article ID or eschol_submission_path = '$article_link'.\nQuery: $articleid_query\nNo history created for this article.\n";
					$article_settings_exists = 0;
					continue;
				}
				
				$article_id = mysql_result($result,0);								
				//echo "\narticle_id: $article_id\n";			

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				//
				// GET LISTING OF FILES IN DIRECTORY
				// 
				$dirListing = scandir($fullPath);
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// BUILD ARRAY OF ALL FILES
				//
				$articleFiles = array();
				$PDFctr = 0;
				foreach($dirListing as $currListing) {
					if(substr($currListing,0,5) == 'text.' && substr($currListing,-7) != 'stamped' && substr($currListing,-3) != 'tmp' && substr($currListing,-5) != 'error') {
						
						if(substr($currListing,0,8) == 'text.pdf') $PDFctr++;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						// GET TIMESTAMP & DATE STRING
						//
						$first_dot_pos = strpos($currListing,'.'); //4
						$second_dot_pos = strpos($currListing,'.',$first_dot_pos + 1); //11
						//$third_dot_pos = strpos($currListing,'.',$second_dot_pos + 1); //FALSE
						if(is_int((int) substr($currListing,$second_dot_pos + 1))) {
							$timestamp = (int) substr($currListing,$second_dot_pos + 1);
						}
						date_default_timezone_set('America/Los_Angeles');
						$dateUploaded = date('Y-m-d H:i:s',$timestamp);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// GET FILE TYPE AND FILE EXTENSION
						//
						$fileType = '';
						$extension = '';
						$bpFileType = substr($currListing,$first_dot_pos + 1,$second_dot_pos - $first_dot_pos - 1);
						if($bpFileType == 'native') {
							$fileType = 'application/msword';
							$extension = 'docx';
						} elseif($bpFileType == 'pdf') {
							$fileType = 'application/pdf';
							$extension = 'pdf';						
						} else {
							$fileType = '';
							$extension = 'unknown';
						}
						
						$fileSize = sprintf("%u",filesize($fullPath.$currListing));
						
						$articleFiles[] = array('currListing' => $currListing, 'pdfNum' => $PDFctr, 'timestamp' => $timestamp, 'dateUploaded' => $dateUploaded, 'fileType' => $fileType, 'extension' => $extension, 'fileSize' => $fileSize, 'isReview' => 0, 'isOrig' => 0, 'isGalley' => 0);
					}
				}
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// MARK LAST PDF FILE AS REVIEW FILE
				//
				foreach($articleFiles as &$file) {
					if($file[pdfNum] == $PDFctr) $file[isReview] = 1;
				}
				unset($file);
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// MARK LAST PDF FILE AS GALLEY VERSION FOR UNPUBLISHED 'Accepted' ARTICLES 
				//
				if($unpublished) {
					foreach($articleFiles as &$file) {
						$acceptedMessage = "";
						$isGalley = 0;
						if($file[pdfNum] == $PDFctr) {
							// check to see if article has event history line of 'Accepted'
							$acceptedQuery = "SELECT message FROM article_event_log ";
							$acceptedQuery .= "WHERE article_id = $article_id";
							
							//echo "acceptedQuery: $acceptedQuery\n";
							$acceptedResult = mysql_query($acceptedQuery);
							if(!$acceptedResult) {
								die("\nInvalid query: " . mysql_error() . "\nacceptedQuery: $acceptedQuery\n");
							}
							
							while($acceptedRow = mysql_fetch_array($acceptedResult)) {
								$acceptedMessage = $acceptedRow[0];
								if(trim($acceptedMessage) == '[bp event history] Accepted') {
									$isGalley = 1;
								}
							}
							
							if($isGalley) $file[isGalley] = 1;
						}
					}
					unset($file);
				}
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// MARK ORIGINAL SUBMISSION FILE
				//
				foreach($articleFiles as &$file) {
					$currImportFile = $file[currListing];
					$message = "";
					$isOrig = 0;
					$versionQuery = "SELECT message FROM article_event_log ";
					$versionQuery .= "WHERE article_id = $article_id AND date_logged = '$file[dateUploaded]'";
					
					//echo "Version Query: $versionQuery\n";
					$result = mysql_query($versionQuery);
					if(!$result) {
						die("\nInvalid query: " . mysql_error() . "\n");
					}

					while ($row = mysql_fetch_array($result)) {
						$message = $row[0];
						if(trim($message) == '[bp revision history] Initial Version') {
							$isOrig = 1;
						}
					}
					
					if($isOrig) $file[isOrig] = 1;
				}

				unset($file);
				
				//print_r($articleFiles);
				//echo "PDF Count: $PDFctr\n\n";

				$editorRevision = 1;
				$editor_file_id = 0;


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				// 
				// BEGIN IMPORT
				//
				foreach($articleFiles as $currFile) {
				
					$currImportFile = $currFile[currListing];
					$timestamp = $currFile[timestamp];
					$dateUploaded = $currFile[dateUploaded];
					$fileType = $currFile[fileType];
					$extension = $currFile[extension];
					$fileSize = $currFile[fileSize];
					$isReview = $currFile[isReview];
					$isOrig = $currFile[isOrig];
					$isGalley = $currFile[isGalley];
				
					$currFullPathImportFile = $fullPath . $currImportFile;

					//echo "\ncurrFullPathImportFile: $currFullPathImportFile\nisReview: $isReview\n";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
					//
					// IMPORT ORIGINAL SUBMISSION FILE
					// Original Version: 	submission/original - text.native.[timestamp] - SM
					//		
					$importOrig = 1;
					if($importOrig && $isOrig) {
																						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// SET METADATA VALUES
						//							
						$file_id = 0; 
						$revision = 1;
						$type = 'submission/original';
						$typeAbbr = 'SM';
						$round = 1;
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// CHECK FOR DUPLICATES BEFORE CREATING RECORD
						//
						$dup_check_stmt = "SELECT * FROM article_files ";
						$dup_check_stmt .= "WHERE article_id = $article_id AND type = '$type'";

						$dup_check_result = mysql_query($dup_check_stmt);
						if(mysql_num_rows($dup_check_result) > 0) {
							//echo "Current Import File: $fullPath$currImportFile\n";
							//echo "dup_check_stmt: $dup_check_stmt\n";
							$dupCtr++;
						} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// CREATE DATABASE RECORD
							// Need to do this first to get file_id
							//
							$insertArticleFileQuery = "
							INSERT INTO article_files
							(revision, article_id, file_name, file_type, file_size, original_file_name, type, date_uploaded, date_modified, round)
							VALUES
							($revision, $article_id, '$currImportFile', '$fileType', $fileSize, '" . mysql_real_escape_string($currImportFile) . "', '$type', '$dateUploaded', '$dateUploaded', $round)								
							";
							
							//echo "     insertArticleFileQuery: $insertArticleFileQuery\n";
							
							$result = mysql_query($insertArticleFileQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							} else {
								$file_id = mysql_insert_id();
								$recsCreated++;
								$typeOrigRecsCreated++;
							}
							//echo "\nfile_id: $file_id\n";
							
							if($file_id == 0) {
								die("ERROR: Could not get file_id after INSERT INTO article_files for file: $fullPath$currImportFile");
							}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// 
							// RENAME FILE FOR OJS
							//								
							$newFileName = $article_id . '-' . $file_id . '-' . $revision . '-' . $typeAbbr . '.' . $extension;
							//echo "Current Import File: $currImportFile\n";
							//echo "New File name: $newFileName\n";
							
							$updateFileNameQuery = "UPDATE article_files SET file_name = '$newFileName' WHERE file_id = $file_id AND article_id = $article_id AND type = '$type'";
							//echo "\nupdateFileNameQuery: $updateFileNameQuery\n";
							$result = mysql_query($updateFileNameQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// 
							// ENTER FILE ID IN ARTICLES TABLE AS ORIGINAL SUBMISSION VERSION
							//								
							
							$updateSubFileIdQuery = "UPDATE articles SET submission_file_id = $file_id where article_id = $article_id";
							//echo "\nupdateSubFileIdQuery: $updateSubFileIdQuery\n";
							$result = mysql_query($updateSubFileIdQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							}								

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// UPLOAD FILE TO FILESYSTEM
							//	
							$uploadPath = $baseUploadDir . '/journals/' . $journalId . '/articles/' . $article_id . '/' . $type . '/';
							//echo "Current Import File: $currFullPathImportFile\n";							
							//echo "     Upload Path: $uploadPath\n";
							//die("DIE - testing\n\n");
							
							//
							// create dirs and subdirs if necessary
							//
							if(!(file_exists($uploadPath))) {
								//echo "\n     Creating Directory!!!\n\n";
								mkdir($uploadPath,0755,1);
							}
							
							//
							// copy file to dir
							//
							copy($currFullPathImportFile,$uploadPath.$newFileName);
						}
					} //end if($isOrig)

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// END ORIGINAL VERSION IMPORT
					//
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
					//
					// IMPORT REVIEW VERSION
					// submission/review - text.pdf.[timestamp] (where timestamp is largest) - RV
					// 	
					if($isReview) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// SET METADATA VALUES
						//							
						$file_id = 0; 
						$revision = 1;
						$type = 'submission/review';
						$typeAbbr = 'RV';
						$round = 1;
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// CHECK FOR DUPLICATES BEFORE CREATING RECORD
						//
						$dup_check_stmt = "SELECT * FROM article_files ";
						$dup_check_stmt .= "WHERE article_id = $article_id AND type = '$type'";

						$dup_check_result = mysql_query($dup_check_stmt);
						if(mysql_num_rows($dup_check_result) > 0) {
							//echo "Current Import File: $fullPath$currImportFile\n";
							//echo "dup_check_stmt: $dup_check_stmt\n";
							$dupCtr++;
						} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// CREATE DATABASE RECORD
							// Need to do this first to get file_id
							//
							$insertArticleFileQuery = "
							INSERT INTO article_files
							(revision, article_id, file_name, file_type, file_size, original_file_name, type, date_uploaded, date_modified, round)
							VALUES
							($revision, $article_id, '$currImportFile', '$fileType', $fileSize, '" . mysql_real_escape_string($currImportFile) . "', '$type', '$dateUploaded', '$dateUploaded', $round)								
							";
							
							//echo "     insertArticleFileQuery: $insertArticleFileQuery\n";
							
							$result = mysql_query($insertArticleFileQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							} else {
								$file_id = mysql_insert_id();
								$recsCreated++;
								$typeReviewRecsCreated++;
							}
							//echo "\nfile_id: $file_id\n";
							
							if($file_id == 0) {
								die("ERROR: Could not get file_id after INSERT INTO article_files for file: $fullPath$currImportFile");
							}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// 
							// RENAME FILE FOR OJS
							//								
							$newFileName = $article_id . '-' . $file_id . '-' . $revision . '-' . $typeAbbr . '.' . $extension;
							//echo "Current Import File: $currImportFile\n";
							//echo "New File name: $newFileName\n";
							
							$updateFileNameQuery = "UPDATE article_files SET file_name = '$newFileName' WHERE file_id = $file_id AND article_id = $article_id AND type = '$type'";
							//echo "\nupdateFileNameQuery: $updateFileNameQuery\n";
							$result = mysql_query($updateFileNameQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							}
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// 
							// ENTER FILE ID IN ARTICLES TABLE AS REVIEW VERSION
							//								
							
							$updateReviewFileIdQuery = "UPDATE articles SET review_file_id = $file_id where article_id = $article_id";
							//echo "\nupdateReviewFileIdQuery: $updateReviewFileIdQuery\n";
							$result = mysql_query($updateReviewFileIdQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							}	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// 
							// CREATE REVIEW ROUND RECORD
							//		
							$reviewRoundDupQuery = "SELECT * FROM review_rounds where submission_id = $article_id";
							$reviewRoundDupQueryResult = mysql_query($reviewRoundDupQuery);
							if(mysql_num_rows($reviewRoundDupQueryResult) > 0) {
								//review_round record already exists
							} else {
								$reviewRoundQuery = "INSERT INTO review_rounds
								(submission_id, round, review_revision)
								VALUES
								($article_id, 1, 1)";
								$result = mysql_query($reviewRoundQuery);
								if(!$result) {
									die("\nInvalid query: " . mysql_error() . "\n");
								}	
							}
							

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// UPLOAD FILE TO FILESYSTEM
							//	
							$uploadPath = $baseUploadDir . '/journals/' . $journalId . '/articles/' . $article_id . '/' . $type . '/';
							//echo "Current Import File: $currFullPathImportFile\n";							
							//echo "     Upload Path: $uploadPath\n";
							//die("DIE - testing\n\n");
							
							//
							// create dirs and subdirs if necessary
							//
							if(!(file_exists($uploadPath))) {
								//echo "\n     Creating Directory!!!\n\n";
								mkdir($uploadPath,0755,1);
							}
							
							//
							// copy file to dir
							//
							copy($currFullPathImportFile,$uploadPath.$newFileName);		
							
						}
					} //end if($isReview)
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// END REVIEW VERSION IMPORT
					//

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
					//
					// IMPORT GALLEY VERSION FOR 'ACCEPTED' UNPUBLISHED ARTICLES
					// submission/
					// 	
					if($isGalley) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// SET METADATA VALUES
						//							
						$file_id = 0; 
						$revision = 1;
						$type = 'public';
						$typeAbbr = 'PB';
						$round = 1;
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// CHECK FOR DUPLICATES BEFORE CREATING RECORD
						//
						$dup_check_stmt = "SELECT * FROM article_files ";
						$dup_check_stmt .= "WHERE article_id = $article_id AND type = '$type'";

						$dup_check_result = mysql_query($dup_check_stmt);
						if(mysql_num_rows($dup_check_result) > 0) {
							//echo "Current Import File: $fullPath$currImportFile\n";
							//echo "dup_check_stmt: $dup_check_stmt\n";
							$dupCtr++;
						} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// CREATE DATABASE RECORD
							// Need to do this first to get file_id
							//
							$insertArticleFileQuery = "
							INSERT INTO article_files
							(revision, article_id, file_name, file_type, file_size, original_file_name, type, date_uploaded, date_modified, round)
							VALUES
							($revision, $article_id, '$currImportFile', '$fileType', $fileSize, '" . mysql_real_escape_string($currImportFile) . "', '$type', '$dateUploaded', '$dateUploaded', $round)								
							";
							
							//echo "     insertArticleFileQuery: $insertArticleFileQuery\n";
							
							$result = mysql_query($insertArticleFileQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							} else {
								$file_id = mysql_insert_id();
								$recsCreated++;
								$typeGalleyRecsCreated++;
							}
							//echo "\nfile_id: $file_id\n";
							
							if($file_id == 0) {
								die("ERROR: Could not get file_id after INSERT INTO article_files for file: $fullPath$currImportFile");
							}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// 
							// RENAME FILE FOR OJS
							//								
							$newFileName = $article_id . '-' . $file_id . '-' . $revision . '-' . $typeAbbr . '.' . $extension;
							//echo "Current Import File: $currImportFile\n";
							//echo "New File name: $newFileName\n";
							
							$updateFileNameQuery = "UPDATE article_files SET file_name = '$newFileName' WHERE file_id = $file_id AND article_id = $article_id AND type = '$type'";
							//echo "\nupdateFileNameQuery: $updateFileNameQuery\n";
							$result = mysql_query($updateFileNameQuery);
							if(!$result) {
								die("\nInvalid query: " . mysql_error() . "\n");
							}	
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							// 
							// CREATE article_galleys RECORD
							//
							$insertGalleyQuery = "
							INSERT INTO article_galleys
							(locale, article_id, file_id, label)
							VALUES
							('en_US', $article_id, $file_id, 'PDF')
							";
							
							//echo "     insertGalleyQuery: $insertGalleyQuery\n";
							
							$insertGalleyResult = mysql_query($insertGalleyQuery);
							if(!$insertGalleyResult) {
								die("\nInvalid query: " . mysql_error() . "\ninsertGalleyQuery: $insertGalleyQuery\n");
							}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// UPLOAD FILE TO FILESYSTEM
							//	
							$uploadPath = $baseUploadDir . '/journals/' . $journalId . '/articles/' . $article_id . '/' . $type . '/';
							//echo "Current Import File: $currFullPathImportFile\n";							
							//echo "     Upload Path: $uploadPath\n";
							//die("DIE - testing\n\n");
							
							//
							// create dirs and subdirs if necessary
							//
							if(!(file_exists($uploadPath))) {
								//echo "\n     Creating Directory!!!\n\n";
								mkdir($uploadPath,0755,1);
							}
							
							//
							// copy file to dir
							//
							copy($currFullPathImportFile,$uploadPath.$newFileName);		
							
						}
					} //end if($isGalley)
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// END GALLEY VERSION IMPORT
					//					
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
					// IMPORT EDITOR VERSIONS
					// Editor Versions: 	submission/editor - all text.* files , sorted based on timestamp - ED
					// every file but the original should be Editor versions
					// the last should also be review version.
					// 
					
					$importEditorVersions = 1;
					if($importEditorVersions && !$isOrig) {						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// SET METADATA VALUES
						//							 
						$type = 'submission/editor';
						$typeAbbr = 'ED';
						$round = 1;				
						
						// CREATE DATABASE RECORD
						// (no way to check for record already existing??)
						//
						$insertArticleFileQuery = "INSERT INTO article_files (";
						if($editor_file_id != 0) $insertArticleFileQuery .= "file_id, ";
						$insertArticleFileQuery .= "revision, article_id, file_name, file_type, file_size, original_file_name, type, date_uploaded, date_modified, round)";
						$insertArticleFileQuery .= " VALUES (";
						if($editor_file_id != 0) $insertArticleFileQuery .= "$editor_file_id, ";
						$insertArticleFileQuery .= "$editorRevision, $article_id, '$currImportFile', '$fileType', $fileSize, '" . mysql_real_escape_string($currImportFile) . "', '$type', '$dateUploaded', '$dateUploaded', $round)";
						//echo "\ninsertArticleFileQuery: $insertArticleFileQuery\n";
						
						
						$result = mysql_query($insertArticleFileQuery);
						if(!$result) {
							die("\nInvalid query: " . mysql_error() . "\n");
						} else {
							$editor_file_id = mysql_insert_id();
							$recsCreated++;
							$typeEditorRecsCreated++;
						}
						
						//echo "\nfile_id: $editor_file_id\n";
						
						if($editor_file_id == 0) {
							die("ERROR: Could not get file_id after INSERT INTO article_files for file: $fullPath$currImportFile\n");
						}	
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// RENAME FILE FOR OJS
						//
						$newFileName = $article_id . '-' . $editor_file_id . '-' . $editorRevision . '-' . $typeAbbr . '.' . $extension;
						//echo "Current Import File: $currImportFile\n";
						//echo "New File name: $newFileName\n";
						
						$updateFileNameQuery = "UPDATE article_files SET file_name = '$newFileName' WHERE file_id = $editor_file_id AND revision = $editorRevision AND article_id = $article_id AND type = '$type'";
						//echo "\nupdateFileNameQuery: $updateFileNameQuery\n";
						$result = mysql_query($updateFileNameQuery);
						if(!$result) {
							die("\nInvalid query: " . mysql_error() . "\n");
						}
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// ENTER FILE ID IN ARTICLES TABLE AS EDITOR VERSION
						//
						$updateEdFileIdQuery = "UPDATE articles SET editor_file_id = $editor_file_id where article_id = $article_id";
						//echo "\nupdateSubFileIdQuery: $updateSubFileIdQuery\n";
						$result = mysql_query($updateEdFileIdQuery);
						if(!$result) {
							die("\nInvalid query: " . mysql_error() . "\n");
						}	
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// UPLOAD FILE TO FILESYSTEM
						//	
						$uploadPath = $baseUploadDir . '/journals/' . $journalId . '/articles/' . $article_id . '/' . $type . '/';
						//echo "Current Import File: $currFullPathImportFile\n";							
						//echo "Upload Path: $uploadPath\n";
						//die("DIE - testing\n\n");
						
						//
						// create dirs and subdirs if necessary
						//
						if(!(file_exists($uploadPath))) {
							//echo "\n     Creating Directory!!!\n\n";
							mkdir($uploadPath,0755,1);
						}
						
						//
						// copy file to dir
						//
						copy($currFullPathImportFile,$uploadPath.$newFileName);
						//echo "------------------------------\n\n";
						
						$editorRevision++;
								
					} //end if(!$isOrig)			
				}//end foreach($articleFiles as $currImportFile)
			}//end if(substr($dir,0,1) != '.')
		}//end foreach($parentDirs as $dir)
	} //end foreach ($importParentDir as $dir)
	
	//
	//PRINT RESULTS
	//
	//if($dupCtr > 0) {
		echo "\nCount of article files that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	//}
	
	echo "\nOriginal Versions Created: $typeOrigRecsCreated\n";
	echo "\nReview Versions Created: $typeReviewRecsCreated\n";
	echo "\nGalley Versions Created: $typeGalleyRecsCreated\n";
	echo "\nEditor Versions Created: $typeEditorRecsCreated\n";
	echo "\nTOTAL Records created: $recsCreated\n";
	echo "\nIMPORT OF ARTICLE FILES FINISHED.\n\n";
		
} //end function import_reviews

mysql_close($conn);

?>