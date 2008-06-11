<?php

/**
 * @file ArticleFileDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 * @class ArticleFileDAO
 *
 * Class for ArticleFile DAO.
 * Operations for retrieving and modifying ArticleFile objects.
 *
 * $Id$
 */

import('article.ArticleFile');

define('INLINEABLE_TYPES_FILE', Config::getVar('general', 'registry_dir') . DIRECTORY_SEPARATOR . 'inlineTypes.txt');

class ArticleFileDAO extends DAO {
	/**
	 * Array of MIME types that can be displayed inline in a browser
	 */
	var $inlineableTypes;

	/**
	 * Retrieve an article by ID.
	 * @param $fileId int
	 * @param $revision int optional, if omitted latest revision is used
	 * @param $articleId int optional
	 * @return ArticleFile
	 */
	function &getArticleFile($fileId, $revision = null, $articleId = null) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		if ($revision == null) {
			if ($articleId != null) {
				$result = &$this->retrieveLimit(
					'SELECT a.* FROM article_files a WHERE file_id = ? AND article_id = ? ORDER BY revision DESC',
					array($fileId, $articleId),
					1
				);
			} else {
				$result = &$this->retrieveLimit(
					'SELECT a.* FROM article_files a WHERE file_id = ? ORDER BY revision DESC',
					$fileId,
					1
				);
			}

		} else {
			if ($articleId != null) {
				$result = &$this->retrieve(
					'SELECT a.* FROM article_files a WHERE file_id = ? AND revision = ? AND article_id = ?',
					array($fileId, $revision, $articleId)
				);
			} else {
				$result = &$this->retrieve(
					'SELECT a.* FROM article_files a WHERE file_id = ? AND revision = ?',
					array($fileId, $revision)
				);
			}
		}

		$returner = null;
		if (isset($result) && $result->RecordCount() != 0) {
			$returner = &$this->_returnArticleFileFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all revisions of an article file.
	 * @param $articleId int
	 * @return ArticleFile
	 */
	function &getArticleFileRevisions($fileId, $round = null) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		$articleFiles = array();

		// FIXME If "round" is review-specific, it shouldn't be in this table
		if ($round == null) {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_files a WHERE file_id = ? ORDER BY revision',
				$fileId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_files a WHERE file_id = ? AND round = ? ORDER BY revision',
				array($fileId, $round)
			);
		}

		while (!$result->EOF) {
			$articleFiles[] = &$this->_returnArticleFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $articleFiles;
	}

	/**
	 * Retrieve revisions of an article file in a range.
	 * @param $articleId int
	 * @return ArticleFile
	 */
	function &getArticleFileRevisionsInRange($fileId, $start = 1, $end = null) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		$articleFiles = array();

