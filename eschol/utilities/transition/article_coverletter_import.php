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
import_coverletters($importHome,$importParentDir,1,$baseUploadDir); //unpublished
import_coverletters($importHome,$importParentDir,0,$baseUploadDir); //published

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 
// Import cover.[fileType].[timestamp] files from bepress data into OJS
//
function import_coverletters($importHome,$importParentDir,$unpublished,$baseUploadDir) {
	
	$articleFilesImported = 0;
	$commentsToEdImported = 0;
	$notesImported = 0;
	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// print what we're doing
	//
	$unpubString = $unpublished ? "UNPUBLISHED" : "PUBLISHED";
	echo "\n**** IMPORTING $unpubString COVER LETTERS ****\n";
	
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
		
		foreach($journalDirs as $dir) {	
			$eschol_articleid = '';
			if(substr($dir,0,1) != '.') {	
				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				//
				// get OJS article ID
				//
				if($unpublished) $eschol_articleid = $eschol_articleid_begin . '_' . $dir;
				$article_id = get_article_id($unpublished,$eschol_articleid,$dir);
				//echo "\nOJSarticleID: $article_id\n";
				if(!$article_id) {
					echo "\nALERT: Could not determine article ID for dir: $dir\n";
					continue;
				}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				//
				// PROCESS cover.[fileType].[timestamp] files
				//
				
				$articleFileListing = array();

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				//
				// GET LIST OF FILES
				//				
				$fullDirPath = '';
				if($unpublished) {
					$fullDirPath = $importHome . $journalPath . 'unpublished/' . $dir;		
				} else {
					$fullDirPath = $importHome . $grandparentPath . $dir;
				}	
				$articleFileListing = scandir($fullDirPath);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				//
				// ITERATE THROUGH FILES
				//					
				foreach($articleFileListing as $fileName) {
					$fileNameElements = array();
					$fileType = '';
					$timestamp = '';
					$dateUploaded = '';
					
					if(substr($fileName,0,6) != 'cover.') continue;
					
					$filePath = $fullDirPath . '/' . $fileName;
					//echo "\nfilePath: $filePath\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					// EXPLODE FILE NAME
					//
					$fileNameElements = explode('.',$fileName);
					if(count($fileNameElements) != 3) {
						"ALERT: cover fileName anomaly\nfilePath: $filePath\n";
					}	
					//echo "\nfileNameElements: $fileNameElements\n";
					//print_r($fileNameElements);
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					// GET FILE TYPE & EXTENSION
					//
					$fileType = $fileNameElements[1];
					if($fileType == 'txt') {
						$fileType = 'text/plain';
						$extension = 'txt';
					} elseif($fileType == 'upload') {
						$fileType = 'application/msword';
						$extension = 'docx';
					} else {
						echo "\n**ALERT** File Type not recognized. \nfilePath: $filePath\nfileType: $fileType\n";
					}
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					// GET DATE UPLOADED
					//
					$timestamp = $fileNameElements[2];
					date_default_timezone_set('America/Los_Angeles');
					$dateUploaded = date('Y-m-d H:i:s',$timestamp);					

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					// GET FILE SIZE
					//
					$fileSize = sprintf("%u",filesize($filePath));
					
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					// GET ASSOCIATED ARTICLE FILE ID
					//
					$assocFileId = 0;
					$assocFileName = '';
					$assocFileType = '';
					$articleFileQuery = "SELECT file_id, file_name, type FROM article_files ";
					$articleFileQuery .= "WHERE article_id = $article_id AND date_uploaded = '$dateUploaded' AND original_file_name NOT LIKE 'cover.%'";
					//echo "\narticleFileQuery: $articleFileQuery\n";
					$articleFileResult = mysql_query($articleFileQuery);
					if($articleFileResult === FALSE) {
						die("\nInvalid query: " . mysql_error() . "\narticleFileQuery: $articleFileQuery\n");
					} elseif(!$articleFileResult) {
						echo "ALERT: could not find article.file_id for file: $filePath\narticleFileQuery: $articleFileQuery\n";
					} else {
						while($articleFileRec = mysql_fetch_array($articleFileResult)) {
							$assocFileId = $articleFileRec[0];
							$assocFileName = $articleFileRec[1];
							$assocFileType = $articleFileRec[2];
							if($assocFileId == 0) {
								echo "ALERT: could not find article.file_id for file: $filePath\narticleFileQuery: $articleFileQuery\n";
							}
						}
					}
					
					//echo "\nassocFileId: $assocFileId\nassocFileType:$assocFileType\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
					//
					// BEGIN COVER LETTER IMPORT
					//						
					if($assocFileId) {	
						//echo "\n----------------------------------------------------------------------------------\n";
						//echo "fileType: $fileType\n";

						$newFileName = '';	
						$newFileId = 0;
						$newType = '';	
						
						if($fileType == 'application/msword') {
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//
							// UPLOAD FILE TO article_files
							//
							if($assocFileType == 'submission/original') {
								$revision = 1;
								$newType = 'supp';
								$typeAbbr = 'SP';
							} elseif($assocFileType == 'submission/editor' OR $assocFileType == 'submission/review') {
								$revision = 1;
								$newType = 'note';
								$typeAbbr = 'NT';	
							}
					
							if($newType != '') {
	
								//echo "\n----------------------------------------------------------------------------------\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
								//
								// CHECK FOR DUPLICATES BEFORE CREATING article_files RECORD
								//
								$articleFileDupQuery = "SELECT * FROM article_files WHERE article_id = $article_id AND original_file_name = '" . mysql_real_escape_string($fileName) . "' AND date_uploaded = '$dateUploaded'";
								//echo "\narticleFileDupQuery: $articleFileDupQuery\n";
								
								$articleFileDupResult = mysql_query($articleFileDupQuery);
								if($articleFileDupResult === FALSE) {
									die("\nInvalid query: " . mysql_error() . "\narticleFileDupQuery: $articleFileDupQuery\n");
								}
								
								if(mysql_num_rows($articleFileDupResult) > 0) {
									$dupCtr++;
								} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// CREATE DATABASE RECORD
									// Need to do this first to get newFileId
									//
									$insertArticleFileQuery = "INSERT INTO article_files ";
									$insertArticleFileQuery .= "(revision, article_id, file_name, file_type, file_size, original_file_name, type, date_uploaded, date_modified, round) ";
									$insertArticleFileQuery .= "VALUES ($revision, $article_id, '" . mysql_real_escape_string($fileName) . "', '$fileType', $fileSize, '" . mysql_real_escape_string($fileName) . "', '$newType', '$dateUploaded', '$dateUploaded', 1)";							
									
									//echo "\ninsertArticleFileQuery: $insertArticleFileQuery\n";
									
									$insertArticleFileResult = mysql_query($insertArticleFileQuery);
									if(!$insertArticleFileResult) {
										die("\nInvalid query: " . mysql_error() . "\n");
									} else {
										$newFileId = mysql_insert_id();
										$articleFilesImported++;
									}
									
									//echo "\nnewFileId: $newFileId\n";
									
									if($newFileId == 0) {
										die("ERROR: Could not get newFileId after INSERT INTO article_files for file: $filePath");
									}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									// 
									// RENAME FILE FOR OJS
									//								
									$newFileName = $article_id . '-' . $newFileId . '-' . $revision . '-' . $typeAbbr . '.' . $extension;
									//echo "Current Import File: $filePath\n";
									//echo "New File name: $newFileName\n";
									
									$updateFileNameQuery = "UPDATE article_files SET file_name = '$newFileName' WHERE file_id = $newFileId AND article_id = $article_id AND type = '$newType'";
									//echo "\nupdateFileNameQuery: $updateFileNameQuery\n";
									
									$updateFileNameResult = mysql_query($updateFileNameQuery);
									if(!$updateFileNameResult) {
										die("\nInvalid query: " . mysql_error() . "\n");
									}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// UPLOAD FILE TO FILESYSTEM
									//	
									$uploadPath = $baseUploadDir . '/journals/' . $journalId . '/articles/' . $article_id . '/' . $newType . '/';
									//echo "\nuploadPath: $uploadPath\n";
									
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
									//echo "filePath: $filePath\n";
									copy($filePath,$uploadPath.$newFileName);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
									//
									// ADD NEW RECORD TO SUPP FILE TABLE
									//
									if($typeAbbr=='SP') {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
										//
										// CHECK FOR DUPLICATES BEFORE CREATING SUPP FILE REC
										//
										$suppFileDupQuery = "SELECT * FROM article_supplementary_files WHERE file_id = $newFileId AND article_id = $article_id AND date_submitted = '$dateUploaded'";
										
										$suppFileDupResult = mysql_query($suppFileDupQuery);
										if($suppFileDupResult === FALSE) {
											die("\nInvalid query: " . mysql_error() . "\nsuppFileDupQuery: $suppFileDupQuery\n");
										} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
											//
											// CREATE SUPP FILE REC
											//	
											$suppFileQuery = "INSERT INTO article_supplementary_files ";
											$suppFileQuery .= "(file_id, article_id, type, show_reviewers, date_submitted) ";
											$suppFileQuery .= "VALUES ($newFileId, $article_id, 'Cover Letter', 0, '$dateUploaded')";
											//echo "\nsuppFileQuery: $suppFileQuery\n";
											
											$suppFileResult = mysql_query($suppFileQuery);
											if($suppFileResult === FALSE) {
												die("\nInvalid query: " . mysql_error() . "\nsuppFileQuery: $suppFileQuery\n");
											}
										}
									} //end if($typeAbbr=='SP') {							
								} // end if not dup
							} // end if($newType != '')
						} // end if($fileType == 'application/msword')

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						// END IMPORT OF NATIVE FILES
						//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						// BEGIN IMPORT OF COMMENTS/NOTES FOR ALL FILES
						//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						// GET COMMENT TEXT
						//	
						$commentText = '';
						if($fileType == 'application/msword') {
							if($typeAbbr=='SP') {
								$commentText = "[bp coverletter] Cover letter for original author's submission imported by eScholarship as Supplementary File $newFileName\n";
							} elseif($typeAbbr=='NT') {
								$commentText = "Cover Letter for article version $assocFileName was imported by eScholarship as an attachment to this Submission Note (file name: $fileName)\n";
							} else {
								echo "ALERT: unrecognized typeAbbr: $typeAbbr\nfilePath: $filePath\n";
							}
						} elseif($fileType == 'text/plain') {
							if(file_exists($filePath)) {
								$commentText = "[bp coverletter]\n" . file_get_contents($filePath);
								//echo "\ncommentText after file_get_contents: $commentText\n";
								
								if($commentText == '') {
									echo "\n**ALERT** Txt file $filePath was empty.\n";
								}									
							} else {
								die("**ERROR** Txt file $filePath does not exist.\n");
							}								
						} else {
							echo "ALERT: unrecognized fileType: $fileType\nfilePath: $filePath\n";
						}
						
						//echo "\ncommentText: $commentText\n";	
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						// POPULATE articles.comments_to_ed
						//
						//echo "\nassocFileType: $assocFileType\n";
						if($assocFileType == 'submission/original') {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
							//
							//  CHECK FOR DUPLICATES BEFORE POPULATING articles.comments_to_ed
							//	
							$coverLetterDupQuery = "SELECT * FROM articles WHERE article_id = $article_id AND comments_to_ed != '' AND comments_to_ed IS NOT NULL AND comments_to_ed NOT LIKE '%" . mysql_real_escape_string($commentText) . "%'";
							
							//echo "\ncoverLetterDupQuery: $coverLetterDupQuery\n";
							
							$coverLetterDupResult = mysql_query($coverLetterDupQuery);
							if($coverLetterDupResult === FALSE) {
								die("\nInvalid query: " . mysql_error() . "\ncoverLetterDupQuery: $coverLetterDupQuery\n");
							}
							
							if(mysql_num_rows($coverLetterDupResult) > 0) {
								$dupCtr++;
							} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
								//
								// UPDATE articles.comments_to_ed FIELD
								//	
								$commentsImportQuery = "UPDATE articles SET ";
								$commentsImportQuery .= "comments_to_ed = CONCAT(COALESCE(comments_to_ed, ''),IF(comments_to_ed != '','\n',''),'" . mysql_real_escape_string($commentText) . "') ";
								$commentsImportQuery .= "WHERE article_id = $article_id";
								//echo "\ncommentsImportQuery: $commentsImportQuery\n";
								$commentsImportResult = mysql_query($commentsImportQuery);
								if($commentsImportResult === FALSE) {
									die("\nInvalid query: " . mysql_error() . "\ncommentsImportQuery\n$commentsImportQuery\n");
								} else {
									$commentsToEdImported++;
								}
							}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
						//
						// CREATE notes RECORD
						//
						} elseif($assocFileType == 'submission/editor' OR $assocFileType == 'submission/review') {
							$title = "[bp coverletter] Cover Letter for $assocFileName";
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
							//
							// CHECK FOR DUPLICATES BEFORE CREATING notes RECORD
							//							
							$notesDupQuery = "SELECT * FROM notes ";
							$notesDupQuery .= "WHERE assoc_id = $article_id AND date_created = '$dateUploaded' AND title = '$title' AND contents = '" . mysql_real_escape_string($commentText) . "' AND context_id = $journalId"; 
							$notesDupResult = mysql_query($notesDupQuery);
							if($notesDupResult === FALSE) {
								die("\nInvalid query: " . mysql_error() . "\nnotesDupQuery: $notesDupQuery\n");
							}
							
							if(mysql_num_rows($notesDupResult) > 0) {
								$dupCtr++;
							} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
								//
								// INSERT notes RECORD
								//
								$notesImportQuery = "INSERT INTO notes ";
								$notesImportQuery .= "(assoc_type, assoc_id, user_id, date_created, date_modified, title, file_id, context_id, contents) ";
								$notesImportQuery .= "VALUES (257, $article_id, 1, '$dateUploaded', '$dateUploaded', '$title', $newFileId, $journalId, '" . mysql_real_escape_string($commentText) . "')";
								//echo "\nnotesImportQuery: $notesImportQuery\n";
								
								$notesImportResult = mysql_query($notesImportQuery);
								if($notesImportResult === FALSE) {
									die("\nInvalid query: " . mysql_error() . "\nnotesImportQuery\n$notesImportQuery\n");
								} else {
									$notesImported++;
								}
							}
						} else {
							echo "\n**ALERT** Type not recognized. Not importing.\nfilePath: $filePath\nassocFileType: $assocFileType\n";
						} 
					} // end if($assocFileId)
				} //end foreach($articleFileListing as $fileName)
			} //end if(substr($dir,0,1) != '.')
		} //end foreach($journalDirs as $dir)
	} //end foreach($importParentDir as $currJournalInfo)
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nDuplicates (no record created): $dupCtr\n"; 
	}
	echo "\narticles.comments_to_ed populated: $commentsToEdImported\n";
	echo "\nnotes created: $notesImported\n";
	echo "\narticle_files records created: $articleFilesImported\n";
	echo "\nIMPORT OF COVER LETTERS FOR $unpubString ARTICLES FINISHED.\n\n";
}

mysql_close($conn);
?>