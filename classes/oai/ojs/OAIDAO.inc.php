<?php

/**
 * OAIDAO.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai.ojs
 *
 * DAO operations for the OJS OAI interface.
 *
 * $Id$
 */
 
 class OAIDAO extends DAO {
 
 	/** @var $oai JournalOAI parent OAI object */
 	var $oai;
 	
 
 	/**
	 * Constructor.
	 */
	function OAIDAO() {
		parent::DAO();
	}
	
	/**
	 * Set parent OAI object.
	 * @param JournalOAI
	 */
	function setOAI(&$oai) {
		$this->oai = $oai;
	}
	
	
	//
	// Records
	//
	
	/**
	 * Return the *nix timestamp of the earliest published article.
	 * @param $journalId int optional
	 * @return int
	 */
	function getEarliestDatestamp($journalId = null) {
		$result = &$this->retrieve(
			'SELECT MIN(pa.date_published)
			FROM published_articles pa, issues i
			WHERE i.published = 1'
			. (isset($journalId) ? ' AND i.journal_id = ?' : ''),
			
			isset($journalId) ? $journalId : false
		);
		
		if (isset($result->fields[0])) {
			$timestamp = strtotime($result->fields[0]);
		}
		if (!isset($timestamp) || $timestamp == -1) {
			$timestamp = 0;
		}
		
		return $timestamp;
	}
	
	/**
	 * Check if an article ID specifies a published article.
	 * @param $articleId int
	 * @param $journalId int optional
	 * @return boolean
	 */
	function recordExists($articleId, $journalId = null) {
		$result = &$this->retrieve(
			'SELECT COUNT(*)
			FROM published_articles pa, issues i
			WHERE i.published = 1 AND pa.article_id = ?'
			. (isset($journalId) ? ' AND i.journal_id = ?' : ''),
			
			isset($journalId) ? array($articleId, $journalId) : $articleId
		);
		
		return $result->fields[0] == 1;
	}
	
	/**
	 * Return OAI record for specified article.
	 * @param $articleId int
	 * @param $journalId int optional
	 * @return OAIRecord
	 */
	function &getRecord($articleId, $journalId = null) {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');

		$result = &$this->retrieve(
			'SELECT pa.*, a.*,
			j.path AS journal_path,
			j.title as journal_title,
			s.abbrev as section_abbrev,
			i.date_published AS issue_published
			FROM published_articles pa, issues i, journals j, articles a
			LEFT JOIN sections s ON s.section_id = a.section_id
			WHERE pa.article_id = a.article_id AND j.journal_id = a.journal_id
			AND pa.article_id = ?'
			. (isset($journalId) ? ' AND a.journal_id = ?' : '')
			. ' AND pa.issue_id = i.issue_id AND i.published = 1',
			isset($journalId) ? array($articleId, $journalId) : $articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			$row = &$result->GetRowAssoc(false);
			return $this->_returnRecordFromRow($row);
		}
	}
	
	/**
	 * Return set of OAI records matching specified parameters.
	 * @param $journalId int
	 * @param $sectionId int
	 * @parma $from int timestamp
	 * @parma $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIRecord
	 */
	function &getRecords($journalId, $sectionId, $from, $until, $offset, $limit, &$total) {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$records = array();
		
		$params = array();
		if (isset($journalId)) {
			array_push($params, $journalId);
		}
		if (isset($sectionId)) {
			array_push($params, $sectionId);
		}
		if (isset($from)) {
			array_push($params, $from);
		}
		if (isset($until)) {
			array_push($params, $until);
		}
		$result = &$this->retrieve(
			'SELECT pa.*, a.*,
			j.path AS journal_path,
			j.title as journal_title,
			s.abbrev as section_abbrev,
			i.date_published AS issue_published
			FROM published_articles pa, issues i, journals j, articles a
			LEFT JOIN sections s ON s.section_id = a.section_id
			WHERE pa.article_id = a.article_id AND j.journal_id = a.journal_id'
			. (isset($journalId) ? ' AND a.journal_id = ?' : '')
			. (isset($sectionId) ? ' AND a.section_id = ?' : '')
			. (isset($from) ? ' AND pa.date_published >= ?' : '')
			. (isset($until) ? ' AND pa.date_published <= ?' : '')
			. ' AND pa.issue_id = i.issue_id AND i.published = 1',
			$params
		);
		
		$total = $result->RecordCount();
		
		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row = &$result->GetRowAssoc(false);
			$records[] = &$this->_returnRecordFromRow($row);
			$result->moveNext();
		}
		$result->Close();
		
		return $records;
	}
	
	/**
	 * Return set of OAI identifiers matching specified parameters.
	 * @param $journalId int
	 * @param $sectionId int
	 * @parma $from int timestamp
	 * @parma $until int timestamp
	 * @param $offset int
	 * @param $limit int
	 * @param $total int
	 * @return array OAIIdentifier
	 */
	function &getIdentifiers($journalId, $sectionId, $from, $until, $offset, $limit, &$total) {
		$journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
		$records = array();
		
		$params = array();
		if (isset($journalId)) {
			array_push($params, $journalId);
		}
		if (isset($sectionId)) {
			array_push($params, $sectionId);
		}
		if (isset($from)) {
			array_push($params, $from);
		}
		if (isset($until)) {
			array_push($params, $until);
		}
		$result = &$this->retrieve(
			'SELECT pa.article_id,
			j.title AS journal_title, j.path AS journal_path,
			s.abbrev as section_abbrev,
			FROM published_articles pa, issues i, journals j, articles a
			LEFT JOIN sections s ON s.section_id = a.section_id
			WHERE pa.article_id = a.article_id AND j.journal_id = a.journal_id'
			. (isset($journalId) ? ' AND a.journal_id = ?' : '')
			. (isset($sectionId) ? ' AND a.section_id = ?' : '')
			. (isset($from) ? ' AND pa.date_published >= ?' : '')
			. (isset($until) ? ' AND pa.date_published <= ?' : '')
			. ' AND pa.issue_id = i.issue_id AND i.published = 1',
			$params
		);
		
		$total = $result->RecordCount();
		
		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row = &$result->GetRowAssoc(false);
			$records[] = &$this->_returnIdentifierFromRow($row);
			$result->moveNext();
		}
		$result->Close();
		
		return $records;
	}
	
	/**
	 * Return OAIRecord object from database row.
	 * @param $row array
	 * @return OAIRecord
	 */
	function &_returnRecordFromRow(&$row) {
		$record = &new OAIRecord();
		
		$record->identifier = $this->oai->articleIdToIdentifier($row['article_id']);
		$record->datestamp = strtotime($row['date_published']); // FIXME Add a "last-modified" field?
		$record->sets = array($row['journal_path'] . ':' . $row['section_abbrev']);
		
		$record->url = Request::getIndexUrl() . '/' . $row['journal_path'] . '/article/' . $row['article_id']; // FIXME Replace with correct path
		$record->title = $row['title']; // FIXME include localized titles as well?
		$record->creator = array();
		$record->subject = array($row['discipline'], $row['subject'], $row['subject_class']);
		$record->description = $row['abstract'];
		$record->publisher = $row['journal_title']; // FIXME
		$record->contributor = array($row['sponsor']);
		$record->date = date('Y-m-d', strtotime($row['issue_published'])); 
		$record->type = array('Article', $row['type']);
		$record->format = array();
		$record->source = $row['journal_title'];
		$record->language = $row['language'];
		$record->relation = array();
		$record->coverage = array($row['coverage_geo'], $row['coverage_chron'], $row['coverage_sample']);
		$record->rights = array(); // FIXME from journal settings
		
		// Get author names
		$authors = $this->authorDao->getAuthorsByArticle($row['article_id']);
		for ($i = 0, $num = count($authors); $i < $count; $i++) {
			$authorName = $authors[$i]->getFullName();
			$affiliation = $authors[$i]->getAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$record->creator[] = $authorName;
		}
		
		// Get galley formats
		$result = &$this->retrieve(
			'SELECT DISTINCT(f.file_type) FROM article_galleys g, article_files f WHERE g.file_id = f.file_id AND g.article_id = ?',
			$row['article_id']
		);
		while (!$result->EOF) {
			$record->format[] = $result->fields[0];
			$result->MoveNext();
		}
		$result->Close();
		
		// Get supplementary files
		$suppFiles = $this->suppFileDao->getSuppFilesByArticle($row['article_id']);
		for ($i = 0, $num = count($suppFiles); $i < $count; $i++) {
			// FIXME replace with correct UR
			$record->relation[] = Request::getIndexUrl() . '/' . $row['journal_path'] . '/article/' . $row['article_id'] . '/supplementary/' . $suppFile->getSuppFileId();
		}
		
		return $record;
	}
	
	/**
	 * Return OAIIdentifier object from database row.
	 * @param $row array
	 * @return OAIIdentifier
	 */
	function &_returnIdentifierFromRow(&$row) {
		$record = &new OAIRecord();
		
		$record->identifier = $this->oai->articleIdToIdentifier($row['article_id']);
		$record->datestamp = strtotime($row['date_published']);
		$record->sets = array($row['journal_path'] . ':' . $row['section_abbrev']);
		
		return $record;
	}
	
	
	//
	// Resumption tokens
	//
	
	/**
	 * Clear stale resumption tokens.
	 */
	function clearTokens() {
		$this->update(
			'DELETE FROM oai_resumption_tokens WHERE expire < ?', time()
		);
	}
	
	/**
	 * Retrieve a resumption token.
	 * @return OAIResumptionToken
	 */
	function &getToken($tokenId) {
		$result = &$this->retrieve(
			'SELECT * FROM oai_resumption_tokens WHERE token = ?', $tokenId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return new OAIResumptionToken($row['token'], $row['record_offset'], unserialize($row['params']), $row['expire']);
		}
	}
	
	/**
	 * Insert an OAI resumption token, generating a new ID.
	 * @param $token OAIResumptionToken
	 * @return OAIResumptionToken
	 */
	function &insertToken(&$token) {
		do {
			// Generate unique token ID
			$token->id = md5(uniqid(mt_rand(), true));
			$result = &$this->retrieve(
				'SELECT COUNT(*) FROM oai_resumption_tokens WHERE token = ?',
				$token->id
			);
		} while($result->fields[0] != 0);
		
		$this->update(
			'INSERT INTO oai_resumption_tokens (token, record_offset, params, expire)
			VALUES
			(?, ?, ?, ?)',
			array($token->id, $token->offset, serialize($token->params), $token->expire)
		);
		
		return $token;
	}
	
}
