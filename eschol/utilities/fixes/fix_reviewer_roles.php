#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$proposedFix = '';
$low = 0;
$high = 7834;
$rolesCreated = 0;

for ($i = $low; $i <= $high; $i++) {
	$journalIdQuery = "SELECT review_assignments.reviewer_id, articles.journal_id FROM review_assignments ";
	$journalIdQuery .= "LEFT JOIN articles ON review_assignments.submission_id = articles.article_id ";
	$journalIdQuery .= "WHERE review_assignments.reviewer_id = $i";
	//echo "\njournalIdQuery: $journalIdQuery\n";
	
	$journalIdResult = mysql_query($journalIdQuery);
	if(!$journalIdResult) {
		die("\nInvalid query: " . mysql_error() . "\njournalIdQuery: $journalIdQuery");
	} else {
		while($reviewerJournalRow = mysql_fetch_array($journalIdResult)) {
			//echo "\nreviewerJournalRow:";
			//print_r($reviewerJournalRow);
			$userId = $reviewerJournalRow[0];
			$journalId = $reviewerJournalRow[1];
			$roleFixQuery = "INSERT INTO roles (journal_id, user_id, role_id) VALUES ($journalId, $userId, 4096)";
			
			$roleFixResult = mysql_query($roleFixQuery);
			if(!$roleFixResult) {
				//echo "\nInvalid query: " . mysql_error() . "\nroleFixQuery: $roleFixQuery\n";
			} else {
				echo "\nroleFixQuery: $roleFixQuery\n";
				$rolesCreated++;
			}
		}
	}
}

mysql_close($conn);

echo "\nRoles Created: $rolesCreated\n";

?>