<?php

/**
 * @file ReviewerReportsDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * @class ReviewerReportsDAO
 * @ingroup plugins_reports_reviewer
 * @see ReviewerReportsPlugin
 *
 * @brief Review report DAO
 */

//$Id$


import('classes.article.ArticleComment');
import('lib.pkp.classes.db.DBRowIterator');

class ReviewerReportsDAO extends DAO {
	/**
	 * Get the reviewer report data.
	 * @param $journalId int
	 * @return array
	 */
	function getReviewerReports($journalId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$result =& $this->retrieve(
			'SELECT	article_id,
				comments,
				author_id
			FROM	article_comments
			WHERE	comment_type = ?',
			array(
				COMMENT_TYPE_PEER_REVIEW
			)
		);
		import('lib.pkp.classes.db.DBRowIterator');
		$commentsReturner = new DBRowIterator($result);

		$result =& $this->retrieve(
			'SELECT r.round AS round,
				COALESCE(asl.setting_value, aspl.setting_value) AS article,				
				a.article_id AS articleId,				
				CASE a.status WHEN "4" THEN "Rejected" WHEN "3" THEN "Published" WHEN "1" THEN "Pending" WHEN "0" THEN "Archived" ELSE "No Match" END AS articlestatus,
				u.user_id AS reviewerId,
				u.username AS reviewer,
				u.first_name AS firstName,
				u.middle_name AS middleName,
				u.last_name AS lastName,
				u.email AS email
			FROM	review_assignments r
				LEFT JOIN articles a ON r.submission_id = a.article_id
				LEFT JOIN article_settings asl ON (a.article_id=asl.article_id AND asl.locale=? AND asl.setting_name=?)
				LEFT JOIN article_settings aspl ON (a.article_id=aspl.article_id AND aspl.locale=a.locale AND aspl.setting_name=?),
				users u
				LEFT JOIN user_settings usl ON (u.user_id=usl.user_id)
			WHERE	u.user_id=r.reviewer_id AND a.journal_id= ? and usl.setting_name= ?
			ORDER BY article',
			array(
				$locale, // Article title
				'title',
				'title',
				$journalId,
				'affiliation'
			)
		);
		$reviewerReturner = new DBRowIterator($result);

//		return array($commentsReturner, $reviewerReturner);
		return($reviewerReturner);
	}
}

?>
