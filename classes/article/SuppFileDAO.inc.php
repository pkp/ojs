<?php

/**
 * @file classes/article/SuppFileDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SuppFileDAO
 * @ingroup article
 * @see SuppFile
 *
 * @brief Operations for retrieving and modifying SuppFile objects.
 */

import('classes.article.SuppFile');

class SuppFileDAO extends DAO {

	/**
	 * Get supp file objects cache.
	 * @return GenericCache
	 */
	function &_getSuppFileCache() {
		if (!isset($this->suppFileCache)) {
			$cacheManager =& CacheManager::getManager();
			$this->suppFileCache =& $cacheManager->getObjectCache('suppfile', 0, array(&$this, '_suppFileCacheMiss'));
		}
		return $this->suppFileCache;
	}

	/**
	 * Callback when there is no object in cache.
	 * @param $cache GenericCache
	 * @param $id int The wanted object id.
	 * @return SuppFile
	 */
	function &_suppFileCacheMiss(&$cache, $id) {
		$suppFile =& $this->getSuppFile($id, null, false);
		$cache->setCache($id, $suppFile);
		return $suppFile;
	}

	/**
	 * Flush the supp file galley cache.
	 */
	function flushCache() {
		$cache =& $this->_getSuppFileCache();
		$cache->flush();
		unset($cache);
	}

