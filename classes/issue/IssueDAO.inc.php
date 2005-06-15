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

import ('issue.Issue');

class IssueDAO extends DAO {
 
 	/**
	 * Constructor.
	 */
	function IssueDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve Issue by issue id********
	 * @param $issueId int
	 * @return Issue object
	 */
	function getIssueById($issueId, $journalId = null) {
		if (isset($journalId)) {
			$result = &$this->retrieve(
				'SELECT i.* FROM issues i WHERE issue_id = ? AND journal_id = ?',
				array($issueId, $journalId)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT i.* FROM issues i WHERE issue_id = ?', $issueId
			);
		}

		if ($result->RecordCount() == 0) {
			return null;
		} else {
			$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
			$result->Close();
			return $issue;
		}
	}

	/**
	 * Retrieve Issue by public issue id
	 * @param $publicIssueId string
	 * @return Issue object
	 */
	function getIssueByPublicIssueId($publicIssueId, $journalId = null) {
		if (isset($journalId)) {
			$result = &$this->retrieve(
				'SELECT i.* FROM issues i WHERE public_issue_id = ? AND journal_id = ?',
				array($publicIssueId, $journalId)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT i.* FROM issues i WHERE public_issue_id = ?', $publicIssueId
			);
		}

		if ($result->RecordCount() == 0) {
			return null;
		} else {
			$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
			$result->Close();
			return $issue;
		}
	}

	/**
	 * Retrieve Issue by "best" issue id -- public ID if it exists,
	 * falling back on the internal issue ID otherwise.
	 * @param $issueId string
	 * @return Issue object
	 */
	function getIssueByBestIssueId($issueId, $journalId = null) {
		$issue = $this->getIssueByPublicIssueId($issueId, $journalId);
		if (isset($issue)) return $issue;

		// Fall back on internal issue ID.
		return $this->getIssueById($issueId, $journalId);
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

		if ($result->RecordCount() == 0) {
			return null;
		} else {
			$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
			$result->Close();
			return $issue;
		}
	}

	/**
	 * Retrieve current issue
	 * @return Issue object
	 */
	function getCurrentIssue($journalId) {
		$result = &$this->retrieve(
			'SELECT i.* FROM issues i WHERE journal_id = ? AND current = 1', $journalId
		);

		if ($result->RecordCount() == 0) {
			return null;
		} else {
			$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
			$result->Close();
			return $issue;
		}
	}	

	/**
	 * update current issue
	 * @return Issue object
	 */
	function updateCurrentIssue($journalId, $issue = null) {
		$this->update(
			'UPDATE issues SET current = 0 WHERE journal_id = ? AND current = 1', $journalId
		);
		if ($issue) $this->updateIssue($issue);
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
		$issue->setDateNotified($row['date_notified']);
		$issue->setAccessStatus($row['access_status']);
		$issue->setOpenAccessDate($row['open_access_date']);
		$issue->setDescription($row['description']);
		$issue->setPublicIssueId($row['public_issue_id']);
		$issue->setLabelFormat($row['label_format']);
		$issue->setFileName($row['file_name']);
		$issue->setOriginalFileName($row['original_file_name']);
		$issue->setCoverPageDescription($row['cover_page_description']);
		$issue->setShowCoverPage($row['show_cover_page']);
		$issue->setNumArticles($this->getNumArticles($row['issue_id']));
		return $issue;
	}
	
