<?php

/**
 * @file classes/journal/SectionDAO.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
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
			$this->cache = $cacheManager->getObjectCache('sections', 0, array($this, '_cacheMiss'));
		}
		return $this->cache;
	}

	/**
	 * Retrieve a section by ID.
	 * @param $sectionId int
	 * @param $journalId int Journal ID optional
	 * @param $useCache boolean optional
	 * @return Section
	 */
	function getById($sectionId, $journalId = null, $useCache = false) {
		if ($useCache) {
			$cache = $this->_getCache();
			$returner = $cache->get($sectionId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		$sql = 'SELECT * FROM sections WHERE section_id = ?';
		$params = array((int) $sectionId);
		if ($journalId !== null) {
			$sql .= ' AND journal_id = ?';
			$params[] = (int) $journalId;
		}
		$result = $this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();

		return $returner;
	}

	/**
	 * Retrieve a section by abbreviation.
	 * @param $sectionAbbrev string
	 * @param $journalId int Journal ID
	 * @param $locale string optional
	 * @return Section
	 */
	function getByAbbrev($sectionAbbrev, $journalId, $locale = null) {
		$params = array('abbrev', $sectionAbbrev, (int) $journalId);
		if ($locale !== null) {
			$params[] = $locale;
		}

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

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
	}

	/**
	 * Retrieve a section by title.
	 * @param $sectionTitle string
	 * @param $journalId int Journal ID
	 * @param $locale string optional
	 * @return Section
	 */
	function getByTitle($sectionTitle, $journalId, $locale = null) {
		$params = array('title', $sectionTitle, (int) $journalId);
		if ($locale !== null) {
			$params[] = $locale;
		}

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

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		return $returner;
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
			array((int) $submissionId));

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();

		return $returner;
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
			array('abbrev', 'identifyType', 'description')
		);
	}

	/**
	 * Get the list of fields for which data can not be localized.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		return array_merge(
			parent::getAdditionalFieldNames(),
			array('path')
		);
	}

	/**
	 * Update the localized fields for this table
	 * @param $section object
	 */
	function updateLocaleFields($section) {
		$this->updateDataObjectSettings('section_settings', $section, array(
			'section_id' => $section->getId()
		));
	}

	/**
	 * Insert a new section.
	 * @param $section Section
	 * @return int new Section ID
	 */
	function insertObject($section) {
		$this->update(
			'INSERT INTO sections
				(journal_id, review_form_id, seq, meta_indexed, meta_reviewed, abstracts_not_required, editor_restricted, hide_title, hide_author, abstract_word_count)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int)$section->getJournalId(),
				(int)$section->getReviewFormId(),
				(float) $section->getSequence(),
				$section->getMetaIndexed() ? 1 : 0,
				$section->getMetaReviewed() ? 1 : 0,
				$section->getAbstractsNotRequired() ? 1 : 0,
				$section->getEditorRestricted() ? 1 : 0,
				$section->getHideTitle() ? 1 : 0,
				$section->getHideAuthor() ? 1 : 0,
				(int) $section->getAbstractWordCount()
			)
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
					abstract_word_count = ?
				WHERE section_id = ?',
			array(
				(int)$section->getReviewFormId(),
				(float) $section->getSequence(),
				(int)$section->getMetaIndexed(),
				(int)$section->getMetaReviewed(),
				(int)$section->getAbstractsNotRequired(),
				(int)$section->getEditorRestricted(),
				(int)$section->getHideTitle(),
				(int)$section->getHideAuthor(),
				$this->nullOrInt($section->getAbstractWordCount()),
				(int)$section->getId()
			)
		);
		$this->updateLocaleFields($section);
	}

	/**
	 * Delete a section by ID.
	 * @param $sectionId int Section ID
	 * @param $contextId int optional
	 */
	function deleteById($sectionId, $contextId = null) {
		$subEditorsDao = DAORegistry::getDAO('SubEditorsDAO');
		$subEditorsDao->deleteBySectionId($sectionId, $contextId);

		// Remove articles from this section
		$submissionDao = DAORegistry::getDAO('SubmissionDAO'); /* @var $submissionDao SubmissionDAO */
		$submissionDao->removeSubmissionsFromSection($sectionId);

		if (isset($contextId) && !$this->sectionExists($sectionId, $contextId)) return false;
		$this->update('DELETE FROM section_settings WHERE section_id = ?', (int) $sectionId);
		$this->update('DELETE FROM sections WHERE section_id = ?', (int) $sectionId);
	}

	/**
	 * Delete sections by journal ID
	 * NOTE: This does not delete dependent entries EXCEPT from section_editors. It is intended
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
		$returner = array();

		$result = $this->retrieve(
			'SELECT s.*, se.user_id AS editor_id FROM section_editors se, sections s WHERE se.section_id = s.section_id AND s.journal_id = se.context_id AND s.journal_id = ?',
			(int) $journalId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$section = $this->_fromRow($row);
			if (!isset($returner[$row['editor_id']])) {
				$returner[$row['editor_id']] = array($section);
			} else {
				$returner[$row['editor_id']][] = $section;
			}
			$result->MoveNext();
		}

		$result->Close();
		return $returner;
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
			(int) $journalId, $rangeInfo
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
			false, $rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve all empty (without articles) section ids for a journal.
	 * @param $journalId int Journal ID
	 * @return array
	 */
	function getEmptyByJournalId($journalId) {
		$result = $this->retrieve(
			'SELECT s.section_id FROM sections s LEFT JOIN submissions a ON (a.section_id = s.section_id) WHERE a.section_id IS NULL AND s.journal_id = ?',
			(int) $journalId
		);

		$returner = array();
		while (!$result->EOF) {
			$returner[] = $result->fields[0];
			$result->MoveNext();
		}
		$result->Close();
		return $returner;
	}

	/**
	 * Check if a section exists with the specified ID.
	 * @param $sectionId int Section ID
	 * @param $journalId int Journal ID
	 * @return boolean
	 */
	function sectionExists($sectionId, $journalId) {
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM sections WHERE section_id = ? AND journal_id = ?',
			array((int) $sectionId, (int) $journalId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Sequentially renumber sections in their sequence order.
	 * @param $journalId int Journal ID
	 */
	function resequenceSections($journalId) {
		$result = $this->retrieve(
			'SELECT section_id FROM sections WHERE journal_id = ? ORDER BY seq',
			(int) $journalId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($sectionId) = $result->fields;
			$this->update(
				'UPDATE sections SET seq = ? WHERE section_id = ?',
				array(
					$i,
					$sectionId
				)
			);

			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Get the ID of the last inserted section.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('sections', 'section_id');
	}

}