	/**
	 * Retrieve a supplementary file by ID.
	 * @param $suppFileId int
	 * @param $articleId int optional
	 * @param $useCache boolean optional
	 * @return SuppFile
	 */
	function &getSuppFile($suppFileId, $articleId = null, $useCache = false) {
		if ($useCache) {
			$cache =& $this->_getSuppFileCache();
			$returner = $cache->get($suppFileId);
			if ($returner && $articleId != null && $articleId != $returner->getArticleId()) $returner = null;
			return $returner;
		}

		$params = array($suppFileId);
		if ($articleId) $params[] = $articleId;

		$result =& $this->retrieve(
			'SELECT s.*, a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified FROM article_supplementary_files s LEFT JOIN article_files a ON (s.file_id = a.file_id) WHERE s.supp_id = ?' . ($articleId?' AND s.article_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnSuppFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve a supplementary file by public supp file ID.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $articleId int
	 * @return SuppFile
	 */
	function &getSuppFileByPubId($pubIdType, $pubId, $articleId = null) {
		$suppFiles =& $this->getSuppFilesBySetting('pub-id::'.$pubIdType, $pubId, $articleId);
		if (empty($suppFiles)) {
			$suppFile = null;
		} else {
			assert(count($suppFiles) == 1);
			$suppFile =& $suppFiles[0];
		}

		return $suppFile;
	}

	/**
	 * Find supp files by querying supp file settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $articleId int optional
	 * @param $journalId int optional
	 * @return array The supp files identified by setting.
	 */
	function &getSuppFilesBySetting($settingName, $settingValue, $articleId = null, $journalId = null) {
		$params = array($settingName);

		$sql = 'SELECT s.*, af.file_name, af.original_file_name, af.file_type, af.file_size, af.date_uploaded, af.date_modified
			FROM	article_supplementary_files s
				LEFT JOIN article_files af ON s.file_id = af.file_id
				INNER JOIN articles a ON a.article_id = s.article_id
				LEFT JOIN published_articles pa ON s.article_id = pa.article_id ';
		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN article_supp_file_settings sfs ON s.supp_id = sfs.supp_id AND sfs.setting_name = ?
				WHERE	(sfs.setting_value IS NULL OR sfs.setting_value = \'\')';
		} else {
			$params[] = $settingValue;
			$sql .= 'INNER JOIN article_supp_file_settings sfs ON s.supp_id = sfs.supp_id
				WHERE	sfs.setting_name = ? AND sfs.setting_value = ?';
		}
		if ($articleId) {
			$params[] = (int) $articleId;
			$sql .= ' AND s.article_id = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND a.journal_id = ?';
		}
		$sql .= ' ORDER BY a.journal_id, pa.issue_id, s.supp_id';
		$result =& $this->retrieve($sql, $params);

		$suppFiles = array();
		while (!$result->EOF) {
			$suppFiles[] =& $this->_returnSuppFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();

		return $suppFiles;
	}

	/**
	 * Retrieve all supplementary files for an article.
	 * @param $articleId int
	 * @return array SuppFiles
	 */
	function &getSuppFilesByArticle($articleId) {
		$suppFiles = array();

		$result =& $this->retrieve(
			'SELECT s.*, a.file_name, a.original_file_name, a.file_type, a.file_size, a.date_uploaded, a.date_modified FROM article_supplementary_files s LEFT JOIN article_files a ON (s.file_id = a.file_id) WHERE s.article_id = ? ORDER BY s.seq',
			(int) $articleId
		);

		while (!$result->EOF) {
			$suppFiles[] =& $this->_returnSuppFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $suppFiles;
	}

	/**
	 * Retrieve all supplementary files of a journal.
	 * @param $journalId int
	 * @return DAOResultFactory
	 */
	function &getSuppFilesByJournalId($journalId) {
		$result =& $this->retrieve(
			'SELECT
				s.*,
				af.file_name, af.original_file_name, af.file_stage, af.file_type, af.file_size, af.date_uploaded, af.date_modified
			FROM article_supplementary_files s
			LEFT JOIN article_files af ON (s.file_id = af.file_id)
			INNER JOIN articles a ON (s.article_id = a.article_id)
			WHERE a.journal_id = ?',
			(int) $journalId
		);

		$returner = new DAOResultFactory($result, $this, '_returnSuppFileFromRow');
		return $returner;
	}

	/**
	 * Get the list of fields for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'creator', 'subject', 'typeOther', 'description', 'publisher', 'sponsor', 'source');
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
	 * Update the localized fields for this supp file.
	 * @param $suppFile
	 */
	function updateLocaleFields(&$suppFile) {
		$this->updateDataObjectSettings('article_supp_file_settings', $suppFile, array(
			'supp_id' => $suppFile->getId()
		));
	}

	/**
	 * Internal function to return a SuppFile object from a row.
	 * @param $row array
	 * @return SuppFile
	 */
	function &_returnSuppFileFromRow(&$row) {
		$suppFile = new SuppFile();
		$suppFile->setId($row['supp_id']);
		$suppFile->setRemoteURL($row['remote_url']);
		$suppFile->setFileId($row['file_id']);
		$suppFile->setArticleId($row['article_id']);
		$suppFile->setType($row['type']);
		$suppFile->setDateCreated($this->dateFromDB($row['date_created']));
		$suppFile->setLanguage($row['language']);
		$suppFile->setShowReviewers($row['show_reviewers']);
		$suppFile->setDateSubmitted($this->datetimeFromDB($row['date_submitted']));
		$suppFile->setSequence($row['seq']);

		//ArticleFile set methods
		$suppFile->setFileName($row['file_name']);
		$suppFile->setOriginalFileName($row['original_file_name']);
		$suppFile->setFileType($row['file_type']);
		$suppFile->setFileSize($row['file_size']);
		$suppFile->setDateModified($this->datetimeFromDB($row['date_modified']));
		$suppFile->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

		$this->getDataObjectSettings('article_supp_file_settings', 'supp_id', $row['supp_id'], $suppFile);

		HookRegistry::call('SuppFileDAO::_returnSuppFileFromRow', array(&$suppFile, &$row));

		return $suppFile;
	}

	/**
	 * Insert a new SuppFile.
	 * @param $suppFile SuppFile
	 */
	function insertSuppFile(&$suppFile) {
		if ($suppFile->getDateSubmitted() == null) {
			$suppFile->setDateSubmitted(Core::getCurrentDate());
		}
		if ($suppFile->getSequence() == null) {
			$suppFile->setSequence($this->getNextSuppFileSequence($suppFile->getArticleId()));
		}
		$this->update(
			sprintf('INSERT INTO article_supplementary_files
				(remote_url, file_id, article_id, type, date_created, language, show_reviewers, date_submitted, seq)
				VALUES
				(?, ?, ?, ?, %s, ?, ?, %s, ?)',
				$this->dateToDB($suppFile->getDateCreated()), $this->datetimeToDB($suppFile->getDateSubmitted())),
			array(
				$suppFile->getRemoteURL(),
				$suppFile->getFileId(),
				$suppFile->getArticleId(),
				$suppFile->getType(),
				$suppFile->getLanguage(),
				$suppFile->getShowReviewers(),
				$suppFile->getSequence()
			)
		);
		$suppFile->setId($this->getInsertSuppFileId());
		$this->updateLocaleFields($suppFile);
		return $suppFile->getId();
	}

	/**
	 * Update an existing SuppFile.
	 * @param $suppFile SuppFile
	 */
	function updateSuppFile(&$suppFile) {
		$returner = $this->update(
			sprintf('UPDATE article_supplementary_files
				SET
					remote_url = ?,
					file_id = ?,
					type = ?,
					date_created = %s,
					language = ?,
					show_reviewers = ?,
					seq = ?
				WHERE supp_id = ?',
				$this->dateToDB($suppFile->getDateCreated())),
			array(
				$suppFile->getRemoteURL(),
				$suppFile->getFileId(),
				$suppFile->getType(),
				$suppFile->getLanguage(),
				$suppFile->getShowReviewers(),
				$suppFile->getSequence(),
				$suppFile->getId()
			)
		);
		$this->updateLocaleFields($suppFile);
		return $returner;
	}

	/**
	 * Delete a SuppFile.
	 * @param $suppFile SuppFile
	 */
	function deleteSuppFile(&$suppFile) {
		return $this->deleteSuppFileById($suppFile->getId());
	}

	/**
	 * Delete a supplementary file by ID.
	 * @param $suppFileId int
	 * @param $articleId int optional
	 */
	function deleteSuppFileById($suppFileId, $articleId = null) {
		if (isset($articleId)) {
			$returner = $this->update('DELETE FROM article_supplementary_files WHERE supp_id = ? AND article_id = ?', array($suppFileId, $articleId));
			if ($returner) $this->update('DELETE FROM article_supp_file_settings WHERE supp_id = ?', $suppFileId);
			return $returner;

		} else {
			$this->update('DELETE FROM article_supp_file_settings WHERE supp_id = ?', $suppFileId);
			return $this->update(
				'DELETE FROM article_supplementary_files WHERE supp_id = ?', $suppFileId
			);
		}
	}

	/**
	 * Delete supplementary files by article.
	 * @param $articleId int
	 */
	function deleteSuppFilesByArticle($articleId) {
		$suppFiles =& $this->getSuppFilesByArticle($articleId);
		foreach ($suppFiles as $suppFile) {
			$this->deleteSuppFile($suppFile);
		}
	}

	/**
	 * Check if a supplementary file exists with the associated file ID.
	 * @param $articleId int
	 * @param $fileId int
	 * @return boolean
	 */
	function suppFileExistsByFileId($articleId, $fileId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM article_supplementary_files
			WHERE article_id = ? AND file_id = ?',
			array($articleId, $fileId)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Sequentially renumber supplementary files for an article in their sequence order.
	 * @param $articleId int
	 */
	function resequenceSuppFiles($articleId) {
		$result =& $this->retrieve(
			'SELECT supp_id FROM article_supplementary_files WHERE article_id = ? ORDER BY seq',
			$articleId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($suppId) = $result->fields;
			$this->update(
				'UPDATE article_supplementary_files SET seq = ? WHERE supp_id = ?',
				array($i, $suppId)
			);
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the the next sequence number for an article's supplementary files (i.e., current max + 1).
	 * @param $articleId int
	 * @return int
	 */
	function getNextSuppFileSequence($articleId) {
		$result =& $this->retrieve(
			'SELECT MAX(seq) + 1 FROM article_supplementary_files WHERE article_id = ?',
			$articleId
		);
		$returner = floor($result->fields[0]);

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted supplementary file.
	 * @return int
	 */
	function getInsertSuppFileId() {
		return $this->getInsertId('article_supplementary_files', 'supp_id');
	}

	/**
	 * Retrieve supp file by public supp file id or, failing that,
	 * internal supp file ID; public ID takes precedence.
	 * @param $suppId string
	 * @param $articleId int
	 * @return SuppFile object
	 */
	function &getSuppFileByBestSuppFileId($suppId, $articleId) {
		$suppFile =& $this->getSuppFileByPubId('publisher-id', $suppId, $articleId);
		if (!isset($suppFile) && ctype_digit("$suppId")) $suppFile =& $this->getSuppFile((int) $suppId, $articleId);
		return $suppFile;
	}

	/**
	 * Checks if public identifier exists (other than for the specified
	 * supplementary file ID, which is treated as an exception).
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $suppId int An ID to be excluded from the search.
	 * @param $journalId int
	 * @return boolean
	 */
	function pubIdExists($pubIdType, $pubId, $suppId, $journalId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM article_supp_file_settings sfs
				INNER JOIN article_supplementary_files f ON sfs.supp_id = f.supp_id
				INNER JOIN articles a ON f.article_id = a.article_id
			WHERE sfs.setting_name = ? AND sfs.setting_value = ? AND f.supp_id <> ? AND a.journal_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $suppId,
				(int) $journalId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * Change the public ID of a supplementary file.
	 * @param $suppFileId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function changePubId($suppFileId, $pubIdType, $pubId) {
		$idFields = array(
			'supp_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'supp_id' => $suppFileId,
			'locale' => '',
			'setting_name' => 'pub-id::'.$pubIdType,
			'setting_type' => 'string',
			'setting_value' => (string)$pubId
		);
		$this->replace('article_supp_file_settings', $updateArray, $idFields);
	}


	/**
	 * Delete the public IDs of all supplementary files in a journal.
	 * @param $journalId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deleteAllPubIds($journalId, $pubIdType) {
		$journalId = (int) $journalId;
		$settingName = 'pub-id::'.$pubIdType;

		$suppFiles =& $this->getSuppFilesByJournalId($journalId);
		while ($suppFile =& $suppFiles->next()) {
			$this->update(
				'DELETE FROM article_supp_file_settings WHERE setting_name = ? AND supp_id = ?',
				array(
					$settingName,
					(int)$suppFile->getId()
				)
			);
			unset($suppFile);
		}
		$this->flushCache();
	}

	/**
	 * Delete the public ID of a supp file.
	 * @param $suppFileId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deletePubId($suppFileId, $pubIdType) {
		$settingName = 'pub-id::'.$pubIdType;
		$this->update(
			'DELETE FROM article_supp_file_settings WHERE setting_name = ? AND supp_id = ?',
			array(
				$settingName,
				(int)$suppFileId
			)
		);
		$this->flushCache();
	}
}

?>
