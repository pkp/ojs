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

import('oai.OAI');
import('issue.Issue');

class OAIDAO extends DAO {
 
 	/** @var $oai JournalOAI parent OAI object */
 	var $oai;
 	
 	/** Helper DAOs */
 	var $journalDao;
 	var $sectionDao;
 	var $authorDao;
 	var $suppFileDao;
 	var $journalSettingsDao;
 	
 
 	/**
	 * Constructor.
	 */
	function OAIDAO() {
		parent::DAO();
		$this->journalDao = &DAORegistry::getDAO('JournalDAO');
		$this->sectionDao = &DAORegistry::getDAO('SectionDAO');
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$this->journalSettingsDao = &DAORegistry::getDAO('JournalSettingsDAO');
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
			WHERE pa.issue_id = i.issue_id AND i.published = 1'
			. (isset($journalId) ? ' AND i.journal_id = ?' : ''),
			
			isset($journalId) ? $journalId : false
		);
		
		if (isset($result->fields[0])) {
			$timestamp = strtotime($this->datetimeFromDB($result->fields[0]));
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
			WHERE pa.issue_id = i.issue_id AND i.published = 1 AND pa.article_id = ?'
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
		$result = &$this->retrieve(
			'SELECT pa.*, a.*,
			j.path AS journal_path,
			j.title as journal_title,
			s.abbrev as section_abbrev,
			i.date_published AS issue_published,
			i.title AS issue_title,
			i.volume AS issue_volume,
			i.number AS issue_number,
			i.year AS issue_year,
			i.label_format AS issue_label_format
			FROM published_articles pa, issues i, journals j, articles a
			LEFT JOIN sections s ON s.section_id = a.section_id
			WHERE pa.article_id = a.article_id AND j.journal_id = a.journal_id
			AND pa.issue_id = i.issue_id AND i.published = 1
			AND pa.article_id = ?'
			. (isset($journalId) ? ' AND a.journal_id = ?' : ''),
			isset($journalId) ? array($articleId, $journalId) : $articleId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$row = &$result->GetRowAssoc(false);
			$returner = &$this->_returnRecordFromRow($row);
		}
		$result->Close();
		return $returner;
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
		$records = array();
		
		$params = array();
		if (isset($journalId)) {
			array_push($params, $journalId);
		}
		if (isset($sectionId)) {
			array_push($params, $sectionId);
		}
		$result = &$this->retrieve(
			'SELECT pa.*, a.*,
			j.path AS journal_path,
			j.title as journal_title,
			s.abbrev as section_abbrev,
			i.date_published AS issue_published,
			i.title AS issue_title,
			i.volume AS issue_volume,
			i.number AS issue_number,
			i.year AS issue_year,
			i.label_format AS issue_label_format
			FROM published_articles pa, issues i, journals j, articles a
			LEFT JOIN sections s ON s.section_id = a.section_id
			WHERE pa.article_id = a.article_id AND j.journal_id = a.journal_id
			AND pa.issue_id = i.issue_id AND i.published = 1'
			. (isset($journalId) ? ' AND a.journal_id = ?' : '')
			. (isset($sectionId) ? ' AND a.section_id = ?' : '')
			. (isset($from) ? ' AND pa.date_published >= ' . $this->datetimeToDB($from) : '')
			. (isset($until) ? ' AND pa.date_published <= ' . $this->datetimeToDB($until) : ''),
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
		$records = array();
		
		$params = array();
		if (isset($journalId)) {
			array_push($params, $journalId);
		}
		if (isset($sectionId)) {
			array_push($params, $sectionId);
		}
		$result = &$this->retrieve(
			'SELECT pa.article_id, pa.date_published,
			j.title AS journal_title, j.path AS journal_path,
			s.abbrev as section_abbrev
			FROM published_articles pa, issues i, journals j, articles a
			LEFT JOIN sections s ON s.section_id = a.section_id
			WHERE pa.article_id = a.article_id AND j.journal_id = a.journal_id
			AND pa.issue_id = i.issue_id AND i.published = 1'
			. (isset($journalId) ? ' AND a.journal_id = ?' : '')
			. (isset($sectionId) ? ' AND a.section_id = ?' : '')
			. (isset($from) ? ' AND pa.date_published >= ' . $this->datetimeToDB($from) : '')
			. (isset($until) ? ' AND pa.date_published <= ' . $this->datetimeToDB($until) : ''),
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
		
		$articleId = $row['article_id'];
		if ($this->journalSettingsDao->getSetting($row['journal_id'], 'enablePublicArticleId')) {
			if (!empty($row['public_article_id'])) {
				$articleId = $row['public_article_id'];
			}
		}
		
		// FIXME Use public ID in OAI identifier?
		// FIXME Use "last-modified" field for datestamp?
		$record->identifier = $this->oai->articleIdToIdentifier($row['article_id']);
		$record->datestamp = $this->oai->UTCDate(strtotime($this->datetimeFromDB($row['date_published'])));
		$record->sets = array($row['journal_path'] . ':' . $row['section_abbrev']);
		
		$record->url = Request::getIndexUrl() . '/' . $row['journal_path'] . '/article/view/' . $articleId;
		$record->title = $row['title']; // FIXME include localized titles as well?
		$record->creator = array();
		$record->subject = array($row['discipline'], $row['subject'], $row['subject_class']);
		$record->description = $row['abstract'];
		$record->publisher = $row['journal_title'];
		$record->contributor = array($row['sponsor']);
		$record->date = date('Y-m-d', strtotime($this->datetimeFromDB($row['issue_published']))); 
		$record->type = array('Peer-reviewed Article', $row['type']); //FIXME?
		$record->format = array();
		$record->source = $row['journal_title'] . '; ' . $this->_formatIssueId($row);
		$record->language = $row['language'];
		$record->relation = array();
		$record->coverage = array($row['coverage_geo'], $row['coverage_chron'], $row['coverage_sample']);
		$record->rights = $this->journalSettingsDao->getSetting($row['journal_id'], 'copyrightNotice');
		
		// Get publisher
		$publisher = $this->journalSettingsDao->getSetting($row['journal_id'], 'publisher');
		if (isset($publisher['institution']) && !empty($publisher['institution'])) {
			$record->publisher = $publisher['institution'];
		}
		
		// Get author names
		$authors = $this->authorDao->getAuthorsByArticle($row['article_id']);
		for ($i = 0, $num = count($authors); $i < $num; $i++) {
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
		for ($i = 0, $num = count($suppFiles); $i < $num; $i++) {
			// FIXME replace with correct URL
			$record->relation[] = Request::getIndexUrl() . '/' . $row['journal_path'] . '/article/download/' . $articleId . '/' . $suppFiles[$i]->getFileId();
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
		$record->datestamp = $this->oai->UTCDate(strtotime($this->datetimeFromDB($row['date_published'])));
		$record->sets = array($row['journal_path'] . ':' . $row['section_abbrev']);
		
		return $record;
	}
	
	// FIXME Common code with issue.Issue
	function _formatIssueId(&$row) {
		switch ($row['issue_label_format']) {
			case ISSUE_LABEL_VOL_YEAR:
				$vol = $row['issue_volume'];
				$year = $row['issue_year'];
				$volLabel = Locale::translate('issue.vol');
				return "$volLabel $vol ($year)";
			case ISSUE_LABEL_YEAR:
				return $row['issue_year'];
			case ISSUE_LABEL_TITLE:
				return $row['issue_title'];
			case ISSUE_LABEL_NUM_VOL_YEAR:
			default:
				$num = $row['issue_number'];
				$vol = $row['issue_volume'];
				$year = $row['issue_year'];
				$volLabel = Locale::translate('issue.vol');
				$numLabel = Locale::translate('issue.no');
				return "$volLabel $vol, $numLabel $num ($year)";
		}
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
	
	
	//
	// Sets
	//
	
	/**
	 * Return hierarchy of OAI sets (journals plus journal sections).
	 * @param $journalId int
	 * @param $offset int
	 * @param $total int
	 * @return array OAISet
	 */
	function &getJournalSets($journalId, $offset, &$total) {
		if (isset($journalId)) {
			$journals = array($this->journalDao->getJournal($journalId));
		} else {
			$journals = &$this->journalDao->getJournals();
			$journals = &$journals->toArray();
		}
		
		// FIXME Set descriptions
		$sets = array();
		foreach ($journals as $journal) {
			$title = $journal->getTitle();
			$abbrev = $journal->getPath();
			array_push($sets, new OAISet($abbrev, $title, ''));
			
			$sections = &$this->sectionDao->getJournalSections($journal->getJournalId());
			foreach ($sections->toArray() as $section) {
				array_push($sets, new OAISet($abbrev . ':' . $section->getAbbrev(), $section->getTitle(), ''));
			}
		}
		
		if ($offset != 0) {
			$sets = array_slice($sets, $offset);
		}
		
		return $sets;
	}
	
	/**
	 * Return the journal ID and section ID corresponding to a journal/section pairing.
	 * @param $journalSpec string
	 * @param $sectionSpec string
	 * @param $restrictJournalId int
	 * @return array (int, int)
	 */
	function getSetJournalSectionId($journalSpec, $sectionSpec, $restrictJournalId = null) {
		$journalId = null;
		
		$journal = &$this->journalDao->getJournalByPath($journalSpec);
		if (!isset($journal) || (isset($restrictJournalId) && $journal->getJournalId() != $restrictJournalId)) {
			return array(0, 0);
		}
		
		$journalId = $journal->getJournalId();
		$sectionId = null;
		
		if (isset($sectionSpec)) {
			$section = &$this->sectionDao->getSectionByAbbrev($sectionSpec, $journal->getJournalId());
			if (isset($section)) {
				$sectionId = $section->getSectionId();
			} else {
				$sectionId = 0;
			}
		}
		
		return array($journalId, $sectionId);
	}
	
}

?>
