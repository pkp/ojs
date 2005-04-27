<?php

/**
 * RTDAO.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt.ojs
 *
 * DAO operations for the OJS Reading Tools interface.
 *
 * $Id$
 */

import('rt.ojs.JournalRT');

class RTDAO extends DAO {

	//
	// RT
	//
	
	/**
	 * Retrieve an RT configuration.
	 * @param $versionId int
	 * @return RT
	 */
	function getJournalRTByJournalId($journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM rt_settings WHERE journal_id = ?',
			$journalId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnJournalRTFromRow($result->GetRowAssoc(false));
		}
	}

	function updateJournalRT($rt) {
		return $this->update(
			'UPDATE rt_settings
			SET
				version_id = ?,
				capture_cite = ?,
				view_metadata = ?,
				supplementary_files = ?,
				printer_friendly = ?,
				author_bio = ?,
				define_terms = ?,
				add_comment = ?,
				email_author = ?,
				email_others = ?,
				bib_format = ?
			WHERE journal_id = ?',
			array(
				$rt->getVersion(),
				$rt->getCaptureCite(),
				$rt->getViewMetadata(),
				$rt->getSupplementaryFiles(),
				$rt->getPrinterFriendly(),
				$rt->getAuthorBio(),
				$rt->getDefineTerms(),
				$rt->getAddComment(),
				$rt->getEmailAuthor(),
				$rt->getEmailOthers(),
				$rt->getBibFormat(),
				$rt->getJournalId()
			)
		);
	}
	
	function deleteJournalRT($journalId) {
		return $this->update(
			'DELETE FROM rt_settings WHERE journal_id = ?',
			$journalId
		);
	}
	
	/**
	 * Insert a new RT configuration.
	 * @param $rt object
	 */
	function insertJournalRT(&$rt) {
		return $this->update(
			'INSERT INTO rt_settings (
				journal_id,
				version_id,
				capture_cite,
				view_metadata,
				supplementary_files,
				printer_friendly,
				author_bio,
				define_terms,
				add_comment,
				email_author,
				email_others,
				bib_format
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$rt->getJournalId(),
				$rt->getVersion(),
				$rt->getCaptureCite(),
				$rt->getViewMetadata(),
				$rt->getSupplementaryFiles(),
				$rt->getPrinterFriendly(),
				$rt->getAuthorBio(),
				$rt->getDefineTerms(),
				$rt->getAddComment(),
				$rt->getEmailAuthor(),
				$rt->getEmailOthers(),
				$rt->getBibFormat()
			)
		);
	}
	

	//
	// RT Versions
	//

	/**
	 * Retrieve all RT versions for a journal.
	 * @param $journalId int
	 * @return array RTVersion
	 */
	function &getVersions($journalId) {
		$versions = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM rt_versions WHERE journal_id = ? ORDER BY version_key',
			$journalId
		);
		
		while (!$result->EOF) {
			$versions[] = &$this->_returnVersionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $versions;
	}

	/**
	 * Retrieve a version.
	 * @param $versionId int
	 * @param $journalId int
	 * @return RTVersion
	 */
	function &getVersion($versionId, $journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM rt_versions WHERE version_id = ? AND journal_id = ?',
			array($versionId, $journalId)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnVersionFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Insert a new version.
	 * @param $journalId int
	 * @param $version RTVersion
	 */
	function insertVersion($journalId, &$version) {
		$this->update(
			'INSERT INTO rt_versions
			(journal_id, version_key, locale, title, description)
			VALUES
			(?, ?, ?, ?, ?)',
			array($journalId, $version->key, $version->locale, $version->title, $version->description)
		);
		
		$version->versionId = $this->getInsertId('rt_versions', 'version_id');
		
		foreach ($version->contexts as $context) {
			$context->versionId = $version->versionId;
			$this->insertContext($context);
		}
	}
	
	/**
	 * Update an exisiting verison.
	 * @param $version RTVersion
	 */
	function updateVersion($journalId, &$version) {
		// FIXME Update contexts and searches?
		return $this->update(
			'UPDATE rt_versions
			SET
				title = ?,
				description = ?,
				version_key = ?,
				locale = ?
			WHERE version_id = ? AND journal_id = ?',
			array(
				$version->getTitle(),
				$version->getDescription(),
				$version->getKey(),
				$version->getLocale(),
				$version->getVersionId(),
				$journalId
			)
		);
	}

	/**
	 * Delete all versions by journal ID.
	 * @param $journalId int
	 */
	function deleteVersionsByJournalId($journalId) {
		foreach ($this->getVersions($journalId) as $version) {
			$this->deleteVersion($version->getVersionId(), $journalId);
		}
	}

	/**
	 * Delete a version.
	 * @param $versionId int
	 * @param $journalId int
	 */
	function deleteVersion($versionId, $journalId) {
		$this->deleteContextsByVersionId($versionId);
		return $this->update(
			'DELETE FROM rt_versions WHERE version_id = ? AND journal_id = ?',
			array($versionId, $journalId)
		);
	}

	/**
	 * Delete RT versions (and dependent entities) by journal ID.
	 * @param $journalId int
	 */
	function deleteVersionsByJournal($journalId) {
		$versions = &RTDAO::getVersions($journalId);
		foreach ($versions as $version) {
			deleteVersion($version->getVersionId(), $journalId);
		}
	}
	
	/**
	 * Return RT object from database row.
	 * @param $row array
	 * @return RTVersion
	 */
	function &_returnJournalRTFromRow(&$row) {
		$rt = &new JournalRT($row['journal_id']);
		$rt->setVersion($row['version_id']);
		$rt->setCaptureCite($row['capture_cite']);
		$rt->setViewMetadata($row['view_metadata']);
		$rt->setSupplementaryFiles($row['supplementary_files']);
		$rt->setPrinterFriendly($row['printer_friendly']);
		$rt->setAuthorBio($row['author_bio']);
		$rt->setDefineTerms($row['define_terms']);
		$rt->setAddComment($row['add_comment']);
		$rt->setEmailAuthor($row['email_author']);
		$rt->setEmailOthers($row['email_others']);
		$rt->setBibFormat($row['bib_format']);
		return $rt;
	}
	
	/**
	 * Return RTVersion object from database row.
	 * @param $row array
	 * @return RTVersion
	 */
	function &_returnVersionFromRow(&$row) {
		$version = &new RTVersion();
		$version->setVersionId($row['version_id']);
		$version->setKey($row['version_key']);
		$version->setLocale($row['locale']);
		$version->setTitle($row['title']);
		$version->setDescription($row['description']);
		$version->setContexts($this->getContexts($row['version_id']));
		return $version;
	}
	
	/**
	 * Return RTSearch object from database row.
	 * @param $row array
	 * @return RTSearch
	 */
	function &_returnSearchFromRow(&$row) {
		$search = &new RTSearch();
		$search->setSearchId($row['search_id']);
		$search->setContextId($row['context_id']);
		$search->setTitle($row['title']);
		$search->setDescription($row['description']);
		$search->setUrl($row['url']);
		$search->setSearchUrl($row['search_url']);
		$search->setSearchPost($row['search_post']);
		$search->setOrder($row['seq']);
		return $search;
	}
	
	
	
	//
	// RT Contexts
	//

	/**
	 * Retrieve an RT context.
	 * @param $contextId int
	 * @return RT
	 */
	function getContext($contextId) {
		$result = &$this->retrieve(
			'SELECT * FROM rt_contexts WHERE context_id = ?',
			array($contextId)
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnContextFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Retrieve all RT contexts for a version (in order).
	 * @param $versionId int
	 * @return array RTContext
	 */
	function &getContexts($versionId) {
		$contexts = array();

		$result = &$this->retrieve(
			'SELECT * FROM rt_contexts WHERE version_id = ? ORDER BY seq',
			$versionId
		);
		
		while (!$result->EOF) {
			$contexts[] = &$this->_returnContextFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $contexts;
	}
	
	/**
	 * Insert a context.
	 * @param $versionId int
	 * @param $context RTContext
	 */
	function insertContext(&$context) {
		$this->update(
			'INSERT INTO rt_contexts
			(version_id, title, abbrev, description, author_terms, define_terms, seq)
			VALUES
			(?, ?, ?, ?, ?, ?, ?)',
			array($context->versionId, $context->title, $context->abbrev, $context->description, $context->authorTerms, $context->defineTerms, $context->order)
		);
		
		$context->contextId = $this->getInsertId('rt_contexts', 'context_id');
		
		foreach ($context->searches as $search) {
			$search->contextId = $context->contextId;
			$this->insertSearch($search);
		}
		
	}
	
	/**
	 * Update an existing context.
	 * @param $context RTContext
	 */
	function updateContext(&$context) {
		// FIXME Update searches?
		return $this->update(
			'UPDATE rt_contexts
			SET title = ?, abbrev = ?, description = ?, author_terms = ?, define_terms = ?, seq = ?
			WHERE context_id = ? AND version_id = ?',
			array($context->title, $context->abbrev, $context->description, $context->authorTerms, $context->defineTerms, $context->order, $context->contextId, $context->versionId)
		);
	}
	
	/**
	 * Delete all contexts by version ID.
	 * @param $versionId int
	 */
	function deleteContextsByVersionId($versionId) {
		foreach ($this->getContexts($versionId) as $context) {
			$this->deleteContext(
				$context->getContextId(),
				$context->getVersionId()
			);
		}
	}

	/**
	 * Delete a context.
	 * @param $contextId int
	 * @param $versionId int
	 */
	function deleteContext($contextId, $versionId) {
		$result = $this->update(
			'DELETE FROM rt_contexts WHERE context_id = ? AND version_id = ?',
			array($contextId, $versionId)
		);
		if ($result) $this->deleteSearchesByContextId($contextId);
		return $result;
	}
	
	/**
	 * Return RTContext object from database row.
	 * @param $row array
	 * @return RTContext
	 */
	function &_returnContextFromRow(&$row) {
		$context = &new RTContext();
		$context->setContextId($row['context_id']);
		$context->setVersionId($row['version_id']);
		$context->setTitle($row['title']);
		$context->setAbbrev($row['abbrev']);
		$context->setDescription($row['description']);
		$context->setAuthorTerms($row['author_terms']);
		$context->setDefineTerms($row['define_terms']);
		$context->setOrder($row['seq']);
		$context->setSearches($this->getSearches($row['context_id']));
		return $context;
	}
	
	
	
	//
	// RT Searches
	//

	/**
	 * Retrieve an RT search.
	 * @param $searchId int
	 * @return RTSearch
	 */
	function getSearch($searchId) {
		$result = &$this->retrieve(
			'SELECT * FROM rt_searches WHERE search_id = ?',
			$searchId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSearchFromRow($result->GetRowAssoc(false));
		}
	}

	/**
	 * Retrieve all RT searches for a context (in order).
	 * @param $contextId int
	 * @return array RTSearch
	 */
	function getSearches($contextId) {
		$searches = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM rt_searches WHERE context_id = ? ORDER BY seq',
			$contextId
		);
		
		while (!$result->EOF) {
			$searches[] = &$this->_returnSearchFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $searches;
	}
	
	/**
	 * Insert new search.
	 * @param $search RTSearch
	 */
	function insertSearch(&$search) {
		$this->update(
			'INSERT INTO rt_searches
			(context_id, title, description, url, search_url, search_post, seq)
			VALUES
			(?, ?, ?, ?, ?, ?, ?)',
			array(
				$search->getContextId(),
				$search->getTitle(),
				$search->getDescription(),
				$search->getUrl(),
				$search->getSearchUrl(),
				$search->getSearchPost(),
				$search->getOrder()
			)
		);
		
		$search->searchId = $this->getInsertId('rt_searches', 'search_id');
	}
	
	/**
	 * Update an existing search.
	 * @param $search RTSearch
	 */
	function updateSearch(&$search) {
		return $this->update(
			'UPDATE rt_searches
			SET title = ?, description = ?, url = ?, search_url = ?, search_post = ?, seq = ?
			WHERE search_id = ? AND context_id = ?',
			array(
				$search->getTitle(),
				$search->getDescription(),
				$search->getUrl(),
				$search->getSearchUrl(),
				$search->getSearchPost(),
				$search->getOrder(),
				$search->getSearchId(),
				$search->getContextId()
			)
		);
	}

	/**
	 * Delete all searches by context ID.
	 * @param $contextId int
	 */
	function deleteSearchesByContextId($contextId) {
		return $this->update(
			'DELETE FROM rt_searches WHERE context_id = ?',
			$contextId
		);
	}

	/**
	 * Delete a search.
	 * @param $searchId int
	 * @param $contextId int
	 */
	function deleteSearch($searchId, $contextId) {
		return $this->update(
			'DELETE FROM rt_searches WHERE search_id = ? AND context_id = ?',
			array($searchId, $contextId)
		);
	}
	
}

?>
