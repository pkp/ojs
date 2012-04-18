#!/usr/bin/php
<?php

require './ojs_db_connect.php';

$emailKey = 'REVIEW_REMIND_AUTO_ONECLICK';
$newBody = '{$reviewerName}:

Just a gentle reminder of our request for your review of the submission, "{$articleTitle}," for {$journalName} due on {$reviewDueDate}. We would still be pleased to receive it as soon as you are able to prepare it.

If you do not have your username and password for the journal' . "'" . 's web site, you can use this link to reset your password (which will then be emailed to you along with your username). {$passwordResetUrl}

Submission URL: {$submissionReviewUrl}

Please confirm your ability to complete this vital contribution to the work of the journal. I look forward to hearing from you.

{$editorialContactSignature}';

$newBody = mysql_real_escape_string($newBody);

$emailQuery = "UPDATE email_templates_default_data SET body = '$newBody' WHERE email_key like 'REVIEW_REMIND_AUTO%'";
echo "emailQuery: $emailQuery\n";
$emailUpdateResult = mysql_query($emailQuery);
if(!$emailUpdateResult) {
	die("\nInvalid query: " . mysql_error() . "\n");
}
echo "Database Updated\n";

mysql_close($conn);
	
?>