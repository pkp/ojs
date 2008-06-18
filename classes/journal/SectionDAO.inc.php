<?php

/**
 * @file SectionDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 * @class SectionDAO
 *
 * Class for section DAO.
 * Operations for retrieving and modifying Section objects.
 *
 * $Id$
 */

import ('journal.Section');

class SectionDAO extends DAO {
	/**
	 * Retrieve a section by ID.
	 * @param $sectionId int
	 * @return Section
	 */
	function &getSection($sectionId, $journalId = null) {
		$sql = 'SELECT * FROM sections WHERE section_id = ?';
		$params = array($sectionId);
		if ($journalId !== null) {
			$sql .= ' AND journal_id = ?';
			$params[] = $journalId;
		}
		$result = &$this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSectionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a section by abbreviation.
	 * @param $sectionAbbrev string
	 * @param $locale string optional
	 * @return Section
	 */
	function &getSectionByAbbrev($sectionAbbrev, $journalId, $locale = null) {
		$sql = 'SELECT s.* FROM sections s, section_settings l WHERE l.section_id = s.section_id AND l.setting_name = ? AND l.setting_value = ? AND s.journal_id = ?';
		$params = array('abbrev', $sectionAbbrev, $journalId);
		if ($locale !== null) {
			$sql .= ' AND l.locale = ?';
			$params[] = $locale;
		}

		$result = &$this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSectionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a section by title.
	 * @param $sectionTitle string
	 * @return Section
	 */
	function &getSectionByTitle($sectionTitle, $journalId, $locale = null) {
		$sql = 'SELECT s.* FROM sections s, section_settings l WHERE l.section_id = s.section_id AND l.setting_name = ? AND l.setting_value = ? AND s.journal_id = ?';
		$params = array('title', $sectionTitle, $journalId);
		if ($locale !== null) {
			$sql .= ' AND l.locale = ?';
			$params[] = $locale;
		}

		$result = &$this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSectionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a section by title and abbrev.
	 * @param $sectionTitle string
	 * @param $sectionAbbrev string
	 * @param $locale string optional
	 * @return Section
	 */
	function &getSectionByTitleAndAbbrev($sectionTitle, $sectionAbbrev, $journalId, $locale) {
		$sql = 'SELECT s.* FROM sections s, section_settings l1, section_settings l2 WHERE l1.section_id = s.section_id AND l2.section_id = s.section_id AND l1.setting_name = ? AND l2.setting_name = ? AND l1.setting_value = ? AND l2.setting_value = ? AND s.journal_id = ?';
		$params = array('title', 'abbrev', $sectionTitle, $sectionAbbrev, $journalId);
		if ($locale !== null) {
			$sql .= ' AND l1.locale = ? AND l2.locale = ?';
			$params[] = $locale;
			$params[] = $locale;
		}

		$result = &$this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSectionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return a Section object from a row.
	 * @param $row array
	 * @return Section
	 */
	function &_returnSectionFromRow(&$row) {
		$section = &new Section();
		$section->setSectionId($row['section_id']);
		$section->setJournalId($row['journal_id']);
		$section->setSequence($row['seq']);
		$section->setMetaIndexed($row['meta_indexed']);
		$section->setMetaReviewed($row['meta_reviewed']);
		$section->setAbstractsNotRequired($row['abstracts_not_required']);
		$section->setEditorRestricted($row['editor_restricted']);
		$section->setHideTitle($row['hide_title']);
		$section->setHideAuthor($row['hide_author']);
		$section->setHideAbout($row['hide_about']);
		$section->setDisableComments($row['disable_comments']);

		$this->getDataObjectSettings('section_settings', 'section_id', $row['section_id'], $section);

		HookRegistry::call('SectionDAO::_returnSectionFromRow', array(&$section, &$row));

		return $section;
	}

	/**
	 * Get the list of fields for which data can be localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'abbrev', 'policy', 'identifyType');
	}

	/**
	 * Update the localized fields for this table
	 * @param $section object
	 */
	function updateLocaleFields(&$section) {
		$this->updateDataObjectSettings('section_settings', $section, array(
			'section_id' => $section->getSectionId()
		));
	}

	/**
	 * Insert a new section.
	 * @param $section Section
	 */	
	function insertSection(&$section) {
		$this->update(
			'INSERT INTO sections
				(journal_id, seq, meta_indexed, meta_reviewed, abstracts_not_required, editor_restricted, hide_title, hide_author, hide_about, disable_comments)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$section->getJournalId(),
				$section->getSequence() == null ? 0 : $section->getSequence(),
				$section->getMetaIndexed() ? 1 : 0,
				$section->getMetaReviewed() ? 1 : 0,
				$section->getAbstractsNotRequired() ? 1 : 0,
				$section->getEditorRestricted() ? 1 : 0,
				$section->getHideTitle() ? 1 : 0,
				$section->getHideAuthor() ? 1 : 0,
				$section->getHideAbout() ? 1 : 0,
				$section->getDisableComments() ? 1 : 0
			)
		);

		$section->setSectionId($this->getInsertSectionId());
		$this->updateLocaleFields($section);
		return $section->getSectionId();
	}

	/**
	 * Update an existing section.
	 * @param $section Section
	 */
	function updateSection(&$section) {
		$returner = $this->update(
			'UPDATE sections
				SET
					seq = ?,
					meta_indexed = ?,
					meta_reviewed = ?,
					abstracts_not_required = ?,
					editor_restricted = ?,
					hide_title = ?,
					hide_author = ?,
					hide_about = ?,
					disable_comments = ?
				WHERE section_id = ?',
			array(
				$section->getSequence(),
				$section->getMetaIndexed(),
				$section->getMetaReviewed(),
				$section->getAbstractsNotRequired(),
				$section->getEditorRestricted(),
				$section->getHideTitle(),
				$section->getHideAuthor(),
				$section->getHideAbout(),
				$section->getDisableComments(),
				$section->getSectionId()
			)
		);
		$this->updateLocaleFields($section);
		return $returner;
	}

	/**
	 * Delete a section.
	 * @param $section Section
	 */
	function deleteSection(&$section) {
		return $this->deleteSectionById($section->getSectionId(), $section->getJournalId());
	}

	/**
	 * Delete a section by ID.
	 * @param $sectionId int
	 * @param $journalId int optional
	 */
	function deleteSectionById($sectionId, $journalId = null) {
		$sectionEditorsDao = &DAORegistry::getDAO('SectionEditorsDAO');
		$sectionEditorsDao->deleteEditorsBySectionId($sectionId, $journalId);

		// Remove articles from this section
		$articleDao = &DAORegistry::getDAO('ArticleDAO');
		$articleDao->removeArticlesFromSection($sectionId);

		// Delete published article entries from this section -- they must
		// be re-published.
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->deletePublishedArticlesBySectionId($sectionId);

		if (isset($journalId) && !$this->sectionExists($sectionId, $journalId)) return false;
		$this->update('DELETE FROM section_settings WHERE section_id = ?', array($sectionId));
		return $this->update('DELETE FROM sections WHERE section_id = ?', array($sectionId));
	}

	/**
	 * Delete sections by journal ID
	 * NOTE: This does not delete dependent entries EXCEPT from section_editors. It is intended
	 * to be called only when deleting a journal.
	 * @param $journalId int
	 */
	function deleteSectionsByJournal($journalId) {
		$sections =& $this->getJournalSections($journalId);
		while (($section =& $sections->next())) {
			$this->deleteSection($section);
			unset($section);
		}
	}

	/**
	 * Retrieve an array associating all section editor IDs with 
	 * arrays containing the sections they edit.
	 * @return array editorId => array(sections they edit)
	 */
	function &getEditorSections($journalId) {
		$returner = array();

		$result = &$this->retrieve(
			'SELECT s.*, se.user_id AS editor_id FROM section_editors se, sections s WHERE se.section_id = s.section_id AND s.journal_id = se.journal_id AND s.journal_id = ?',
			$journalId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$section = &$this->_returnSectionFromRow($row);
			if (!isset($returner[$row['editor_id']])) {
				$returner[$row['editor_id']] = array($section);
			} else {
				$returner[$row['editor_id']][] = $section;
			}
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all sections in which articles are currently published in
	 * the given issue.
	 * @return array
	 */
	function &getSectionsForIssue($issueId) {
		$returner = array();

		$result = &$this->retrieve(
			'SELECT DISTINCT s.*, COALESCE(o.seq, s.seq) AS section_seq FROM sections s, published_articles pa, articles a LEFT JOIN custom_section_orders o ON (a.section_id = o.section_id AND o.issue_id = ?) WHERE s.section_id = a.section_id AND pa.article_id = a.article_id AND pa.issue_id = ? ORDER BY section_seq',
			array($issueId, $issueId)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[] = &$this->_returnSectionFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all sections for a journal.
	 * @return DAOResultFactory containing Sections ordered by sequence
	 */
	function &getJournalSections($journalId, $rangeInfo = null) {
		$result = &$this->retrieveRange(
			'SELECT * FROM sections WHERE journal_id = ? ORDER BY seq',
			$journalId, $rangeInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnSectionFromRow');
		return $returner;
	}

	/**
	 * Retrieve the IDs and titles of the sections for a journal in an associative array.
	 * @return array
	 */
	function &getSectionTitles($journalId, $submittableOnly = false) {
		$sections = array();

		$sectionsIterator =& $this->getJournalSections($journalId);
		while (($section =& $sectionsIterator->next())) {
			if ($submittableOnly) {
				if (!$section->getEditorRestricted()) {
					$sections[$section->getSectionId()] = $section->getSectionTitle();
				}
			} else {
				$sections[$section->getSectionId()] = $section->getSectionTitle();
			}
			unset($section);
		}

		return $sections;
	}

	/**
	 * Check if a section exists with the specified ID.
	 * @param $sectionId int
	 * @param $journalId int
	 * @return boolean
	 */
	function sectionExists($sectionId, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM sections WHERE section_id = ? AND journal_id = ?',
			array($sectionId, $journalId)
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber sections in their sequence order.
	 * @param $journalId int
	 */
	function resequenceSections($journalId) {
		$result = &$this->retrieve(
			'SELECT section_id FROM sections WHERE journal_id = ? ORDER BY seq',
			$journalId
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

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the ID of the last inserted section.
	 * @return int
	 */
	function getInsertSectionId() {
		return $this->getInsertId('sections', 'section_id');
	}

	/**
	 * Delete the custom ordering of an issue's sections.
	 * @param $issueId int
	 */
	function deleteCustomSectionOrdering($issueId) {
		return $this->update(
			'DELETE FROM custom_section_orders WHERE issue_id = ?', $issueId
		);
	}

	/**
	 * Sequentially renumber custom section orderings in their sequence order.
	 * @param $issueId int
	 */
	function resequenceCustomSectionOrders($issueId) {
		$result = &$this->retrieve(
			'SELECT section_id FROM custom_section_orders WHERE issue_id = ? ORDER BY seq',
			$issueId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($sectionId) = $result->fields;
			$this->update(
				'UPDATE custom_section_orders SET seq = ? WHERE section_id = ? AND issue_id = ?',
				array($i, $sectionId, $issueId)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Check if an issue has custom section ordering.
	 * @param $issueId int
	 * @return boolean
	 */
	function customSectionOrderingExists($issueId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM custom_section_orders WHERE issue_id = ?',
			$issueId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 0 ? false : true;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the custom section order of a section.
	 * @param $issueId int
	 * @param $sectionId int
	 * @return int
	 */
	function getCustomSectionOrder($issueId, $sectionId) {
		$result = &$this->retrieve(
			'SELECT seq FROM custom_section_orders WHERE issue_id = ? AND section_id = ?',
			array($issueId, $sectionId)
		);

		$returner = null;
		if (!$result->EOF) {
			list($returner) = $result->fields;
		}
		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Import the current section orders into the specified issue as custom
	 * issue orderings.
	 * @param $issueId int
	 */
	function setDefaultCustomSectionOrders($issueId) {
		$result = &$this->retrieve(
			'SELECT s.section_id FROM sections s, issues i WHERE i.journal_id = s.journal_id AND i.issue_id = ? ORDER BY seq',
			$issueId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($sectionId) = $result->fields;
			$this->insertCustomSectionOrder($issueId, $sectionId, $i);
			$result->moveNext();
		}

		$result->close();
		unset($result);
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
			array(
				$sectionId,
				$issueId,
				$seq
			)
		);
	}

	/**
	 * Move a custom issue ordering up or down, resequencing as necessary.
	 * @param $issueId int
	 * @param $sectionId int
	 * @param $newPos int The new position (0-based) of this section
	 * @param $up boolean Whether we're moving the section up or down
	 */
	function moveCustomSectionOrder($issueId, $sectionId, $newPos, $up) {
		$this->update(
			'UPDATE custom_section_orders SET seq = ? ' . ($up?'-':'+') . ' 0.5 WHERE issue_id = ? AND section_id = ?',
			array($newPos, $issueId, $sectionId)
		);
		$this->resequenceCustomSectionOrders($issueId);
	}
}

?>
