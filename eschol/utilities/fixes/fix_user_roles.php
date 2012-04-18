#!/usr/bin/php
<?php

require_once './import_journals.php';
require_once './import_set_parameters.php';
require_once './ojs_db_connect.php';

$bpAdminFilename = 'administrators.tsv';
$rolesDeleted = 0;

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// FOR EACH JOURNAL
//
foreach($importParentDir as $currJournalInfo) {
	//echo "\ncurrJournalInfo:";
	//print_r($currJournalInfo);
	$journalPath = $currJournalInfo[0];
	$eschol_articleid_begin = $currJournalInfo[1];
	$journalId = $currJournalInfo[2];

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	//
	// GET LIST OF ADMIN BP IDS FROM ADMINISTRATORS.TSV
	//
	$bpAdminFilepath = $importHome . $journalPath . $bpAdminFilename;
	$bpUserIds = array();
	$bpEmails = array();
	
	$row = 1;
	$numRows = 0;				
	if (file_exists($bpAdminFilepath)) {
		//open file
		$handle = fopen($bpAdminFilepath, "r");
		$numRows = count(file($bpAdminFilepath));
		//echo "\nnum of rows in file: $numRows\n";

		while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
			$bp_userid = 0;
			if($row > 1) {
				$bpUserId = trim($data[0]);
				$bpEmail = trim($data[1]);
				$bpUserIds[] = $bpUserId;
				$bpEmails[] = $bpEmail;
			} //end if($row > 1)
			$row++;
		}
	} else {
		echo "\nadministrators file does not exist!: $bpAdminFilepath\n";
	}
	
	//echo "\nbpUserIds:\n";
	//print_r($bpUserIds);
	
	//echo "\nbpEmails:\n";
	//print_r($bpEmails);

	if (file_exists($bpAdminFilepath)) {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		// GET LIST OF OJS JOURNAL MGRS, EDITORS, & SECTION EDITORS FOR THIS JOURNAL
		//
		$ojsEditors = array();
		$rolesQuery = "SELECT roles.user_id, user_settings.setting_value, users.username, roles.role_id FROM roles ";
		$rolesQuery .= "LEFT OUTER JOIN user_settings ON roles.user_id = user_settings.user_id AND setting_name = 'eschol_bpid' ";
		$rolesQuery .= "LEFT OUTER JOIN users ON roles.user_id = users.user_id ";
		$rolesQuery .= "WHERE roles.journal_id = $journalId ";
		$rolesQuery .= "AND (roles.role_id = 16 OR roles.role_id = 256 OR roles.role_id = 512) ";
		//$rolesQuery .= "AND user_settings.setting_name = 'eschol_bpid'";
		//echo "\nrolesQuery: $rolesQuery\n";
		$rolesQueryResult = mysql_query($rolesQuery);
		if($rolesQueryResult === FALSE) {
			die("\nInvalid query: " . mysql_error() . "\nrolesQuery: $rolesQuery\n");
		}
		while ($rolesRow = mysql_fetch_array($rolesQueryResult)) {
			$ojsEditorId = $rolesRow[0];
			$bpId = $rolesRow[1];
			$ojsEditorEmail = $rolesRow[2];
			$ojsRoleId = $rolesRow[3];
			if($ojsEditorId > 14) {
				$ojsEditors[] = array('ojsEditorId' => $ojsEditorId, 'bpId' => $bpId, 'ojsEditorEmail' => $ojsEditorEmail, 'ojsRoleId' => $ojsRoleId);
			}
		}
		
		//echo "\nojsEditors:\n";
		//print_r($ojsEditors);

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		// CHECK THAT user_settings 'eschol_bpid' OR email address IS LISTED IN ADMINISTRATORS.TSV || user_id is listed as editor for some article
		// IF USER DOES NOT EXIST IN ADMINISTRATORS.TSV, DELETE ROLES RECORD
		//
		foreach ($ojsEditors as $ojsEditor) {
			$currOjsId = $ojsEditor['ojsEditorId'];
			$currBpId = $ojsEditor['bpId'];
			$currEdEmail = $ojsEditor['ojsEditorEmail'];
			$currRoleId = $ojsEditor['ojsRoleId'];
			
			if(!in_array($currBpId,$bpUserIds) && !(in_array($currEdEmail,$bpEmails))) {
				// 16 = journal manager
				// 256 = editor
				// 512 = section editor
				$deleteRoleQuery = "DELETE FROM roles WHERE journal_id = $journalId AND user_id = $currOjsId AND (role_id = 16 OR role_id = 256 OR role_id = 512)";
				//echo "\ndeleteRoleQuery: $deleteRoleQuery\n";
				$deleteRoleResult = mysql_query($deleteRoleQuery);
				if($deleteRoleResult === FALSE) {
					die("\nInvalid query: " . mysql_error() . "\ndeleteRoleQuery: $deleteRoleQuery\n");
				} else {
					echo "\n$journalPath\t$currOjsId\t$currRoleId\t$currEdEmail";
					$rolesDeleted++;
				}	
			}
			
		}
			
		// GET LIST OF REVIEWER BP IDS FROM REVIEWERS.TSV
		// FOR EACH ROLES WHERE JOURNAL_ID = X AND ROLE_ID = REVIEWER
			// CHECK THAT user_settings 'eschol_bpid' IS LISTED IN ADMINISTRATORS.TSV
			// IF USER DOES NOT EXIST IN REVIEWER.TSV, DELETE ROLES RECORD
	} //end if (file_exists($bpAdminFilepath))
} //end foreach($importParentDir as $currJournalInfo)

mysql_close($conn);

echo "\nRoles records deleted: $rolesDeleted\n";
echo "\nROLES CLEANUP FINISHED.\n\n";
?>