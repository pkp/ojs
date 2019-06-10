<?php

/**
 * @file classes/article/ArticleGalleyDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyDAO
 * @ingroup article
 * @see ArticleGalley
 *
 * @brief Operations for retrieving and modifying ArticleGalley objects.
 */

import('classes.article.ArticleGalley');
import('lib.pkp.classes.submission.RepresentationDAO');
import('lib.pkp.classes.plugins.PKPPubIdPluginDAO');

class ArticleGalleyDAO extends RepresentationDAO implements PKPPubIdPluginDAO {

	/**
	 * Return a new data object.
	 * @return ArticleGalley
	 */
	function newDataObject() {
		return new ArticleGalley();
	}

	/**
	 * @copydoc RepresentationDAO::getById()
	 */
	function getById($galleyId, $submissionId = null, $contextId = null) {
		$params = array((int) $galleyId);
		if ($submissionId) $params[] = (int) $submissionId;
		if ($contextId) $params[] = (int) $contextId;

		$result = $this->retrieve(
			'SELECT	sf.*, g.*
			FROM	submission_galleys g
				' . ($contextId?' JOIN submissions s ON (s.submission_id = g.submission_id)':'') . '
				LEFT JOIN submission_files sf ON (g.file_id = sf.file_id)
				LEFT JOIN submission_files nsf ON (nsf.file_id = g.file_id AND nsf.revision > sf.revision)
			WHERE	g.galley_id = ?
				AND nsf.file_id IS NULL ' .
				($submissionId !== null?' AND g.submission_id = ?':'') .
				($contextId?' AND s.context_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = $this->_fromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		HookRegistry::call('ArticleGalleyDAO::getById', array(&$galleyId, &$submissionId, &$returner));
		return $returner;
	}

	/**
	 * Retrieve a galley by ID.
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $articleId int
	 * @return ArticleGalley
	 */
	function getGalleyByPubId($pubIdType, $pubId, $articleId = null) {
		$galleyFactory = $this->getGalleysBySetting('pub-id::'.$pubIdType, $pubId, $articleId);
		if ($galleyFactory->wasEmpty()) return null;

		assert($galleyFactory->getCount() == 1);
		return $galleyFactory->next();
	}

	/**
	 * Find galleys by querying galley settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $articleId int optional
	 * @param $journalId int optional
	 * @return DAOResultFactory The factory for galleys identified by setting.
	 */
	function getGalleysBySetting($settingName, $settingValue, $articleId = null, $journalId = null) {
		$params = array($settingName);

		$sql = 'SELECT	g.*
			FROM	submission_galleys g
				INNER JOIN submissions a ON a.submission_id = g.submission_id
				LEFT JOIN published_submissions pa ON g.submission_id = pa.submission_id ';
		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN submission_galley_settings gs ON g.galley_id = gs.galley_id AND gs.setting_name = ?
				WHERE	(gs.setting_value IS NULL OR gs.setting_value = \'\')';
		} else {
			$params[] = (string) $settingValue;
			$sql .= 'INNER JOIN submission_galley_settings gs ON g.galley_id = gs.galley_id
				WHERE	gs.setting_name = ? AND gs.setting_value = ? AND g.is_current_submission_version = 1';
		}
		$sql .= ' AND pa.is_current_submission_version = 1';
		if ($articleId) {
			$params[] = (int) $articleId;
			$sql .= ' AND g.submission_id = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND a.context_id = ?';
		}
		$sql .= ' ORDER BY a.context_id, pa.issue_id, g.galley_id';
		$result = $this->retrieve($sql, $params);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * @copydoc RepresentationDAO::getBySubmissionId()
	 */
	function getBySubmissionId($submissionId, $contextId = null, $submissionVersion = null) {
		$params = array((int) $submissionId);
		if ($contextId) $params[] = (int) $contextId;
		if ($submissionVersion) $params[] = (int) $submissionVersion;

		return new DAOResultFactory(
			$this->retrieve(
				'SELECT sf.*, g.*
				FROM submission_galleys g
				' . ($contextId?'INNER JOIN submissions s ON (g.submission_id = s.submission_id) ':'') . '
				LEFT JOIN submission_files sf ON (g.file_id = sf.file_id)
				LEFT JOIN submission_files nsf ON (nsf.file_id = g.file_id AND nsf.revision > sf.revision)
				WHERE g.submission_id = ?
					AND nsf.file_id IS NULL' .
					 ($contextId?' AND s.context_id = ? ':'') .
					 ($submissionVersion?' AND g.submission_version = ? ':' AND g.is_current_submission_version = 1 ') .
				'ORDER BY g.seq',
				$params
			),
			$this, '_fromRow'
		);
	}

	/**
	 * Retrieve all galleys of a journal.
	 * @param $journalId int
	 * @return DAOResultFactory
	 */
	function getByContextId($journalId) {
		$result = $this->retrieve(
			'SELECT	sf.*, g.*
			FROM	submission_galleys g
				INNER JOIN submissions a ON (g.submission_id = a.submission_id)
				LEFT JOIN submission_files sf ON (g.file_id = sf.file_id)
				LEFT JOIN submission_files nsf ON (nsf.file_id = g.file_id AND nsf.revision > sf.revision)
			WHERE	a.context_id = ? AND g.is_current_submission_version = 1
				AND nsf.file_id IS NULL',
			(int) $journalId
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve article galley by public galley id or, failing that,
	 * internal galley ID; public galley ID takes precedence.
	 * @param $galleyId string
	 * @param $articleId int
	 * @return ArticleGalley object
	 */
	function getByBestGalleyId($galleyId, $articleId) {
		$galley = null;
		if ($galleyId != '') $galley = $this->getGalleyByPubId('publisher-id', $galleyId, $articleId);
		if (!isset($galley) && ctype_digit("$galleyId")) $galley = $this->getById((int) $galleyId, $articleId);
		return $galley;
	}

	/**
	 * Get the list of fields for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array();
	}

	/**
	 * Get a list of additional fields that do not have
	 * dedicated accessors.
	 * @return array
	 */
	function getAdditionalFieldNames() {
		$additionalFields = parent::getAdditionalFieldNames();
		$additionalFields[] = 'pub-id::publisher-id';
		return $additionalFields;
	}

	/**
	 * Update the localized fields for this galley.
	 * @param $galley
	 */
	function updateLocaleFields($galley) {
		$this->updateDataObjectSettings('submission_galley_settings', $galley, array(
			'galley_id' => $galley->getId()
		));
	}

	/**
	 * Internal function to return an ArticleGalley object from a row.
	 * @param $row array
	 * @return ArticleGalley
	 */
	function _fromRow($row) {
		$galley = $this->newDataObject();

		$galley->setId($row['galley_id']);
		$galley->setSubmissionId($row['submission_id']);
		$galley->setLocale($row['locale']);
		$galley->setLabel($row['label']);
		$galley->setSequence($row['seq']);
		$galley->setRemoteURL($row['remote_url']);
		$galley->setFileId($row['file_id']);
		$galley->setSubmissionVersion($row['submission_version']);
		$galley->setPrevVerAssocId($row['prev_ver_id']);
		$galley->setIsCurrentSubmissionVersion($row['is_current_submission_version']);

		$this->getDataObjectSettings('submission_galley_settings', 'galley_id', $row['galley_id'], $galley);

		HookRegistry::call('ArticleGalleyDAO::_fromRow', array(&$galley, &$row));

		return $galley;
	}

	/**
	 * Insert a new ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function insertObject($galley) {
		$this->update(
			'INSERT INTO submission_galleys
				(submission_id, label, locale, seq, remote_url, file_id, submission_version, prev_ver_id, is_current_submission_version)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $galley->getSubmissionId(),
				$galley->getLabel(),
				$galley->getLocale(),
				$galley->getSequence() == null ? $this->getNextGalleySequence($galley->getSubmissionId()) : $galley->getSequence(),
				$galley->getRemoteURL(),
				$galley->getFileId(),
				(int) $galley->getSubmissionVersion(),
				(int) $galley->getPrevVerAssocId(),
				(int) $galley->getIsCurrentSubmissionVersion(),
			)
		);
		$galley->setId($this->getInsertId());
		$this->updateLocaleFields($galley);

		HookRegistry::call('ArticleGalleyDAO::insertNewGalley', array(&$galley, $galley->getId()));

		return $galley->getId();
	}

	/**
	 * Update an existing ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function updateObject($galley) {
		$this->update(
			'UPDATE submission_galleys
				SET
					locale = ?,
					label = ?,
					seq = ?,
					remote_url = ?,
					file_id = ?,
					submission_version = ?,
					prev_ver_id = ?,
					is_current_submission_version = ?
				WHERE galley_id = ?',
			array(
				$galley->getLocale(),
				$galley->getLabel(),
				(float) $galley->getSequence(),
				$galley->getRemoteURL(),
				(int) $galley->getFileId(),
				(int) $galley->getSubmissionVersion(),
				(int) $galley->getPrevVerAssocId(),
				(int) $galley->getIsCurrentSubmissionVersion(),
				(int) $galley->getId(),
			)
		);
		$this->updateLocaleFields($galley);
	}

	/**
	 * Delete an ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function deleteObject($galley) {
		return $this->deleteById($galley->getId());
	}

	/**
	 * Delete a galley by ID.
	 * @param $galleyId int Galley ID.
	 * @param $articleId int Optional article ID.
	 */
	function deleteById($galleyId, $articleId = null) {

		HookRegistry::call('ArticleGalleyDAO::deleteById', array(&$galleyId, &$articleId));

		$params = array((int) $galleyId);
		if ($articleId) $params[] = (int) $articleId;
		$this->update(
			'DELETE FROM submission_galleys
			WHERE galley_id = ?'
			. ($articleId?' AND submission_id = ?':''),
			$params
		);
		if ($this->getAffectedRows()) {
			$this->update('DELETE FROM submission_galley_settings WHERE galley_id = ?', array((int) $galleyId));
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			import('lib.pkp.classes.submission.SubmissionFile'); // Import constants

			$galleyFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_GALLEY, $galleyId, $articleId, SUBMISSION_FILE_PROOF);
			foreach ($galleyFiles as $file) {
				// delete dependent files for each galley file
				$submissionFileDao->deleteAllRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $file->getFileId(), SUBMISSION_FILE_DEPENDENT);
			}
			// delete the galley files.
			$submissionFileDao->deleteAllRevisionsByAssocId(ASSOC_TYPE_GALLEY, $galleyId, SUBMISSION_FILE_PROOF);
		}
	}

	/**
	 * Delete galleys (and dependent galley image entries) by article.
	 * NOTE that this will not delete article_file entities or the respective files.
	 * @param $articleId int
	 */
	function deleteByArticleId($articleId) {
		$galleys = $this->getBySubmissionId($articleId);
		while ($galley = $galleys->next()) {
			$this->deleteById($galley->getId(), $articleId);
		}
	}

	/**
	 * Sequentially renumber galleys for an article in their sequence order.
	 * @param $articleId int
	 */
	function resequenceGalleys($articleId) {
		$result = $this->retrieve(
			'SELECT galley_id FROM submission_galleys WHERE submission_id = ? ORDER BY seq',
			(int) $articleId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($galleyId) = $result->fields;
			$this->update(
				'UPDATE submission_galleys SET seq = ? WHERE galley_id = ?',
				array($i, $galleyId)
			);
			$result->MoveNext();
		}
		$result->Close();
	}

	/**
	 * Get the the next sequence number for an article's galleys (i.e., current max + 1).
	 * @param $articleId int
	 * @return int
	 */
	function getNextGalleySequence($articleId) {
		$result = $this->retrieve(
			'SELECT MAX(seq) + 1 FROM submission_galleys WHERE submission_id = ?',
			(int) $articleId
		);
		$returner = floor($result->fields[0]);

		$result->Close();
		return $returner;
	}

	/**
	 * Get the ID of the last inserted gallery.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('submission_galleys', 'galley_id');
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::pubIdExists()
	 */
	function pubIdExists($pubIdType, $pubId, $excludePubObjectId, $contextId) {
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM submission_galley_settings sgs
				INNER JOIN submission_galleys sg ON sgs.galley_id = sg.galley_id
				INNER JOIN submissions s ON sg.submission_id = s.submission_id
			WHERE sgs.setting_name = ? AND sgs.setting_value = ? AND sgs.galley_id <> ? AND s.context_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $excludePubObjectId,
				(int) $contextId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
		return $returner;
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::changePubId()
	 */
	function changePubId($pubObjectId, $pubIdType, $pubId) {
		$idFields = array(
			'galley_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'galley_id' => (int) $pubObjectId,
			'locale' => '',
			'setting_name' => 'pub-id::'.$pubIdType,
			'setting_type' => 'string',
			'setting_value' => (string)$pubId
		);
		$this->replace('submission_galley_settings', $updateArray, $idFields);
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::deletePubId()
	 */
	function deletePubId($pubObjectId, $pubIdType) {
		$settingName = 'pub-id::'.$pubIdType;
		$this->update(
			'DELETE FROM submission_galley_settings WHERE setting_name = ? AND galley_id = ?',
			array(
				$settingName,
				(int)$pubObjectId
			)
		);
		$this->flushCache();
	}

	/**
	 * @copydoc PKPPubIdPluginDAO::deleteAllPubIds()
	 */
	function deleteAllPubIds($contextId, $pubIdType) {
		$settingName = 'pub-id::'.$pubIdType;

		$galleys = $this->getByContextId($contextId);
		while ($galley = $galleys->next()) {
			$this->update(
				'DELETE FROM submission_galley_settings WHERE setting_name = ? AND galley_id = ?',
				array(
					$settingName,
					(int)$galley->getId()
				)
			);
		}
		$this->flushCache();
	}

	/**
	 * Get all published submission galleys (eventually with a pubId assigned and) matching the specified settings.
	 * @param $contextId integer optional
	 * @param $pubIdType string
	 * @param $title string optional
	 * @param $author string optional
	 * @param $issueId integer optional
	 * @param $pubIdSettingName string optional
	 * (e.g. medra::status or medra::registeredDoi)
	 * @param $pubIdSettingValue string optional
	 * @param $rangeInfo DBResultRange optional
	 * @return DAOResultFactory
	 */
	function getExportable($contextId, $pubIdType = null, $title = null, $author = null, $issueId = null, $pubIdSettingName = null, $pubIdSettingValue = null, $rangeInfo = null) {
		$params = array();
		if ($pubIdSettingName) {
			$params[] = $pubIdSettingName;
		}
		$params[] = (int) $contextId;
		if ($pubIdType) {
			$params[] = 'pub-id::'.$pubIdType;
		}
		if ($title) {
			$params[] = 'title';
			$params[] = '%' . $title . '%';
		}
		if ($author) array_push($params, $authorQuery = '%' . $author . '%', $authorQuery);
		if ($issueId) {
			$params[] = (int) $issueId;
		}
		import('classes.plugins.PubObjectsExportPlugin');
		if ($pubIdSettingName && $pubIdSettingValue && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED) {
			$params[] = $pubIdSettingValue;
		}

		import('classes.article.Article'); // STATUS_DECLINED constant
		$result = $this->retrieveRange(
				'SELECT	sf.*, g.*
			FROM	submission_galleys g
				JOIN submissions s ON (s.submission_id = g.submission_id AND s.status <> ' . STATUS_DECLINED .')
				LEFT JOIN published_submissions ps ON (ps.submission_id = g.submission_id) and (ps.published_submission_version = s.submission_version) and ps.is_current_submission_version = 1
				JOIN issues i ON (ps.issue_id = i.issue_id)
				LEFT JOIN submission_files sf ON (g.file_id = sf.file_id)
				LEFT JOIN submission_files nsf ON (nsf.file_id = g.file_id AND nsf.revision > sf.revision AND nsf.file_id IS NULL )
				' . ($pubIdType != null?' LEFT JOIN submission_galley_settings gs ON (g.galley_id = gs.galley_id)':'')
				. ($title != null?' LEFT JOIN submission_settings sst ON (s.submission_id = sst.submission_id)':'')
				. ($author != null?' LEFT JOIN authors au ON (s.submission_id = au.submission_id)
						LEFT JOIN author_settings asgs ON (asgs.author_id = au.author_id AND asgs.setting_name = \''.IDENTITY_SETTING_GIVENNAME.'\')
						LEFT JOIN author_settings asfs ON (asfs.author_id = au.author_id AND asfs.setting_name = \''.IDENTITY_SETTING_FAMILYNAME.'\')
					':'')
				. ($pubIdSettingName != null?' LEFT JOIN submission_galley_settings gss ON (g.galley_id = gss.galley_id AND gss.setting_name = ?)':'') .'
			WHERE
				i.published = 1 AND s.context_id = ? AND g.is_current_submission_version = 1 
				' . ($pubIdType != null?' AND gs.setting_name = ? AND gs.setting_value IS NOT NULL':'')
				. ($title != null?' AND (sst.setting_name = ? AND sst.setting_value LIKE ?)':'')
				. ($author != null?' AND (asgs.setting_value LIKE ? OR asfs.setting_value LIKE ?)':'')
				. ($issueId != null?' AND ps.issue_id = ?':'')
				. (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue == EXPORT_STATUS_NOT_DEPOSITED)?' AND gss.setting_value IS NULL':'')
				. (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED)?' AND gss.setting_value = ?':'')
				. (($pubIdSettingName != null && is_null($pubIdSettingValue))?' AND (gss.setting_value IS NULL OR gss.setting_value = \'\')':'') .'
				ORDER BY ps.date_published DESC, s.submission_id DESC, g.galley_id DESC',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	function newVersion($submissionId) {
		parent::newVersion($submissionId);
	}
}


