<?php

/**
 * JournalOAI.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package oai.ojs
 *
 * OJS-specific OAI interface.
 * Designed to support both a site-wide and journal-specific OAI interface
 * (based on where the request is directed).
 *
 * $Id$
 */

import('oai.OAI');
import('oai.ojs.OAIDAO');

class JournalOAI extends OAI {

	/** @var $site Site associated site object */
	var $site;
	
	/** @var $journal Journal associated journal object */
	var $journal;
	
	/** @var $journalId int null if no journal */
	var $journalId;
	
	/** @var $dao OAIDAO DAO for retrieving OAI records/tokens from database */
	var $dao;
	
	
	/**
	 * @see OAI#OAI
	 */
	function JournalOAI($config) {
		parent::OAI($config);
		
		$this->site = &Request::getSite();
		$this->journal = &Request::getJournal();
		$this->journalId = isset($this->journal) ? $this->journal->getJournalId() : null;
		$this->dao = &DAORegistry::getDAO('OAIDAO');
		$this->dao->setOAI($this);
	}
	
	/**
	 * Convert article ID to OAI identifier.
	 * @param $articleId int
	 * @return string
	 */
	function articleIdToIdentifier($articleId) {
		return 'oai:' . $this->config->repositoryId . ':' . 'article/' . $articleId;
	}
	
	/**
	 * Convert OAI identifier to article ID.
	 * @param $identifier string
	 * @return int
	 */
	function identifierToArticleId($identifier) {
		$prefix = 'oai:' . $this->config->repositoryId . ':' . 'article/';
		if (strstr($identifier, $prefix)) {
			return (int) str_replace($prefix, '', $identifier);
		} else {
			return false;
		}
	}
	
	
	//
	// OAI interface functions
	//
	
	/**
	 * @see OAI#repositoryInfo
	 */
	function &repositoryInfo() {
		$info = &new OAIRepository();
		
		if (isset($this->journal)) {
			$info->repositoryName = $this->journal->getTitle();
			$info->adminEmail = $this->journal->getSetting('contactEmail');

		} else {
			$info->repositoryName = $this->site->getTitle();
			$info->adminEmail = $this->site->getContactEmail();
		}
		
		$info->sampleIdentifier = $this->articleIdToIdentifier(1);
		$info->earliestDatestamp = $this->dao->getEarliestDatestamp($this->journalId);
		
		return $info;
	}
	
	/**
	 * @see OAI#validIdentifier
	 */
	function validIdentifier($identifier) {
		return $this->identifierToArticleId($identifier) !== false;
	}
	
	/**
	 * @see OAI#identifierExists
	 */
	function identifierExists($identifier) {
		$recordExists = false;
		$articleId = $this->identifierToArticleId($identifier);
		if ($articleId) {
			$recordExists = $this->dao->recordExists($articleId, $this->journalId);
		}
		return isset($recordExists);
	}
	
	/**
	 * @see OAI#record
	 */
	function &record($identifier) {
		$articleId = $this->identifierToArticleId($identifier);
		if ($articleId) {
			$record = &$this->dao->getRecord($articleId, $this->journalId);
		}		
		return isset($record) ? $record : false;
	}
	
	/**
	 * @see OAI#records
	 */
	function &records($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		$sectionId = null;
		if (isset($set)) {
			$this->extractSet($set, $this->journalId, $sectionId);
		}
		$records = &$this->dao->getRecords($this->journalId, $sectionId, $from, $until, $offset, $limit, &$total);
		return $records;
	}
	
	/**
	 * @see OAI#identifiers
	 */
	function &identifiers($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		$sectionId = null;
		if (isset($set)) {
			$this->extractSet($set, $this->journalId, $sectionId);
		}
		$records = &$this->dao->getIdentifiers($this->journalId, $sectionId, $from, $until, $offset, $limit, &$total);
		return $records;
	}
	
	/**
	 * @see OAI#sets
	 */
	function &sets($offset, &$total) {
		// FIXME Implement
		// Sets = {All journals + all journal sections}
		return array();
	}
	
	/**
	 * @see OAI#resumptionToken
	 */
	function &resumptionToken($tokenId) {
		$this->dao->clearTokens();
		$token = $this->dao->getToken($tokenId);
		return isset($token) ? $token : false;
	}
	
	/**
	 * @see OAI#saveResumptionToken
	 */
	function &saveResumptionToken($offset, $params) {
		$token = &new OAIResumptionToken(null, $offset, $params, time() + $this->config->tokenLifetime);
		return $this->dao->insertToken($token);
	}
	
}

?>
