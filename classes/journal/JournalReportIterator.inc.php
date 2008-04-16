<?php

/**
 * @file JournalReportIterator.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 * @class JournalReportIterator
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

	/** @var $sectionDao object */
	var $sectionDao;

	/** @var $articleDao object */
	var $articleDao;

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

	/** @var $editAssignmentDao object */
	var $editAssignmentDao;

	/** @var $maxAuthorCount int The most authors that can be expected for an article. */
	var $maxAuthorCount;

	/** @var $maxReviewerCount int The most reviewers that can be expected for a submission. */
	var $maxReviewerCount;

	/** @var $maxEditorCount int The most editors that can be expected for a submission. */
	var $maxEditorCount;

	/** @var $reportType int The report type (REPORT_TYPE_...) */
	var $type;

	/** @var $sectionCache array */
	var $sectionCache;

	/**
	 * Constructor.
	 * Initialize the JournalReportIterator
	 * @param $journalId int ID of journal this report is generated on
	 * @param $records object ADO record set
	 * @param $dateStart string optional
	 * @param $dateEnd string optional
	 * @param $reportType int REPORT_TYPE_...
	 */
	function JournalReportIterator($journalId, &$records, $dateStart, $dateEnd, $reportType) {
		$this->sectionDao =& DAORegistry::getDAO('SectionDAO');
		$this->articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		$this->userDao =& DAORegistry::getDAO('UserDAO');
		$this->journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');
		$this->countryDao =& DAORegistry::getDAO('CountryDAO');
		$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
		$this->editAssignmentDao =& DAORegistry::getDAO('EditAssignmentDAO');

		parent::DBRowIterator($records);

		$this->type = $reportType;

		$this->maxAuthorCount = $this->journalStatisticsDao->getMaxAuthorCount($journalId, $dateStart, $dateEnd);
		$this->maxReviewerCount = $this->journalStatisticsDao->getMaxReviewerCount($journalId, $dateStart, $dateEnd);
		if ($this->type !== REPORT_TYPE_EDITOR) {
			$this->maxEditorCount = $this->journalStatisticsDao->getMaxEditorCount($journalId, $dateStart, $dateEnd);
		}
	}

	/**
	 * Get a section (cached) by ID.
	 * @param $sectionId int
	 * @return object
	 */
	function &getSection($sectionId) {
		if (!isset($this->sectionCache[$sectionId])) {
			$this->sectionCache[$sectionId] =& $this->sectionDao->getSection($sectionId);
		}
		return $this->sectionCache[$sectionId];
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

		$article =& $this->articleDao->getArticle($row['article_id']);
		$ret['title'] = $article->getArticleTitle();

		$section =& $this->getSection($row['section_id']);
		$ret['section'] = $section->getSectionTitle();

		// Author Names & Affiliations
		$maxAuthors = $this->getMaxAuthors();
		$ret['authors'] = $maxAuthors==0?array():array_fill(0, $maxAuthors, '');
		$ret['affiliations'] = $maxAuthors==0?array():array_fill(0, $maxAuthors, '');
		$ret['countries'] = $maxAuthors==0?array():array_fill(0, $maxAuthors, '');
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

		if ($this->type === REPORT_TYPE_EDITOR) {
			$user = null;
			if ($row['editor_id']) $user =& $this->userDao->getUser($row['editor_id']);
			$ret['editor'] = $user?$user->getFullName():'';
		} else {
			$editAssignments =& $this->editAssignmentDao->getEditAssignmentsByArticleId($row['article_id']);
			$maxEditors = $this->getMaxEditors();
			$ret['editors'] = $maxEditors==0?array():array_fill(0, $maxEditors, '');

			$editorIndex = 0;
			while ($editAssignment =& $editAssignments->next()) {
				$ret['editors'][$editorIndex++] = $editAssignment->getEditorFullName();
			}
		}

		// Reviewer Names
		$ratingOptions =& ReviewAssignment::getReviewerRatingOptions();
		if ($this->type === REPORT_TYPE_REVIEWER) {
			$user = null;
			if ($row['reviewer_id']) $user =& $this->userDao->getUser($row['reviewer_id']);
			$ret['reviewer'] = $user?$user->getFullName():'';

			if ($row['quality']) {
				$ret['score'] = Locale::translate($ratingOptions[$row['quality']]);
			} else {
				$ret['score'] = '';
			}
			$ret['affiliation'] = $user?$user->getAffiliation():'';
		} else {
			$maxReviewers = $this->getMaxReviewers();
			$ret['reviewers'] = $maxReviewers==0?array():array_fill(0, $maxReviewers, '');
			$ret['scores'] = $maxReviewers==0?array():array_fill(0, $maxReviewers, '');
			$ret['recommendations'] = $maxReviewers==0?array():array_fill(0, $maxReviewers, '');
			$reviewAssignments =& $this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id']);
			$reviewerIndex = 0;
			foreach ($reviewAssignments as $reviewAssignment) {
				$reviewerId = $reviewAssignment->getReviewerId();
				$ret['reviewers'][$reviewerIndex] = $reviewAssignment->getReviewerFullName();
				$rating = $reviewAssignment->getQuality();
				if ($rating != '') {
					$ret['scores'][$reviewerIndex] = Locale::translate($ratingOptions[$rating]);
				}
				$recommendation = $reviewAssignment->getRecommendation();
				if ($recommendation !== '' && $recommendation !== null) {
					$recommendationOptions =& $reviewAssignment->getReviewerRecommendationOptions();
					$ret['recommendations'][$reviewerIndex] = Locale::translate($recommendationOptions[$recommendation]);
				}
				$reviewerIndex++;
			}
		}

		// Fetch the last editorial decision for this article.
		$editorDecisions = $this->authorSubmissionDao->getEditorDecisions($row['article_id']);
		$lastDecision = array_pop($editorDecisions);

		if ($lastDecision) {
			import('submission.sectionEditor.SectionEditorSubmission');
			$decisionOptions =& SectionEditorSubmission::getEditorDecisionOptions();
			$ret['decision'] = Locale::translate($decisionOptions[$lastDecision['decision']]);
			$ret['dateDecided'] = $lastDecision['dateDecided'];

			$decisionTime = strtotime($lastDecision['dateDecided']);
			$submitTime = strtotime($ret['dateSubmitted']);
			if ($decisionTime === false || $decisionTime === -1 || $submitTime === false || $submitTime === -1) {
				$ret['daysToDecision'] = '';
			} else {
				$ret['daysToDecision'] = round(($decisionTime - $submitTime) / 3600 / 24);
			}
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

	/**
	 * Return the maximum number of editors that can be expected for a
	 * single article in this report. This call can be used for all
	 * report types EXCEPT, of course, REPORT_TYPE_EDITOR.
	 */
	function getMaxEditors() {
		return $this->maxEditorCount;
	}
}

?>
