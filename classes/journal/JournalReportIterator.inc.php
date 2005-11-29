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
	/** @var $fields Array of fields included in this report */
	var $fields;

	/** @var $locale Name of report's locale */
	var $locale;

	/** @var $altLocaleNum int 1 iff current locale is journal's alt locale 1, 2 iff current locale is journal's alt locale 2 */
	var $altLocaleNum;

	/** @var $journalStatisticsDao object */
	var $journalStatisticsDao;

	/** @var $authorDao object */
	var $authorDao;

	/** @var $authorSubmissionDao object */
	var $authorSubmissionDao;

	/** @var $maxAuthorCount int If authors or affiliations column is included, this is the most authors that can be expected for an article. */
	var $maxAuthorCount;

	/** @var $maxReviewerCount int If reviewers column is included, this is the most reviewers that can be expected for a submission. */
	var $maxReviewerCount;

	/**
	 * Constructor.
	 * Initialize the JournalReportIterator
	 * @param $journalId int ID of journal this report is generated on
	 * @param $records object ADO record set
	 * @param $fields array Set of fields included in this report
	 */
	function JournalReportIterator($journalId, &$records, &$fields, $dateStart, $dateEnd) {
		parent::DBRowIterator($records);
		$this->fields =& $fields;

		$this->journalStatisticsDao =& DAORegistry::getDAO('JournalStatisticsDAO');

		$this->altLocaleNum = Locale::isAlternateJournalLocale($journalId);

		if ($this->hasField('authors') || $this->hasField('affiliations')) {
			$this->authorDao =& DAORegistry::getDao('AuthorDAO');
			$this->maxAuthorCount = $this->journalStatisticsDao->getMaxAuthorCount($journalId, $dateStart, $dateEnd);
		}

		if ($this->hasField('reviewers')) {
			$this->reviewAssignmentDao =& DAORegistry::getDAO('ReviewAssignmentDAO');
			$this->maxReviewerCount = $this->journalStatisticsDao->getMaxReviewerCount($journalId, $dateStart, $dateEnd);
		}

		if ($this->hasField('status') || $this->hasField('dateDecided')) {
			$this->authorSubmissionDao =& DAORegistry::getDAO('AuthorSubmissionDAO');
		}
	}

	/**
	 * Return the object representing the next row.
	 * @return object
	 */
	function &next() {
		$row =& parent::next();
		if ($row == null) return $row;

		$ret = array(
			'submissionId' => $row['article_id']
		);

		if ($this->hasField('dateSubmitted'))
			$ret['dateSubmitted'] = $row['date_submitted'];

		if ($this->hasField('title'))
			$ret['title'] = $row['submission_title'];

		// Localize the section title, if it was requested
		if ($this->hasField('section')) {
			$ret['section'] = null;
			switch ($this->altLocaleNum) {
				case 1: $ret['section'] = $row['section_title_alt1']; break;
				case 2: $ret['section'] = $row['section_title_alt2']; break;
			}
			if (empty($ret['section'])) $ret['section'] = $row['section_title'];
		}

		// Author Names & Affiliations
		$hasAuthors = $this->hasField('authors');
		$hasAffiliations = $this->hasField('affiliations');
		if ($hasAuthors || $hasAffiliations) {
			if ($hasAuthors) $ret['authors'] = array_fill(0, $this->getMaxAuthors(), '');
			if ($hasAffiliations) $ret['affiliations'] = array_fill(0, $this->getMaxAuthors(), '');
			$authors =& $this->authorDao->getAuthorsByArticle($row['article_id']);
			$authorIndex = 0;
			foreach ($authors as $author) {
				if ($hasAuthors) $ret['authors'][$authorIndex] = $author->getFullName();
				if ($hasAffiliations) $ret['affiliations'][$authorIndex] = $author->getAffiliation();
				$authorIndex++;
			}
		}

		// Editor Names
		if ($this->hasField('editor')) {
			$lastName = $row['editor_last_name'];
			$middleName = $row['editor_middle_name'];
			$firstName = $row['editor_first_name'];
			if (!empty($middleName)) {
				$ret['editor'] = "$lastName, $firstName $middleName";
			} elseif (!empty($firstName) && !empty($lastName)) {
				$ret['editor'] = "$lastName, $firstName";
			} else {
				$ret['editor'] = '';
			}
		}

		// Reviewer Names
		if ($this->hasField('reviewers')) {
			$ret['reviewers'] = array_fill(0, $this->getMaxReviewers(), '');
			$reviewAssignments =& $this->reviewAssignmentDao->getReviewAssignmentsByArticleId($row['article_id']);
			$reviewerIndex = 0;
			$reviewerIds = array();
			foreach ($reviewAssignments as $reviewAssignment) {
				$reviewerId = $reviewAssignment->getReviewerId();
				if (!empty($reviewerId) && !in_array($reviewerId, $reviewerIds)) {
					array_push($reviewerIds, $reviewerId);
					$ret['reviewers'][$reviewerIndex] = $reviewAssignment->getReviewerFullName();
					$reviewerIndex++;
				}
			}
		}

		if ($this->hasField('dateDecided')) {
			// Fetch the last editorial decision for this article.
			$editorDecisions =& $this->authorSubmissionDao->getEditorDecisions($row['article_id']);
			$lastDecision = array_pop($editorDecisions);
			$ret['dateDecided'] = ($lastDecision?$lastDecision['dateDecided']:'');
		}

		if ($this->hasField('status')) {
			$ret['status'] = $row['status'];
		}

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
	 * Return true iff this report contains the specified field
	 */
	function hasField($name) {
		if ($this->fields !== null) return in_array($name, $this->fields);
		return $this->fields;
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
