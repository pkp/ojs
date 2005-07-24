<?php

/**
 * ArticleGalleyDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for ArticleGalley DAO.
 * Operations for retrieving and modifying ArticleGalley/ArticleHTMLGalley objects.
 *
 * $Id$
 */

import('article.ArticleGalley');
import('article.ArticleHTMLGalley');

class ArticleGalleyDAO extends DAO {

	/** Helper file DAOs. */
	var $articleFileDao;

	/**
	 * Constructor.
	 */
	function ArticleGalleyDAO() {
		parent::DAO();
		$this->articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
	}
	
	/**
	 * Retrieve a galley by ID.
	 * @param $galleyId int
	 * @param $articleId int optional
	 * @return ArticleGalley
	 */
	function &getGalley($galleyId, $articleId = null) {
		if (isset($articleId)) {
			$result = &$this->retrieve(
				'SELECT g.*,
				a.file_name, a.original_file_name, a.file_type, a.file_size, a.status, a.date_uploaded, a.date_modified
				FROM article_galleys g
				LEFT JOIN article_files a ON (g.file_id = a.file_id)
				WHERE g.galley_id = ? AND g.article_id = ?',
				array($galleyId, $articleId)
			);
			
		} else {
			$result = &$this->retrieve(
				'SELECT g.*,
				a.file_name, a.original_file_name, a.file_type, a.file_size, a.status, a.date_uploaded, a.date_modified
				FROM article_galleys g
				LEFT JOIN article_files a ON (g.file_id = a.file_id)
				WHERE g.galley_id = ?',
				$galleyId
			);
		}

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnGalleyFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $returner;
	}
	
	/**
	 * Retrieve all galleys for an article.
	 * @param $articleId int
	 * @return array ArticleGalleys
	 */
	function &getGalleysByArticle($articleId) {
		$galleys = array();
		
		$result = &$this->retrieve(
			'SELECT g.*,
			a.file_name, a.original_file_name, a.file_type, a.file_size, a.status, a.date_uploaded, a.date_modified
			FROM article_galleys g
			LEFT JOIN article_files a ON (g.file_id = a.file_id)
			WHERE g.article_id = ? ORDER BY g.seq',
			$articleId
		);
		
		while (!$result->EOF) {
			$galleys[] = &$this->_returnGalleyFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $galleys;
	}
	
	/**
	 * Internal function to return an ArticleGalley object from a row.
	 * @param $row array
	 * @return ArticleGalley
	 */
	function &_returnGalleyFromRow(&$row) {
		if ($row['html_galley']) {
			$galley = &new ArticleHTMLGalley();
			
			// HTML-specific settings
			$galley->setStyleFileId($row['style_file_id']);
			if ($row['style_file_id']) {
				$galley->setStyleFile($this->articleFileDao->getArticleFile($row['style_file_id']));
			}
		
			// Retrieve images
			$images = &$this->getGalleyImages($row['galley_id']);
			$galley->setImageFiles($images); 
			
		} else {
			$galley = &new ArticleGalley();
		}
		$galley->setGalleyId($row['galley_id']);
		$galley->setArticleId($row['article_id']);
		$galley->setFileId($row['file_id']);
		$galley->setLabel($row['label']);
		$galley->setSequence($row['seq']);
		$galley->setViews($row['views']);
		
		// ArticleFile set methods
		$galley->setFileName($row['file_name']);
		$galley->setOriginalFileName($row['original_file_name']);
		$galley->setFileType($row['file_type']);
		$galley->setFileSize($row['file_size']);
		$galley->setStatus($row['status']);
		$galley->setDateModified($this->datetimeFromDB($row['date_modified']));
		$galley->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));
		
