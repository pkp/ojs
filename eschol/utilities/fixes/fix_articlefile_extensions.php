#!/usr/bin/php
<?php

require_once './import_set_parameters.php';
require './ojs_db_connect.php';
$extensionsFixed = 0;
		
//
// these are type 'public' and 'supp' files only, it looks like 
// select * from article_files where original_file_name like '%origin=ojsimport' OR original_file_name like '%origin=repeccitec';
//
$typeMapping = array();
$typeMapping[] = array('fileType' => 'application/pdf','ext' => 'pdf');
$typeMapping[] = array('fileType' => '','ext' => '');

$importedFilesQuery = "SELECT af.file_id, af.file_name, af.file_type, af.original_file_name, af.type, af.article_id, a.journal_id FROM article_files AS af ";
$importedFilesQuery .= "lEFT JOIN articles AS a ON af.article_id = a.article_id ";
$importedFilesQuery .= "WHERE af.original_file_name like '%origin=ojsimport' OR af.original_file_name like '%origin=repeccitec'";
$importedFilesResult = mysql_query($importedFilesQuery);
if($importedFilesResult === FALSE) {
	die("\nInvalid query: " . mysql_error() . "\nimportedFilesQuery: $importedFilesQuery\n");
} elseif(!$importedFilesResult) {
	echo "No records found.\nimportedFilesQuery: $importedFilesQuery\n";
} else {
	while($articleFileRec = mysql_fetch_array($importedFilesResult)) {
		//echo "\n--------------------------------------------------------------------------\n";
		//print_r($articleFileRec);
		$fileId = 0;
		$fileName = '';
		$fileType = '';
		$origFileNameLong = '';
		$type = '';
		$articleId = 0;
		$journalId = 0;
		
		$fileId = $articleFileRec[0];
		$fileName = $articleFileRec[1];
		$fileType = $articleFileRec[2];
		$origFileNameLong = $articleFileRec[3];
		$type = $articleFileRec[4];
		$articleId = $articleFileRec[5];
		$journalId = $articleFileRec[6];

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		// GET CURRENT FILENAME ELEMENTS
		//	
		$currMainFileName = '';
		$currFileExt = '';
		$currFileNameElements = explode('.',$fileName);
		$currMainFileName = $currFileNameElements[0];
		$currFileExt = $currFileNameElements[1];
		
		//echo "\ncurrMainFileName: $currMainFileName\n";
		//echo "\ncurrFileExt: $currFileExt\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		// GET ORIGINAL FILE EXTENSION
		//	
		$origFileExt = '';
		$origFileNameLongElements = explode('?',$origFileNameLong);
		$origFileName = $origFileNameLongElements[0];
		//echo "\norigFileName: $origFileName\n";
		$origFileNameElements = explode('.',$origFileName);
		$origFileExtIndex = count($origFileNameElements) - 1;
		$origFileExt = strtolower($origFileNameElements[$origFileExtIndex]);
		
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		// CHECK FOR RECOGNIZED FILE EXTENSION
		//
		if($origFileExt != 'txt' && $origFileExt != 'pdf' && $origFileExt != 'xls' && $origFileExt != 'ai' 
		&& $origFileExt != 'tif' && $origFileExt != 'doc' && $origFileExt != 'jpg' && $origFileExt != 'docx' 
		&& $origFileExt != 'm4v' && $origFileExt != 'mp3' && $origFileExt != '3gp' && $origFileExt != 'mov' 
		&& $origFileExt != 'ppt' && $origFileExt != 'wmv' && $origFileExt != 'jpeg' && $origFileExt != 'tiff'
		&& $origFileExt != 'avi' && $origFileExt != 'mp4' && $origFileExt != 'pdf' && $origFileExt != 'bmp'
		&& $origFileExt != 'csv' && $origFileExt != 'zip' && $origFileExt != 'wav' && $origFileExt != 'jar'
		&& $origFileExt != 'svg' && $origFileExt != 'htm' && $origFileExt != 'gif' && $origFileExt != 'sit'
		&& $origFileExt != 'stm' && $origFileExt != 'hqx' && $origFileExt != 'exe'
		&& $origFileExt != 'png' && $origFileExt != 'sav' && $origFileExt != 'r'
		) {
			die("\n\nERROR: Unrecognized extension: $origFileExt\nfileName: $fileName\norigFileNameLong: $origFileNameLong\nfileId = $fileId\n");
		}

		if($currFileExt != $origFileExt) {	
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
			//
			// GET NEW FILENAME
			//			
			$fixedFileName = '';
			$fixedFileName = $currMainFileName . '.' . $origFileExt;
			
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
			//
			// UPDATE THE FILENAME ON THE FILE SYSTEM
			//
			$uploadPath = "$baseUploadDir/journals/$journalId/articles/$articleId/$type/";
			$filePath = $uploadPath . $fileName;
			$fixedFilePath = $uploadPath . $fixedFileName;
			echo "\n--------------------------------------------------------------------------\n";
			echo "\nuploadPath: $uploadPath\n";
			echo "\nfilePath: $filePath\n";
			echo "\nfixedFilePath: $fixedFilePath\n";
			
			//
			// create dirs and subdirs if necessary
			//
			if(!(file_exists($filePath))) {
				echo "\n***ERROR***: file does not exist: $filePath. Not updating.\n";
			} else {
				rename($filePath,$fixedFilePath);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				//
				// UPDATE THE FILENAME IN THE DATABASE
				//
				$fileNameFixQuery = "UPDATE article_files SET file_name = '$fixedFileName' WHERE file_id = $fileId";
				echo "\nfileNameFixQuery: $fileNameFixQuery\n";
				
				$fileNameFixResult = mysql_query($fileNameFixQuery);
				if($fileNameFixResult === FALSE) {
					die("\nInvalid query: " . mysql_error() . "\nfileNameFixResult: $fileNameFixResult\n");
				} else {
					$extensionsFixed++;
				}
			}	
		}
	}
}


mysql_close($conn);

echo "\nExtensions Fixed: $extensionsFixed\n";

?>