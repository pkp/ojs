<?php

/**
 * ArticleFileDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for ArticleFile DAO.
 * Operations for retrieving and modifying ArticleFile objects.
 *
 * $Id$
 */

class ArticleFileDAO extends DAO {


	/**
	 * Constructor.
	 */
	function ArticleFileDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve an article by ID.
	 * @param $articleId int
	 * @return ArticleFile
	 */
	function &getArticleFile($fileId, $revision = null) {
		if ($revision == null) {
			$result = &$this->selectLimit('SELECT a.* FROM article_files a WHERE file_id = '.$fileId.' ORDER BY revision DESC', 1);
		} else {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_files a WHERE file_id = ? AND revision = ?',
				array($fileId, $revision)
			);
		}

		if (!isset($result) || $result->RecordCount() == 0) {
			return null;
		} else {
			return $this->_returnArticleFileFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve all revisions of an article file.
	 * @param $articleId int
	 * @return ArticleFile
	 */
	function &getArticleFileRevisions($fileId, $round = null) {
		$articleFiles = array();
		
		if ($round == null) {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_files a WHERE file_id = ?',
				$fileId
			);
		} else {
			$result = &$this->retrieve(
				'SELECT a.* FROM article_files a WHERE file_id = ? AND round = ?',
				array($fileId, $round)
			);
		}
		
		while (!$result->EOF) {
			$articleFiles[] = &$this->_returnArticleFileFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
	
		return $articleFiles;
	}
	
	/**
	 * Retrieve the current revision number for a file.
	 * @param $fileId int
	 * @return int
	 */
	function &getRevisionNumber($fileId) {
		$result = &$this->retrieve(
			'SELECT MAX(revision) AS max_revision FROM article_files a WHERE file_id = ?',
			$fileId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
		} else {
			$row = $result->FetchRow();
			return $row['max_revision'];
		}
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
		$articleFile->setRevision($row['revision']);
		$articleFile->setArticleId($row['article_id']);
		$articleFile->setFileName($row['file_name']);
		$articleFile->setFileType($row['file_type']);
		$articleFile->setFileSize($row['file_size']);
		$articleFile->setType($row['type']);
		$articleFile->setStatus($row['status']);
		$articleFile->setDateUploaded($row['date_uploaded']);
		$articleFile->setDateModified($row['date_modified']);
		$articleFile->setRound($row['round']);
		$articleFile->setViewable($row['viewable']);
		return $articleFile;
	}

	/**
	 * Insert a new ArticleFile.
	 * @param $articleFile ArticleFile
	 * @return int
	 */	
	function insertArticleFile(&$articleFile) {
		$this->update(
			'INSERT INTO article_files
				(file_id, revision, article_id, file_name, file_type, file_size, type, status, date_uploaded, date_modified, round, viewable)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$articleFile->getFileId(),
				$articleFile->getRevision() === null ? 1 : $articleFile->getRevision(),
				$articleFile->getArticleId(),
				$articleFile->getFileName(),
				$articleFile->getFileType(),
				$articleFile->getFileSize(),
				$articleFile->getType(),
				$articleFile->getStatus(),
				$articleFile->getDateUploaded(),
				$articleFile->getDateModified(),
				$articleFile->getRound(),
				$articleFile->getViewable()
			)
		);
		
		$articleFile->setFileId($this->getInsertArticleFileId());
		
		return $this->getInsertArticleFileId();
	}
	
	/**
	 * Update an existing article file.
	 * @param $article ArticleFile
	 */
	function updateArticleFile(&$articleFile) {
		$this->update(
			'UPDATE article_files
				SET
					article_id = ?,
					file_name = ?,
					file_type = ?,
					file_size = ?,
					type = ?,
					status = ?,
					date_uploaded = ?,
					date_modified = ?,
					round = ?,
					viewable = ?
				WHERE file_id = ? AND revision = ?',
			array(
				$articleFile->getArticleId(),
				$articleFile->getFileName(),
				$articleFile->getFileType(),
				$articleFile->getFileSize(),
				$articleFile->getType(),
				$articleFile->getStatus(),
				$articleFile->getDateUploaded(),
				$articleFile->getDateModified(),
				$articleFile->getRound(),
				$articleFile->getViewable(),
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
		return $this->deleteArticleFileById($articleFile->getArticleFileId());
	}
	
	/**
	 * Delete an article file by ID.
	 * @param $articleId int
	 * @param $revision int
	 */
	function deleteArticleFileById($fileId, $revision) {
		return $this->update(
			'DELETE FROM article_files WHERE file_id = ? AND revision = ?', array($fileId, $revision)
		);
	}
	
	/**
	 * Get the ID of the last inserted article file.
	 * @return int
	 */
	function getInsertArticleFileId() {
		return $this->getInsertId('article_files', 'file_id');
	}
	
}

?>
