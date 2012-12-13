<?php

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
// GET ARRAY OF ARTICLE DIRS FOR A GIVEN JOURNAL
//
function get_import_dirs($journalPath,$journalId,$importHome,$unpublished) {	

	$parentDirs = array();
	if($unpublished == 1) {
		$grandparentPath = $importHome . $journalPath . 'unpublished/';	
		//echo "\ngrandparentPath: $grandparentPath\n";
		$parentDirs = scandir($grandparentPath); //array of directories
	} else {
		$grandparentPath = $importHome;
		$vols = scandir($importHome . $journalPath);
		$volDir = '';
		foreach($vols as $vol) {
			//if(substr($vol,0,3) == 'vol') {
			if(substr($vol,0,1) == 'v' OR substr($vol,0,6) == 'Series' OR is_int($vol)) {
				//echo "\nvol: $vol\n";
				$volDir = $importHome . $journalPath . $vol . '/';
				$issues = scandir($volDir);
				$issueDir = '';
				foreach($issues as $issue) {
					//if(substr($issue,0,3) == 'iss') {
					//if(substr($issue,0,3) == 'iss' OR substr($issue,0,1) == 'n' OR is_int($issue)) {
					if(substr($issue,0,3) == 'iss' OR substr($issue,0,1) == 'n' OR is_int($issue) OR substr($issue,0,3) == 'Vol') {
						//echo "\nissue: $issue\n";
						$issueDir = $volDir . $issue . '/';
						$arts = scandir($issueDir);
						foreach($arts as $art) {
							//if(substr($art,0,3) == 'art') {
							//if(substr($art,0,3) == 'art' OR substr($art,0,1) == 'p' OR is_int($art)) {
							//if(substr($art,0,3) == 'art' OR substr($art,0,1) == 'p' OR is_int(substr($art,0,1))) {
							if(substr($art,0,1) != '.' && $art != 'settings.tsv' && $art != 'templates') {
								$parentDirs[] = $journalPath . $vol . '/' . $issue . '/' . $art;
								//echo "\nparentDirs[$artKey]: $parentDirs[$artKey]\n";
							}
						}
					}						
				}
			}
		}
	}
		
	return $parentDirs;
}

function get_article_id($unpublished,$escholArticleId,$journalPath) {
	$article_id = 0;
	$article_link = '';
	if($unpublished) {
		$articleid_query = 'SELECT article_id FROM article_settings ';
		$article_link = $escholArticleId;
		$articleid_query .= "WHERE setting_name = 'eschol_articleid' AND setting_value = '$article_link'";
	} else {
		$articleid_query = 'SELECT article_id FROM article_settings ';
		$article_link = $journalPath;
		$articleid_query .= "WHERE setting_name = 'eschol_submission_path' AND setting_value LIKE '%$article_link%'";
	}
	
	//echo "\narticleid_query: $articleid_query\n";
	$articleIdResult = 0;
	$articleIdResult = mysql_query($articleid_query);
	if(!$articleIdResult) {
		die("\nInvalid query: " . mysql_error() . "\n");
	} 
	
	if (mysql_num_rows($articleIdResult)==0) {
		echo "\n**ALERT** No article_settings record exists for this article.\nQuery: $articleid_query\n";
		$article_id = 0;
	} else {
		$article_id = mysql_result($articleIdResult,0);	
	}
	
	return $article_id;
}

?>
