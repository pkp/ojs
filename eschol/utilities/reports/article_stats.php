#!/usr/bin/php
<?php

require '../ojs_db_connect.php';

$outfile = "article_stats.csv";

// open file and write header row
$f = fopen($outfile, "w");
fwrite($f, "Journal ID, Journal Name, At least one reviewer, At least one peer reviewer recommendation, Editorial Decision, At least one copyediting file, Proofreading activity, # issues published since 1/1/2012, Date last published, # reviewers enrolled, # editors enrolled, # section editors enrolled\n");
fclose($f);

// get journals
$journalsSql = "select j.journal_id, j.path, js.setting_value  from journals as j  left join journal_settings as js ON js.journal_id = j.journal_id where j.enabled = 1 and js.setting_name = 'title'";
$journalsResult = runQuery($journalsSql);

// iterate through journals
while($journalsRow = mysql_fetch_array($journalsResult)) {
	$journalId = $journalsRow[0];
	$journalName = $journalsRow[2];
	$hasReviewer = getArticlesWithReviewer($journalId);
	$hasReviewerRec = getArticlesWithReviewerRec($journalId);
	$hasEditorialDecision = getArticlesWithEditorialDecision($journalId);
	$hasCopyeditingFile = getArticlesWithCopyeditingFile($journalId);
	$hasProofreading = getArticlesWithProofing($journalId);
	$issuesSince2012 = getIssuesSince2012($journalId);
	$dateLastPublished = getDateLastPublished($journalId);
	$reviewers = getNumReviewersEnrolled($journalId);
	$editors = getEditorsEnrolled($journalId);
	$sectionEditors = getSectionEditorsEnrolled($journalId);

	// write row of data
	$f = fopen($outfile, "a");
	fwrite($f, "$journalId,");
	fwrite($f, '"' . $journalName . '",');
        fwrite($f, "$hasReviewer,");
        fwrite($f, "$hasReviewerRec,");
        fwrite($f, "$hasEditorialDecision,");
        fwrite($f, "$hasCopyeditingFile,");
        fwrite($f, "$hasProofreading,");
        fwrite($f, "$issuesSince2012,");
        fwrite($f, "$dateLastPublished,");
        fwrite($f, "$reviewers,");
        fwrite($f, "$editors,");
	fwrite($f, "$sectionEditors");
	fwrite($f, "\n");
	fclose($f);

}

function runQuery($sql) {
	$result = mysql_query($sql);
	if($result === FALSE) {
		die("\nInvalid query: " . mysql_error() . "\nsql: $sql \n");
	} else {
		return $result;
	}
}

function getSimpleSqlCount($sql) {
	$result = runQuery($sql);
        while($row = mysql_fetch_array($result)) {
                $count = $row[0];
        }
        return $count;
}

function getArticlesWithReviewer($journalId) {
	$sql = "SELECT COUNT(distinct(ra.submission_id)) FROM review_assignments AS ra LEFT JOIN articles AS a ON ra.submission_id = a.article_id WHERE a.journal_id = $journalId AND ra.date_assigned IS NOT null AND ra.date_confirmed IS NOT null AND ra.declined = 0 AND ra.replaced = 0 AND ra.cancelled = 0";
	return getSimpleSqlCount($sql);
}

function getArticlesWithReviewerRec($journalId) {
	$sql = "SELECT COUNT(distinct(ra.submission_id)) FROM review_assignments AS ra LEFT JOIN articles AS a ON ra.submission_id = a.article_id WHERE a.journal_id = $journalId AND ra.date_assigned IS NOT null AND ra.date_confirmed IS NOT null AND ra.declined = 0 AND ra.replaced = 0 AND ra.cancelled = 0 AND recommendation IS NOT NULL AND recommendation != 0";
	return getSimpleSqlCount($sql);
}

function getArticlesWithEditorialDecision($journalId) {
	$sql = "SELECT COUNT(DISTINCT(ed.article_id)) FROM edit_decisions AS ed LEFT JOIN articles AS a ON ed.article_id = a.article_id WHERE a.journal_id = $journalId AND decision IS NOT NULL and decision != 0";
	return getSimpleSqlCount($sql);
}

function getArticlesWithCopyeditingFile($journalId) {
	$sql = "SELECT COUNT(distinct(ra.submission_id)) FROM review_assignments AS ra LEFT JOIN articles AS a ON ra.submission_id = a.article_id WHERE a.journal_id = $journalId AND ra.date_assigned IS NOT null AND ra.date_confirmed IS NOT null AND ra.declined = 0 AND ra.replaced = 0 AND ra.cancelled = 0";
	return getSimpleSqlCount($sql);
}

function getArticlesWithProofing($journalId) {
	$sql = "select count(distinct(assoc_id)) from signoffs as s left join articles as a on s.assoc_id = a.article_id where a.journal_id = $journalId and s.symbolic like 'SIGNOFF_PROOFREADING%'  and s.date_completed is not null and s.date_notified is not null";
        return getSimpleSqlCount($sql);
}

function getIssuesSince2012($journalId) {
        $sql = "select count(*) from issues where journal_id = $journalId and published = 1 and date_published > '2012-01-01 00:00:00' AND date_published IS NOT NULL";
        return getSimpleSqlCount($sql);
}

function getDateLastPublished($journalId) {
	$date = 'never published';
	$sql = "select date_published from issues where journal_id = $journalId and published = 1 and date_published IS NOT NULL order by date_published ASC";
        $result = runQuery($sql);
        while($row = mysql_fetch_array($result)) {
                $date = $row[0];
        }
        return $date;	
}

function getNumReviewersEnrolled($journalId) {
	$sql = "select count(*) from users AS u  left join roles as r ON r.user_id = u.user_id where r.role_id = 4096 and r.journal_id = $journalId";
        return getSimpleSqlCount($sql);
}

function getEditorsEnrolled($journalId) {
        $sql = "select count(*) from users AS u  left join roles as r ON r.user_id = u.user_id where r.role_id = 256 and r.journal_id = $journalId";
        return getSimpleSqlCount($sql);
}

function getSectionEditorsEnrolled($journalId) {
        $sql = "select count(*) from users AS u  left join roles as r ON r.user_id = u.user_id where r.role_id = 512 and r.journal_id = $journalId";
        return getSimpleSqlCount($sql);
}

mysql_close($conn);

?>