	/**
	 * Returns issue object from a database row, with some extra publishing info.
	 * @param $row array
	 * @return Issue object
	 */
	function _returnPublishedIssueFromRow(&$row) { // FIXME?
		$issue = &$this->_returnIssueFromRow($row);
		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$issue->setAuthors($publishedArticleDao->getPublishedArticleAuthors($issue->getIssueId()));
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
				(journal_id, title, volume, number, year, published, current, date_published, date_notified, access_status, open_access_date, description, public_issue_id, label_format, file_name, original_file_name, cover_page_description, show_cover_page)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$issue->getJournalId(),
				$issue->getTitle(),
				$issue->getVolume(),
				$issue->getNumber(),
				$issue->getYear(),
				$issue->getPublished(),
				$issue->getCurrent(),
				$issue->getDatePublished(),
				$issue->getDateNotified(),
				$issue->getAccessStatus(),
				$issue->getOpenAccessDate(),
				$issue->getDescription(),
				$issue->getPublicIssueId(),
				$issue->getLabelFormat(),
				$issue->getFileName(),
				$issue->getOriginalFileName(),
				$issue->getCoverPageDescription(),
				$issue->getShowCoverPage()
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
					date_notified = ?,
					open_access_date = ?,
					description = ?,
					public_issue_id = ?,
					access_status = ?,
					label_format = ?,
					file_name = ?,
					original_file_name = ?,
					cover_page_description = ?,
					show_cover_page = ?
				WHERE issue_id = ?',
			array(
				$issue->getJournalId(),
				$issue->getTitle(),
				$issue->getVolume(),
				$issue->getNumber(),
				$issue->getYear(),
				$issue->getPublished(),
				$issue->getCurrent(),
				$issue->getDatePublished(),
				$issue->getDateNotified(),
				$issue->getOpenAccessDate(),
				$issue->getDescription(),
				$issue->getPublicIssueId(),
				$issue->getAccessStatus(),
				$issue->getLabelFormat(),
				$issue->getFileName(),
				$issue->getOriginalFileName(),
				$issue->getCoverPageDescription(),
				$issue->getShowCoverPage(),
				$issue->getIssueId()
			)
		);
	}

	/**
	 * Delete issue. Deletes associated published articles and cover file.
	 * @param $issue object issue
	 */
	function deleteIssue(&$issue) {
		if (($fileName = $issue->getFileName()) != '') {
			import('file.PublicFileManager');
			$publicFileManager = new PublicFileManager();
			$publicFileManager->removeJournalFile($issue->getJournalId(), $fileName);
		}

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->deletePublishedArticlesByIssueId($issue->getIssueId());

		$this->update(
			'DELETE FROM issues WHERE issue_id = ?', $issue->getIssueId()
		);
	}

	/**
	 * Delete issues by journal id. Does not delete dependent entities; this is intended
	 * to be called while deleting a journal, which deletes dependents.
	 * @param $journalId int
	 */
	function deleteIssuesByJournal($journalId) {
		$this->update(
			'DELETE FROM issues WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Checks if issue exists
	 * @param $publicIssueId string
	 * @return boolean
	 */
	function issueIdExists($issueId, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM issues WHERE issue_id = ? AND journal_id = ?',
			array($issueId, $journalId)
		);
		return $result->fields[0] ? true : false;
	}

	/**
	 * Checks if public identifier exists
	 * @param $publicIssueId string
	 * @return boolean
	 */
	function publicIssueIdExists($publicIssueId, $issueId, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM issues WHERE public_issue_id = ? AND issue_id <> ? AND journal_id = ?', array($publicIssueId, $issueId, $journalId)
		);
		return $result->fields[0] ? true : false;
	}

	/**
	 * Get issue by article id
	 * @param articleId int
	 * @return issue object
	 */
	function getIssueByArticleId($articleId) {
		$sql = 'SELECT i.* FROM issues i LEFT JOIN published_articles a ON (i.issue_id = a.issue_id) WHERE article_id = ?';
		$result = &$this->retrieve($sql, $articleId);	

		if ($result->RecordCount() == 0) {
			$issue = null;
		} else {
			$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
			$issue = &$this->_returnIssueFromRow($result->GetRowAssoc(false));
			$issue->setAuthors($publishedArticleDao->getPublishedArticleAuthors($issue->getIssueId()));
		}
		
		$result->Close();
		return $issue;
	}

	/**
	 * Get all issues organized by published date********
	 * @param $journalId int
	 * @param $rangeInfo object DBResultRange (optional)
	 * @return issues object ItemIterator
	 */
	function getIssues($journalId, $rangeInfo = null) {
		$issues = array();

		$sql = 'SELECT i.* FROM issues i WHERE journal_id = ? ORDER BY current DESC, date_published DESC';
		$result = &$this->retrieveRange($sql, $journalId, $rangeInfo);
		
		return new DAOResultFactory(&$result, &$this, '_returnPublishedIssueFromRow');
	}

	/**
	 * Get published issues organized by published date********
	 * @param $journalId int
	 * @param $current bool retrieve current or not
	 * @param $rangeInfo object DBResultRange
	 * @return issues ItemIterator
	 */
	function getPublishedIssues($journalId, $current = false, $rangeInfo = null) {
		$issues = array();

		if ($current) {
			$sql = 'SELECT i.* FROM issues i WHERE journal_id = ? AND (published = 1 OR current = 1) ORDER BY current DESC, year ASC, volume ASC, number ASC';
		} else {
			$sql = 'SELECT i.* FROM issues i WHERE journal_id = ? AND published = 1 ORDER BY current DESC, date_published DESC';
		}
		$result = &$this->retrieveRange($sql, $journalId, $rangeInfo);
		
		return new DAOResultFactory(&$result, &$this, '_returnPublishedIssueFromRow');
	}

	/**
	 * Get unpublished issues organized by published date********
	 * @param $journalId int
	 * @param $current bool retrieve current or not
	 * @param $rangeInfo object DBResultRange
 	 * @return issues ItemIterator
	 */
	function getUnpublishedIssues($journalId, $current = false, $rangeInfo = null) {
		$issues = array();

		if ($current) {
			$sql = 'SELECT i.* FROM issues i WHERE journal_id = ? AND (published = 0 OR current = 1) ORDER BY current DESC, year ASC, volume ASC, number ASC';
		} else {
			$sql = 'SELECT i.* FROM issues i WHERE journal_id = ? AND published = 0 ORDER BY year ASC, volume ASC, number ASC';
		}
		$result = &$this->retrieveRange($sql, $journalId, $rangeInfo);

		return new DAOResultFactory(&$result, &$this, '_returnIssueFromRow');
	}
	
	/**
	 * Return number of articles assigned to an issue.
	 * @param $issueId int
	 * @return int
	 */
	function getNumArticles($issueId) {
		$result = &$this->retrieve('SELECT COUNT(*) FROM published_articles WHERE issue_id = ?', $issueId);
		return isset($result->fields[0]) ? $result->fields[0] : 0;
	}

 }
  
?>
