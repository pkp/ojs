<?php

/**
 * @file OAIDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai.ojs
 * @class OAIDAO
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
	var $articleDao;
	var $issueDao;
 	var $authorDao;
 	var $suppFileDao;
 	var $journalSettingsDao;
 
 	var $journalCache;
	var $sectionCache;
 
 	/**
	 * Constructor.
	 */
	function OAIDAO() {
		parent::DAO();
		$this->journalDao =& DAORegistry::getDAO('JournalDAO');
		$this->sectionDao =& DAORegistry::getDAO('SectionDAO');
		$this->articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->issueDao =& DAORegistry::getDAO('IssueDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
		$this->journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');

		$this->journalCache = array();
		$this->sectionCache = array();
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
		$result =& $this->retrieve(
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

		$result->Close();
		unset($result);

		return $timestamp;
	}
	
	/**
	 * Check if an article ID specifies a published article.
	 * @param $articleId int
	 * @param $journalId int optional
	 * @return boolean
	 */
	function recordExists($articleId, $journalId = null) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM published_articles pa, issues i
			WHERE pa.issue_id = i.issue_id AND i.published = 1 AND pa.article_id = ?'
			. (isset($journalId) ? ' AND i.journal_id = ?' : ''),
			
			isset($journalId) ? array($articleId, $journalId) : $articleId
		);
		
		$returner = $result->fields[0] == 1;

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Return OAI record for specified article.
	 * @param $articleId int
	 * @param $journalId int optional
	 * @return OAIRecord
	 */
	function &getRecord($articleId, $journalId = null) {
		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.article_id,
				i.issue_id
				s.section_id,
			FROM	published_articles pa,
				issues i,
				journals j,
				articles a,
				sections s
			WHERE	pa.article_id = a.article_id
				AND s.section_id = a.section_id
				AND j.journal_id = a.journal_id
				AND pa.issue_id = i.issue_id
				AND i.published = 1
				AND pa.article_id = ?'
			. (isset($journalId) ? ' AND a.journal_id = ?' : ''),
			isset($journalId) ? array($articleId, $journalId) : $articleId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$row =& $result->GetRowAssoc(false);
			$returner =& $this->_returnRecordFromRow($row);
		}

		$result->Close();
		unset($result);

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
		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.article_id,
				j.journal_id,
				s.section_id,
				i.issue_id
			FROM	published_articles pa,
				issues i,
				journals j,
				articles a,
				sections s
			WHERE	pa.article_id = a.article_id
				AND s.section_id = a.section_id
				AND j.journal_id = a.journal_id
				AND pa.issue_id = i.issue_id
				AND i.published = 1'
				. (isset($journalId) ? ' AND a.journal_id = ?' : '')
				. (isset($sectionId) ? ' AND a.section_id = ?' : '')
				. (isset($from) ? ' AND pa.date_published >= ' . $this->datetimeToDB($from) : '')
				. (isset($until) ? ' AND pa.date_published <= ' . $this->datetimeToDB($until) : '')
				. ' ORDER BY journal_id',
			$params
		);
		
		$total = $result->RecordCount();
		
		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row =& $result->GetRowAssoc(false);
			$records[] =& $this->_returnRecordFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);
		
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
		$result =& $this->retrieve(
			'SELECT	pa.article_id,
				pa.date_published,
				j.journal_id,
				s.section_id
			FROM	published_articles pa,
				issues i,
				journals j,
				articles a,
				sections s
			WHERE	pa.article_id = a.article_id
				AND s.section_id = a.section_id
				AND j.journal_id = a.journal_id
				AND pa.issue_id = i.issue_id AND i.published = 1'
				. (isset($journalId) ? ' AND a.journal_id = ?' : '')
				. (isset($sectionId) ? ' AND a.section_id = ?' : '')
				. (isset($from) ? ' AND pa.date_published >= ' . $this->datetimeToDB($from) : '')
				. (isset($until) ? ' AND pa.date_published <= ' . $this->datetimeToDB($until) : '')
				. ' ORDER BY journal_id',
			$params
		);
		
		$total = $result->RecordCount();
		
		$result->Move($offset);
		for ($count = 0; $count < $limit && !$result->EOF; $count++) {
			$row =& $result->GetRowAssoc(false);
			$records[] =& $this->_returnIdentifierFromRow($row);
			$result->moveNext();
		}

		$result->Close();
		unset($result);
		
		return $records;
	}

	function stripAssocArray($values) {
		foreach (array_keys($values) as $key) {
			$values[$key] = strip_tags($values[$key]);
		}
		return $values;
	}

	/**
	 * Cached function to get a journal
	 * @param $journalId int
	 * @return object
	 */
	function &getJournal($journalId) {
		if (!isset($this->journalCache[$journalId])) {
			$this->journalCache[$journalId] =& $this->journalDao->getJournal($journalId);
		}
		return $this->journalCache[$journalId];
	}

	/**
	 * Cached function to get an issue
	 * @param $issueId int
	 * @return object
	 */
	function &getIssue($issueId) {
		if (!isset($this->issueCache[$issueId])) {
			$this->issueCache[$issueId] =& $this->issueDao->getIssueById($issueId);
		}
		return $this->issueCache[$issueId];
	}

	/**
	 * Cached function to get a journal section
	 * @param $sectionId int
	 * @return object
	 */
	function &getSection($sectionId) {
		if (!isset($this->sectionCache[$sectionId])) {
			$this->sectionCache[$sectionId] =& $this->sectionDao->getSection($sectionId);
		}
		return $this->sectionCache[$sectionId];
	}


	/**
	 * Return OAIRecord object from database row.
	 * @param $row array
	 * @return OAIRecord
	 */
	function &_returnRecordFromRow(&$row) {
		$record =& new OAIRecord();
		
		$articleId = $row['article_id'];
		if ($this->journalSettingsDao->getSetting($row['journal_id'], 'enablePublicArticleId')) {
			if (!empty($row['public_article_id'])) {
				$articleId = $row['public_article_id'];
			}
		}

		$article =& $this->articleDao->getArticle($articleId);
		$journal =& $this->getJournal($row['journal_id']);
		$section =& $this->getSection($row['section_id']);
		$issue =& $this->getIssue($row['issue_id']);

		// FIXME Use public ID in OAI identifier?
		// FIXME Use "last-modified" field for datestamp?
		$record->identifier = $this->oai->articleIdToIdentifier($row['article_id']);
		$record->datestamp = $this->oai->UTCDate(strtotime($this->datetimeFromDB($row['date_published'])));
		$record->sets = array($journal->getPath() . ':' . $section->getSectionAbbrev());
		
		$record->url = Request::url($journal->getPath(), 'article', 'view', array($articleId));

		$record->titles = $this->stripAssocArray((array) $article->getTitle(null));

		$record->subjects = array_merge_recursive(
			$this->stripAssocArray((array) $article->getDiscipline(null)),
			$this->stripAssocArray((array) $article->getSubject(null)),
			$this->stripAssocArray((array) $article->getSubjectClass(null))
		);
		$record->descriptions = $this->stripAssocArray((array) $article->getAbstract(null));
		$record->publishers = $this->stripAssocArray((array) $journal->getTitle(null)); // Provide a default; may be overridden later
		$record->contributors = $this->stripAssocArray((array) $article->getSponsor(null));
		$record->date = date('Y-m-d', strtotime($issue->getDatePublished()));
		$types = $this->stripAssocArray((array) $section->getIdentifyType(null));
		$record->types = empty($types)?array(Locale::getLocale() => Locale::translate('rt.metadata.pkp.peerReviewed')):$types;
		$record->format = array();

		$record->sources = $this->stripAssocArray((array) $journal->getTitle(null));
		foreach ($record->sources as $key => $source) {
			$record->sources[$key] .= '; ' . $this->_formatIssueId($row);
		}

		$record->language = strip_tags($article->getLanguage());
		$record->relation = array();
		$record->coverage = array_merge_recursive(
			$this->stripAssocArray((array) $article->getCoverageGeo(null)),
			$this->stripAssocArray((array) $article->getCoverageChron(null)),
			$this->stripAssocArray((array) $article->getCoverageSample(null))
		);

		$record->rights = (array) $this->journalSettingsDao->getSetting($row['journal_id'], 'copyrightNotice');
		$record->pages = $article->getPages();
		
		// Get publisher (may override earlier publisher)
		$publisherInstitution = (array) $journal->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$record->publishers = $publisherInstitution;
		}
		
		// Get author names
		$authors = $this->authorDao->getAuthorsByArticle($row['article_id']);
		$record->creator = array();
		for ($i = 0, $num = count($authors); $i < $num; $i++) {
			$authorName = $authors[$i]->getFullName();
			$affiliation = $authors[$i]->getAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$record->creator[] = $authorName;
		}
		
		// Get galley formats
		$result =& $this->retrieve(
			'SELECT DISTINCT(f.file_type) FROM article_galleys g, article_files f WHERE g.file_id = f.file_id AND g.article_id = ?',
			$row['article_id']
		);
		while (!$result->EOF) {
			$record->format[] = $result->fields[0];
			$result->MoveNext();
		}

		$result->Close();
		unset($result);
		
		// Get supplementary files
		$suppFiles =& $this->suppFileDao->getSuppFilesByArticle($row['article_id']);
		for ($i = 0, $num = count($suppFiles); $i < $num; $i++) {
			// FIXME replace with correct URL
			$record->relation[] = Request::url($journal->getPath(), 'article', 'download', array($articleId, $suppFiles[$i]->getFileId()));
		}

		$record->primaryLocale = $journal->getPrimaryLocale();

		return $record;
	}
	
	/**
	 * Return OAIIdentifier object from database row.
	 * @param $row array
	 * @return OAIIdentifier
	 */
	function &_returnIdentifierFromRow(&$row) {
		$journal =& $this->getJournal($row['journal_id']);
		$section =& $this->getSection($row['section_id']);

		$record =& new OAIRecord();
		
		$record->identifier = $this->oai->articleIdToIdentifier($row['article_id']);
		$record->datestamp = $this->oai->UTCDate(strtotime($this->datetimeFromDB($row['date_published'])));
		$record->sets = array($journal->getPath() . ':' . $section->getSectionAbbrev());
		
		return $record;
	}
	
	// FIXME Common code with issue.Issue
	function _formatIssueId(&$row) {
		$issue =& $this->getIssue($row['issue_id']);
		return $issue->getIssueIdentification();
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
		$result =& $this->retrieve(
			'SELECT * FROM oai_resumption_tokens WHERE token = ?', $tokenId
		);
		
		if ($result->RecordCount() == 0) {
			$token = null;
			
		} else {
			$row =& $result->getRowAssoc(false);
			$token =& new OAIResumptionToken($row['token'], $row['record_offset'], unserialize($row['params']), $row['expire']);
		}

		$result->Close();
		unset($result);

		return $token;
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
			$result =& $this->retrieve(
				'SELECT COUNT(*) FROM oai_resumption_tokens WHERE token = ?',
				$token->id
			);
			$val = $result->fields[0];

			$result->Close();
			unset($result);
		} while($val != 0);
		
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
			$journals =& $this->journalDao->getJournals();
			$journals =& $journals->toArray();
		}
		
		// FIXME Set descriptions
		$sets = array();
		foreach ($journals as $journal) {
			$title = $journal->getJournalTitle();
			$abbrev = $journal->getPath();
			array_push($sets, new OAISet($abbrev, $title, ''));
			
			$sections =& $this->sectionDao->getJournalSections($journal->getJournalId());
			foreach ($sections->toArray() as $section) {
				array_push($sets, new OAISet($abbrev . ':' . $section->getSectionAbbrev(), $section->getSectionTitle(), ''));
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
		
		$journal =& $this->journalDao->getJournalByPath($journalSpec);
		if (!isset($journal) || (isset($restrictJournalId) && $journal->getJournalId() != $restrictJournalId)) {
			return array(0, 0);
		}
		
		$journalId = $journal->getJournalId();
		$sectionId = null;
		
		if (isset($sectionSpec)) {
			$section =& $this->sectionDao->getSectionByAbbrev($sectionSpec, $journal->getJournalId());
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
