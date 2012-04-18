#!/usr/bin/php
<?php

require_once './import_journals.php';
require_once './import_set_parameters.php';
require_once './ojs_db_connect.php';

$baseFileDir = '/apps/subi/ojs/files/journals';
$unknownFileDescs = array();
$filesToBeFixed = 0;
$filesNotFound = 0;
$filesRenamedOnServer = 0;
$articleFilesUpdated = 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// GET LIST OF SUBMISSION FILES IMPORTED DURING TRANSITION
//
$importedArticleFiles = array();
$importedArticleFilesQuery = "SELECT file_id, article_files.article_id, file_name, file_type, original_file_name, type, journal_id FROM article_files ";
$importedArticleFilesQuery .= "LEFT OUTER JOIN articles ON article_files.article_id = articles.article_id ";
$importedArticleFilesQuery .= "WHERE original_file_name LIKE 'text.native%' AND type LIKE 'submission/%' AND file_type = 'application/msword' ";
//echo "\nimportedArticleFilesQuery: $importedArticleFilesQuery\n";
$importedArticleFilesResult = mysql_query($importedArticleFilesQuery);
if($importedArticleFilesResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nimportedArticleFilesQuery: $importedArticleFilesQuery\n");
} else {
	while ($articleFilesRow = mysql_fetch_array($importedArticleFilesResult)) {
		$fileId = $articleFilesRow[0];
		$articleId = $articleFilesRow[1];
		$fileName = $articleFilesRow[2];
		$fileType = $articleFilesRow[3];
		$originalFileName = $articleFilesRow[4];
		$type = $articleFilesRow[5];
		$journalId = $articleFilesRow[6];
		$importedArticleFiles[] = array('fileId' => $fileId, 'articleId' => $articleId, 'fileName' => $fileName, 'fileType' => $fileType, 'originalFileName' => $originalFileName, 'type' => $type, 'journalId' => $journalId);
	}	
}

