#!/usr/bin/php
<?php

//removed db stuff. need to create db $conn
//

$articleFilesUpdated = 0;
$coverLettersFixed = 0;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// FIND ARTICLE FILES THAT NEED FIXING 
//
$badArticleFilesInfo = array();
$badFilesQuery = "SELECT file_id, article_files.article_id, file_name, journal_id FROM article_files ";
$badFilesQuery .= "LEFT JOIN articles ON article_files.article_id = articles.article_id ";
$badFilesQuery .= "WHERE type = 'supp' AND original_file_name LIKE 'cover.upload.%'";
$badFilesResult = mysql_query($badFilesQuery);
if($badFilesResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nbadFilesQuery: $badFilesQuery\n");
} else {
	while($badArticleFileRec = mysql_fetch_array($badFilesResult)) {
		$badArticleFilesInfo[] = $badArticleFileRec;
		$numBadRecs++;
	}	
}


//echo "\nbadArticleFilesInfo:\n";
//print_r($badArticleFilesInfo);
//echo "\nnumBadRecs: $numBadRecs\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
//
// LOOP THROUGH ARTICLES
//
foreach($badArticleFilesInfo as $badFileInfo) {

	$fileId = $badFileInfo['file_id'];
	$articleId = $badFileInfo['article_id'];
	$badFileName = $badFileInfo['file_name'];
	$journalId = $badFileInfo['journal_id'];
	$newFileName = str_replace('-SP.','-NT.',$badFileName);
	//echo "\nnewFileName: $newFileName\n";
	$newType = 'note';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                
        //
        // CHANGE FILENAME ON FILESYSTEM
        //
	$baseUploadDir = '/apps/subi/ojs/files';
	$baseFilesDir = $baseUploadDir . '/journals/' . $journalId . '/articles/' . $articleId;
	$oldFilePath = $baseFilesDir . '/supp/';
	$oldFileFullPath = $oldFilePath . $badFileName;
	$newFilePath = $baseFilesDir . '/' . $newType . '/';
	$newFileFullPath = $newFilePath . $newFileName;
	//echo "\noldFilePath: $oldFilePath\n";
	//echo "\noldFileFullPath: $badFileName\n";
	//echo "\nnewFilePath: $newFilePath\n";
	//echo "\nnewFileFullPath: $newFileName\n";
	if(!(file_exists($newFilePath))) {
		echo "\nWill create new directory $newFilePath\n";
		//mkdir($newFilePath,0755,1);
	}	
	if(file_exists($oldFileFullPath)) {
		//rename($oldFilePath.$badFileName,$newFilePath.$newFileName);
		echo "\nOld file $oldFileFullPath exists.\nWill move to $newFileFullPath.\n";
	} else {
		echo "\n$oldFileFullPath does not exist\n";
	}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                
	//
	// CHANGE FILENAME, TYPE IN article_files TABLE
	//	
	$updateArticleFileQuery = "UPDATE article_files SET file_name = '$newFileName', type = 'note' WHERE file_id = $fileId";
	echo "\nupdateArticleFileQuery: $updateArticleFileQuery\n";
	/***
	$updateArticleFileResult = mysql_query($updateArticleFileQuery);
	if($updateArticleFileResult === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\nupdateArticleFileQuery: $articleFileQuery\n");
	} else {
		$articleFilesUpdated++;
	}
	***/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                
        //
        // CHANGE NOTES IN ARTICLE_FILE TO POINT TO NOTES SECTION
        //

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                
        //
        // CREATE NOTES ARTICLE FOR FILE
        //
	

}


mysql_close($conn);

echo "\narticle_files updated: $articleFilesUpdated\n";


?>