		return $galley;
	}

	/**
	 * Insert a new ArticleGalley.
	 * @param $galley ArticleGalley
	 */	
	function insertGalley(&$galley) {
		$this->update(
			'INSERT INTO article_galleys
				(article_id, file_id, label, html_galley, style_file_id, seq)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$galley->getArticleId(),
				$galley->getFileId(),
				$galley->getLabel(),
				(int)$galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? $galley->getStyleFileId() : null,
				$galley->getSequence() == null ? $this->getNextGalleySequence($galley->getArticleID()) : $galley->getSequence()
			)
		);
		$galley->setGalleyId($this->getInsertGalleyId());
		return $galley->getGalleyId();
	}
	
	/**
	 * Update an existing ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function updateGalley(&$galley) {
		return $this->update(
			'UPDATE article_galleys
				SET
					file_id = ?,
					label = ?,
					html_galley = ?,
					style_file_id = ?,
					seq = ?
				WHERE galley_id = ?',
			array(
				$galley->getFileId(),
				$galley->getLabel(),
				(int)$galley->isHTMLGalley(),
				$galley->isHTMLGalley() ? $galley->getStyleFileId() : null,
				$galley->getSequence(),
				$galley->getGalleyId()
			)
		);
		
		if($galley->isHTMLGalley()) {
			// FIXME Update images, etc.
		}
	}
	
	/**
	 * Delete an ArticleGalley.
	 * @param $galley ArticleGalley
	 */
	function deleteGalley(&$galley) {
		return $this->deleteGalleyById($galley->getGalleyId());
	}
	
	/**
	 * Delete a galley by ID.
	 * @param $galleyId int
	 * @param $articleId int optional
	 */
	function deleteGalleyById($galleyId, $articleId = null) {
		$this->deleteImagesByGalley($galleyId);
		if (isset($articleId)) {
			return $this->update(
				'DELETE FROM article_galleys WHERE galley_id = ? AND article_id = ?',
				array($galleyId, $articleId)
			);
		
		} else {
			return $this->update(
				'DELETE FROM article_galleys WHERE galley_id = ?', $galleyId
			);
		}
	}
	
	/**
	 * Delete galleys (and dependent galley image entries) by article.
	 * NOTE that this will not delete article_file entities or the respective files.
	 * @param $articleId int
	 */
	function deleteGalleysByArticle($articleId) {
		$galleys = &$this->getGalleysByArticle($articleId);
		foreach ($galleys as $galley) {
			$this->deleteGalleyById($galley->getGalleyId(), $articleId);
		}
	}
	
	/**
	 * Check if a galley exists with the associated file ID.
	 * @param $articleId int
	 * @param $fileId int
	 * @return boolean
	 */
	function galleyExistsByFileId($articleId, $fileId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM article_galleys
			WHERE article_id = ? AND file_id = ?',
			array($articleId, $fileId)
		);
		
		return isset($result->fields[0]) && $result->fields[0] == 1 ? true : false;
	}
	
	/**
	 * Increment the views count for a galley.
	 * @param $galleyId int
	 */
	function incrementViews($galleyId) {
		return $this->update(
			'UPDATE article_galleys SET views = views + 1 WHERE galley_id = ?',
			$galleyId
		);
	}
	
	/**
	 * Sequentially renumber galleys for an article in their sequence order.
	 * @param $articleId int
	 */
	function resequenceGalleys($articleId) {
		$result = &$this->retrieve(
			'SELECT galley_id FROM article_galleys WHERE article_id = ? ORDER BY seq',
			$articleId
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
	}
	
	/**
	 * Get the the next sequence number for an article's galleys (i.e., current max + 1).
	 * @param $articleId int
	 * @return int
	 */
	function getNextGalleySequence($articleId) {
		$result = &$this->retrieve(
			'SELECT MAX(seq) + 1 FROM article_galleys WHERE article_id = ?',
			$articleId
		);
		return floor($result->fields[0]);
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
		
		$result = &$this->retrieve(
			'SELECT a.* FROM article_html_galley_images i, article_files a
			WHERE i.file_id = a.file_id AND i.galley_id = ?',
			$galleyId
		);
		
		while (!$result->EOF) {
			$images[] = &$this->articleFileDao->_returnArticleFileFromRow($result->GetRowAssoc(false));
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
			'INSERT INTO article_html_galley_images
			(galley_id, file_id)
			VALUES
			(?, ?)',
			array($galleyId, $fileId)
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
			array($galleyId, $fileId)
		);
	}
	
	/**
	 * Delete HTML galley images by galley.
	 * @param $galleyId int
	 */
	function deleteImagesByGalley($galleyId) {
		return $this->update(
			'DELETE FROM article_html_galley_images WHERE galley_id = ?',
			$galleyId
		);
	}
}

?>
