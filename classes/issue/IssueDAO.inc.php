<?php

/**
 * @file classes/issue/IssueDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueDAO
 * @ingroup issue
 * @see Issue
 *
 * @brief Operations for retrieving and modifying Issue objects.
 */

import ('classes.issue.Issue');

class IssueDAO extends DAO {
	var $caches;

	function _cacheMiss(&$cache, $id) {
		if ($cache->getCacheId() === 'current') {
			$issue =& $this->getCurrentIssue($id, false);
		} else {
			$issue =& $this->getIssueById($id, null, false);
		}
		$cache->setCache($id, $issue);
		return $issue;
	}

	function &_getCache($cacheId) {
		if (!isset($this->caches)) $this->caches = array();
		if (!isset($this->caches[$cacheId])) {
			$cacheManager =& CacheManager::getManager();
			$this->caches[$cacheId] =& $cacheManager->getObjectCache('issues', $cacheId, array(&$this, '_cacheMiss'));
		}
		return $this->caches[$cacheId];
	}

	/**
	 * Retrieve Issue by issue id
	 * @param $issueId int
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return Issue object
	 */
	function &getIssueById($issueId, $journalId = null, $useCache = false) {
		if ($useCache) {
			$cache =& $this->_getCache('issues');
			$returner = $cache->get($issueId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		if (isset($journalId)) {
			$result =& $this->retrieve(
				'SELECT i.* FROM issues i WHERE issue_id = ? AND journal_id = ?',
				array($issueId, $journalId)
			);
		} else {
			$result =& $this->retrieve(
				'SELECT i.* FROM issues i WHERE issue_id = ?', $issueId
			);
		}

		$issue = null;
		if ($result->RecordCount() != 0) {
			$issue =& $this->_returnIssueFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $issue;
	}

	/**
	 * Retrieve Issue by public issue id
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return Issue object
	 */
	function &getIssueByPubId($pubIdType, $pubId, $journalId = null, $useCache = false) {
		if ($useCache && $pubIdType == 'publisher-id') {
			$cache =& $this->_getCache('issues');
			$returner = $cache->get($pubId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		$issues =& $this->getIssuesBySetting('pub-id::'.$pubIdType, $pubId, $journalId);
		if (empty($issues)) {
			$issue = null;
		} else {
			assert(count($issues) == 1);
			$issue =& $issues[0];
		}

		return $issue;
	}

	/**
	 * Find issues by querying issue settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $journalId int optional
	 * @return array The issues identified by setting.
	 */
	function &getIssuesBySetting($settingName, $settingValue, $journalId = null) {
		$params = array($settingName);
		$sql = 'SELECT	i.*
			FROM	issues i ';
		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN issue_settings ist ON i.issue_id = ist.issue_id AND ist.setting_name = ?
				WHERE	(ist.setting_value IS NULL OR ist.setting_value = "")';
		} else {
			$params[] = $settingValue;
			$sql .= 'INNER JOIN issue_settings ist ON i.issue_id = ist.issue_id
				WHERE	ist.setting_name = ? AND ist.setting_value = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND i.journal_id = ?';
		}
		$sql .= ' ORDER BY i.issue_id';
		$result =& $this->retrieve($sql, $params);

		$issues = array();
		while (!$result->EOF) {
			$issues[] =& $this->_returnIssueFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();

		return $issues;
	}

	/**
	 * Retrieve Issue by some combination of volume, number, and year
	 * @param $journalId int
	 * @param $volume int
	 * @param $number int
	 * @param $year int
	 * @return Iterator object
	 */
	function &getPublishedIssuesByNumber($journalId, $volume = null, $number = null, $year = null) {
		$sql = 'SELECT i.* FROM issues i WHERE i.published = 1 AND i.journal_id = ?';
		$params = array($journalId);

		if ($volume !== null) {
			$sql .= ' AND i.volume = ?';
			$params[] = $volume;
		}
		if ($number !== null) {
			$sql .= ' AND i.number = ?';
			$params[] = $number;
		}
		if ($year !== null) {
			$sql .= ' AND i.year = ?';
			$params[] = $year;
		}

		$result =& $this->retrieve($sql, $params);
		$returner = new DAOResultFactory($result, $this, '_returnIssueFromRow');
		return $returner;
	}

	/**
	 * Retrieve Issue by "best" issue id -- public ID if it exists,
	 * falling back on the internal issue ID otherwise.
	 * @param $issueId string
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return Issue object
	 */
	function &getIssueByBestIssueId($issueId, $journalId = null, $useCache = false) {
		$issue =& $this->getIssueByPubId('publisher-id', $issueId, $journalId, $useCache);
		if (!isset($issue) && ctype_digit("$issueId")) $issue =& $this->getIssueById((int) $issueId, $journalId, $useCache);
		return $issue;
	}

	/**
	 * Retrieve the last created issue
	 * @param $journalId int
	 * @return Issue object
	 */
	function &getLastCreatedIssue($journalId) {
		$result =& $this->retrieveLimit(
			'SELECT i.* FROM issues i WHERE journal_id = ? ORDER BY year DESC, volume DESC, number DESC', $journalId, 1
		);

		$issue = null;
		if ($result->RecordCount() != 0) {
			$issue =& $this->_returnIssueFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $issue;
	}

	/**
	 * Retrieve current issue
	 * @param $journalId int
	 * @param $useCache boolean optional
	 * @return Issue object
	 */
	function &getCurrentIssue($journalId, $useCache = false) {
		if ($useCache) {
			$cache =& $this->_getCache('current');
			$returner = $cache->get($journalId);
			return $returner;
		}

		$result =& $this->retrieve(
			'SELECT i.* FROM issues i WHERE journal_id = ? AND current = 1', $journalId
		);

		$issue = null;
		if ($result->RecordCount() != 0) {
			$issue =& $this->_returnIssueFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $issue;
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

		$this->flushCache();
	}


	/**
	 * Change the public ID of an issue.
	 * @param $issueId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function changePubId($issueId, $pubIdType, $pubId) {
		$idFields = array(
			'issue_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'issue_id' => $issueId,
			'locale' => '',
			'setting_name' => 'pub-id::'.$pubIdType,
			'setting_type' => 'string',
			'setting_value' => (string)$pubId
		);
		$this->replace('issue_settings', $updateArray, $idFields);
		$this->flushCache();
	}

	/**
	 * creates and returns an issue object from a row
	 * @param $row array
	 * @return Issue object
	 */
	function &_returnIssueFromRow($row) {
		$issue = new Issue();
		$issue->setId($row['issue_id']);
		$issue->setJournalId($row['journal_id']);
		$issue->setVolume($row['volume']);
		$issue->setNumber($row['number']);
		$issue->setYear($row['year']);
		$issue->setPublished($row['published']);
		$issue->setCurrent($row['current']);
		$issue->setDatePublished($this->datetimeFromDB($row['date_published']));
		$issue->setDateNotified($this->datetimeFromDB($row['date_notified']));
		$issue->setLastModified($this->datetimeFromDB($row['last_modified']));
		$issue->setAccessStatus($row['access_status']);
		$issue->setOpenAccessDate($this->datetimeFromDB($row['open_access_date']));
		$issue->setShowVolume($row['show_volume']);
		$issue->setShowNumber($row['show_number']);
		$issue->setShowYear($row['show_year']);
		$issue->setShowTitle($row['show_title']);
		$issue->setStyleFileName($row['style_file_name']);
		$issue->setOriginalStyleFileName($row['original_style_file_name']);

		$this->getDataObjectSettings('issue_settings', 'issue_id', $row['issue_id'], $issue);

		HookRegistry::call('IssueDAO::_returnIssueFromRow', array(&$issue, &$row));

		return $issue;
	}

	/**
	 * Get a list of fields for which localized data is supported
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'coverPageDescription', 'coverPageAltText', 'showCoverPage', 'hideCoverPageArchives', 'hideCoverPageCover', 'originalFileName', 'fileName', 'width', 'height', 'description');
	}

	/**
	 * Get a list of additional fields that do not have
	 * dedicated accessors.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		$additionalFields = parent::getAdditionalFieldNames();
		// FIXME: Move this to a PID plug-in.
		$additionalFields[] = 'pub-id::publisher-id';
		return $additionalFields;
	}

	/**
	 * Update the localized fields for this object.
	 * @param $issue
	 */
	function updateLocaleFields(&$issue) {
		$this->updateDataObjectSettings('issue_settings', $issue, array(
			'issue_id' => $issue->getId()
		));
	}

	/**
	 * inserts a new issue into issues table
	 * @param Issue object
	 * @return Issue Id int
	 */
	function insertIssue(&$issue) {
		$this->update(
			sprintf('INSERT INTO issues
				(journal_id, volume, number, year, published, current, date_published, date_notified, last_modified, access_status, open_access_date, show_volume, show_number, show_year, show_title, style_file_name, original_style_file_name)
				VALUES
				(?, ?, ?, ?, ?, ?, %s, %s, %s, ?, %s, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($issue->getDatePublished()), $this->datetimeToDB($issue->getDateNotified()), $this->datetimeToDB($issue->getLastModified()), $this->datetimeToDB($issue->getOpenAccessDate())),
			array(
				(int) $issue->getJournalId(),
				$issue->getVolume(),
				$issue->getNumber(),
				$issue->getYear(),
				$issue->getPublished(),
				$issue->getCurrent(),
				(int) $issue->getAccessStatus(),
				(int) $issue->getShowVolume(),
				(int) $issue->getShowNumber(),
				(int) $issue->getShowYear(),
				(int) $issue->getShowTitle(),
				$issue->getStyleFileName(),
				$issue->getOriginalStyleFileName()
			)
		);

		$issue->setId($this->getInsertIssueId());

		$this->updateLocaleFields($issue);

		if ($this->customIssueOrderingExists($issue->getJournalId())) {
			$this->resequenceCustomIssueOrders($issue->getJournalId());
		}

		return $issue->getId();
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
		$result =& $this->retrieve(
			'SELECT i.* FROM issues i WHERE journal_id = ? AND volume = ? AND number = ? AND year = ? AND issue_id <> ?',
			array($journalId, $volume, $number, $year, $issueId)
		);
		$returner = $result->RecordCount() != 0 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * updates an issue
	 * @param Issue object
	 */
	function updateIssue($issue) {
		$issue->stampModified();
		$this->update(
			sprintf('UPDATE issues
				SET
					journal_id = ?,
					volume = ?,
					number = ?,
					year = ?,
					published = ?,
					current = ?,
					date_published = %s,
					date_notified = %s,
					last_modified = %s,
					open_access_date = %s,
					access_status = ?,
					show_volume = ?,
					show_number = ?,
					show_year = ?,
					show_title = ?,
					style_file_name = ?,
					original_style_file_name = ?
				WHERE issue_id = ?',
			$this->datetimeToDB($issue->getDatePublished()), $this->datetimeToDB($issue->getDateNotified()), $this->datetimeToDB($issue->getLastModified()), $this->datetimeToDB($issue->getOpenAccessDate())),
			array(
				(int) $issue->getJournalId(),
				$issue->getVolume(),
				$issue->getNumber(),
				$issue->getYear(),
				(int) $issue->getPublished(),
				(int) $issue->getCurrent(),
				(int) $issue->getAccessStatus(),
				(int) $issue->getShowVolume(),
				(int) $issue->getShowNumber(),
				(int) $issue->getShowYear(),
				(int) $issue->getShowTitle(),
				$issue->getStyleFileName(),
				$issue->getOriginalStyleFileName(),
				(int) $issue->getId()
			)
		);

		$this->updateLocaleFields($issue);

		if ($this->customIssueOrderingExists($issue->getJournalId())) {
			$this->resequenceCustomIssueOrders($issue->getJournalId());
		}

		$this->flushCache();
	}

	/**
	 * Delete issue. Deletes associated issue galleys, cover pages, and published articles.
	 * @param $issue object issue
	 */
	function deleteIssue(&$issue) {
		import('classes.file.PublicFileManager');
		$publicFileManager = new PublicFileManager();

		if (is_array($issue->getFileName(null))) foreach ($issue->getFileName(null) as $fileName) {
			if ($fileName != '') {
				$publicFileManager->removeJournalFile($issue->getJournalId(), $fileName);
			}
		}
		if (($fileName = $issue->getStyleFileName()) != '') {
			$publicFileManager->removeJournalFile($issue->getJournalId(), $fileName);
		}

		$issueId = $issue->getId();

		// Delete issue-specific ordering if it exists.
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$sectionDao->deleteCustomSectionOrdering($issueId);

		// Delete published issue galleys and issue files
		$issueGalleyDao =& DAORegistry::getDAO('IssueGalleyDAO');
		$issueGalleyDao->deleteGalleysByIssue($issueId);

		$issueFileDao =& DAORegistry::getDAO('IssueFileDAO');
		$issueFileDao->deleteIssueFiles($issueId);

		import('classes.file.IssueFileManager');
		$issueFileManager = new IssueFileManager($issueId);
		$issueFileManager->deleteIssueTree();

		// Delete published articles
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->deletePublishedArticlesByIssueId($issueId);

		// Delete issue settings and issue
		$this->update('DELETE FROM issue_settings WHERE issue_id = ?', $issueId);
		$this->update('DELETE FROM issues WHERE issue_id = ?', $issueId);
		$this->resequenceCustomIssueOrders($issue->getJournalId());

		$this->flushCache();
	}

	/**
	 * Delete issues by journal id. Deletes dependent entities.
	 * @param $journalId int
	 */
	function deleteIssuesByJournal($journalId) {
		$issues =& $this->getIssues($journalId);
		while (($issue =& $issues->next())) {
			$this->deleteIssue($issue);
			unset($issue);
		}
	}

	/**
	 * Checks if issue exists
	 * @param $issueId int
	 * @param $journalId int
	 * @return boolean
	 */
	function issueIdExists($issueId, $journalId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM issues WHERE issue_id = ? AND journal_id = ?',
			array($issueId, $journalId)
		);
		return $result->fields[0] ? true : false;
	}

	/**
	 * Checks if public identifier exists (other than for the specified
	 * issue ID, which is treated as an exception).
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $issueId int An ID to be excluded from the search.
	 * @param $journalId int
	 * @return boolean
	 */
	function pubIdExists($pubIdType, $pubId, $issueId, $journalId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM issue_settings ist
				INNER JOIN issues i ON ist.issue_id = i.issue_id
			WHERE ist.setting_name = ? AND ist.setting_value = ? AND i.issue_id <> ? AND i.journal_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $issueId,
				(int) $journalId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Get issue by article id
	 * @param articleId int
	 * @param journalId int optional
	 * @return issue object
	 */
	function &getIssueByArticleId($articleId, $journalId = null) {
		$params = array($articleId);
		$sql = 'SELECT	i.*
			FROM	issues i,
				published_articles pa,
				articles a
			WHERE	i.issue_id = pa.issue_id AND
				pa.article_id = ? AND
				pa.article_id = a.article_id';
		if ($journalId !== null) {
			$sql .= ' AND i.journal_id = ? AND a.journal_id = i.journal_id';
			$params[] = $journalId;
		}

		$result =& $this->retrieve($sql, $params);

		$issue = null;
		if ($result->RecordCount() != 0) {
			$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
			$issue =& $this->_returnIssueFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $issue;
	}

	/**
	 * Get all issues organized by published date
	 * @param $journalId int
	 * @param $rangeInfo object DBResultRange (optional)
	 * @return issues object ItemIterator
	 */
	function &getIssues($journalId, $rangeInfo = null) {
		$issues = array();

		$sql = 'SELECT i.* FROM issues i WHERE journal_id = ? ORDER BY current DESC, date_published DESC';
		$result =& $this->retrieveRange($sql, $journalId, $rangeInfo);

		$returner = new DAOResultFactory($result, $this, '_returnIssueFromRow');
		return $returner;
	}

	/**
	 * Get published issues organized by published date
	 * @param $journalId int
	 * @param $rangeInfo object DBResultRange
	 * @return issues ItemIterator
	 */
	function &getPublishedIssues($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT i.* FROM issues i LEFT JOIN custom_issue_orders o ON (o.issue_id = i.issue_id) WHERE i.journal_id = ? AND i.published = 1 ORDER BY o.seq ASC, i.current DESC, i.date_published DESC',
			$journalId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnIssueFromRow');
		return $returner;
	}

	/**
	 * Get unpublished issues organized by published date
	 * @param $journalId int
	 * @param $rangeInfo object DBResultRange
	 * @return issues ItemIterator
	 */
	function &getUnpublishedIssues($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT i.* FROM issues i WHERE journal_id = ? AND published = 0 ORDER BY year ASC, volume ASC, number ASC',
			$journalId, $rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnIssueFromRow');
		return $returner;
	}

	/**
	 * Return number of articles assigned to an issue.
	 * @param $issueId int
	 * @return int
	 */
	function getNumArticles($issueId) {
		$result =& $this->retrieve('SELECT COUNT(*) FROM published_articles WHERE issue_id = ?', $issueId);
		$returner = isset($result->fields[0]) ? $result->fields[0] : 0;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Delete the custom ordering of a published issue.
	 * @param $journalId int
	 */
	function deleteCustomIssueOrdering($journalId) {
		return $this->update(
			'DELETE FROM custom_issue_orders WHERE journal_id = ?', $journalId
		);
	}

	/**
	 * Sequentially renumber custom issue orderings in their sequence order.
	 * @param $journalId int
	 */
	function resequenceCustomIssueOrders($journalId) {
		$result =& $this->retrieve(
			'SELECT i.issue_id FROM issues i LEFT JOIN custom_issue_orders o ON (o.issue_id = i.issue_id) WHERE i.journal_id = ? ORDER BY o.seq',
			$journalId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($issueId) = $result->fields;
			$resultB =& $this->retrieve('SELECT issue_id FROM custom_issue_orders WHERE journal_id=? AND issue_id=?', array($journalId, $issueId));
			if (!$resultB->EOF) {
				$this->update(
					'UPDATE custom_issue_orders SET seq = ? WHERE issue_id = ? AND journal_id = ?',
					array($i, $issueId, $journalId)
				);
			} else {
				// This entry is missing. Create it.
				$this->insertCustomIssueOrder($journalId, $issueId, $i);
			}
			$resultB->Close();
			unset($resultB);
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Check if a journal has custom issue ordering.
	 * @param $journalId int
	 * @return boolean
	 */
	function customIssueOrderingExists($journalId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM custom_issue_orders WHERE journal_id = ?',
			$journalId
		);
		$returner = isset($result->fields[0]) && $result->fields[0] == 0 ? false : true;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the custom issue order of a journal.
	 * @param $journalId int
	 * @param $issueId int
	 * @return int
	 */
	function getCustomIssueOrder($journalId, $issueId) {
		$result =& $this->retrieve(
			'SELECT seq FROM custom_issue_orders WHERE journal_id = ? AND issue_id = ?',
			array($journalId, $issueId)
		);

		$returner = null;
		if (!$result->EOF) {
			list($returner) = $result->fields;
		}
		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Import the current issue orders into the specified journal as custom
	 * issue orderings.
	 * @param $journalId int
	 */
	function setDefaultCustomIssueOrders($journalId) {
		$publishedIssues =& $this->getPublishedIssues($journalId);
		$i=1;
		while ($issue =& $publishedIssues->next()) {
			$this->insertCustomIssueOrder($journalId, $issue->getId(), $i);
			unset($issue);
			$i++;
		}
	}

	/**
	 * INTERNAL USE ONLY: Insert a custom issue ordering
	 * @param $journalId int
	 * @param $issueId int
	 * @param $seq int
	 */
	function insertCustomIssueOrder($journalId, $issueId, $seq) {
		$this->update(
			'INSERT INTO custom_issue_orders (issue_id, journal_id, seq) VALUES (?, ?, ?)',
			array(
				$issueId,
				$journalId,
				$seq
			)
		);
	}

	/**
	 * Move a custom issue ordering up or down, resequencing as necessary.
	 * @param $journalId int
	 * @param $issueId int
	 * @param $newPos int The new position (0-based) of this section
	 */
	function moveCustomIssueOrder($journalId, $issueId, $newPos) {
		$result =& $this->retrieve('SELECT issue_id FROM custom_issue_orders WHERE journal_id=? AND issue_id=?', array($journalId, $issueId));
		if (!$result->EOF) {
			$this->update(
				'UPDATE custom_issue_orders SET seq = ? WHERE journal_id = ? AND issue_id = ?',
				array($newPos, $journalId, $issueId)
			);
		} else {
			// This entry is missing. Create it.
			$this->insertCustomIssueOrder($journalId, $issueId, $newPos);
		}
		$result->Close();
		unset($result);
		$this->resequenceCustomIssueOrders($journalId);
	}

	/**
	 * Delete the public IDs of all issues of a journal.
	 * @param $journalId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deleteAllPubIds($journalId, $pubIdType) {
		$journalId = (int) $journalId;
		$settingName = 'pub-id::'.$pubIdType;

		// issues
		$issues =& $this->getIssues($journalId);
		while ($issue =& $issues->next()) {
			$this->update(
				'DELETE FROM issue_settings WHERE setting_name = ? AND issue_id = ?',
				array(
					$settingName,
					(int)$issue->getId()
				)
			);
			unset($issue);
		}
		$this->flushCache();
	}

	/**
	 * Flush the issue cache.
	 */
	function flushCache() {
		$cache =& $this->_getCache('issues');
		$cache->flush();
		unset($cache);
		$cache =& $this->_getCache('current');
		$cache->flush();
	}
}

?>
