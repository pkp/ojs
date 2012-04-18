#!/usr/bin/php
<?php

require_once './import_set_parameters.php';
require_once './ojs_db_connect.php';

$baseUploadDir = '/apps/subi/ojs/files/journals';
$filesNotFound = 0;
$transitionFilesMissing = 0;
$filesFixed = 0;

ini_set("memory_limit","200M"); //increasing memory limit from default 128M

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// GET LIST OF SUBMISSION FILES IMPORTED DURING TRANSITION
//
$importedArticleFiles = array();
$importedArticleFilesQuery = "SELECT file_id, article_files.article_id, file_name, file_type, original_file_name, type, journal_id, article_settings.setting_value, article_settings.setting_name FROM article_files ";
$importedArticleFilesQuery .= "INNER JOIN articles ON article_files.article_id = articles.article_id ";
$importedArticleFilesQuery .= "LEFT OUTER JOIN article_settings ON article_files.article_id = article_settings.article_id AND (article_settings.setting_name = 'eschol_articleid' OR article_settings.setting_name = 'eschol_submission_path') ";
$importedArticleFilesQuery .= "WHERE original_file_name LIKE 'text.native%' AND type LIKE 'submission/%' AND file_type = 'application/msword' ";
$importedArticleFilesQuery .= "ORDER BY articles.journal_id";
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
		$transitionInfo = $articleFilesRow[7];
		$settingName = $articleFilesRow[8];
		$importedArticleFiles[] = array('fileId' => $fileId, 'articleId' => $articleId, 'fileName' => $fileName, 'fileType' => $fileType, 'originalFileName' => $originalFileName, 'type' => $type, 'journalId' => $journalId, 'transitionInfo' => $transitionInfo, 'settingName' => $settingName);
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
	$currTransitionInfo = $articleFile['transitionInfo'];
	$currSettingName = $articleFile['settingName'];
	
	$fileDir = "$baseUploadDir/$currJournalId/articles/$articleId/$currType/";
	$filePath = $fileDir . $currFileName;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// IF FILE DOESN'T EXIST ON SERVER, CHECK FOR IT IN TRANSITION DATA
	//	
	if(!file_exists($filePath)) {	
		//echo "\nFile does not exist in database:\nfile_id $currFileId\narticleId: $articleId\ncurrJournalId: $currJournalId\ncurrFileName: $currFileName\ncurrOriginalFileName: $currOriginalFileName\ntransitionInfo: $currTransitionInfo\nfilePath: $filePath\n";
		$filesNotFound++;
		$parentDir = "";
		$bpArticleId = 0;
		foreach($importParentDir as $journalInfo) {
			if($journalInfo[2] == $currJournalId) {
				$parentDir = $journalInfo[0];
				break;
			}
		}
		if($currSettingName == 'eschol_articleid') {
			$bpArticleId = array_pop(explode('_',$currTransitionInfo));
			$sourcePath = $importHome . $parentDir . 'unpublished/' . $bpArticleId . '/' . $currOriginalFileName;
		} else {
			$sourcePath = $importHome . $currTransitionInfo . '/' .$currOriginalFileName;
		}
		
		if(file_exists($sourcePath)) {
			if(is_dir($sourcePath)) {
				$sourcePath = $sourcePath . '/0';
				if(!file_exists($sourcePath)) {
					echo "\nFile does not exist in transition data: $sourcePath\n";
				}
			}
			//echo "\nsourcePath: $sourcePath\n";
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			// COPY FILE FROM TRANSITION DATA TO OJS FILES DIRECTORY
			//

			//
			// create dirs and subdirs if necessary
			//
			if(!(file_exists($fileDir))) {
				//echo "\n     Creating Directory!!!\n\n";
				mkdir($fileDir,0755,1);
			}
			
			//
			// copy file to dir
			//
			copy($sourcePath, $filePath);
			echo "\ncp $sourcePath $filePath\n";
			$filesFixed++;
		} else {
			//echo "\nFile does not exist in transition data: $sourcePath\n";
			$transitionFilesMissing++;
		}
	}
}



mysql_close($conn);

echo "\nFiles not found in /apps/subi/ojs/files/: $filesNotFound\n";
echo "\nFiles not found in transition data: $transitionFilesMissing\n";
echo "\nFiles fixed: $filesFixed\n";
?>