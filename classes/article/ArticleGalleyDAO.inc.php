<?php

/**
 * @file classes/article/ArticleGalleyDAO.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
		$this->articleFileDao = DAORegistry::getDAO('ArticleFileDAO');
	}

	/**
	 * Return a new data object.
	 * @return ArticleGalley
	 */
	function newDataObject() {
		return new ArticleGalley();
	}

	/**
	 * Retrieve a galley by ID.
	 * @param $galleyId int
	 * @param $articleId int optional
	 * @return ArticleGalley
	 */
	function &getGalley($galleyId, $articleId = null) {
		$params = array((int) $galleyId);
		if ($articleId !== null) $params[] = (int) $articleId;
		$result = $this->retrieve(
			'SELECT	g.*
			FROM	submission_galleys g
			WHERE	g.galley_id = ?' .
			($articleId !== null?' AND g.submission_id = ?':''),
			$params
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner =& $this->_returnGalleyFromRow($result->GetRowAssoc(false));
		} else {
			HookRegistry::call('ArticleGalleyDAO::getNewGalley', array(&$galleyId, &$articleId, &$returner));
		}

		$result->Close();
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
		$result = $this->retrieve(
			'SELECT COUNT(*)
			FROM article_galley_settings ags
				INNER JOIN submission_galleys ag ON ags.galley_id = ag.galley_id
				INNER JOIN submissions a ON ag.submission_id = a.submission_id
			WHERE ags.setting_name = ? AND ags.setting_value = ? AND ags.galley_id <> ? AND a.context_id = ?',
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
		$galleyFactory =& $this->getGalleysBySetting('pub-id::'.$pubIdType, $pubId, $articleId);
		if ($galleyFactory->wasEmpty()) {
			$galley = null;
		} else {
			assert($galleyFactory->getCount() == 1);
			$galley =& $galleyFactory->next();
		}

		return $galley;
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
			$sql .= 'LEFT JOIN article_galley_settings gs ON g.galley_id = gs.galley_id AND gs.setting_name = ?
				WHERE	(gs.setting_value IS NULL OR gs.setting_value = "")';
		} else {
			$params[] = $settingValue;
			$sql .= 'INNER JOIN article_galley_settings gs ON g.galley_id = gs.galley_id
				WHERE	gs.setting_name = ? AND gs.setting_value = ?';
		}
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

		return new DAOResultFactory($result, $this, '_returnGalleyFromRow');
	}

	/**
	 * Retrieve all galleys for an article.
	 * @param $articleId int
	 * @return array ArticleGalleys
	 */
	function getGalleysByArticle($articleId) {
		$galleys = array();

		$result = $this->retrieve(
			'SELECT g.*
			FROM submission_galleys g
			WHERE g.submission_id = ? ORDER BY g.seq',
			(int) $articleId
		);

		while (!$result->EOF) {
			$galleys[] = $this->_returnGalleyFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		HookRegistry::call('ArticleGalleyDAO::getArticleGalleys', array(&$galleys, &$articleId)); // FIXME: XMLGalleyPlugin uses this; should convert to DAO auto call

		return $galleys;
	}

	/**
	 * Retrieve all galleys for an article.
	 * @param $articleId int
	 * @return array ArticleGalleys
	 */
	function getByArticleId($articleId) {
		$result = $this->retrieve(
				'SELECT g.*
				FROM submission_galleys g
				WHERE g.submission_id = ? ORDER BY g.seq',
				(int) $articleId
		);

		return new DAOResultFactory($result, $this, '_returnGalleyFromRow');
	}

	/**
	 * Retrieve all galleys of a journal.
	 * @param $journalId int
	 * @return DAOResultFactory
	 */
	function getGalleysByJournalId($journalId) {
		$result = $this->retrieve(
			'SELECT
				g.*
			FROM submission_galleys g
			INNER JOIN submissions a ON (g.submission_id = a.submission_id)
			WHERE a.context_id = ?',
			(int) $journalId
		);

		return new DAOResultFactory($result, $this, '_returnGalleyFromRow');
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
	function &_returnGalleyFromRow($row) {
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
		$galley->setSubmissionId($row['submission_id']);
		$galley->setLocale($row['locale']);
		$galley->setLabel($row['label']);
		$galley->setSequence($row['seq']);
		$galley->setRemoteURL($row['remote_url']);
		$galley->setIsAvailable($row['is_available']);

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
			'INSERT INTO submission_galleys
				(submission_id, file_id, label, locale, html_galley, style_file_id, seq, remote_url, is_available)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				(int) $galley->getSubmissionId(),
				0,
				$galley->getLabel(),
				$galley->getLocale(),
				(int) $galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? (int) $galley->getStyleFileId() : null,
				$galley->getSequence() == null ? $this->getNextGalleySequence($galley->getSubmissionId()) : $galley->getSequence(),
				$galley->getRemoteURL(),
				$galley->getIsAvailable(),
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
	function updateGalley(&$galley) {
		$this->update(
			'UPDATE submission_galleys
				SET
					file_id = ?,
					label = ?,
					locale = ?,
					html_galley = ?,
					style_file_id = ?,
					seq = ?,
					remote_url = ?,
					is_available = ?
				WHERE galley_id = ?',
			array(
				0,
				$galley->getLabel(),
				$galley->getLocale(),
				(int) $galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? (int) $galley->getStyleFileId() : null,
				$galley->getSequence(),
				$galley->getRemoteURL(),
				(int) $galley->getIsAvailable(),
				(int) $galley->getId(),
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
				'DELETE FROM submission_galleys WHERE galley_id = ? AND submission_id = ?',
				array((int) $galleyId, (int) $articleId)
			);
		} else {
			$this->update(
				'DELETE FROM submission_galleys WHERE galley_id = ?', (int) $galleyId
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
		$result = $this->retrieve(
			'SELECT COUNT(*) FROM submission_galleys
			WHERE submission_id = ? AND file_id = ?',
			array((int) $articleId, (int) $fileId)
		);

		$returner = isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Increment the views count for a galley.
	 * @param $galleyId int
	 */
	function incrementViews($galleyId) {
		if ( !HookRegistry::call('ArticleGalleyDAO::incrementGalleyViews', array(&$galleyId)) ) {
			return $this->update(
				'UPDATE submission_galleys SET views = views + 1 WHERE galley_id = ?',
				(int) $galleyId
			);
		} else return false;
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

		$result = $this->retrieve(
			'SELECT a.* FROM submission_galley_html_images i, article_files a
			WHERE i.file_id = a.file_id AND i.galley_id = ?',
			(int) $galleyId
		);

		while (!$result->EOF) {
			$images[] =& $this->articleFileDao->_returnArticleFileFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		return $images;
	}

	/**
	 * Attach an image to an HTML galley.
	 * @param $galleyId int
	 * @param $fileId int
	 */
	function insertGalleyImage($galleyId, $fileId) {
		return $this->update(
			'INSERT INTO submission_galley_html_images
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
			'DELETE FROM submission_galley_html_images
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
			'DELETE FROM submission_galley_html_images WHERE galley_id = ?',
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
		while ($galley = $galleys->next()) {
			$this->update(
				'DELETE FROM article_galley_settings WHERE setting_name = ? AND galley_id = ?',
				array(
					$settingName,
					(int)$galley->getId()
				)
			);
		}
		$this->flushCache();
	}
}

?>
