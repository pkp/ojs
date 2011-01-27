<?php

/**
 * @file classes/oai/ojs/OAIDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIDAO
 * @ingroup oai_ojs
 * @see OAI
 *
 * @brief DAO operations for the OJS OAI interface.
 */

// $Id$


import('lib.pkp.classes.oai.OAI');
import('classes.issue.Issue');

class OAIDAO extends DAO {
 	/** @var $oai JournalOAI parent OAI object */
 	var $oai;

 	/** Helper DAOs */
 	var $journalDao;
 	var $sectionDao;
	var $publishedArticleDao;
	var $articleGalleyDao;
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
		$this->publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$this->articleGalleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
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
			'SELECT	MIN(a.last_modified)
			FROM	published_articles pa,
				articles a,
				issues i
			WHERE	pa.issue_id = i.issue_id AND
				i.published = 1 AND
				a.article_id = pa.article_id'
			. (isset($journalId) ? ' AND i.journal_id = ?' : ''),

			isset($journalId) ? ((int) $journalId) : false
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
			'SELECT	COUNT(*)
			FROM	published_articles pa,
				issues i
			WHERE	pa.issue_id = i.issue_id AND
				i.published = 1 AND
				pa.article_id = ?'
			. (isset($journalId) ? ' AND i.journal_id = ?' : ''),

			isset($journalId) ? array((int) $articleId, (int) $journalId) : ((int) $articleId)
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
				a.last_modified,
				a.article_id,
				i.issue_id,
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
				AND pa.issue_id = i.issue_id
				AND i.published = 1
				AND pa.article_id = ?'
			. (isset($journalId) ? ' AND a.journal_id = ?' : ''),
			isset($journalId) ? array((int) $articleId, (int) $journalId) : ((int) $articleId)
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
			array_push($params, (int) $journalId);
		}
		if (isset($sectionId)) {
			array_push($params, (int) $sectionId);
		}
		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.last_modified,
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
				. (isset($from) ? ' AND a.last_modified >= ' . $this->datetimeToDB($from) : '')
				. (isset($until) ? ' AND a.last_modified <= ' . $this->datetimeToDB($until) : '')
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
			array_push($params, (int) $journalId);
		}
		if (isset($sectionId)) {
			array_push($params, (int) $sectionId);
		}
		$result =& $this->retrieve(
			'SELECT	pa.article_id,
				a.last_modified,
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
				. (isset($from) ? ' AND a.last_modified >= ' . $this->datetimeToDB($from) : '')
				. (isset($until) ? ' AND a.last_modified <= ' . $this->datetimeToDB($until) : '')
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
		$record = new OAIRecord();

		$articleId = $row['article_id'];

		$publishedArticle =& $this->publishedArticleDao->getPublishedArticleByArticleId($articleId);
		$journal =& $this->getJournal($row['journal_id']);
		$section =& $this->getSection($row['section_id']);
		$issue =& $this->getIssue($row['issue_id']);
		$galleys =& $this->articleGalleyDao->getGalleysByArticle($articleId);

		$record->setData('article', $publishedArticle);
		$record->setData('journal', $journal);
		$record->setData('section', $section);
		$record->setData('issue', $issue);
		$record->setData('galleys', $galleys);

		$record->identifier = $this->oai->articleIdToIdentifier($row['article_id']);
		$record->datestamp = OAIUtils::UTCDate(strtotime($this->datetimeFromDB($row['last_modified'])));
		$record->sets = array($journal->getPath() . ':' . $section->getLocalizedAbbrev());

		HookRegistry::call('OAIDAO::_returnRecordFromRow', array(&$record, &$row));
		
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

		$record = new OAIRecord();

		$record->identifier = $this->oai->articleIdToIdentifier($row['article_id']);
		$record->datestamp = OAIUtils::UTCDate(strtotime($this->datetimeFromDB($row['last_modified'])));
		$record->sets = array($journal->getPath() . ':' . $section->getLocalizedAbbrev());

		HookRegistry::call('OAIDAO::_returnIdentifierFromRow', array(&$record, &$row));
		
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
		$result =& $this->retrieve(
			'SELECT * FROM oai_resumption_tokens WHERE token = ?',
			array($tokenId)
		);

		if ($result->RecordCount() == 0) {
			$token = null;

		} else {
			$row =& $result->getRowAssoc(false);
			$token = new OAIResumptionToken($row['token'], $row['record_offset'], unserialize($row['params']), $row['expire']);
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
				array($token->id)
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
			$title = $journal->getLocalizedTitle();
			$abbrev = $journal->getPath();
			array_push($sets, new OAISet(urlencode($abbrev), $title, ''));

			$sections =& $this->sectionDao->getJournalSections($journal->getId());
			foreach ($sections->toArray() as $section) {
				array_push($sets, new OAISet(urlencode($abbrev) . ':' . urlencode($section->getLocalizedAbbrev()), $section->getLocalizedTitle(), ''));
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
		if (!isset($journal) || (isset($restrictJournalId) && $journal->getId() != $restrictJournalId)) {
			return array(0, 0);
		}

		$journalId = $journal->getId();
		$sectionId = null;

		if (isset($sectionSpec)) {
			$section =& $this->sectionDao->getSectionByAbbrev($sectionSpec, $journal->getId());
			if (isset($section)) {
				$sectionId = $section->getId();
			} else {
				$sectionId = 0;
			}
		}

		return array($journalId, $sectionId);
	}
}

?>
