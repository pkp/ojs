#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$authorsQuery = "select articles.article_id, authors.user_id, min(authors.author_id)
	from articles, authors
	where authors.submission_id = articles.article_id
	and (articles.user_id = 1 or articles.user_id = 14)
	and (articles.journal_id >= 22)
	and authors.user_id IS NOT NULL
	group by authors.submission_id";
	
$result = mysql_query($authorsQuery);
if(!$result) {
	die("\nInvalid query: " . mysql_error() . "\n");
}

$articlesUpdated = 0;
//if(mysql_num_rows($result) > 0) {
	while ($row = mysql_fetch_array($result)) {
		$articleId = $row[0];
		$userId = $row[1];
		$authorId = $row[2];
		
		$updateArticlesQuery = "UPDATE articles, authors SET articles.user_id = $userId
		WHERE articles.article_id = $articleId
		AND (articles.user_id = 1 OR articles.user_id = 14)
		AND authors.user_id IS NOT NULL";
	
		//echo "Query: $updateArticlesQuery\n";
		
		$updateArticlesQueryResult = mysql_query($updateArticlesQuery);
		if(!$updateArticlesQueryResult) {
			die("\nInvalid query: " . mysql_error() . "\n");
		}
		
		$articlesUpdated++;
	}
//}

echo "Articles Updated: $articlesUpdated\n";

mysql_close($conn);
	
?>