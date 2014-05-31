<?php

/**
 * @file classes/article/ArticleGalleyDAO.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyDAO
 * @ingroup article
 * @see ArticleGalley
 *
 * @brief Operations for retrieving and modifying ArticleGalley/ArticleHTMLGalley objects.
 */

import('classes.article.ArticleGalley');
import('classes.article.ArticleHTMLGalley');

class ArticleGalleyDAO extends DAO {
	/** Helper file DAOs. */
	var $articleFileDao;

	/**
	 * Constructor.
	 */
	function ArticleGalleyDAO() {
		parent::DAO();
		$this->articleFileDao =& DAORegistry::getDAO('ArticleFileDAO');
	}

	/**
	 * Get galley objects cache.
	 * @return GenericCache
	 */
	function &_getGalleyCache() {
		if (!isset($this->galleyCache)) {
			$cacheManager =& CacheManager::getManager();
			$this->galleyCache =& $cacheManager->getObjectCache('galley', 0, array(&$this, '_galleyCacheMiss'));
		}
		return $this->galleyCache;
	}

	/**
	 * Callback when there is no object in cache.
	 * @param $cache GenericCache
	 * @param $id int The wanted object id.
	 * @return ArticleGalley
	 */
	function &_galleyCacheMiss(&$cache, $id) {
		$galley =& $this->getGalley($id, null, false);
		$cache->setCache($id, $galley);
		return $galley;
	}

