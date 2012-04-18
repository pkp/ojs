#!/usr/bin/php
<?php

//require './import_set_parameters.php';

$importHome = '/apps/subi/transition/cdltransition.bepress.com/cdlib/';
$baseUploadDir = '/apps/subi/ojs/files';
$importParentDir[] = array('nelc/uee/', 'nelc_uee', 38, 'wendrich@humnet.ucla.edu','Wendrich','Willeke','');
$fileTypeInfo = '/apps/subi/apache/htdocs/ojs/eschol/docs/uee_review_filetype.txt';

/***
$importHome= '/Users/bhui/Documents/bepressdata/'; // Barbara's local machine
$baseUploadDir = '/apps/subi/ojs/files';
$importParentDir[] = array('nelc/uee/', 'nelc_uee', 38, 'wendrich@humnet.ucla.edu','Wendrich','Willeke','');
$fileTypeInfo = '/Library/WebServer/Documents/ojs/eschol/docs/uee_review_filetype.txt';
***/
require './ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import for published and unpublished articles
//
import_reviews($importHome,$importParentDir,1,$baseUploadDir,$fileTypeInfo); //unpublished
import_reviews($importHome,$importParentDir,0,$baseUploadDir,$fileTypeInfo); //published

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Function to import reviewer report
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_reviews($importHome,$importParentDir,$unpublished,$baseUploadDir,$fileTypeInfo) {

	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	
	echo "\n**** IMPORTING REVIEWS FOR $unpubString ARTICLES ***\n";
		
	//
	// LOOP THROUGH JOURNALS
	//
	$commentsCreated = 0;
	$articlesCreated = 0;
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
		// 	READ IN REVIEW FILETYPE INFO
		//

		$nativeReviewTypes = array();
		$typeDescs = array();
		if(file_exists($fileTypeInfo)) {
			//open file
			$handle = fopen($fileTypeInfo, "r");
			//echo "\nOpened handle to $fileTypeInfo successfully\n";
			$numRows = count(file($fileTypeInfo));
			//echo "\nnum of rows in $fileTypeInfo: $numRows\n";			
			while (($data = fgetcsv($handle, 1000, ":")) !== FALSE) {	
				$nativeFilePath = "";
				$nativeTypeDesc = "";
				$nativeMimeType = "";
				$nativeFileExt = "";
				$nativeFilePathElements = array();
				$source = "";
				$nativeFileName = "";
				$key = "";
				$nativeFilePath = trim($data[0]);
				$nativeTypeDesc = trim($data[1]);
				
				$nativeFilePathElements = explode('/',$nativeFilePath);
				if($nativeFilePathElements[1] == "unpublished") {
					$source = $nativeFilePathElements[1] . "/" . $nativeFilePathElements[2];
					$nativeFileName = $nativeFilePathElements[3];
				} else {
					$source = $nativeFilePathElements[1];
					$nativeFileName = $nativeFilePathElements[2];
				}
				
				switch($nativeTypeDesc) {
					case "Microsoft Office Document Microsoft Word Document":
						$nativeMimeType = "application/msword";
						$nativeFileExt = ".doc";
						break;
					case "Zip archive data, at least v2.0 to extract":
						$nativeMimeType = "application/msword";
						$nativeFileExt = ".docx";
						break;
					case "Rich Text Format data, version 1, ANSI":
						$nativeMimeType = "text/rtf";
						$nativeFileExt = ".rtf";
						break;		
					case "PDF document, version 1.6":
						$nativeMimeType = "application/pdf";
						$nativeFileExt = ".pdf";
						break;	
					case "PDF document, version 1.5":
						$nativeMimeType = "application/pdf";
						$nativeFileExt = ".pdf";
						break;	
					case "Rich Text Format data, version 1, unknown character set":
						$nativeMimeType = "text/rtf";
						$nativeFileExt = ".rtf";
						break;
					default:
						$nativeMimeType = "unknown";
						$nativeFileExt = "";
				}
				
				$key = $source . '/' . $nativeFileName;
				
				$nativeReviewTypes[$key] = array('nativeFilePath' => $nativeFilePath, 'nativeTypeDesc' => $nativeTypeDesc, 'nativeMimeType' => $nativeMimeType, 'nativeFileExt' => $nativeFileExt, 'source' => $source, 'nativeFileName' => $nativeFileName);
				//$typeDescs[] = $nativeTypeDesc;
			}
		} else {
			die("\nfileTypeInfo file does not exist!: $fileTypeInfo\n");
		}
		
		if(count($nativeReviewTypes) != $numRows) {
			die("\nError reading in native file types from $fileTypeInfo. Count of records read in does not match numRows ($numRows)\n");
		}

		//echo "\ntypeDescs: \n";
		//print_r($typeDescs);
		
		//$uniqueTypeDescs = array();
		//$uniqueTypeDescs = array_unique($typeDescs);
		//echo "\nuniqueTypeDescs: \n";
		//print_r($uniqueTypeDescs);
		
		//echo "\nnativeReviewTypes: \n";
		//print_r($nativeReviewTypes);
		
		//die("\nTesting\n");
		
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
					echo "\n**ERROR** No article_settings record exists with setting_name 'title' for this article.\nQuery: $articleid_query\nNo data will be imported for this article.";
				} else {
					$article_id = mysql_result($result,0);	
				}
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				//
				// QUERY DB FOR Article Title
				//	
				
				$articleTitle = '';
				$articleTitleQuery = "SELECT setting_value FROM article_settings WHERE article_id = $article_id AND setting_name = 'title'";
				$result = 0;
				$result = mysql_query($articleTitleQuery);
				if(!$result) {
					die("\nInvalid query: " . mysql_error() . "\n");
				}
				if (mysql_num_rows($result)==0) {
					echo "\n**ALERT** No article_settings record exists with setting_name 'title' for this article.\nQuery: $articleTitleQuery\nComment Title will be empty\n";
				} else {
					$articleTitle = mysql_result($result,0);
				}
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
				// 
				// QUERY DB FOR Author ID
				//
				$authorId = 0;
				$authorIdQuery = "SELECT user_id from articles WHERE article_id = $article_id";
				$result = 0;
				$result = mysql_query($authorIdQuery);
				if(!result) {
					die("\nInvalid query: " . mysql_error() . "\n");
				}
				if (mysql_num_rows($result)==0) {
					echo "\n**ERROR** No article_settings record exists with setting_name 'title' for this article.\nQuery: $authorIdQuery\nNo records will be imported.";
					continue;
				} else {
					$authorId = mysql_result($result,0);
				}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////	
				//
				//GET LIST OF FILENAMES FOR EACH ARTICLE
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
					$isAttachment = 0;
					
					//we only want files named 'review-*'
					if(substr($fileOrDir,0,7)=="review-") {
						$listingFullPath = $grandparentPath . $dir . '/' . $fileOrDir;
						
						if(is_dir($listingFullPath)) {
							// deal with any attachments - they are in a sub-dir named 'review-[reviewNum]-[timestamp]'. filename = [reviewNum]
							$subDirFiles = scandir($listingFullPath);
							foreach($subDirFiles as $subDirFile) {
								$subDirFileFullPath = $listingFullPath . '/' . $subDirFile;
								if(!is_dir($subDirFileFullPath) && substr($subDirFile,0,1) != '.' && substr($subDirFile,-3,3) != 'tmp') {
									$fileName = $subDirFile;
									$filePath = $subDirFileFullPath;
									$commentName = $fileOrDir;
									$isAttachment = 1;
									$fullFileListing[] = array('fileName' => $fileName, 'filePath' => $filePath, 'commentName' => $commentName, 'isAttachment' => $isAttachment);
									//echo "\nfileName: $fileName\nfilePath: $filePath\nisAttachment: $isAttachment\n";
								}
							}
						} else {
							// if it's just a regular file
							$fileName = $fileOrDir;
							$filePath = $listingFullPath;
							$commentName = $fileOrDir;
							$isAttachment = 0;
							$fullFileListing[] = array('fileName' => $fileName, 'filePath' => $filePath, 'commentName' => $commentName, 'isAttachment' => $isAttachment);
						}
						
					}
				}
				
				//echo "\nfullFileListing: \n";
				//print_r($fullFileListing);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// GET HASH OF VALUES FOR EACH REVIEW FILE
				//	
				

				$decision_key = 0;
				$num_review_files = 0;
				$num_decision_files = 0;
				$review_files = array();
				$decision_files = array();
				
				foreach($fullFileListing as $articleFileArray) {
				
					$fileName = $articleFileArray['fileName'];
					$filePath = $articleFileArray['filePath'];
					$commentName = $articleFileArray['commentName'];
					$isAttachment = $articleFileArray['isAttachment'];
					
					//if($isAttachment) echo "\nfileName: $fileName\nfilePath: $filePath\ncommentName: $commentName\nisAttachment: $isAttachment\n";
					

					$second_dash_pos = strpos($commentName,"-",8); //9
					$first_dot_pos = strpos($commentName,".",$first_dash_pos + 1); //19

					if($first_dot_pos == FALSE) {
						$commentName = $commentName . '.pdf';
						$first_dot_pos = strlen($commentName);
					}
					$review_num = substr($commentName,7,($second_dash_pos - 7));
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// GET TIMESTAMP & DATE UPLOADED
					//
					$timestamp = substr($commentName,($second_dash_pos + 1),($first_dot_pos - $second_dash_pos - 1));
					date_default_timezone_set('America/Los_Angeles');
					$dateUploaded = date('Y-m-d H:i:s',$timestamp);	

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// GET FILE SIZE
					//						
					$fileSize = sprintf("%u",filesize($filePath));
						
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// GET FILE TYPE & CATEGORY
					//
					$fileType = '';
					$fileType = substr($commentName,($first_dot_pos + 1)); //substr($commentName,19)
					switch ($fileType) {
						case "cover.txt":
							$fileType = "text/plain";
							$fileCategory = "cover";
							$fileExt = ".txt";
							break;
						case "txt":
							$fileType = "text/plain";
							$fileCategory = "main";
							$fileExt = ".txt";
							break;
						case "pdf":
							$fileType = "application/pdf";
							$fileCategory = "main";
							$fileExt = ".pdf";
							break;
						case "native":
							//$fileType = "unknown";
							$fileCategory = "main";
							$fileExt = "";
							break;
						case "cover":
							$fileType = "";
							$fileCategory = "cover";
							$fileExt = "";
							break;
						default:
							$fileType = "unknown";
							$fileCategory = "unknown";
							$fileExt = "";
					}
					
					if($isAttachment) {
						$fileType = "application/pdf";
						$fileCategory = "main";
						$fileExt = ".pdf";
					}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// FOR NATIVE FILES, LOOK UP FILE TYPE
					//	
					if($fileType=='native') {
						if($unpublished) {
							$nativeKey = "unpublished/" . $dir . "/" . $fileName;
						} else {
							$nativeKey = $dir . "/" . $fileName;	
						}
						$fileType = $nativeReviewTypes[$nativeKey]['nativeMimeType'];
						$fileExt = $nativeReviewTypes[$nativeKey]['nativeFileExt'];
					}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					//
					// BUILD ARRAY OF VALUES FOR EACH REVIEW FILE TO BE UPLOADED
					//					
					//if($isAttachment) echo "\nfileName: $fileName\nfilePath: $filePath\ncommentName: $commentName\nisAttachment: $isAttachment\n";
					array_push($review_files,array('fileName' => $fileName, 'filePath' => $filePath, 'commentName' => $commentName, 'isAttachment' => $isAttachment, 'reviewNum' => $review_num, 'timestamp' => $timestamp, 'dateUploaded' => $dateUploaded, 'fileType' => $fileType, 'fileCategory' => $fileCategory, 'fileSize' => $fileSize, 'fileExt' => $fileExt));
											
					$num_review_files++;

					//echo "Dir Name = $grandparentPath$dir\nCount of ReviewFiles: $num_review_files\n";				

				} //end foreach($fullFileListing as $articleFileArray)
				echo "\ncurrImportFile: $currImportFile\n";
				echo "\nDir Name = $grandparentPath$dir\nCount of ReviewFiles: $num_review_files\n";
				print_r($review_files);		
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// GET DISTINCT REVIEW IDS FOR THIS ARTICLE
				//
				$distinctReviewNums = array();
				if($num_review_files > 0) {
					//echo "\nDir Name = $grandparentPath$dir\nCount of ReviewFiles: $num_review_files\n";
					//print_r($review_files);
					foreach($review_files as $reviewFile) {
						$currReviewNum = $reviewFile['reviewNum'];
						if(array_search($currReviewNum,$distinctReviewNums) === FALSE) {					
							array_push($distinctReviewNums, $currReviewNum);
						}
						
					}
					//echo "reviewIds array:\n";
					//print_r($distinctReviewNums);
				}				
						

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// ITERATE THROUGH FILES
				//	
				foreach($distinctReviewNums as $reviewNum) {
					//echo "\nreviewNum: $reviewNum\n";
					$reviewId = 0;
					$reviewIdQueryRun = 0;
					
					//iterate through review_file array. act on files that have this review number.
					foreach($review_files as $currReviewArray) {
						if($currReviewArray['reviewNum'] == $reviewNum) {
												
							$fileName = $currReviewArray['fileName'];
							$filePath = $currReviewArray['filePath'];
							$commentName = $currReviewArray['commentName'];
							$isAttachment = $currReviewArray['isAttachment'];
							$reviewNum = $currReviewArray['reviewNum'];
							$timestamp = $currReviewArray['timestamp'];
							$dateUploaded = $currReviewArray['dateUploaded'];
							$fileType = $currReviewArray['fileType'];
							$fileCategory = $currReviewArray['fileCategory'];
							$fileSize = $currReviewArray['fileSize'];
							$fileExt = $currReviewArray['fileExt'];
							
							$commentType = 1;
							$roleId = 4096;					
							$revision = 1;
							$round = 1;
							
							//if($isAttachment) {
							//	echo "currReviewArray:\n";
							//	print_r($currReviewArray);
							//}
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// SET VIEWABLE
							//
							if($fileType == 'text/plain') {
								if($fileCategory == 'cover') {
									$viewable = 0;
								} elseif($fileCategory == 'main') {
									$viewable = 1;
								} else {
									//error?
								}
							} else {
								$viewable = 0;
							}
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// QUERY OJS DB FOR REVIEW ID
							//
							if(!$reviewIdQueryRun) {
								$reviewIdQuery = "SELECT review_id FROM review_assignments WHERE submission_id = $article_id AND date_completed = '$dateUploaded'";
								$result = mysql_query($reviewIdQuery);
								$reviewIdQueryRun = 1;
								if(!$result) {
									die("\nInvalid query: " . mysql_error() . "\n");
								}
								if (mysql_num_rows($result)==0) {
									echo "\n**ALERT** No review_assignments record exists for article_id $article_id and date_completed $dateUploaded.\narticle_comments record will be created without assoc_id\nDir Name = $grandparentPath$dir\nQuery: $reviewIdQuery\n";
									//die("\n**ERROR** No review_assignments record exists for this article_id and date_completed.\nDir Name = $grandparentPath$dir\nQuery: $reviewIdQuery\n");
								} else {
									$reviewId = mysql_result($result,0);
								}
							}
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// GET TEXT OF REVIEW IF TXT FILE
							//
							$reviewText = '';
							//OLD $filePath = $grandparentPath . $dir . '/' . $fileName;
							if ($fileType == 'text/plain') {
								if(file_exists($filePath)) {
									//open file
									//$handle = fopen($filePath, "r");
									//echo "\nOpened handle to $filePath successfully\n";
									$reviewText = file_get_contents($filePath);
									//echo "\nreviewText: $reviewText\n";
									//fclose($handle);
									
									if($reviewText == '') {
										echo "\n**ALERT** Txt file $filePath was empty.\n";
									}									
								} else {
									die("**ERROR** Txt file $filePath does not exist.\n");
								}
							} 
							
							if($fileType == 'text/plain') {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
								//
								// CHECK FOR DUPLICATE RECORD BEFORE IMPORTING
								//
								$commentDupQuery = "SELECT * FROM article_comments where comment_type = $commentType AND article_id = $article_id AND assoc_id = $reviewId AND comments = '" . mysql_real_escape_string($reviewText) . "'";
								$dupCheckResult = mysql_query($commentDupQuery);
								if(!$dupCheckResult) {
									die("\nInvalid query: " . mysql_error() . "\n");
								}
								if(mysql_num_rows($dupCheckResult) > 0) {
									$dupCtr++;
								} else {							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									//IMPORT TXT FILES INTO article_comments
									//
									$articleCommentsQuery = "INSERT INTO article_comments (comment_type, role_id, article_id, assoc_id, author_id, date_posted, comment_title, comments, viewable) ";
									$articleCommentsQuery .= "VALUES ($commentType, $roleId, $article_id, $reviewId, $authorId, '$dateUploaded', '" . mysql_real_escape_string($articleTitle) . "', '" . mysql_real_escape_string($reviewText) . "', $viewable)";
									echo "\narticleCommentsQuery: $articleCommentsQuery\n";
									$result = mysql_query($articleCommentsQuery);
									if(!$result) {
										die("\nInvalid query: " . mysql_error() . "\n");
									} else {
										$commentsCreated++;
									}		
								}
							} 
							
							//if($fileType == 'pdf' OR ($fileType == 'text/plain' AND $viewable)) {
							if(($fileType != 'text/plain' && $fileType != 'unknown') || ($fileType == 'text/plain' && $viewable)) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
								//
								// IMPORT TXT AND PDF FILES INTO article_files
								//
								//files are uploaded to article_files table as type 'submission/review' with new file_id. If it's for the author too, then viewable = 1, otherwise NULL.
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
								//
								// SET METADATA VALUES
								//	
								$file_id = 0; 
								$revision = 1;
								$type = 'submission/review';
								$typeAbbr = 'RV';
								/***
								if($fileType == 'pdf') {
									$extension = 'pdf';
								} elseif($fileType == 'text/plain') {
									$extension = 'txt';
								}
								***/
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
								//
								// CHECK FOR DUPLICATES BEFORE CREATING RECORD
								//
								$dup_check_stmt = "SELECT file_id FROM article_files ";
								$dup_check_stmt .= "WHERE article_id = $article_id AND type = '$type' AND original_file_name = '$fileName' AND date_uploaded = '$dateUploaded'";
		
								$dup_check_result = mysql_query($dup_check_stmt);
								if(mysql_num_rows($dup_check_result) > 0) {
									//echo "Current Import File: $filePath\n";
									//echo "dup_check_stmt: $dup_check_stmt\n";
									$file_id = mysql_result($dup_check_result,0);	
									$dupCtr++;
									
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// ENTER FILE ID IN REVIEW_ASSIGNMENTS TABLE AS REVIEWER_FILE_ID (EVEN IF THIS IS A DUP, JUST IN CASE)
									//
									if($reviewId) {
										$updateRevFileIdQuery = "UPDATE review_assignments SET reviewer_file_id = $file_id WHERE review_id = $reviewId";
										//echo "\nupdateSubFileIdQuery: $updateSubFileIdQuery\n";
										$result = mysql_query($updateRevFileIdQuery);
										if(!$result) {
											die("\nInvalid query: " . mysql_error() . "\n");
										}
									}
								} else {

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// CREATE DB RECORD FOR FILE
									//								
									$insertArticleFileQuery = "
									INSERT INTO article_files
									(revision, article_id, file_name, file_type, file_size, original_file_name, type, viewable, date_uploaded, date_modified, round)
									VALUES
									($revision, $article_id, '$fileName', '$fileType', $fileSize, '" . mysql_real_escape_string($fileName) . "', '$type', $viewable, '$dateUploaded', '$dateUploaded', $round)								
									";
									
									echo "     insertArticleFileQuery: $insertArticleFileQuery\n";
	
									$result = mysql_query($insertArticleFileQuery);
									if(!$result) {
										die("\nInvalid query: " . mysql_error() . "\n");
									} else {
										$file_id = mysql_insert_id();
										$recsCreated++;
										$articlesCreated++;
									}
									
									//echo "\nfile_id: $file_id\n";
									
									if($file_id == 0) {
										die("ERROR: Could not get file_id after INSERT INTO article_files for file: $filePath");
									}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									// 
									// RENAME FILE FOR OJS
									//		
									//$newFileName = $article_id . '-' . $file_id . '-' . $revision . '-' . $typeAbbr . '.' . $extension;
									$newFileName = $article_id . '-' . $file_id . '-' . $revision . '-' . $typeAbbr . $fileExt;
									//echo "Current Import File: $filePath\n";
									//echo "New File name: $newFileName\n";
									
									$updateFileNameQuery = "UPDATE article_files SET file_name = '$newFileName' WHERE file_id = $file_id AND article_id = $article_id AND type = '$type'";
									echo "\nupdateFileNameQuery: $updateFileNameQuery\n";
									$result = mysql_query($updateFileNameQuery);
									if(!$result) {
										die("\nInvalid query: " . mysql_error() . "\n");
									}
									
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// ENTER FILE ID IN REVIEW_ASSIGNMENTS TABLE AS REVIEWER_FILE_ID
									//
									if($reviewId) {
										$updateRevFileIdQuery = "UPDATE review_assignments SET reviewer_file_id = $file_id WHERE review_id = $reviewId";
										//echo "\nupdateSubFileIdQuery: $updateSubFileIdQuery\n";
										$result = mysql_query($updateRevFileIdQuery);
										if(!$result) {
											die("\nInvalid query: " . mysql_error() . "\n");
										}
									}
								
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// UPLOAD FILE TO FILESYSTEM
									//	
									$uploadPath = $baseUploadDir . '/journals/' . $journalId . '/articles/' . $article_id . '/' . $type . '/';
									//echo "Current Import File: $filePath\n";	
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

									echo "\nfilePath: $filePath\nCopying to: $uploadPath$newFileName\n";
									copy($filePath,$uploadPath.$newFileName);
								}							
							}
						}
					}
				}
			}//end if(substr($dir,0,1) != '.')
		}//end foreach($parentDirs as $dir)
	} //end foreach ($importParentDir as $dir)
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of review records that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	}
	
	echo "\nText reviews imported: $commentsCreated\n";
	echo "\nArticle files uploaded: $articlesCreated\n";
	echo "\nIMPORT OF REVIEWS FOR $unpubString ARTICLES FINISHED.\n\n";	
	
} //end function import_reviews

mysql_close($conn);
	
?>