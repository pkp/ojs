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
		$section->setPolicy($row['policy']);
		
		return $section;
	}

	/**
	 * Insert a new section.
	 * @param $section Section
	 */	
	function insertSection(&$section) {
		return $this->update(
			'INSERT INTO sections
				(journal_id, title, abbrev, seq, meta_indexed, policy)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$section->getJournalId(),
				$section->getTitle(),
				$section->getAbbrev(),
				$section->getSequence() == null ? 0 : $section->getSequence(),
				$section->getMetaIndexed(),
				$section->getPolicy()
			)
		);
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
					policy = ?
				WHERE section_id = ?',
			array(
				$section->getTitle(),
				$section->getAbbrev(),
				$section->getSequence(),
				$section->getMetaIndexed(),
				$section->getPolicy(),
				$section->getSectionId()
			)
		);
	}
	
	/**
	 * Delete a section.
	 * @param $section Section
	 */
	function deleteSection(&$section) {
		return $this->deleteJournalById($section->getSectionId(), $section->getJournalId());
	}
	
	/**
	 * Delete a section by ID.
	 * @param $sectionId int
	 * @param $journalId int optional
	 */
	function deleteSectionById($sectionId, $journalId = null) {
		if (isset($journalId)) {
			return $this->update(
				'DELETE FROM sections WHERE section_id = ? AND journalId = ?', array($sectionId, $journalId)
			);
		
		} else {
			return $this->update(
				'DELETE FROM sections WHERE section_id = ?', $sectionId
			);
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
	
		return $returner;
	}
	
	/**
	 * Retrieve all sections for a journal.
	 * @return array Sections ordered by sequence
	 */
	function &getJournalSections($journalId) {
		$sections = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM sections WHERE journal_id = ? ORDER BY seq',
			$journalId
		);
		
		while (!$result->EOF) {
			$sections[] = &$this->_returnSectionFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $sections;
	}
	
	/**
	 * Retrieve the IDs and titles of the sections for a journal in an associative array.
	 * @return array
	 */
	function &getSectionTitles($journalId) {
		$sections = array();
		
		$result = &$this->retrieve(
			'SELECT section_id, title FROM sections WHERE journal_id = ? ORDER BY seq',
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
