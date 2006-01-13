<?php

/**
 * SectionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package journal
 *
 * Class for section DAO.
 * Operations for retrieving and modifying Section objects.
 *
 * $Id$
 */

import ('journal.Section');

class SectionDAO extends DAO {

	/**
	 * Constructor.
	 */
	function SectionDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve a section by ID.
	 * @param $sectionId int
	 * @return Section
	 */
	function &getSection($sectionId) {
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE section_id = ?', $sectionId
		);

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
	 * @return Section
	 */
	function &getSectionByAbbrev($sectionAbbrev, $journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE abbrev = ? AND journal_id = ?',
			array($sectionAbbrev, $journalId)
		);

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
	function &getSectionByTitle($sectionTitle, $journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE (title = ? OR title_alt1 = ? OR title_alt2 = ?) AND journal_id = ?',
			array($sectionTitle, $sectionTitle, $sectionTitle, $journalId)
		);

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
	 * @return Section
	 */
	function &getSectionByTitleAndAbbrev($sectionTitle, $sectionAbbrev, $journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE (title = ? OR title_alt1 = ? OR title_alt2 = ?) AND (abbrev = ? OR abbrev_alt1 = ? OR abbrev_alt2 = ?) AND journal_id = ?',
			array($sectionTitle, $sectionTitle, $sectionTitle, $sectionAbbrev, $sectionAbbrev, $sectionAbbrev, $journalId)
		);

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
		$section->setTitle($row['title']);
		$section->setTitleAlt1($row['title_alt1']);
		$section->setTitleAlt2($row['title_alt2']);
		$section->setAbbrev($row['abbrev']);
		$section->setAbbrevAlt1($row['abbrev_alt1']);
		$section->setAbbrevAlt2($row['abbrev_alt2']);
		$section->setSequence($row['seq']);
		$section->setMetaIndexed($row['meta_indexed']);
		$section->setAbstractsDisabled($row['abstracts_disabled']);
		$section->setIdentifyType($row['identify_type']);
		$section->setEditorRestricted($row['editor_restricted']);
		$section->setHideTitle($row['hide_title']);
		$section->setPolicy($row['policy']);
		
		HookRegistry::call('SectionDAO::_returnSectionFromRow', array(&$section, &$row));

		return $section;
	}

	/**
	 * Insert a new section.
	 * @param $section Section
	 */	
	function insertSection(&$section) {
		$this->update(
			'INSERT INTO sections
				(journal_id, title, title_alt1, title_alt2, abbrev, abbrev_alt1, abbrev_alt2, seq, meta_indexed, abstracts_disabled, identify_type, policy, editor_restricted, hide_title)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$section->getJournalId(),
				$section->getTitle(),
				$section->getTitleAlt1(),
				$section->getTitleAlt2(),
				$section->getAbbrev(),
				$section->getAbbrevAlt1(),
				$section->getAbbrevAlt2(),
				$section->getSequence() == null ? 0 : $section->getSequence(),
				$section->getMetaIndexed() ? 1 : 0,
				$section->getAbstractsDisabled() ? 1 : 0,
				$section->getIdentifyType(),
				$section->getPolicy(),
				$section->getEditorRestricted() ? 1 : 0,
				$section->getHideTitle() ? 1 : 0
			)
		);
		
		$section->setSectionId($this->getInsertSectionId());
		return $section->getSectionId();
	}
	
	/**
	 * Update an existing section.
	 * @param $section Section
	 */
	function updateSection(&$section) {
		return $this->update(
			'UPDATE sections
				SET
					title = ?,
					title_alt1 = ?,
					title_alt2 = ?,
					abbrev = ?,
					abbrev_alt1 = ?,
					abbrev_alt2 = ?,
					seq = ?,
					meta_indexed = ?,
					abstracts_disabled = ?,
					identify_type = ?,
					policy = ?,
					editor_restricted = ?,
					hide_title = ?
				WHERE section_id = ?',
			array(
				$section->getTitle(),
				$section->getTitleAlt1(),
				$section->getTitleAlt2(),
				$section->getAbbrev(),
				$section->getAbbrevAlt1(),
				$section->getAbbrevAlt2(),
				$section->getSequence(),
				$section->getMetaIndexed(),
				$section->getAbstractsDisabled(),
				$section->getIdentifyType(),
				$section->getPolicy(),
				$section->getEditorRestricted(),
				$section->getHideTitle(),
				$section->getSectionId()
			)
		);
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

		if (isset($journalId)) {
			return $this->update(
				'DELETE FROM sections WHERE section_id = ? AND journal_id = ?', array($sectionId, $journalId)
			);
		
		} else {
			return $this->update(
				'DELETE FROM sections WHERE section_id = ?', $sectionId
			);
		}
	}
	
	/**
	 * Delete sections by journal ID
	 * NOTE: This does not delete dependent entries EXCEPT from section_editors. It is intended
	 * to be called only when deleting a journal.
	 * @param $journalId int
	 */
	function deleteSectionsByJournal($journalId) {
		$sectionEditorsDao = &DAORegistry::getDAO('SectionEditorsDAO');
		$sectionEditorsDao->deleteEditorsByJournalId($journalId);

		return $this->update(
			'DELETE FROM sections WHERE journal_id = ?', $journalId
		);
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
		
		$result = &$this->retrieve(
			($submittableOnly?
			'SELECT section_id, title, title_alt1, title_alt2 FROM sections WHERE journal_id = ? AND editor_restricted = 0 ORDER BY seq':
			'SELECT section_id, title, title_alt1, title_alt2 FROM sections WHERE journal_id = ? ORDER BY seq'),
			$journalId
		);

		$localeNumber = Locale::isAlternateJournalLocale($journalId);

		while (!$result->EOF) {
			$sectionTitle = $result->fields[$localeNumber + 1];
			if (!isset($sectionTitle)) $sectionTitle = $result->fields[1];
			$sections[$result->fields[0]] = $sectionTitle;
			$result->moveNext();
		}

		$result->Close();
		unset($result);
	
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
				array(
					$i,
					$sectionId,
					$issueId
				)
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
			$this->_insertCustomSectionOrder($issueId, $sectionId, $i);
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
	function _insertCustomSectionOrder($issueId, $sectionId, $seq) {
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
	 * @param $up boolean Move this section up iff true; otherwise down
	 */
	function moveCustomSectionOrder($issueId, $sectionId, $up) {
		$this->update(
			'UPDATE custom_section_orders SET seq = seq ' . ($up?'-':'+') . ' 1.5 WHERE issue_id = ? AND section_id = ?',
			array($issueId, $sectionId)
		);
		$this->resequenceCustomSectionOrders($issueId);
	}
}

?>
