<?php

/**
 * FrontMatterSectionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * Class for Front Matter Section DAO.
 * Operations for retrieving and modifying Front Matter Section objects.
 *
 * $Id$
 */
 
 class FrontMatterSectionDAO extends DAO {
 
 	/**
	 * Constructor.
	 */
	function FrontMatterSectionDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve Front Matter Section by issue id
	 * @param $issueId int
	 * @return Front Matter Section objects array
	 */
	function getFrontMatterSections($issueId) {
		$frontMatterSections = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM front_matter_sections WHERE issue_id = ? ORDER BY seq ASC', $issueId
		);
				
		while (!$result->EOF) {
			$frontMatterSections[] = &$this->_returnFrontMatterSectionFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		
		return $frontMatterSections;
	}

	/**
	 * Retrieve Front Matter Section by front section id
	 * @param $frontSectionId int
	 * @return Front Matter Section object
	 */
	function getFrontMatterSectionById($frontSectionId) {
		$result = &$this->retrieve(
			'SELECT * FROM front_matter_sections WHERE front_section_id = ?', $frontSectionId
		);
		$frontMatterSection = &$this->_returnFrontMatterSectionFromRow($result->GetRowAssoc(false));
		$result->Close();
		return $frontMatterSection;
	}

	/**
	 * creates and returns a front matter section object from a row
	 * @param $row array
	 * @return Front Matter Section object
	 */
	function _returnFrontMatterSectionFromRow($row) {
		$frontMatterSection = &new FrontMatterSection();
		$frontMatterSection->setFrontSectionId($row['front_section_id']);
		$frontMatterSection->setIssueId($row['issue_id']);
		$frontMatterSection->setTitle($row['title']);
		$frontMatterSection->setAbbrev($row['abbrev']);
		$frontMatterSection->setSeq($row['seq']);
		return $frontMatterSection;
	}
	
	/**
	 * inserts a new front matter section
	 * @param FrontMatterSection object
	 * @return Front Matter Section id int
	 */
	function insertFrontMatterSection($frontMatterSection) {
		$this->update(
			'INSERT INTO front_matter_sections
				(issue_id, title, abbrev, seq)
				VALUES
				(?, ?, ?, ?)',
			array(
				$frontMatterSection->getIssueId(),
				$frontMatterSection->getTitle(),
				$frontMatterSection->getAbbrev(),
				$frontMatterSection->getSeq()
			)
		);

		return $this->getInsertFrontMatterSectionId();		
	}
		
	/**
	 * Get the ID of the last inserted front matter.
	 * @return int
	 */
	function getInsertFrontMatterSectionId() {
		return $this->getInsertId('front_matter_sections', 'front_section_id');
	}

	/**
	 * updates a front matter section object
	 * @param FrontMatterSection object
	 */
	function updateFrontMatterSection($frontMatterSection) {
		$this->update(
			'UPDATE front_matter_sections
				SET
					issue_id = ?,
					title = ?,
					abbrev = ?,
					seq = ?
				WHERE front_section_id = ?',
			array(
				$frontMatterSection->getIssueId(),
				$frontMatterSection->getTitle(),
				$frontMatterSection->getAbbrev(),
				$frontMatterSection->getSeq(),
				$frontMatterSection->getFrontSectionId()
			)
		);
	}

	/**
	 * Delete front matter section by id
	 * @param $frontSectionId int
	 */
	function deleteFrontMatterSectionById($frontSectionId) {
		$this->update(
			'DELETE FROM front_matter_sections WHERE front_section_id = ?', $frontSectionId
		);
	}

	/**
	 * Delete all front matter sections with exact issue id
	 * @param $issueId int
	 */
	function deleteFrontMatterSections($issueId) {
		$this->update(
			'DELETE FROM front_matter_sections WHERE issue_id = ?', $issueId
		);		
	}

 }
  
?>
