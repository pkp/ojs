<?php

/**
 * JournalReportIterator.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 *
 * Wrapper around DBRowIterator providing "factory" features for journal
 * reports.
 *
 * $Id$
 */

import('db.DBRowIterator');

class JournalReportIterator extends DBRowIterator {
	/** @var $locale Name of report's locale */
	var $locale;

	/** @var $altLocaleNum int 1 iff current locale is journal's alt locale 1, 2 iff current locale is journal's alt locale 2 */
	var $altLocaleNum;

	/** @var $journalStatisticsDao object */
	var $journalStatisticsDao;

	/** @var $authorDao object */
	var $authorDao;

	/** @var $userDao object */
	var $userDao;

	/** @var $countryDao object */
	var $countryDao;

	/** @var $authorSubmissionDao object */
	var $authorSubmissionDao;

	/** @var $maxAuthorCount int The most authors that can be expected for an article. */
	var $maxAuthorCount;

	/** @var $maxReviewerCount int The most reviewers that can be expected for a submission. */
	var $maxReviewerCount;

	/**
	 * Constructor.
	 * Initialize the JournalReportIterator
	 * @param $journalId int ID of journal this report is generated on
	 * @param $records object ADO record set
	 */
	function JournalReportIterator($journalId, &$records, $dateStart, $dateEnd) {
		$this->authorDao =& DAORegistry::getDao('AuthorDAO');
		$this->authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$this->countryDao =& DAORegistry::getDAO('CountryDAO');

		parent::DBRowIterator($records);

		$this->altLocaleNum = Locale::isAlternateJournalLocale($journalId);
		$this->maxAuthorCount = $this->journalStatisticsDao->getMaxAuthorCount($journalId, $dateStart, $dateEnd);
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->maxReviewerCount = $this->journalStatisticsDao->getMaxReviewerCount($journalId, $dateStart, $dateEnd);
	}

	/**
	 * Return the object representing the next row.
	 * @return object
	 */
	function &next() {
		$row =& parent::next();
		if ($row == null) return $row;

		$ret = array(
			'articleId' => $row['article_id']
		);

		$ret['dateSubmitted'] = $this->journalStatisticsDao->dateFromDB($row['date_submitted']);
		$ret['title'] = $row['article_title'];

		$ret['section'] = null;
		switch ($this->altLocaleNum) {
			case 1: $ret['section'] = $row['section_title_alt1']; break;
			case 2: $ret['section'] = $row['section_title_alt2']; break;
		}
		if (empty($ret['section'])) $ret['section'] = $row['section_title'];

		// Author Names & Affiliations
		$ret['authors'] = array_fill(0, $this->getMaxAuthors(), '');
		$ret['affiliations'] = array_fill(0, $this->getMaxAuthors(), '');
		$ret['countries'] = array_fill(0, $this->getMaxAuthors(), '');
		$authors =& $this->authorDao->getAuthorsByArticle($row['article_id']);
		$authorIndex = 0;
		foreach ($authors as $author) {
			$ret['authors'][$authorIndex] = $author->getFullName();
			$ret['affiliations'][$authorIndex] = $author->getAffiliation();
			
			$country = $author->getCountry();
			if (!empty($country)) {
				$ret['countries'][$authorIndex] = $this->countryDao->getCountry($country);
			}
			$authorIndex++;
		}

		// Editor Names
		if (!empty($row['editor_id'])) {
			$editor =& $this->userDao->getUser($row['editor_id']);
			if ($editor) {
				$ret['editor'] = $editor->getFullName();
			}
		} else {
			$ret['editor'] = '';
		}

		// Reviewer Names
		$ret['reviewers'] = array_fill(0, $this->getMaxReviewers(), '');
		$ret['scores'] = array_fill(0, $this->getMaxReviewers(), '');
		$ret['recommendations'] = array_fill(0, $this->getMaxReviewers(), '');
		$reviewAssignments =& $this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id']);
		$reviewerIndex = 0;
		foreach ($reviewAssignments as $reviewAssignment) {
			$reviewerId = $reviewAssignment->getReviewerId();
			$ret['reviewers'][$reviewerIndex] = $reviewAssignment->getReviewerFullName();
			$rating = $reviewAssignment->getQuality();
			if ($rating != '') {
				$ratingOptions =& $reviewAssignment->getReviewerRatingOptions();
				$ret['scores'][$reviewerIndex] = Locale::translate($ratingOptions[$rating]);
			}
			$recommendation = $reviewAssignment->getRecommendation();
			if ($recommendation != '') {
				$recommendationOptions =& $reviewAssignment->getReviewerRecommendationOptions();
				$ret['recommendations'][$reviewerIndex] = Locale::translate($recommendationOptions[$recommendation]);
			}
			$reviewerIndex++;
		}

		// Fetch the last editorial decision for this article.
		$editorDecisions =& $this->authorSubmissionDao->getEditorDecisions($row['article_id']);
		$lastDecision = array_pop($editorDecisions);

		if ($lastDecision) {
			import('submission.sectionEditor.SectionEditorSubmission');
			$decisionOptions =& SectionEditorSubmission::getEditorDecisionOptions();
			$ret['decision'] = Locale::translate($decisionOptions[$lastDecision['decision']]);
			$ret['dateDecided'] = $lastDecision['dateDecided'];

			$decisionTime = strtotime($lastDecision['dateDecided']);
			$submitTime = strtotime($ret['dateSubmitted']);
			$ret['daysToDecision'] = round(($decisionTime - $submitTime) / 3600 / 24);
		} else {
			$ret['decision'] = '';
			$ret['daysToDecision'] = '';
			$ret['dateDecided'] = '';
		}

		$ret['daysToPublication'] = '';
		if ($row['pub_id']) {
			$submitTime = strtotime($ret['dateSubmitted']);
			$publishTime = strtotime($this->journalStatisticsDao->dateFromDB($row['date_published']));
			if ($publishTime > $submitTime) {
				// Imported documents can be published before
				// they were submitted -- in this case, ignore
				// this metric (as opposed to displaying
				// negative numbers).
				$ret['daysToPublication'] = round(($publishTime - $submitTime) / 3600 / 24);
			}
		}

		$ret['status'] = $row['status'];

		return $ret;
	}

	/**
	 * Return the next row, with key.
	 * @return array ($key, $value)
	 */
	function &nextWithKey() {
		// We don't have keys with rows. (Row numbers might become
		// valuable at some point.)
		return array(null, $this->next());
	}

	function _cleanup() {
		parent::_cleanup();
	}

	/**
	 * Return the maximum number of authors that can be expected for a
	 * single article in this report.
	 */
	function getMaxAuthors() {
		return $this->maxAuthorCount;
	}

	/**
	 * Return the maximum number of reviewers that can be expected for a
	 * single article in this report.
	 */
	function getMaxReviewers() {
		return $this->maxReviewerCount;
	}
}

?>
