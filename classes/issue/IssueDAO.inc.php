<?php

/**
 * IssueDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package issue
 *
 * Class for Issue DAO.
 * Operations for retrieving and modifying Issue objects.
 *
 * $Id$
 */
 
 class IssueDAO extends DAO {
 
 	/**
	 * Constructor.
	 */
	function IssueDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve selected Issues by Journal Id
	 * @param $journalId int
	 * @param $published int
	 * @return Issue objects array
	 */
	function getSelectedIssues($journalId, $published, $current = true) {
		$issues = array();
		
		if ($current) {
			$result = &$this->retrieve(
				'SELECT i.* FROM issues i WHERE journal_id = ? AND (published = ? OR current = 1) ORDER BY year ASC, volume ASC, number ASC', array($journalId, $published)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT i.* FROM issues i WHERE journal_id = ? AND published = ? ORDER BY year ASC, volume ASC, number ASC', array($journalId, $published)
			);			
		}
		
		while (!$result->EOF) {
			$issues[] = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		
		return $issues;
	}

	/**
	 * Retrieve Issues by Journal Id
	 * @param $journalId int
	 * @return Issue objects array
	 */
	function getIssues($journalId) {
		$issues = array();
		
		$result = &$this->retrieve(
			'SELECT i.* FROM issues i WHERE journal_id = ? ORDER BY published DESC, current ASC, year ASC, volume ASC, number ASC', $journalId
		);
				
		while (!$result->EOF) {
			$issues[] = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		
		return $issues;
	}

	/**
	 * Retrieve Issue by issue id
	 * @param $issueId int
	 * @return Issue object
	 */
	function getIssueById($issueId) {
		$result = &$this->retrieve(
			'SELECT i.* FROM issues i WHERE issue_id = ?', $issueId
		);
		$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
		$result->Close();
		return $issue;
	}

	/**
	 * Retrieve the last created issue
	 * @param $journalId int
	 * @return Issue object
	 */
	function getLastCreatedIssue($journalId) {
		$result = &$this->retrieveLimit(
			'SELECT i.* FROM issues i WHERE journal_id = ? ORDER BY year DESC, volume DESC, number DESC', $journalId, 1
		);
		$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
		$result->Close();
		return $issue;
	}

	/**
	 * Retrieve current issue
	 * @return Issue object
	 */
	function getCurrentIssue($journalId) {
		$result = &$this->retrieve(
			'SELECT i.* FROM issues i WHERE journal_id = ? AND current = 1', $journalId
		);
		$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
		$result->Close();
		return $issue;
	}	

	/**
	 * update current issue
	 * @return Issue object
	 */
	function updateCurrentIssue($journalId, $issue) {
		$this->update(
			'UPDATE issues SET current = 0 WHERE journal_id = ? AND current = 1', $journalId
		);
		$this->updateIssue($issue);
	}	

	
	/**
	 * creates and returns an issue object from a row
	 * @param $row array
	 * @return Issue object
	 */
	function _returnIssueFromRow($row) {
		$issue = &new Issue();
		$issue->setIssueId($row['issue_id']);
		$issue->setJournalId($row['journal_id']);
		$issue->setTitle($row['title']);
		$issue->setVolume($row['volume']);
		$issue->setNumber($row['number']);
		$issue->setYear($row['year']);
		$issue->setPublished($row['published']);
		$issue->setCurrent($row['current']);
		$issue->setDatePublished($row['date_published']);
		$issue->setAccessStatus($row['access_status']);
		$issue->setOpenAccessDate($row['open_access_date']);
		$issue->setDescription($row['description']);
		$issue->setPublicIssueId($row['public_issue_id']);
		$issue->setLabelFormat($row['label_format']);
		return $issue;
	}
	
	/**
	 * inserts a new issue into issues table
	 * @param Issue object
	 * @return Issue Id int
	 */
	function insertIssue($issue) {
		$this->update(
			'INSERT INTO issues
				(journal_id, title, volume, number, year, published, current, date_published, access_status, open_access_date, description, public_issue_id, label_format)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$issue->getJournalId(),
				$issue->getTitle(),
				$issue->getVolume(),
				$issue->getNumber(),
				$issue->getYear(),
				$issue->getPublished(),
				$issue->getCurrent(),
				str_replace("'",'',$issue->getDatePublished()),
				$issue->getAccessStatus(),
				str_replace("'",'',$issue->getOpenAccessDate()),
				$issue->getDescription(),
				$issue->getPublicIssueId(),
				$issue->getLabelFormat()
			)
		);

		return $this->getInsertIssueId();		
	}
		
	/**
	 * Get the ID of the last inserted issue.
	 * @return int
	 */
	function getInsertIssueId() {
		return $this->getInsertId('issues', 'issue_id');
	}

	/**
	 * Check if volume, number and year have already been issued
	 * @param $journalId int
	 * @param $volume int
	 * @param $number int
	 * @param $year int
	 * @return boolean
	 */
	function issueExists($journalId, $volume, $number, $year, $issueId) {
		$result = &$this->retrieve(
			'SELECT i.* FROM issues i WHERE journal_id = ? AND volume = ? AND number = ? AND year = ? AND issue_id <> ?', 
			array($journalId, $volume, $number, $year, $issueId)
		);
		return $result->RecordCount() != 0 ? true : false;
	}

	/**
	 * updates an issue
	 * @param Issue object
	 */
	function updateIssue($issue) {
		$this->update(
			'UPDATE issues
				SET
					journal_id = ?,
					title = ?,
					volume = ?,
					number = ?,
					year = ?,
					published = ?,
					current = ?,
					date_published = ?,
					open_access_date = ?,
					description = ?,
					public_issue_id = ?,
					access_status = ?,
					label_format = ?
				WHERE issue_id = ?',
			array(
				$issue->getJournalId(),
				$issue->getTitle(),
				$issue->getVolume(),
				$issue->getNumber(),
				$issue->getYear(),
				$issue->getPublished(),
				$issue->getCurrent(),
				str_replace("'",'',$issue->getDatePublished()),
				str_replace("'",'',$issue->getOpenAccessDate()),
				$issue->getDescription(),
				$issue->getPublicIssueId(),
				$issue->getAccessStatus(),
				$issue->getLabelFormat(),
				$issue->getIssueId()
			)
		);
	}

	/**
	 * Delete issue by id
	 * @param $issueId int
	 */
	function deleteIssueById($issueId) {
		$this->update(
			'DELETE FROM issues WHERE issue_id = ?', $issueId
		);
	}

	/**
	 * Checks if public identifier exists
	 * @param $publicIssueId string
	 * @return boolean
	 */
	function publicIssueIdExists($publicIssueId, $issueId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM issues WHERE public_issue_id = ? AND issue_id <> ?', array($publicIssueId, $issueId)
		);
		return $result->fields[0] ? true : false;
	}

 }
  
?>
