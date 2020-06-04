<?php

/**
 * @file classes/oai/ojs/JournalOAI.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class JournalOAI
 * @ingroup oai
 * @see OAIDAO
 *
 * @brief OJS-specific OAI interface.
 * Designed to support both a site-wide and journal-specific OAI interface
 * (based on where the request is directed).
 */

import('lib.pkp.classes.oai.OAI');
import('classes.oai.ojs.OAIDAO');

class JournalOAI extends OAI {
	/** @var Site associated site object */
	var $site;

	/** @var Journal associated journal object */
	var $journal;

	/** @var int|null Journal ID; null if no journal */
	var $journalId;

	/** @var OAIDAO DAO for retrieving OAI records/tokens from database */
	var $dao;


	/**
	 * @copydoc OAI::OAI()
	 */
	function __construct($config) {
		parent::__construct($config);

		$request = Application::get()->getRequest();
		$this->site = $request->getSite();
		$this->journal = $request->getJournal();
		$this->journalId = isset($this->journal) ? $this->journal->getId() : null;
		$this->dao = DAORegistry::getDAO('OAIDAO');
		$this->dao->setOAI($this);
	}

	/**
	 * Return a list of ignorable GET parameters.
	 * @return array
	 */
	function getNonPathInfoParams() {
		return array('journal', 'page');
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
	 * @return int|false
	 */
	function identifierToArticleId($identifier) {
		$prefix = 'oai:' . $this->config->repositoryId . ':' . 'article/';
		if (strstr($identifier, $prefix)) {
			return (int) str_replace($prefix, '', $identifier);
		} else {
			return false;
		}
	}

	/**
	 * Get the journal ID and section ID corresponding to a set specifier.
	 * @return array
	 */
	function setSpecToSectionId($setSpec, $journalId = null) {
		$tmpArray = preg_split('/:/', $setSpec);
		if (count($tmpArray) == 1) {
			list($journalSpec) = $tmpArray;
			$journalSpec = urldecode($journalSpec);
			$sectionSpec = null;
		} else if (count($tmpArray) == 2) {
			list($journalSpec, $sectionSpec) = $tmpArray;
			$journalSpec = urldecode($journalSpec);
			$sectionSpec = urldecode($sectionSpec);
		} else {
			return array(0, 0);
		}
		return $this->dao->getSetJournalSectionId($journalSpec, $sectionSpec, $this->journalId);
	}


	//
	// OAI interface functions
	//

	/**
	 * @copydoc OAI::repositoryInfo()
	 */
	function repositoryInfo() {
		$info = new OAIRepository();

		if (isset($this->journal)) {
			$info->repositoryName = $this->journal->getLocalizedName();
			$info->adminEmail = $this->journal->getData('contactEmail');

		} else {
			$info->repositoryName = $this->site->getLocalizedTitle();
			$info->adminEmail = $this->site->getLocalizedContactEmail();
		}

		$info->sampleIdentifier = $this->articleIdToIdentifier(1);
		$info->earliestDatestamp = $this->dao->getEarliestDatestamp(array($this->journalId));

		$info->toolkitTitle = 'Open Journal Systems';
		$versionDao = DAORegistry::getDAO('VersionDAO'); /* @var $versionDao VersionDAO */
		$currentVersion = $versionDao->getCurrentVersion();
		$info->toolkitVersion = $currentVersion->getVersionString();
		$info->toolkitURL = 'http://pkp.sfu.ca/ojs/';

		return $info;
	}

	/**
	 * @copydoc OAI::validIdentifier()
	 */
	function validIdentifier($identifier) {
		return $this->identifierToArticleId($identifier) !== false;
	}

	/**
	 * @copydoc OAI::identifierExists()
	 */
	function identifierExists($identifier) {
		$recordExists = false;
		$articleId = $this->identifierToArticleId($identifier);
		if ($articleId) {
			$recordExists = $this->dao->recordExists($articleId, array($this->journalId));
		}
		return $recordExists;
	}

	/**
	 * @copydoc OAI::record()
	 */
	function record($identifier) {
		$articleId = $this->identifierToArticleId($identifier);
		if ($articleId) {
			$record = $this->dao->getRecord($articleId, array($this->journalId));
		}
		if (!isset($record)) {
			$record = false;
		}
		return $record;
	}

	/**
	 * @copydoc OAI::records()
	 */
	function records($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		$records = null;
		if (!HookRegistry::call('JournalOAI::records', array($this, $from, $until, $set, $offset, $limit, &$total, &$records))) {
			$sectionId = null;
			if (isset($set)) {
				list($journalId, $sectionId) = $this->setSpecToSectionId($set);
			} else {
				$journalId = $this->journalId;
			}
			$records = $this->dao->getRecords(array($journalId, $sectionId), $from, $until, $set, $offset, $limit, $total);
		}
		return $records;
	}

	/**
	 * @copydoc OAI::identifiers()
	 */
	function identifiers($metadataPrefix, $from, $until, $set, $offset, $limit, &$total) {
		$records = null;
		if (!HookRegistry::call('JournalOAI::identifiers', array($this, $from, $until, $set, $offset, $limit, &$total, &$records))) {
			$sectionId = null;
			if (isset($set)) {
				list($journalId, $sectionId) = $this->setSpecToSectionId($set);
			} else {
				$journalId = $this->journalId;
			}
			$records = $this->dao->getIdentifiers(array($journalId, $sectionId), $from, $until, $set, $offset, $limit, $total);
		}
		return $records;
	}

	/**
	 * @copydoc OAI::sets()
	 */
	function sets($offset, $limit, &$total) {
		$sets = null;
		if (!HookRegistry::call('JournalOAI::sets', array($this, $offset, $limit, &$total, &$sets))) {
			$sets = $this->dao->getJournalSets($this->journalId, $offset, $limit, $total);
		}
		return $sets;
	}

	/**
	 * @copydoc OAI::resumptionToken()
	 */
	function resumptionToken($tokenId) {
		$this->dao->clearTokens();
		$token = $this->dao->getToken($tokenId);
		if (!isset($token)) {
			$token = false;
		}
		return $token;
	}

	/**
	 * @copydoc OAI::saveResumptionToken()
	 */
	function saveResumptionToken($offset, $params) {
		$token = new OAIResumptionToken(null, $offset, $params, time() + $this->config->tokenLifetime);
		$this->dao->insertToken($token);
		return $token;
	}
}


