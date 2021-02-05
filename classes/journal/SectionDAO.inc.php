<?php

/**
 * @file classes/journal/SectionDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionDAO
 * @ingroup journal
 * @see Section
 *
 * @brief Operations for retrieving and modifying Section objects.
 */

import ('classes.journal.Section');
import ('lib.pkp.classes.context.PKPSectionDAO');

class SectionDAO extends PKPSectionDAO {
	var $cache;

	/**
	 * Get the name of the section table in the database
	 *
	 * @return string
	 */
	protected function _getTableName() {
		return 'sections';
	}

	/**
	 * Get the name of the context ID table column
	 *
	 * @return string
	 */
	protected function _getContextIdColumnName() {
		return 'journal_id';
	}

	function _cacheMiss($cache, $id) {
		$section = $this->getById($id, null, false);
		$cache->setCache($id, $section);
		return $section;
	}

	function &_getCache() {
		if (!isset($this->cache)) {
			$cacheManager = CacheManager::getManager();
			$this->cache = $cacheManager->getObjectCache('sections', 0, [$this, '_cacheMiss']);
		}
		return $this->cache;
	}

	/**
	 * Retrieve a section by ID.
	 * @param $sectionId int
	 * @param $journalId int Journal ID optional
	 * @param $useCache boolean optional
	 * @return Section?
	 */
	function getById($sectionId, $journalId = null, $useCache = false) {
		if ($useCache) {
			$cache = $this->_getCache();
			$returner = $cache->get($sectionId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		$params = [(int) $sectionId];
		if ($journalId !== null) $params[] = (int) $journalId;
		$result = $this->retrieve(
			'SELECT * FROM sections WHERE section_id = ?'
			. ($journalId !== null ? ' AND journal_id = ?' : ''),
			$params
		);

		$row = $result->current();
		return $row?$this->_fromRow((array) $row):null;
	}

	/**
	 * Retrieve a section by abbreviation.
	 * @param $sectionAbbrev string
	 * @param $journalId int Journal ID
	 * @param $locale string optional
	 * @return Section?
	 */
	function getByAbbrev($sectionAbbrev, $journalId, $locale = null) {
		$params = ['abbrev', $sectionAbbrev, (int) $journalId];
		if ($locale !== null) $params[] = $locale;

		$result = $this->retrieve(
			'SELECT	s.*
			FROM	sections s, section_settings l
			WHERE	l.section_id = s.section_id AND
				l.setting_name = ? AND
				l.setting_value = ? AND
				s.journal_id = ?' .
				($locale!==null?' AND l.locale = ?':''),
			$params
		);

		$row = $result->current();
		return $row?$this->_fromRow((array) $row):null;
	}

	/**
	 * Retrieve a section by title.
	 * @param $sectionTitle string
	 * @param $journalId int Journal ID
	 * @param $locale string optional
	 * @return Section?
	 */
	function getByTitle($sectionTitle, $journalId, $locale = null) {
		$params = ['title', $sectionTitle, (int) $journalId];
		if ($locale !== null) $params[] = $locale;

		$result = $this->retrieve(
			'SELECT	s.*
			FROM	sections s, section_settings l
			WHERE	l.section_id = s.section_id AND
				l.setting_name = ? AND
				l.setting_value = ? AND
				s.journal_id = ?' .
				($locale !== null?' AND l.locale = ?':''),
			$params
		);

		$row = $result->current();
		return $row?$this->_fromRow((array) $row):null;
	}

	/**
	 * Retrieve section a submission is assigned to.
	 * @param $submissionId int Submission id
	 * @return Section
	 */
	public function getBySubmissionId($submissionId) {
		$result = $this->retrieve('SELECT sections.* FROM sections
				JOIN submissions
				ON (submissions.section_id = sections.section_id)
				WHERE submissions.submission_id = ?',
			[(int) $submissionId]
		);

		$row = $result->current();
		return $row?$this->_fromRow((array) $row):null;
	}

	/**
	 * Return a new data object.
	 */
	function newDataObject() {
		return new Section();
	}

	/**
	 * Internal function to return a Section object from a row.
	 * @param $row array
	 * @return Section
	 */
	function _fromRow($row) {
		$section = parent::_fromRow($row);

		$section->setId($row['section_id']);
		$section->setJournalId($row['journal_id']);
		$section->setMetaIndexed($row['meta_indexed']);
		$section->setMetaReviewed($row['meta_reviewed']);
		$section->setAbstractsNotRequired($row['abstracts_not_required']);
		$section->setHideTitle($row['hide_title']);
		$section->setHideAuthor($row['hide_author']);
		$section->setIsInactive($row['is_inactive']);
		$section->setAbstractWordCount($row['abstract_word_count']);

		$this->getDataObjectSettings('section_settings', 'section_id', $row['section_id'], $section);

		HookRegistry::call('SectionDAO::_fromRow', array(&$section, &$row));

		return $section;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array_merge(
			parent::getLocaleFieldNames(),
			['abbrev', 'identifyType']
		);
	}

	/**
	 * Update the localized fields for this table
	 * @param $section object
	 */
	function updateLocaleFields($section) {
		$this->updateDataObjectSettings('section_settings', $section,
			['section_id' => $section->getId()]
		);
	}

	/**
	 * Insert a new section.
	 * @param $section Section
	 * @return int new Section ID
	 */
	function insertObject($section) {
		$this->update(
			'INSERT INTO sections
				(journal_id, review_form_id, seq, meta_indexed, meta_reviewed, abstracts_not_required, editor_restricted, hide_title, hide_author, is_inactive, abstract_word_count)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			[
				(int)$section->getJournalId(),
				(int)$section->getReviewFormId(),
				(float) $section->getSequence(),
				$section->getMetaIndexed() ? 1 : 0,
				$section->getMetaReviewed() ? 1 : 0,
				$section->getAbstractsNotRequired() ? 1 : 0,
				$section->getEditorRestricted() ? 1 : 0,
				$section->getHideTitle() ? 1 : 0,
				$section->getHideAuthor() ? 1 : 0,
				$section->getIsInactive() ? 1 : 0,
				(int) $section->getAbstractWordCount()
			]
		);

		$section->setId($this->getInsertId());
		$this->updateLocaleFields($section);
		return $section->getId();
	}

	/**
	 * Update an existing section.
	 * @param $section Section
	 */
	function updateObject($section) {
		$this->update(
			'UPDATE sections
				SET
					review_form_id = ?,
					seq = ?,
					meta_indexed = ?,
					meta_reviewed = ?,
					abstracts_not_required = ?,
					editor_restricted = ?,
					hide_title = ?,
					hide_author = ?,
					is_inactive = ?,
					abstract_word_count = ?
				WHERE section_id = ?',
			[
				(int)$section->getReviewFormId(),
				(float) $section->getSequence(),
				(int)$section->getMetaIndexed(),
				(int)$section->getMetaReviewed(),
				(int)$section->getAbstractsNotRequired(),
				(int)$section->getEditorRestricted(),
				(int)$section->getHideTitle(),
				(int)$section->getHideAuthor(),
				(int)$section->getIsInactive(),
				$this->nullOrInt($section->getAbstractWordCount()),
				(int)$section->getId()
			]
		);
		$this->updateLocaleFields($section);
	}

	/**
	 * Delete a section by ID.
	 * @param $sectionId int Section ID
	 * @param $contextId int optional
	 */
	function deleteById($sectionId, $contextId = null) {
		$subEditorsDao = DAORegistry::getDAO('SubEditorsDAO'); /* @var $subEditorsDao SubEditorsDAO */
		$subEditorsDao->deleteBySubmissionGroupId($sectionId, ASSOC_TYPE_SECTION, $contextId);

		// Remove articles from this section
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$submissionDao->removeSubmissionsFromSection($sectionId);

		if (isset($contextId) && !$this->sectionExists($sectionId, $contextId)) return false;
		$this->update('DELETE FROM section_settings WHERE section_id = ?', [(int) $sectionId]);
		$this->update('DELETE FROM sections WHERE section_id = ?', [(int) $sectionId]);
	}

	/**
	 * Delete sections by journal ID
	 * NOTE: This does not delete dependent entries EXCEPT from subeditor_submission_group. It is intended
	 * to be called only when deleting a journal.
	 * @param $journalId int Journal ID
	 */
	function deleteByJournalId($journalId) {
		$this->deleteByContextId($journalId);
	}

	/**
	 * Retrieve an array associating all section editor IDs with
	 * arrays containing the sections they edit.
	 * @param $journalId int Journal ID
	 * @return array editorId => array(sections they edit)
	 */
	function getEditorSections($journalId) {

		$result = $this->retrieve(
			'SELECT s.*, se.user_id AS editor_id FROM subeditor_submission_group ssg, sections s WHERE ssg.assoc_id = s.section_id AND ssg.assoc_type = ? AND s.journal_id = ssg.context_id AND s.journal_id = ?',
			[(int) ASSOC_TYPE_SECTION, (int) $journalId]
		);

		$returner = [];
		foreach ($result as $row) {
			$section = $this->_fromRow((array) $row);
			if (!isset($returner[$row->editor_id])) {
				$returner[$row->editor_id] = [$section];
			} else {
				$returner[$row->editor_id][] = $section;
			}
		}
		return $returner;
	}

	/**
	 * Retrieve all sections in which articles are currently published in
	 * the given issue.
	 * @param $issueId int Issue ID
	 * @return array
	 */
	function getByIssueId($issueId) {
		import ('classes.submission.Submission'); // import STATUS_* constants
		$issue = Services::get('issue')->get($issueId);
		$allowedStatuses = [STATUS_PUBLISHED];
		if (!$issue->getPublished()) {
			$allowedStatuses[] = STATUS_SCHEDULED;
		}
		$submissionsIterator = Services::get('submission')->getMany([
			'contextId' => $issue->getJournalId(),
			'issueIds' => $issueId,
			'status' => $allowedStatuses,
		]);
		$sectionIds = [];
		foreach ($submissionsIterator as $submission) {
			$sectionIds[] = $submission->getCurrentPublication()->getData('sectionId');
		}
		if (empty($sectionIds)) {
			return [];
		}
		$sectionIds = array_unique($sectionIds);
		$result = $this->retrieve(
			'SELECT s.*, COALESCE(o.seq, s.seq) AS section_seq
				FROM sections s
				LEFT JOIN custom_section_orders o ON (s.section_id = o.section_id AND o.issue_id = ?)
				WHERE s.section_id IN (' . substr(str_repeat('?,', count($sectionIds)), 0, -1) . ')
				ORDER BY section_seq',
			array_merge([(int) $issueId], $sectionIds)
		);

		$sections = [];
		foreach ($result as $row) {
			$sections[] = $this->_fromRow((array) $row);
		}
		return $sections;
	}

	/**
	 * Retrieve all sections for a journal.
	 * @param $journalId int Journal ID
	 * @param $rangeInfo DBResultRange optional
	 * @return DAOResultFactory containing Sections ordered by sequence
	 */

	function getByJournalId($journalId, $rangeInfo = null) {
		return $this->getByContextId($journalId, $rangeInfo);
	}

	/**
	 * Retrieve all sections for a journal.
	 * @param $journalId int Journal ID
	 * @param $rangeInfo DBResultRange optional
	 * @param $submittableOnly boolean optional. Whether to return only sections
	 *  that can be submitted to by anyone.
	 * @return DAOResultFactory containing Sections ordered by sequence
	 */
	 function getByContextId($journalId, $rangeInfo = null, $submittableOnly = false) {
		$result = $this->retrieveRange(
			'SELECT * FROM sections WHERE journal_id = ? ' . ($submittableOnly ? ' AND editor_restricted = 0' : '') . ' ORDER BY seq',
			[(int) $journalId], $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve all sections.
	 * @param $rangeInfo DBResultRange optional
	 * @return DAOResultFactory containing Sections ordered by journal ID and sequence
	 */
	function getAll($rangeInfo = null) {
		$result = $this->retrieveRange(
			'SELECT * FROM sections ORDER BY journal_id, seq',
			[], $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Check if the section is empty.
	 * @param $sectionId int Section ID
	 * @param $journalId int Journal ID
	 * @return boolean
	 */
	function sectionEmpty($sectionId, $journalId) {
		$result = $this->retrieve(
			'SELECT p.publication_id FROM publications p JOIN submissions s ON (s.submission_id = p.submission_id) WHERE p.section_id = ? AND s.context_id = ?', 
			[(int) $sectionId, (int) $journalId]
		);
		$row = $result->current();
		return $row ? false : true;
	}

	/**
	 * Check if a section exists with the specified ID.
	 * @param $sectionId int Section ID
	 * @param $journalId int Journal ID
	 * @return boolean
	 */
	function sectionExists($sectionId, $journalId) {
		$result = $this->retrieve(
			'SELECT COUNT(*) AS row_count FROM sections WHERE section_id = ? AND journal_id = ?',
			[(int) $sectionId, (int) $journalId]
		);
		$row = $result->current();
		return $row ? (boolean) $row->row_count : false;
	}

	/**
	 * Sequentially renumber sections in their sequence order.
	 * @param $journalId int Journal ID
	 */
	function resequenceSections($journalId) {
		$result = $this->retrieve('SELECT section_id FROM sections WHERE journal_id = ? ORDER BY seq', [(int) $journalId]);

		for ($i=1; $row = $result->current(); $i++) {
			$this->update('UPDATE sections SET seq = ? WHERE section_id = ?', [$i, $row->section_id]);
			$result->next();
		}
	}

	/**
	 * Get the ID of the last inserted section.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('sections', 'section_id');
	}

	/**
	 * Delete the custom ordering of an issue's sections.
	 * @param $issueId int
	 */
	function deleteCustomSectionOrdering($issueId) {
		$this->update(
			'DELETE FROM custom_section_orders WHERE issue_id = ?', [(int) $issueId]
		);
	}

	/**
	 * Delete a section from the custom section order table.
	 * @param $issueId int
	 * @param $sectionId int
	 */
	function deleteCustomSection($issueId, $sectionId) {
		$seq = $this->getCustomSectionOrder($issueId, $sectionId);

		$this->update(
			'DELETE FROM custom_section_orders WHERE issue_id = ? AND section_id = ?',
			[(int) $issueId, (int) $sectionId]
		);

		// Reduce the section order of every successive section by one
		$this->update(
			'UPDATE custom_section_orders SET seq = seq - 1 WHERE issue_id = ? AND seq > ?',
			[(int) $issueId, (float) $seq]
		);
	}

	/**
	 * Sequentially renumber custom section orderings in their sequence order.
	 * @param $issueId int
	 */
	function resequenceCustomSectionOrders($issueId) {
		$result = $this->retrieve('SELECT section_id FROM custom_section_orders WHERE issue_id = ? ORDER BY seq', [(int) $issueId]);

		for ($i=1; $row = $result->current(); $i++) {
			$this->update('UPDATE custom_section_orders SET seq = ? WHERE section_id = ? AND issue_id = ?', [$i, $row->section_id, (int) $issueId]);
			$result->next();
		}
	}

	/**
	 * Check if an issue has custom section ordering.
	 * @param $issueId int
	 * @return boolean
	 */
	function customSectionOrderingExists($issueId) {
		$result = $this->retrieve('SELECT COUNT(*) AS row_count FROM custom_section_orders WHERE issue_id = ?', [(int) $issueId]);
		$row = $result->current();
		return $row && $row->row_count != 0;
	}

	/**
	 * Get the custom section order of a section.
	 * @param $issueId int
	 * @param $sectionId int
	 * @return int?
	 */
	function getCustomSectionOrder($issueId, $sectionId) {
		$result = $this->retrieve(
			'SELECT seq FROM custom_section_orders WHERE issue_id = ? AND section_id = ?',
			[(int) $issueId, (int) $sectionId]
		);
		$row = $result->current();
		return $row?$row->seq:null;
	}

	/**
	 * Import the current section orders into the specified issue as custom
	 * issue orderings.
	 * @param $issueId int
	 */
	function setDefaultCustomSectionOrders($issueId) {
		$issueSections = $this->getByIssueId($issueId);
		$i = 1;
		foreach ($issueSections as $section) {
			$this->insertCustomSectionOrder($issueId, $section->getId(), $i);
			$i++;
		}
	}

	/**
	 * INTERNAL USE ONLY: Insert a custom section ordering
	 * @param $issueId int
	 * @param $sectionId int
	 * @param $seq int
	 */
	function insertCustomSectionOrder($issueId, $sectionId, $seq) {
		$this->update(
			'INSERT INTO custom_section_orders (section_id, issue_id, seq) VALUES (?, ?, ?)',
			[(int) $sectionId,(int) $issueId, (float) $seq]
		);
	}

	/**
	 * Update a custom section ordering
	 * @param $issueId int
	 * @param $sectionId int
	 * @param $seq int
	 */
	function updateCustomSectionOrder($issueId, $sectionId, $seq) {
		$this->update(
			'UPDATE custom_section_orders SET seq = ? WHERE issue_id = ? AND section_id = ?',
			[(float) $seq, (int) $issueId, (int) $sectionId]
		);
	}
}
