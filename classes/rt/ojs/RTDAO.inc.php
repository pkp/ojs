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
	function &getJournalRTByJournalId($journalId) {
		$result = &$this->retrieve(
			'SELECT * FROM rt_settings WHERE journal_id = ?',
			$journalId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnJournalRTFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
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
	 * @param $pagingInfo object DBResultRange (optional)
	 * @return array RTVersion
	 */
	function &getVersions($journalId, $pagingInfo = null) {
		$versions = array();
		
		$result = &$this->retrieveRange(
			'SELECT * FROM rt_versions WHERE journal_id = ? ORDER BY version_key',
			$journalId,
			$pagingInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnVersionFromRow');
		return $returner;
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

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnVersionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
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
		
		return $version->versionId;
	}
	
	/**
	 * Update an exisiting verison.
	 * @param $journalId int
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
		$versions = &$this->getVersions($journalId);
		foreach ($versions->toArray() as $version) {
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
		while (!$versions->eof()) {
			$version = &$versions->next();
			$this->deleteVersion($version->getVersionId(), $journalId);
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

		HookRegistry::call('RTDAO::_returnJournalRTFromRow', array(&$rt, &$row));

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

		if (!HookRegistry::call('RTDAO::_returnVersionFromRow', array(&$version, &$row))) {
			$contextsIterator = &$this->getContexts($row['version_id']);
			$version->setContexts($contextsIterator->toArray());
		}

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

		HookRegistry::call('RTDAO::_returnSearchFromRow', array(&$search, &$row));

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
	function &getContext($contextId) {
		$result = &$this->retrieve(
			'SELECT * FROM rt_contexts WHERE context_id = ?',
			array($contextId)
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnContextFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all RT contexts for a version (in order).
	 * @param $versionId int
	 * @param $pagingInfo object DBResultRange (optional)
	 * @return array RTContext
	 */
	function &getContexts($versionId, $pagingInfo = null) {
		$contexts = array();

		$result = &$this->retrieveRange(
			'SELECT * FROM rt_contexts WHERE version_id = ? ORDER BY seq',
			$versionId,
			$pagingInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnContextFromRow');
		return $returner;
	}
	
	/**
	 * Insert a context.
	 * @param $versionId int
	 * @param $context RTContext
	 */
	function insertContext(&$context) {
		$this->update(
			'INSERT INTO rt_contexts
			(version_id, title, abbrev, description, cited_by, author_terms, geo_terms, define_terms, seq)
			VALUES
			(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array($context->versionId, $context->title, $context->abbrev, $context->description, $context->citedBy, $context->authorTerms, $context->geoTerms, $context->defineTerms, $context->order)
		);
		
		$context->contextId = $this->getInsertId('rt_contexts', 'context_id');
		
		foreach ($context->searches as $search) {
			$search->contextId = $context->contextId;
			$this->insertSearch($search);
		}
		
		return $context->contextId;
	}
	
	/**
	 * Update an existing context.
	 * @param $context RTContext
	 */
	function updateContext(&$context) {
		// FIXME Update searches?
		return $this->update(
			'UPDATE rt_contexts
			SET title = ?, abbrev = ?, description = ?, cited_by = ?, author_terms = ?, geo_terms = ?, define_terms = ?, seq = ?
			WHERE context_id = ? AND version_id = ?',
			array($context->title, $context->abbrev, $context->description, $context->citedBy, $context->authorTerms, $context->geoTerms, $context->defineTerms, $context->order, $context->contextId, $context->versionId)
		);
	}
	
	/**
	 * Delete all contexts by version ID.
	 * @param $versionId int
	 */
	function deleteContextsByVersionId($versionId) {
		$contexts = &$this->getContexts($versionId);
		foreach ($contexts->toArray() as $context) {
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
	 * Sequentially renumber contexts in their sequence order.
	 */
	function resequenceContexts($versionId) {
		$result = &$this->retrieve(
			'SELECT context_id FROM rt_contexts WHERE version_id = ? ORDER BY seq',
			$versionId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($contextId) = $result->fields;
			$this->update(
				'UPDATE rt_contexts SET seq = ? WHERE context_id = ?',
				array(
					$i,
					$contextId
				)
			);
			
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
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
		$context->setCitedBy($row['cited_by']);
		$context->setAuthorTerms($row['author_terms']);
		$context->setGeoTerms($row['geo_terms']);
		$context->setDefineTerms($row['define_terms']);
		$context->setOrder($row['seq']);

		if (!HookRegistry::call('RTDAO::_returnContextFromRow', array(&$context, &$row))) {
			$searchesIterator = &$this->getSearches($row['context_id']);
			$context->setSearches($searchesIterator->toArray());
		}

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
	function &getSearch($searchId) {
		$result = &$this->retrieve(
			'SELECT * FROM rt_searches WHERE search_id = ?',
			$searchId
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnSearchFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all RT searches for a context (in order).
	 * @param $contextId int
	 * @param $pagingInfo object DBResultRange (optional)
	 * @return array RTSearch
	 */
	function &getSearches($contextId, $pagingInfo = null) {
		$searches = array();
		
		$result = &$this->retrieveRange(
			'SELECT * FROM rt_searches WHERE context_id = ? ORDER BY seq',
			$contextId,
			$pagingInfo
		);

		$returner = &new DAOResultFactory($result, $this, '_returnSearchFromRow');
		return $returner;
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
		return $search->searchId;
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
	
	/**
	 * Sequentially renumber searches in their sequence order.
	 */
	function resequenceSearches($contextId) {
		$result = &$this->retrieve(
			'SELECT search_id FROM rt_searches WHERE context_id = ? ORDER BY seq',
			$contextId
		);
		
		for ($i=1; !$result->EOF; $i++) {
			list($searchId) = $result->fields;
			$this->update(
				'UPDATE rt_searches SET seq = ? WHERE search_id = ?',
				array(
					$i,
					$searchId
				)
			);
			
			$result->moveNext();
		}
		
		$result->close();
		unset($result);
	}

}

?>
