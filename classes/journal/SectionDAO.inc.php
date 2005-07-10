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
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSectionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a section by abbreviation.
	 * @param $sectionAbbrev string
	 * @return Section
	 */
	function getSectionByAbbrev($sectionAbbrev, $journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE abbrev = ? AND journal_id = ?',
			array($sectionAbbrev, $journalId)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSectionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a section by title.
	 * @param $sectionTitle string
	 * @return Section
	 */
	function getSectionByTitle($sectionTitle, $journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE title = ? AND journal_id = ?',
			array($sectionTitle, $journalId)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSectionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve a section by title and abbrev.
	 * @param $sectionTitle string
	 * @param $sectionAbbrev string
	 * @return Section
	 */
	function getSectionByTitleAndAbbrev($sectionTitle, $sectionAbbrev, $journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE title = ? AND abbrev = ? AND journal_id = ?',
			array($sectionTitle, $sectionAbbrev, $journalId)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSectionFromRow($result->GetRowAssoc(false));
		}
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
		$section->setAbbrev($row['abbrev']);
		$section->setSequence($row['seq']);
		$section->setMetaIndexed($row['meta_indexed']);
		$section->setEditorRestricted($row['editor_restricted']);
		$section->setPolicy($row['policy']);
		
		return $section;
	}

	/**
	 * Insert a new section.
	 * @param $section Section
	 */	
	function insertSection(&$section) {
		$this->update(
			'INSERT INTO sections
				(journal_id, title, abbrev, seq, meta_indexed, policy, editor_restricted)
				VALUES
				(?, ?, ?, ?, ?, ?, ?)',
			array(
				$section->getJournalId(),
				$section->getTitle(),
				$section->getAbbrev(),
				$section->getSequence() == null ? 0 : $section->getSequence(),
				$section->getMetaIndexed(),
				$section->getPolicy(),
				$section->getEditorRestricted()
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
					abbrev = ?,
					seq = ?,
					meta_indexed = ?,
					policy = ?,
					editor_restricted = ?
				WHERE section_id = ?',
			array(
				$section->getTitle(),
				$section->getAbbrev(),
				$section->getSequence(),
				$section->getMetaIndexed(),
				$section->getPolicy(),
				$section->getEditorRestricted(),
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
			'SELECT DISTINCT s.* FROM sections s, published_articles pa, articles a WHERE s.section_id = a.section_id AND pa.article_id = a.article_id AND pa.issue_id = ? ORDER BY s.seq',
			$issueId
		);
		
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$returner[] = &$this->_returnSectionFromRow($row);
			$result->moveNext();
		}
		$result->Close();
	
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
		
		return new DAOResultFactory(&$result, $this, '_returnSectionFromRow');
	}
	
	/**
	 * Retrieve the IDs and titles of the sections for a journal in an associative array.
	 * @return array
	 */
	function &getSectionTitles($journalId, $submittableOnly = false) {
		$sections = array();
		
		$result = &$this->retrieve(
			($submittableOnly?
			'SELECT section_id, title FROM sections WHERE journal_id = ? AND editor_restricted = 0 ORDER BY seq':
			'SELECT section_id, title FROM sections WHERE journal_id = ? ORDER BY seq'),
			$journalId
		);
		
		while (!$result->EOF) {
			$sections[$result->fields[0]] = $result->fields[1];
			$result->moveNext();
		}
		$result->Close();
	
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
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}

	/**
	 * Sequentially renumber sections in their sequence order.
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
	}
	
	/**
	 * Get the ID of the last inserted section.
	 * @return int
	 */
	function getInsertSectionId() {
		return $this->getInsertId('sections', 'section_id');
	}
	
}

?>
