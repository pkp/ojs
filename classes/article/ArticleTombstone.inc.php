<?php

/**
 * @file classes/article/ArticleTombstone.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleTombstone
 * @ingroup article
 * @see ArticleTombstoneDAO
 *
 * @brief Class for article tombstones.
 */

import('lib.pkp.classes.submission.SubmissionTombstone');

class ArticleTombstone extends SubmissionTombstone {
	/**
	 * Constructor.
	 */
	function ArticleTombstone() {
		parent::SubmissionTombstone();
	}

	/**
	 * get journal id
	 * @return int
	 */
	function getJournalId() {
		return $this->getData('journalId');
	}

	/**
	 * set journal id
	 * @param $journalId int
	 */
	function setJournalId($journalId) {
		return $this->setData('journalId', $journalId);
	}

	/**
	 * get section id
	 * @return int
	 */
	function getSectionId() {
		return $this->getData('sectionId');
	}

	/**
	 * set section id
	 * @param $sectionId int
	 */
	function setSectionId($sectionId) {
		return $this->setData('sectionId', $sectionId);
	}
}

?>