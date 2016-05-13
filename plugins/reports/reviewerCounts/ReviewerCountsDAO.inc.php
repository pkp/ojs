<?php

/**
 * @file ReviewerCountsDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ReviewerCountsDAO
 * @ingroup plugins_reports_reviewer
 * @see ReviewerCountsPlugin
 *
 * @brief Review report DAO
 */

//$Id$


import('classes.article.ArticleComment');
import('lib.pkp.classes.db.DBRowIterator');

class ReviewerCountsDAO extends DAO {
	/**
	 * Get the reviewer report data.
	 * @param $journalId int
	 * @return array
	 */
	function getReviewerCounts($journalId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$result =& $this->retrieve(
			'SELECT users.user_id AS reviewerid,
				users.last_name AS lastname,
				users.first_name AS firstname,
				users.email AS email,
				count(*) AS totalreviews
			FROM review_assignments, articles, users
			WHERE users.user_id=review_assignments.reviewer_id && review_assignments.submission_id = articles.article_id && articles.journal_id=? && review_assignments.date_completed !="NULL"
			GROUP BY users.user_id
			ORDER BY count(*) desc',                       
			array(
				$journalId
			)
		);
		$reviewerReturner = new DBRowIterator($result);

		return($reviewerReturner);
	}
}

?>