		if ($end == null) {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_files a WHERE file_id = ? AND revision >= ?',
				array($fileId, $start)
			);
		} else {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_files a WHERE file_id = ? AND revision >= ? AND revision <= ?',
				array($fileId, $start, $end)
			);		
		}

		while (!$result->EOF) {
			$articleFiles[] = &$this->_returnArticleFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $articleFiles;
	}

	/**
	 * Retrieve the current revision number for a file.
	 * @param $fileId int
	 * @return int
	 */
	function &getRevisionNumber($fileId) {
		if ($fileId === null) {
			$returner = null;
			return $returner;
		}
		$result = &$this->retrieve(
			'SELECT MAX(revision) AS max_revision FROM article_files a WHERE file_id = ?',
			$fileId
		);

		if ($result->RecordCount() == 0) {
			$returner = null;
		} else {
			$row = $result->FetchRow();
			$returner = $row['max_revision'];
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Retrieve all article files for an article.
	 * @param $articleId int
	 * @return array ArticleFiles
	 */
	function &getArticleFilesByArticle($articleId) {
		$articleFiles = array();

		$result = &$this->retrieve(
			'SELECT * FROM article_files WHERE article_id = ?',
			$articleId
		);

		while (!$result->EOF) {
			$articleFiles[] = &$this->_returnArticleFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $articleFiles;
	}

	/**
	 * Retrieve all article files for a type and assoc ID.
	 * @param $assocId int
	 * @param $type int
	 * @return array ArticleFiles
	 */
	function &getArticleFilesByAssocId($assocId, $type) {
		import('file.ArticleFileManager');
		$articleFiles = array();

		$result = &$this->retrieve(
			'SELECT * FROM article_files WHERE assoc_id = ? AND type = ?',
			array($assocId, ArticleFileManager::typeToPath($type))
		);

		while (!$result->EOF) {
			$articleFiles[] = &$this->_returnArticleFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $articleFiles;
	}

	/**
	 * Internal function to return an ArticleFile object from a row.
	 * @param $row array
	 * @return ArticleFile
	 */
	function &_returnArticleFileFromRow(&$row) {
		$articleFile = &new ArticleFile();
		$articleFile->setFileId($row['file_id']);
		$articleFile->setSourceFileId($row['source_file_id']);
		$articleFile->setSourceRevision($row['source_revision']);
		$articleFile->setRevision($row['revision']);
		$articleFile->setArticleId($row['article_id']);
		$articleFile->setFileName($row['file_name']);
		$articleFile->setFileType($row['file_type']);
		$articleFile->setFileSize($row['file_size']);
		$articleFile->setOriginalFileName($row['original_file_name']);
		$articleFile->setType($row['type']);
		$articleFile->setAssocId($row['assoc_id']);
		$articleFile->setStatus($row['status']);
		$articleFile->setDateUploaded($this->datetimeFromDB($row['date_uploaded']));
		$articleFile->setDateModified($this->datetimeFromDB($row['date_modified']));
		$articleFile->setRound($row['round']);
		$articleFile->setViewable($row['viewable']);
		HookRegistry::call('ArticleFileDAO::_returnArticleFileFromRow', array(&$articleFile, &$row));
		return $articleFile;
	}

	/**
	 * Insert a new ArticleFile.
	 * @param $articleFile ArticleFile
	 * @return int
	 */	
	function insertArticleFile(&$articleFile) {
		$fileId = $articleFile->getFileId();
		$params = array(
			$articleFile->getRevision() === null ? 1 : $articleFile->getRevision(),
			$articleFile->getArticleId(),
			$articleFile->getSourceFileId(),
			$articleFile->getSourceRevision(),
			$articleFile->getFileName(),
			$articleFile->getFileType(),
			$articleFile->getFileSize(),
			$articleFile->getOriginalFileName(),
			$articleFile->getType(),
			$articleFile->getStatus(),
			$articleFile->getRound(),
			$articleFile->getViewable(),
			$articleFile->getAssocId()
		);

		if ($fileId) {
			array_unshift($params, $fileId);
		}

		$this->update(
			sprintf('INSERT INTO article_files
				(' . ($fileId ? 'file_id, ' : '') . 'revision, article_id, source_file_id, source_revision, file_name, file_type, file_size, original_file_name, type, status, date_uploaded, date_modified, round, viewable, assoc_id)
				VALUES
				(' . ($fileId ? '?, ' : '') . '?, ?, ?, ?, ?, ?, ?, ?, ?, ?, %s, %s, ?, ?, ?)',
				$this->datetimeToDB($articleFile->getDateUploaded()), $this->datetimeToDB($articleFile->getDateModified())),
			$params
		);

		if (!$fileId) {
			$articleFile->setFileId($this->getInsertArticleFileId());
		}

		return $articleFile->getFileId();
	}

	/**
	 * Update an existing article file.
	 * @param $article ArticleFile
	 */
	function updateArticleFile(&$articleFile) {
		$this->update(
			sprintf('UPDATE article_files
				SET
					article_id = ?,
					source_file_id = ?,
					source_revision = ?,
					file_name = ?,
					file_type = ?,
					file_size = ?,
					original_file_name = ?,
					type = ?,
					status = ?,
					date_uploaded = %s,
					date_modified = %s,
					round = ?,
					viewable = ?,
					assoc_id = ?
				WHERE file_id = ? AND revision = ?',
				$this->datetimeToDB($articleFile->getDateUploaded()), $this->datetimeToDB($articleFile->getDateModified())),
			array(
				$articleFile->getArticleId(),
				$articleFile->getSourceFileId(),
				$articleFile->getSourceRevision(),
				$articleFile->getFileName(),
				$articleFile->getFileType(),
				$articleFile->getFileSize(),
				$articleFile->getOriginalFileName(),
				$articleFile->getType(),
				$articleFile->getStatus(),
				$articleFile->getRound(),
				$articleFile->getViewable(),
				$articleFile->getAssocId(),
				$articleFile->getFileId(),
				$articleFile->getRevision()
			)
		);

		return $articleFile->getFileId();

	}

	/**
	 * Delete an article file.
	 * @param $article ArticleFile
	 */
	function deleteArticleFile(&$articleFile) {
		return $this->deleteArticleFileById($articleFile->getFileId(), $articleFile->getRevision());
	}

	/**
	 * Delete an article file by ID.
	 * @param $articleId int
	 * @param $revision int
	 */
	function deleteArticleFileById($fileId, $revision = null) {
		if ($revision == null) {
			return $this->update(
				'DELETE FROM article_files WHERE file_id = ?', $fileId
			);
		} else {
			return $this->update(
				'DELETE FROM article_files WHERE file_id = ? AND revision = ?', array($fileId, $revision)
			);
		}
	}

	/**
	 * Delete all article files for an article.
	 * @param $articleId int
	 */
	function deleteArticleFiles($articleId) {
		return $this->update(
			'DELETE FROM article_files WHERE article_id = ?', $articleId
		);
	}

	/**
	 * Get the ID of the last inserted article file.
	 * @return int
	 */
	function getInsertArticleFileId() {
		return $this->getInsertId('article_files', 'file_id');
	}

	/**
	 * Check whether a file may be displayed inline.
	 * @param $articleFile object
	 * @return boolean
	 */
	function isInlineable(&$articleFile) {
		if (!isset($this->inlineableTypes)) {
			$this->inlineableTypes = array_filter(file(INLINEABLE_TYPES_FILE), create_function('&$a', 'return ($a = trim($a)) && !empty($a) && $a[0] != \'#\';'));
		}
		return in_array($articleFile->getFileType(), $this->inlineableTypes);
	}
}

?>
