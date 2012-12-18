#!/usr/bin/php
<?php

require './import_set_parameters.php';
require '../ojs_db_connect.php';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// Run import
//
import_readers($importHome,$importParentDir);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Function to import mailinglist.tsv files (readers) 
// role_id for readers is 1048576
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function import_readers($importHome,$importParentDir) {
	echo "\n**** IMPORTING READERS ****\n";
	
	//
	// LOOP THROUGH JOURNALS
	//
	$recsCreated = 0;
	$dupCtr = 0;
	foreach ($importParentDir as $dir) {
		
		$journalPath = $dir[0];
		$eschol_article_id_begin = $dir[1];
		$journalId = $dir[2];
	
		$currImportFile = $importHome . $journalPath . 'mailinglist.tsv';
		
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
		//
		//READ HISTORY FILE
		//
		$row = 1;
		$numRows = 0;				
		if (file_exists($currImportFile)) {
			//open file
			$handle = fopen($currImportFile, "r");
			$numRows = count(file($currImportFile));
                	echo "Curr Import File: $currImportFile\n";
			echo "num of rows in file: $numRows\n\n";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////								
			//FOR EACH ROW IN THE MAILINGLIST  FILE
			//

			while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
				$bp_email = '';
				$bp_subscribed = '';
				$bp_unsub_date = '';
				
				if($row > 1) {
					$bp_email = $data[0];
					$bp_subscribed = $data[1];
					$bp_unsub_date = $data[2];
					
					//
					//TRANSLATE VALUES AS NECESSARY
					//
					$bp_unsub_date = ($bp_unsub_date == '0000:00:00' OR $bp_unsub_date == '') ? 'NULL': $bp_unsub_date;
					
					//echo "----------------------------\n";
					//echo "bp_email: $bp_email, bp_subscribed: $bp_subscribed, bp_unsub_date: $bp_unsub_date\n";
					
					//CHECK FOR REQUIRED DATA VALUES
					if($bp_email == '' or $bp_email == 'NULL') {
						echo "\nERROR: Email not populated for $currImportFile Line: $row NumFields in Line: $num Not importing this line.\n";
						continue;
					}
					if($bp_subscribed == '' or $bp_subscribed == 'NULL') {
						echo "\nERROR: BP Subscribed not populated for $currImportFile Line: $row NumFields in Line: $num Not importing this line.\n";
						continue;
					}
					
					if($bp_subscribed == "1") {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
					//
					//get OJS user_id for reader 
					//
					$user_id = 0;
					$userid_query = 'SELECT user_id FROM users ';
					$userid_query .= "WHERE email = '" . mysql_real_escape_string($bp_email) . "'";
					
					//echo "\nuserid_query: $userid_query\n";
					unset($userid_query_result);
					
					$userid_query_result = mysql_query($userid_query);
					
					if(!$userid_query_result) {
						die("\nInvalid query: " . mysql_error() . "\n");
					} 
					
					if (mysql_num_rows($userid_query_result) > 0) {
						$user_id = mysql_result($userid_query_result,0);
					} else {
						///////////////////////
						// CREATE USERS REC
						//////////////////////
						$usersCreateQuery = "INSERT INTO users (username, email) ";
						$usersCreateQuery .= "VALUES('" . mysql_real_escape_string($bp_email) . "', '" . mysql_real_escape_string($bp_email) . "')";
						
						#echo "\nusersCreateQuery: $usersCreateQuery\n";
						$usersCreateResult = mysql_query($usersCreateQuery);
						if(!$usersCreateResult) {
							die("\nInvalid query: " . mysql_error() . "\nusersCreateQuery: $usersCreateQuery\n");
						} else {
							$user_id = mysql_insert_id();
							$reviewersCreated++;
						}
						
						if($user_id == 0) {
							die("ERROR: Could not get user_id after query: $usersCreateQuery\n");
						}	

 					}
					//echo "\nuser_id after userid_query: $user_id\n";
					
					if($bp_subscribed && $user_id) {					
							
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						//
						// CHECK FOR DUPLICATES BEFORE CREATING roles RECORD
						//
						$roleDupQuery = "SELECT * FROM roles ";
						$roleDupQuery .= "WHERE journal_id = $journalId AND user_id = $user_id AND role_id = 1048576";
						
						//echo "\nroleDupQuery: $roleDupQuery\n";
						
						$roleDupResult = mysql_query($roleDupQuery);
						if(!$roleDupResult) {
							die("\nInvalid query: " . mysql_error() . "\nroleDupQuery: $roleDupQuery");
						}
						if(mysql_num_rows($roleDupResult) > 0) {
							$dupCtr++;
						} else {
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////							
							//
							// CREATE roles RECORD
							//
							$rolesQuery = "INSERT INTO roles ";
							$rolesQuery .= "(journal_id, user_id, role_id)";
							$rolesQuery .= " VALUES ($journalId, $user_id, 1048576)";
							
							#echo "\nrolesQuery: $rolesQuery\n";
							$rolesCreateResult = mysql_query($rolesQuery);
							if(!$rolesCreateResult) {
								die("\nInvalid query: " . mysql_error() . "\nrolesQuery: $rolesQuery\n");
							} else {
								$rolesCreated++;
							}	
						}
					}
					} //end if $bp_subscribed == "1"				
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////				
				} //end if($row > 1)
				$row++;
			} //while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE)

			fclose($handle);
		}
	}
	
	//
	//PRINT RESULTS
	//
	if($dupCtr > 0) {
		echo "\nCount of readers recs that already existed in the DB and were therefore not recreated: $dupCtr\n"; 
	}

	echo "\nReaders created as ojs users: $readersCreated\n";
	echo "\nRoles records created: $rolesCreated\n";
	echo "\nIMPORT OF READERS FINISHED.\n\n";
	
}

mysql_close($conn);


?>
