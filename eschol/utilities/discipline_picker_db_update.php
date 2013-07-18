#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$oldDiscQuery = "SELECT article_id, setting_value 
	FROM article_settings
	WHERE setting_name = 'discipline'
	AND setting_value != ''
	AND setting_VALUE IS NOT NULL
	";
	
$result = mysql_query($oldDiscQuery);
if(!$result) {
	die("\nInvalid query: " . mysql_error() . "\n");
}

// APPEND OLD DISCIPLINES TO SUBJECT (AKA KEYWORDS)
$i = 0;
$keywordsUpdated = 0;
$keywordsCreated = 0;
while ($row = mysql_fetch_row($result)) {
	$articleId = $row[0];
	$discipline = $row[1];

	if(trim($discipline != '') AND trim($discipline != "NULL")) {
		$i++;

		// see if subject setting exists already
		$keywordQuery = "SELECT article_id, setting_value
			FROM article_settings
			WHERE article_id = $articleId
			AND setting_name = 'subject'";
		//echo "keywordQuery: $keywordQuery\n";
		$keywordQueryResult = mysql_query($keywordQuery);
		$resultCount = mysql_num_rows($keywordQueryResult);

		if($resultCount > 0) {
			// if subject setting exists, append disciplines to it
			$row = mysql_fetch_row($keywordQueryResult);
			$keywords = $row[1];
			$keywords = trim($keywords);
			$newKeywords = $keywords;
			if($newKeywords != '') {
				$newKeywords = $newKeywords . "; ";
			}
			$newKeywords = $newKeywords . $discipline;

			// UPDATE THE DB
			$updateQuery = "UPDATE article_settings SET setting_value = '" . mysql_real_escape_string($newKeywords) . "' WHERE article_id = $articleId AND setting_name = 'subject'";
			//echo "updateQuery: $updateQuery\n\n";

			$updateResult = mysql_query($updateQuery);
			if(!$updateResult) {
				die("\nInvalid query: " . mysql_error() . "\nupdateQuery: $updateQuery\n");
			}
			$keywordsUpdated++;
		} else {
			// CREATE NEW RECORD
			$createQuery = "INSERT article_settings (article_id, locale, setting_name, setting_value, setting_type) VALUES ($articleId, 'en_US', 'subject', '" . mysql_real_escape_string($discipline) . "', 'string')";
			//echo "createQuery: $createQuery\n";
			$createResult = mysql_query($createQuery);
			if(!$createResult) {
				die("\nInvalid query: " . mysql_error() . "\ncreateQuery: $createQuery\n");
			}
			$keywordsCreated++;
		}
	}
}

// DELETE OLD DISCIPLINES
if($keywordsUpdated + $keywordsCreated == $i) {
	//$deleteQuery = "DELETE FROM article_settings WHERE setting_name = 'discipline'";
}

echo "total: $i\n";
echo "keywordsUpdated: $keywordsUpdated\n";
echo "keywordsCreated: $keywordsCreated\n";

mysql_close($conn);
	
?>
