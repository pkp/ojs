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

class RTDAO extends DAO {

	//
	// RT
	//
	
	/**
	 * Retrieve an RT configuration.
	 * @param $versionId int
	 * @return RT
	 */
	function getJournalRTByJournalId() {
		$result = &$this->retrieve(
			'SELECT * FROM rt_settings WHERE journal_id = ?',
			array($journalId)
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
				email_others = ?
			WHERE journal_id = ?',
			array(
				$rt->getVersionId(),
				$rt->getCaptureCite(),
				$rt->getViewMetadata(),
				$rt->getSupplementaryFiles(),
				$rt->getPrinterFriendly(),
				$rt->getAuthorBio(),
				$rt->getDefineTerms(),
				$rt->getAddComment(),
				$rt->getEmailAuthor(),
				$rt->getEmailOthers(),
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
				email_others
			) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$rt->getJournalId(),
				$rt->getVersionId(),
				$rt->getCaptureCite(),
				$rt->getViewMetadata(),
				$rt->getSupplementaryFiles(),
				$rt->getPrinterFriendly(),
				$rt->getAuthorBio(),
				$rt->getDefineTerms(),
				$rt->getDefineTerms(),
				$rt->getAddComment(),
				$rt->getEmailAuthor(),
				$rt->getEmailOthers()
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
			SET title = ?, description = ?
			WHERE version_id = ? AND journal_id = ?',
			array($version->title, $version->decription, $version->versionId, $journalId)
		);
	}
	
	/**
	 * Delete a version.
	 * @param $versionId int
	 * @param $journalId int
	 */
	function deleteVersion($versionId, $journalId) {
		// FIXME Delete contexts and searches?
		return $this->update(
			'DELETE FROM rt_versions WHERE version_id = ? AND journal_id = ?',
			array($versionId, $journalId)
		);
	}
	
	/**
	 * Return RT object from database row.
	 * @param $row array
	 * @return RTVersion
	 */
	function &_returnJournalRTFromRow(&$row) {
		$rt = &new JournalRT();
		$rt->setCaptureCite($row['capture_cite']);
		$rt->setViewMetadata($row['view_metadata']);
		$rt->setSupplementaryFiles($row['supplementary_files']);
		$rt->setPrinterFriendly($row['printer_friendly']);
		$rt->setAuthorBio($row['author_bio']);
		$rt->setDefineTerms($row['define_terms']);
		$rt->setAddComment($row['add_comment']);
		$rt->setEmailAuthor($row['email_author']);
		$rt->setEmailOthers($row['email_others']);
		return $rt;
	}
	
	/**
	 * Return RTVersion object from database row.
	 * @param $row array
	 * @return RTVersion
	 */
	function &_returnVersionFromRow(&$row) {
		$version = &new RTVersion();
		$version->versionId = $row['version_id'];
		$version->key = $row['version_key'];
		$version->locale = $row['locale'];
		$version->title = $row['title'];
		$version->description = $row['description'];
		$version->contexts = &$this->getContexts($row['version_id']);
		return $version;
	}
	
	
	
	//
	// RT Contexts
	//

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
	 * Delete a context.
	 * @param $contextId int
	 * @param $versionId int
	 */
	function deleteContext($contextId, $versionId) {
		// FIXME Delete searches?
		return $this->update(
			'DELETE FROM rt_contexts WHERE context_id = ? AND version_id = ?',
			array($contextId, $versionId)
		);
	}
	
	/**
	 * Return RTContext object from database row.
	 * @param $row array
	 * @return RTContext
	 */
	function &_returnContextFromRow(&$row) {
		$context = &new RTContext();
		$context->contextId = $row['contextId'];
		$context->versionId = $row['version_id'];
		$context->title = $row['title'];
		$context->abbrev = $row['abbrev'];
		$context->description = $row['description'];
		$context->authorTerms = $row['author_terms'];
		$context->defineTerms = $row['define_terms'];
		$context->order = $row['seq'];
		$context->searches = &$this->getSearches($row['context_id']);
	}
	
	
	
	//
	// RT Searches
	//

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
			array($search->contextId, $search->title, $search->description, $search->url, $search->searchUrl, $search->searchPost, $search->order)
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
			array($search->title, $search->description, $search->url, $search->searchUrl, $search->post, $search->order, $search->searchId, $search->contextId)
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