//echo "\nimportedArticleFiles: \n";
//print_r($importedArticleFiles);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// LOOP THROUGH ARTICLE FILES
//
foreach($importedArticleFiles as $articleFile) {
	$currFileId = $articleFile['fileId'];
	$articleId = $articleFile['articleId'];
	$currFileName = $articleFile['fileName'];
	$currFileType = $articleFile['fileType'];
	$currOriginalFileName = $articleFile['originalFileName'];
	$currType = $articleFile['type'];
	$currJournalId = $articleFile['journalId'];
	
	$fileDir = "$baseFileDir/$currJournalId/articles/$articleId/$currType/";
	$filePath = $fileDir . $currFileName;
	
	if(file_exists($filePath)) {	
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// USE UNIX 'FILE' COMMAND TO GET INFO ABOUT FILE
		//
		$unixFileCommand = "file $filePath";
		//echo "\nunixFileCommand: $unixFileCommand\n";
		$fileCommandOutput = exec($unixFileCommand);
		//echo "\nfileCommandOutput: $fileCommandOutput\n";
		$explodedResult = explode(":",$fileCommandOutput);
		//echo "\nexplodedResult: \n";
		//print_r($explodedResult);
		$fileTypeDesc = trim($explodedResult[1]); 
		//echo "\nfileTypeDesc: $fileTypeDesc\n";
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		// SET MIME TYPE AND EXT
		//
		$explodedFileTypeDesc = explode(",",$fileTypeDesc);
		$fileTypeBase = trim($explodedFileTypeDesc[0]);
		if(strpos($fileTypeBase,"DOS EPS Binary File Postscript") !== FALSE) {
			$fileTypeBase = "DOS EPS Binary File Postscript";
		}
		switch($fileTypeBase) {
			case "Microsoft Office Document Microsoft Word Document":
			case "Microsoft Office Document":
			case "Microsoft Word Document":
				$nativeMimeType = "application/msword";
				$nativeFileExt = ".doc";
				break;
			case "Zip archive data":
				$nativeMimeType = "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
				$nativeFileExt = ".docx";
				break;
			case "Rich Text Format data":
				$nativeMimeType = "text/rtf";
				$nativeFileExt = ".rtf";
				break;		
			case "PDF document":
				$nativeMimeType = "application/pdf";
				$nativeFileExt = ".pdf";
				break;	
			case "(Corel/WP)":
				$nativeMimeType = "application/wordperfect";
				$nativeFileExt = ".wpd";
				break;
			case "OpenDocument Text":
				$nativeMimeType = "application/vnd.oasis.opendocument.text";
				$nativeFileExt = ".odt";
				break;
			case "LaTeX 2e document text":
				$nativeMimeType = "application/x-latex";
				$nativeFileExt = ".tex";
				break;
			case "Non-ISO extended-ASCII C++ program text":
				$nativeMimeType = "text/x-c";
				$nativeFileExt = ".cpp";
				break;
			case "TIFF image data":
				$nativeMimeType = "image/tiff";
				$nativeFileExt = ".tiff";
				break;
			case "ISO Media":
				$nativeMimeType = "video/quicktime";
				$nativeFileExt = ".mov";
				break;
			case "JPEG image data":
				$nativeMimeType = "image/jpeg";
				$nativeFileExt = ".jpg";
				break;
			case "PNG image data":
				$nativeMimeType = "image/png";
				$nativeFileExt = ".png";
				break;
			case "PC bitmap data":
				$nativeMimeType = "image/bmp";
				$nativeFileExt = ".bmp";
				break;
			case "ASCII English text":
			case "ISO-8859 English text":
			case "Little-endian UTF-16 Unicode English character data":
			case "empty":
				$nativeMimeType = "text/plain";
				$nativeFileExt = ".txt";
				break;
			case "PostScript document text conforming DSC level 3.1":
				$nativeMimeType = "application/postscript";
				$nativeFileExt = ".ps";
				break;
			case "HTML document text":
				$nativeMimeType = "text/html";
				$nativeFileExt = ".html";
				break;
			case "DOS EPS Binary File Postscript":
				$nativeMimeType = "application/postscript";
				$nativeFileExt = ".eps";
				break;
			case "data":
			case "MacBinary III data with surprising version number":
				$nativeMimeType = "";
				$nativeFileExt = ".dat";
				break;
			default:
				$nativeMimeType = "unknown";
				$nativeFileExt = "";
		}	
		
		//echo "\nnativeMimeType: $nativeMimeType\nnativeFileExt: $nativeFileExt\n";
		
		if($nativeMimeType != "unknown" && $nativeFileExt != ".docx") {
			$filesToBeFixed++;
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// SET NEW FILE NAME
			//		
			$newFileName = "";
			$fileNameElements = array();
			$fileNameElements = explode(".",$currFileName);
			$baseFileName = trim($fileNameElements[0]);
			$newFileName = "$baseFileName$nativeFileExt";
			//echo "\nold fileName: $currFileName\nnewFileName: $newFileName\ncurrent file_type: $fileType\nnewMimeType: $nativeMimeType\n";
			
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// UPDATE FILE NAME ON SERVER
			//	
			$newFilePath = "";
			$newFilePath = $fileDir . $newFileName;
			$fileMoveCommand = "mv $filePath $newFilePath";
			echo "fileMoveCommand: $fileMoveCommand\n";
			exec($fileMoveCommand);
			$filesRenamedOnServer++;
			
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// UPDATE FILE NAME AND MIME TYPE IN DATABASE
			//
			$updateArticleFileQuery = "UPDATE article_files ";
			$updateArticleFileQuery .= "SET file_name = '$newFileName'";
			if($nativeMimeType != "application/msword") {
				$updateArticleFileQuery .= ", file_type = '$nativeMimeType' ";
			} else {
				$updateArticleFileQuery .= " ";
			}
			$updateArticleFileQuery .= "WHERE file_id = $currFileId AND file_name = '$currFileName' AND type = '$currType'";
			echo "\nupdateArticleFileQuery: $updateArticleFileQuery\n";
			$updateArticleFileResult = mysql_query($updateArticleFileQuery);
			if($updateArticleFileResult === FALSE) {
				die("\nInvalid query: " . mysql_error() . "\nupdateArticleFileQuery: $updateArticleFileQuery\n");
			} else {
				$articleFilesUpdated++;
			}
		} elseif($nativeMimeType == "unknown") {
			if(!in_array($fileTypeBase, $unknownFileDescs)) {
				$unknownFileDescs[] = $fileTypeBase;
			}
		}
	} else {
		//echo "\nFile does not exist: $filePath\n";
		$filesNotFound++;
	}
}

if(count($unknownFileDescs)) {
	echo "\nunknownFileDescs: \n";
	print_r($unknownFileDescs);
}

mysql_close($conn);

echo "\nFiles to be fixed: $filesToBeFixed\n";
echo "\nFiles not found: $filesNotFound\n";
echo "\nFiles renamed on server: $filesRenamedOnServer\n";
echo "\narticle_files recs updated: $articleFilesUpdated\n";
echo "\nFILE TYPE CLEANUP FINISHED.\n\n";
?>