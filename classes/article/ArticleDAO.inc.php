<?php

/**
 * @file ArticleDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 * @class ArticleDAO
 *
 * Class for Article DAO.
 * Operations for retrieving and modifying Article objects.
 *
 * $Id$
 */

import('article.Article');

class ArticleDAO extends DAO {
	var $authorDao;

	/**
	 * Constructor.
	 */
	function ArticleDAO() {
		parent::DAO();
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
	}

	/**
	 * Get a list of field names for which data is localized.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'abstract', 'discipline', 'subjectClass', 'subject', 'coverageGeo', 'coverageChron', 'coverageSample', 'type', 'sponsor');
	}

	/**
	 * Update the settings for this object
	 * @param $article object
	 */
	function updateLocaleFields(&$article) {
		$this->updateDataObjectSettings('article_settings', $article, array(
			'article_id' => $article->getArticleId()
		));
	}

	/**
	 * Retrieve an article by ID.
	 * @param $articleId int
	 * @param $journalId int optional
	 * @return Article
	 */
	function &getArticle($articleId, $journalId = null) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$articleId
		);
		$sql = 'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	article_id = ?';
		if ($journalId !== null) {
			$sql .= ' AND a.journal_id = ?';
			$params[] = $journalId;
		}

		$result = &$this->retrieve($sql, $params);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnArticleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Internal function to return an Article object from a row.
	 * @param $row array
	 * @return Article
	 */
	function &_returnArticleFromRow(&$row) {
		$article = &new Article();
		$this->_articleFromRow($article, $row);
		return $article;
	}

	/**
	 * Internal function to fill in the passed article object from the row.
	 * @param $article Article output article
	 * @param $row array input row
	 */
	function _articleFromRow(&$article, &$row) {
		$article->setArticleId($row['article_id']);
		$article->setUserId($row['user_id']);
		$article->setJournalId($row['journal_id']);
		$article->setSectionId($row['section_id']);
		$article->setSectionTitle($row['section_title']);
		$article->setSectionAbbrev($row['section_abbrev']);
		$article->setLanguage($row['language']);
		$article->setCommentsToEditor($row['comments_to_ed']);
		$article->setDateSubmitted($this->datetimeFromDB($row['date_submitted']));
		$article->setDateStatusModified($this->datetimeFromDB($row['date_status_modified']));
		$article->setLastModified($this->datetimeFromDB($row['last_modified']));
		$article->setStatus($row['status']);
		$article->setSubmissionProgress($row['submission_progress']);
		$article->setCurrentRound($row['current_round']);
		$article->setSubmissionFileId($row['submission_file_id']);
		$article->setRevisedFileId($row['revised_file_id']);
		$article->setReviewFileId($row['review_file_id']);
		$article->setEditorFileId($row['editor_file_id']);
		$article->setCopyeditFileId($row['copyedit_file_id']);
		$article->setPages($row['pages']);
		$article->setFastTracked($row['fast_tracked']);
		

		$article->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));

		$this->getDataObjectSettings('article_settings', 'article_id', $row['article_id'], $article);

		HookRegistry::call('ArticleDAO::_returnArticleFromRow', array(&$article, &$row));

	}

	/**
	 * Insert a new Article.
	 * @param $article Article
	 */	
	function insertArticle(&$article) {
		$article->stampModified();
		$this->update(
			sprintf('INSERT INTO articles
				(user_id, journal_id, section_id, language, comments_to_ed, date_submitted, date_status_modified, last_modified, status, submission_progress, current_round, submission_file_id, revised_file_id, review_file_id, editor_file_id, copyedit_file_id, pages, fast_tracked)
				VALUES
				(?, ?, ?, ?, ?, %s, %s, %s, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getUserId(),
				$article->getJournalId(),
				$article->getSectionId(),
				$article->getLanguage(),
				$article->getCommentsToEditor(),
				$article->getStatus() === null ? STATUS_QUEUED : $article->getStatus(),
				$article->getSubmissionProgress() === null ? 1 : $article->getSubmissionProgress(),
				$article->getCurrentRound() === null ? 1 : $article->getCurrentRound(),
				$article->getSubmissionFileId(),
				$article->getRevisedFileId(),
				$article->getReviewFileId(),
				$article->getEditorFileId(),
				$article->getCopyeditFileId(),
				$article->getPages(),
				$article->getFastTracked() === null ? false : $article->getFastTracked()
			)
		);

		$article->setArticleId($this->getInsertArticleId());
		$this->updateLocaleFields($article);

		// Insert authors for this article
		$authors = &$article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$authors[$i]->setArticleId($article->getArticleId());
			$this->authorDao->insertAuthor($authors[$i]);
		}

		return $article->getArticleId();
	}

	/**
	 * Update an existing article.
	 * @param $article Article
	 */
	function updateArticle(&$article) {
		$article->stampModified();
		$this->update(
			sprintf('UPDATE articles
				SET
					user_id = ?,
					section_id = ?,
					language = ?,
					comments_to_ed = ?,
					date_submitted = %s,
					date_status_modified = %s,
					last_modified = %s,
					status = ?,
					submission_progress = ?,
					current_round = ?,
					submission_file_id = ?,
					revised_file_id = ?,
					review_file_id = ?,
					editor_file_id = ?,
					copyedit_file_id = ?,
					pages = ?,
					fast_tracked = ?
				WHERE article_id = ?',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getUserId(),
				$article->getSectionId(),
				$article->getLanguage(),
				$article->getCommentsToEditor(),
				$article->getStatus(),
				$article->getSubmissionProgress(),
				$article->getCurrentRound(),
				$article->getSubmissionFileId(),
				$article->getRevisedFileId(),
				$article->getReviewFileId(),
				$article->getEditorFileId(),
				$article->getCopyeditFileId(),
				$article->getPages(),
				$article->getFastTracked(),
				$article->getArticleId()
			)
		);

		$this->updateLocaleFields($article);

		// update authors for this article
		$authors = &$article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]->getAuthorId() > 0) {
				$this->authorDao->updateAuthor($authors[$i]);
			} else {
				$this->authorDao->insertAuthor($authors[$i]);
			}
		}

		// Remove deleted authors
		$removedAuthors = $article->getRemovedAuthors();
		for ($i=0, $count=count($removedAuthors); $i < $count; $i++) {
			$this->authorDao->deleteAuthorById($removedAuthors[$i], $article->getArticleId());
		}

		// Update author sequence numbers
		$this->authorDao->resequenceAuthors($article->getArticleId());
	}

	/**
	 * Delete an article.
	 * @param $article Article
	 */
	function deleteArticle(&$article) {
		return $this->deleteArticleById($article->getArticleId());
	}

	/**
	 * Delete an article by ID.
	 * @param $articleId int
	 */
	function deleteArticleById($articleId) {
		$this->authorDao->deleteAuthorsByArticle($articleId);

		$publishedArticleDao = &DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->deletePublishedArticleByArticleId($articleId);

		$commentDao = &DAORegistry::getDAO('CommentDAO');
		$commentDao->deleteCommentsByArticle($articleId);

		$articleNoteDao = &DAORegistry::getDAO('ArticleNoteDAO');
		$articleNoteDao->clearAllArticleNotes($articleId);

		$sectionEditorSubmissionDao = &DAORegistry::getDAO('SectionEditorSubmissionDAO');
		$sectionEditorSubmissionDao->deleteDecisionsByArticle($articleId);
		$sectionEditorSubmissionDao->deleteReviewRoundsByArticle($articleId);

		$reviewAssignmentDao = &DAORegistry::getDAO('ReviewAssignmentDAO');
		$reviewAssignmentDao->deleteReviewAssignmentsByArticle($articleId);

		$editAssignmentDao = &DAORegistry::getDAO('EditAssignmentDAO');
		$editAssignmentDao->deleteEditAssignmentsByArticle($articleId);

		$copyAssignmentDao = &DAORegistry::getDAO('CopyAssignmentDAO');
		$copyAssignmentDao->deleteCopyAssignmentsByArticle($articleId);

		$layoutAssignmentDao = &DAORegistry::getDAO('LayoutAssignmentDAO');
		$layoutAssignmentDao->deleteLayoutAssignmentsByArticle($articleId);

		$proofAssignmentDao = &DAORegistry::getDAO('ProofAssignmentDAO');
		$proofAssignmentDao->deleteProofAssignmentsByArticle($articleId);

		$articleCommentDao = &DAORegistry::getDAO('ArticleCommentDAO');
		$articleCommentDao->deleteArticleComments($articleId);

		$articleGalleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$articleGalleyDao->deleteGalleysByArticle($articleId);

		$articleSearchDao = &DAORegistry::getDAO('ArticleSearchDAO');
		$articleSearchDao->deleteArticleKeywords($articleId);

		$articleEventLogDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		$articleEventLogDao->deleteArticleLogEntries($articleId);

		$articleEmailLogDao = &DAORegistry::getDAO('ArticleEmailLogDAO');
		$articleEmailLogDao->deleteArticleLogEntries($articleId);

		$articleEventLogDao = &DAORegistry::getDAO('ArticleEventLogDAO');
		$articleEventLogDao->deleteArticleLogEntries($articleId);

		$suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFileDao->deleteSuppFilesByArticle($articleId);

		// Delete article files -- first from the filesystem, then from the database
		import('file.ArticleFileManager');
		$articleFileDao = &DAORegistry::getDAO('ArticleFileDAO');
		$articleFiles = &$articleFileDao->getArticleFilesByArticle($articleId);

		$articleFileManager = &new ArticleFileManager($articleId);
		foreach ($articleFiles as $articleFile) {
			$articleFileManager->deleteFile($articleFile->getFileId());
		}

		$articleFileDao->deleteArticleFiles($articleId);

		$this->update('DELETE FROM article_settings WHERE article_id = ?', $articleId);
		$this->update('DELETE FROM articles WHERE article_id = ?', $articleId);
	}

	/**
	 * Get all articles for a journal.
	 * @param $userId int
	 * @param $journalId int
	 * @return DAOResultFactory containing matching Articles
	 */
	function &getArticlesByJournalId($journalId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$articles = array();

		$result = &$this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE a.journal_id = ?',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$journalId
			)
		);

		$returner = &new DAOResultFactory($result, $this, '_returnArticleFromRow');
		return $returner;
	}

	/**
	 * Delete all articles by journal ID.
	 * @param $journalId int
	 */
	function deleteArticlesByJournalId($journalId) {
		$articles = $this->getArticlesByJournalId($journalId);

		while (!$articles->eof()) {
			$article = &$articles->next();
			$this->deleteArticleById($article->getArticleId());
		}
	}

	/**
	 * Get all articles for a user.
	 * @param $userId int
	 * @param $journalId int optional
	 * @return array Articles
	 */
	function &getArticlesByUserId($userId, $journalId = null) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$userId
		);
		if ($journalId) $params[] = $journalId;
		$articles = array();

		$result = &$this->retrieve(
			'SELECT	a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.user_id = ?' .
			(isset($journalId)?' AND a.journal_id = ?':''),
			$params
		);

		while (!$result->EOF) {
			$articles[] = &$this->_returnArticleFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);

		return $articles;
	}

	/**
	 * Get the ID of the journal an article is in.
	 * @param $articleId int
	 * @return int
	 */
	function getArticleJournalId($articleId) {
		$result = &$this->retrieve(
			'SELECT journal_id FROM articles WHERE article_id = ?', $articleId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Check if the specified incomplete submission exists.
	 * @param $articleId int
	 * @param $userId int
	 * @param $journalId int
	 * @return int the submission progress
	 */
	function incompleteSubmissionExists($articleId, $userId, $journalId) {
		$result = &$this->retrieve(
			'SELECT submission_progress FROM articles WHERE article_id = ? AND user_id = ? AND journal_id = ? AND date_submitted IS NULL',
			array($articleId, $userId, $journalId)
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Change the status of the article
	 * @param $articleId int
	 * @param $status int
	 */
	function changeArticleStatus($articleId, $status) {
		$this->update(
			'UPDATE articles SET status = ? WHERE article_id = ?', array($status, $articleId)
		);
	}

	/**
	 * Removes articles from a section by section ID
	 * @param $sectionId int
	 */
	function removeArticlesFromSection($sectionId) {
		$this->update(
			'UPDATE articles SET section_id = null WHERE section_id = ?', $sectionId
		);
	}

	/**
	 * Get the ID of the last inserted article.
	 * @return int
	 */
	function getInsertArticleId() {
		return $this->getInsertId('articles', 'article_id');
	}
}

?>
