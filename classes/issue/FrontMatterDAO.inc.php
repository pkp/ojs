<?php

/**
 * FrontMatterDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * Class for Front Matter DAO.
 * Operations for retrieving and modifying Front Matter objects.
 *
 * $Id$
 */
 
 class FrontMatterDAO extends DAO {
 
 	/**
	 * Constructor.
	 */
	function FrontMatterDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve Front Matter by issue id
	 * @param $issueId int
	 * @return Front Matter objects array
	 */
	function getFrontMatters($issueId) {
		$frontMatters = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM front_matter WHERE issue_id = ? ORDER BY date_created DESC', $issueId
		);
				
		while (!$result->EOF) {
			$frontMatters[] = &$this->_returnFrontMatterFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		
		return $frontMatters;
	}

	/**
	 * Retrieve Front Matter by front id
	 * @param $frontId int
	 * @return Front Matter object
	 */
	function getFrontMatterById($frontId) {
		$result = &$this->retrieve(
			'SELECT * FROM front_matter WHERE front_id = ?', $frontId
		);
		$frontMatter = &$this->_returnFrontMatterFromRow($result->GetRowAssoc(false));
		$result->Close();
		return $frontMatter;
	}

	/**
	 * creates and returns a front matter object from a row
	 * @param $row array
	 * @return Front Matter object
	 */
	function _returnFrontMatterFromRow($row) {
		$frontMatter = &new FrontMatter();
		$frontMatter->setFrontId($row['front_id']);
		$frontMatter->setIssueId($row['issue_id']);
		$frontMatter->setFrontSectionId($row['front_section_id']);
		$frontMatter->setFileName($row['file_name']);
		$frontMatter->setOriginalFileName($row['original_file_name']);
		$frontMatter->setTitle($row['title']);
		$frontMatter->setDateCreated($row['date_created']);
		$frontMatter->setDateModified($row['date_modified']);
		$frontMatter->setCover($row['cover']);
		return $frontMatter;
	}
	
	/**
	 * inserts a new front matter
	 * @param FrontMatter object
	 * @return Front Matter id int
	 */
	function insertFrontMatter($frontMatter) {
		$this->update(
			'INSERT INTO front_matter
				(issue_id, front_section_id, file_name, original_file_name, title, date_created, date_modified, cover)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$frontMatter->getIssueId(),
				$frontMatter->getFrontSectionId(),
				$frontMatter->getFileName(),
				$frontMatter->getOriginalFileName(),
				$frontMatter->getTitle(),
				str_replace("'",'',$frontMatter->getDateCreated()),
				str_replace("'",'',$frontMatter->getDateModified()),
				$frontMatter->getCover()
			)
		);

		return $this->getInsertFrontMatterId();		
	}
		
	/**
	 * Get the ID of the last inserted front matter.
	 * @return int
	 */
	function getInsertFrontMatterId() {
		return $this->getInsertId('front_matter', 'front_id');
	}

	/**
	 * updates a front matter object
	 * @param FrontMatter object
	 */
	function updateFrontMatter($frontMatter) {
		$this->update(
			'UPDATE front_matter
				SET
					issue_id = ?,
					front_section_id = ?,
					file_name = ?,
					original_file_name = ?,
					title = ?,
					date_created = ?,
					date_modified = ?,
					cover = ?
				WHERE front_id = ?',
			array(
				$frontMatter->getIssueId(),
				$frontMatter->getFrontSectionId(),
				$frontMatter->getFileName(),
				$frontMatter->getOriginalFileName(),
				$frontMatter->getTitle(),
				str_replace("'",'',$frontMatter->getDateCreated()),
				str_replace("'",'',$frontMatter->getDateModified()),
				$frontMatter->getCover(),
				$frontMatter->getFrontId()
			)
		);
	}

	/**
	 * Delete front matter by id
	 * @param $frontId int
	 */
	function deleteFrontMatterById($frontId) {
		$this->update(
			'DELETE FROM front_matter WHERE front_id = ?', $frontId
		);
	}

	/**
	 * Delete all front matter with exact issue id
	 * @param $issueId int
	 */
	function deleteFrontMatters($issueId) {
		$this->update(
			'DELETE FROM front_matter WHERE issue_id = ?', $issueId
		);		
	}

	/**
	 * Remove current cover front matter
	 * @param $issueId int
	 */
	function removeCoverFromFrontMatter($issueId) {
		$this->update(
			'UPDATE front_matter SET cover = 0 WHERE issue_id = ? and cover = 1', $issueId
		);
	}

 }
  
?>