	/**
	 * Retrieve a galley by ID.
	 * @param $galleyId int
	 * @param $articleId int optional
	 * @param $useCache boolean optional
	 * @return ArticleGalley
	 */
	function &getGalley($galleyId, $articleId = null, $useCache = false) {
		if ($useCache) {
			$cache =& $this->_getGalleyCache();
			$returner = $cache->get($galleyId);
			if ($returner && $articleId != null && $articleId != $returner->getArticleId()) $returner = null;
			return $returner;
		}

		$params = array((int) $galleyId);
		if ($articleId !== null) $params[] = (int) $articleId;
		$result =& $this->retrieve(
			'SELECT	g.*,
				a.file_name, a.original_file_name, a.file_stage, a.file_type, a.file_size, a.date_uploaded, a.date_modified
			FROM	article_galleys g
				LEFT JOIN article_files a ON (g.file_id = a.file_id)
			WHERE	g.galley_id = ?' .
			($articleId !== null?' AND g.article_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
		} else {
			HookRegistry::call('ArticleGalleyDAO::getNewGalley', array(&$galleyId, &$articleId, &$returner));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Checks if public identifier exists (other than for the specified
	 * galley ID, which is treated as an exception).
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $galleyId int An ID to be excluded from the search.
	 * @param $journalId int
	 * @return boolean
	 */
	function pubIdExists($pubIdType, $pubId, $galleyId, $journalId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*)
			FROM article_galley_settings ags
				INNER JOIN article_galleys ag ON ags.galley_id = ag.galley_id
				INNER JOIN articles a ON ag.article_id = a.article_id
			WHERE ags.setting_name = ? AND ags.setting_value = ? AND ags.galley_id <> ? AND a.journal_id = ?',
			array(
				'pub-id::'.$pubIdType,
				$pubId,
				(int) $galleyId,
				(int) $journalId
			)
		);
		$returner = $result->fields[0] ? true : false;
		$result->Close();
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
	function &getGalleyByPubId($pubIdType, $pubId, $articleId = null) {
		$galleys =& $this->getGalleysBySetting('pub-id::'.$pubIdType, $pubId, $articleId);
		if (empty($galleys)) {
			$galley = null;
		} else {
			assert(count($galleys) == 1);
			$galley =& $galleys[0];
		}

		return $galley;
	}

	/**
	 * Find galleys by querying galley settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $articleId int optional
	 * @param $journalId int optional
	 * @return array The galleys identified by setting.
	 */
	function &getGalleysBySetting($settingName, $settingValue, $articleId = null, $journalId = null) {
		$params = array($settingName);

		$sql = 'SELECT	g.*,
				af.file_name, af.original_file_name, af.file_stage, af.file_type, af.file_size, af.date_uploaded, af.date_modified
			FROM	article_galleys g
				LEFT JOIN article_files af ON (g.file_id = af.file_id)
				INNER JOIN articles a ON a.article_id = g.article_id
				LEFT JOIN published_articles pa ON g.article_id = pa.article_id ';
		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN article_galley_settings gs ON g.galley_id = gs.galley_id AND gs.setting_name = ?
				WHERE	(gs.setting_value IS NULL OR gs.setting_value = "")';
		} else {
			$params[] = $settingValue;
			$sql .= 'INNER JOIN article_galley_settings gs ON g.galley_id = gs.galley_id
				WHERE	gs.setting_name = ? AND gs.setting_value = ?';
		}
		if ($articleId) {
			$params[] = (int) $articleId;
			$sql .= ' AND g.article_id = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND a.journal_id = ?';
		}
		$sql .= ' ORDER BY a.journal_id, pa.issue_id, g.galley_id';
		$result =& $this->retrieve($sql, $params);

		$galleys = array();
		while (!$result->EOF) {
			$galleys[] =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();

		return $galleys;
	}

	/**
	 * Retrieve all galleys for an article.
	 * @param $articleId int
	 * @return array ArticleGalleys
	 */
	function &getGalleysByArticle($articleId) {
		$galleys = array();

		$result =& $this->retrieve(
			'SELECT g.*,
			a.file_name, a.original_file_name, a.file_stage, a.file_type, a.file_size, a.date_uploaded, a.date_modified
			FROM article_galleys g
			LEFT JOIN article_files a ON (g.file_id = a.file_id)
			WHERE g.article_id = ? ORDER BY g.seq',
			(int) $articleId
		);

		while (!$result->EOF) {
			$galleys[] =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		HookRegistry::call('ArticleGalleyDAO::getArticleGalleys', array(&$galleys, &$articleId)); // FIXME: XMLGalleyPlugin uses this; should convert to DAO auto call

		return $galleys;
	}

	/**
	 * Retrieve all galleys of a journal.
	 * @param $journalId int
	 * @return DAOResultFactory
	 */
	function &getGalleysByJournalId($journalId) {
		$result =& $this->retrieve(
			'SELECT
				g.*,
				af.file_name, af.original_file_name, af.file_stage, af.file_type, af.file_size, af.date_uploaded, af.date_modified
			FROM article_galleys g
			LEFT JOIN article_files af ON (g.file_id = af.file_id)
			INNER JOIN articles a ON (g.article_id = a.article_id)
			WHERE a.journal_id = ?',
			(int) $journalId
		);

		$returner = new DAOResultFactory($result, $this, '_returnGalleyFromRow');
		return $returner;
	}

	/**
	 * Retrieve article galley by public galley id or, failing that,
	 * internal galley ID; public galley ID takes precedence.
	 * @param $galleyId string
	 * @param $articleId int
	 * @return galley object
	 */
	function &getGalleyByBestGalleyId($galleyId, $articleId) {
		if ($galleyId != '') $galley =& $this->getGalleyByPubId('publisher-id', $galleyId, $articleId);
		if (!isset($galley) && ctype_digit("$galleyId")) $galley =& $this->getGalley((int) $galleyId, $articleId);
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
		// FIXME: Move this to a PID plug-in.
		$additionalFields[] = 'pub-id::publisher-id';
		return $additionalFields;
	}

	/**
	 * Update the localized fields for this galley.
	 * @param $galley
	 */
	function updateLocaleFields(&$galley) {
		$this->updateDataObjectSettings('article_galley_settings', $galley, array(
			'galley_id' => $galley->getId()
		));
	}

	/**
	 * Internal function to return an ArticleGalley object from a row.
	 * @param $row array
	 * @return ArticleGalley
	 */
	function &_returnGalleyFromRow(&$row) {
		if ($row['html_galley']) {
			$galley = new ArticleHTMLGalley();

			// HTML-specific settings
			$galley->setStyleFileId($row['style_file_id']);
			if ($row['style_file_id']) {
				$galley->setStyleFile($this->articleFileDao->getArticleFile($row['style_file_id']));
			}

			// Retrieve images
			$images =& $this->getGalleyImages($row['galley_id']);
			$galley->setImageFiles($images);

		} else {
			$galley = new ArticleGalley();
		}
		$galley->setId($row['galley_id']);
		$galley->setArticleId($row['article_id']);
		$galley->setLocale($row['locale']);
		$galley->setFileId($row['file_id']);
		$galley->setLabel($row['label']);
		$galley->setFileStage($row['file_stage']);
		$galley->setSequence($row['seq']);
		$galley->setRemoteURL($row['remote_url']);

		// ArticleFile set methods
		$galley->setFileName($row['file_name']);
		$galley->setOriginalFileName($row['original_file_name']);
		$galley->setFileType($row['file_type']);
		$galley->setFileSize($row['file_size']);
		$galley->setDateModified($this->datetimeFromDB($row['date_modified']));
		$galley->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));

		$this->getDataObjectSettings('article_galley_settings', 'galley_id', $row['galley_id'], $galley);

		HookRegistry::call('ArticleGalleyDAO::_returnGalleyFromRow', array(&$galley, &$row));

		return $galley;
	}

	/**
	 * Insert a new ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function insertGalley(&$galley) {
		$this->update(
			'INSERT INTO article_galleys
				(article_id, file_id, label, locale, html_galley, style_file_id, seq, remote_url)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $galley->getArticleId(),
				(int) $galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				(int) $galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? (int) $galley->getStyleFileId() : null,
				$galley->getSequence() == null ? $this->getNextGalleySequence($galley->getArticleId()) : $galley->getSequence(),
				$galley->getRemoteURL()
			)
		);
		$galley->setId($this->getInsertGalleyId());
		$this->updateLocaleFields($galley);

		HookRegistry::call('ArticleGalleyDAO::insertNewGalley', array(&$galley, $galley->getId()));

		return $galley->getId();
	}

	/**
	 * Update an existing ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function updateGalley(&$galley) {
		$this->update(
			'UPDATE article_galleys
				SET
					file_id = ?,
					label = ?,
					locale = ?,
					html_galley = ?,
					style_file_id = ?,
					seq = ?,
					remote_url = ?
				WHERE galley_id = ?',
			array(
				(int) $galley->getFileId(),
				$galley->getLabel(),
				$galley->getLocale(),
				(int) $galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? (int) $galley->getStyleFileId() : null,
				$galley->getSequence(),
				$galley->getRemoteURL(),
				(int) $galley->getId()
			)
		);
		$this->updateLocaleFields($galley);
	}

	/**
	 * Delete an ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function deleteGalley(&$galley) {
		return $this->deleteGalleyById($galley->getId());
	}

	/**
	 * Delete a galley by ID.
	 * @param $galleyId int
	 * @param $articleId int optional
	 */
	function deleteGalleyById($galleyId, $articleId = null) {

		HookRegistry::call('ArticleGalleyDAO::deleteGalleyById', array(&$galleyId, &$articleId));

		if (isset($articleId)) {
			$this->update(
				'DELETE FROM article_galleys WHERE galley_id = ? AND article_id = ?',
				array((int) $galleyId, (int) $articleId)
			);
		} else {
			$this->update(
				'DELETE FROM article_galleys WHERE galley_id = ?', (int) $galleyId
			);
		}
		if ($this->getAffectedRows()) {
			$this->update('DELETE FROM article_galley_settings WHERE galley_id = ?', array((int) $galleyId));
			$this->deleteImagesByGalley($galleyId);
		}
	}

	/**
	 * Delete galleys (and dependent galley image entries) by article.
	 * NOTE that this will not delete article_file entities or the respective files.
	 * @param $articleId int
	 */
	function deleteGalleysByArticle($articleId) {
		$galleys =& $this->getGalleysByArticle($articleId);
		foreach ($galleys as $galley) {
			$this->deleteGalleyById($galley->getId(), $articleId);
		}
	}

	/**
	 * Check if a galley exists with the associated file ID.
	 * @param $articleId int
	 * @param $fileId int
	 * @return boolean
	 */
	function galleyExistsByFileId($articleId, $fileId) {
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM article_galleys
			WHERE article_id = ? AND file_id = ?',
			array((int) $articleId, (int) $fileId)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Increment the views count for a galley.
	 * @param $galleyId int
	 */
	function incrementViews($galleyId) {
		if ( !HookRegistry::call('ArticleGalleyDAO::incrementGalleyViews', array(&$galleyId)) ) {
			return $this->update(
				'UPDATE article_galleys SET views = views + 1 WHERE galley_id = ?',
				(int) $galleyId
			);
		} else return false;
	}

	/**
	 * Sequentially renumber galleys for an article in their sequence order.
	 * @param $articleId int
	 */
	function resequenceGalleys($articleId) {
		$result =& $this->retrieve(
			'SELECT galley_id FROM article_galleys WHERE article_id = ? ORDER BY seq',
			(int) $articleId
		);

		for ($i=1; !$result->EOF; $i++) {
			list($galleyId) = $result->fields;
			$this->update(
				'UPDATE article_galleys SET seq = ? WHERE galley_id = ?',
				array($i, $galleyId)
			);
			$result->moveNext();
		}

		$result->close();
		unset($result);
	}

	/**
	 * Get the the next sequence number for an article's galleys (i.e., current max + 1).
	 * @param $articleId int
	 * @return int
	 */
	function getNextGalleySequence($articleId) {
		$result =& $this->retrieve(
			'SELECT MAX(seq) + 1 FROM article_galleys WHERE article_id = ?',
			(int) $articleId
		);
		$returner = floor($result->fields[0]);

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Get the ID of the last inserted gallery.
	 * @return int
	 */
	function getInsertGalleyId() {
		return $this->getInsertId('article_galleys', 'galley_id');
	}


	//
	// Extra routines specific to HTML galleys.
	//

	/**
	 * Retrieve array of the images for an HTML galley.
	 * @param $galleyId int
	 * @return array ArticleFile
	 */
	function &getGalleyImages($galleyId) {
		$images = array();

		$result =& $this->retrieve(
			'SELECT a.* FROM article_html_galley_images i, article_files a
			WHERE i.file_id = a.file_id AND i.galley_id = ?',
			(int) $galleyId
		);

		while (!$result->EOF) {
			$images[] =& $this->articleFileDao->_returnArticleFileFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $images;
	}

	/**
	 * Attach an image to an HTML galley.
	 * @param $galleyId int
	 * @param $fileId int
	 */
	function insertGalleyImage($galleyId, $fileId) {
		return $this->update(
			'INSERT INTO article_html_galley_images
			(galley_id, file_id)
			VALUES
			(?, ?)',
			array((int) $galleyId, (int) $fileId)
		);
	}

	/**
	 * Delete an image from an HTML galley.
	 * @param $galleyId int
	 * @param $fileId int
	 */
	function deleteGalleyImage($galleyId, $fileId) {
		return $this->update(
			'DELETE FROM article_html_galley_images
			WHERE galley_id = ? AND file_id = ?',
			array((int) $galleyId, (int) $fileId)
		);
	}

	/**
	 * Delete HTML galley images by galley.
	 * @param $galleyId int
	 */
	function deleteImagesByGalley($galleyId) {
		return $this->update(
			'DELETE FROM article_html_galley_images WHERE galley_id = ?',
			(int) $galleyId
		);
	}

	/**
	 * Change the public ID of a galley.
	 * @param $galleyId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 */
	function changePubId($galleyId, $pubIdType, $pubId) {
		$idFields = array(
			'galley_id', 'locale', 'setting_name'
		);
		$updateArray = array(
			'galley_id' => $galleyId,
			'locale' => '',
			'setting_name' => 'pub-id::'.$pubIdType,
			'setting_type' => 'string',
			'setting_value' => (string)$pubId
		);
		$this->replace('article_galley_settings', $updateArray, $idFields);
	}

	/**
	 * Delete the public IDs of all galleys in a journal.
	 * @param $journalId int
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 */
	function deleteAllPubIds($journalId, $pubIdType) {
		$journalId = (int) $journalId;
		$settingName = 'pub-id::'.$pubIdType;

		$galleys =& $this->getGalleysByJournalId($journalId);
		while ($galley =& $galleys->next()) {
			$this->update(
				'DELETE FROM article_galley_settings WHERE setting_name = ? AND galley_id = ?',
				array(
					$settingName,
					(int)$galley->getId()
				)
			);
			unset($galley);
		}
		$this->flushCache();
	}
}

?>
