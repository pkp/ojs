<?php

/**
 * ArticleDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for Article DAO.
 * Operations for retrieving and modifying Article objects.
 *
 * $Id$
 */

class ArticleDAO extends DAO {

	var $authorDao;

	/**
	 * Constructor.
	 */
	function ArticleDAO() {
		parent::DAO();
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
	}
	
	/**
	 * Retrieve an article by ID.
	 * @param $articleId int
	 * @return Article
	 */
	function &getArticle($articleId) {
		$result = &$this->retrieve(
			'SELECT a.*, s.title AS section_title FROM articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE article_id = ?', $articleId
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnArticleFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return an Article object from a row.
	 * @param $row array
	 * @return Article
	 */
	function &_returnArticleFromRow(&$row) {
		$article = &new Article();
		$article->setArticleId($row['article_id']);
		$article->setUserId($row['user_id']);
		$article->setJournalId($row['journal_id']);
		$article->setSectionId($row['section_id']);
		$article->setSectionTitle($row['section_title']);
		$article->setTitle($row['title']);
		$article->setTitleAlt1($row['title_alt1']);
		$article->setTitleAlt2($row['title_alt2']);
		$article->setAbstract($row['abstract']);
		$article->setAbstractAlt1($row['abstract_alt1']);
		$article->setAbstractAlt2($row['abstract_alt2']);
		$article->setDiscipline($row['discipline']);
		$article->setSubjectClass($row['subject_class']);
		$article->setSubject($row['subject']);
		$article->setCoverageGeo($row['coverage_geo']);
		$article->setCoverageChron($row['coverage_chron']);
		$article->setCoverageSample($row['coverage_sample']);
		$article->setType($row['type']);
		$article->setLanguage($row['language']);
		$article->setSponsor($row['sponsor']);
		$article->setCommentsToEditor($row['comments_to_ed']);
		$article->setDateSubmitted($row['date_submitted']);
		$article->setStatus($row['status']);
		$article->setSubmissionProgress($row['submission_progress']);
		$article->setSubmissionFileId($row['submission_file_id']);
		$article->setRevisedFileId($row['revised_file_id']);
		
		$article->setAuthors($this->authorDao->getAuthorsByArticle($row['article_id']));
		
		return $article;
	}

	/**
	 * Insert a new Article.
	 * @param $article Article
	 */	
	function insertArticle(&$article) {
		$this->update(
			'INSERT INTO articles
				(user_id, journal_id, section_id, title, title_alt1, title_alt2, abstract, abstract_alt1, abstract_alt2, discipline, subject_class, subject, coverage_geo, coverage_chron, coverage_sample, type, language, sponsor, comments_to_ed, date_submitted, status, submission_progress, submission_file_id, revised_file_id)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$article->getUserId(),
				$article->getJournalId(),
				$article->getSectionId(),
				$article->getTitle() === null ? '' : $article->getTitle(),
				$article->getTitleAlt1(),
				$article->getTitleAlt2(),
				$article->getAbstract(),
				$article->getAbstractAlt1(),
				$article->getAbstractAlt2(),
				$article->getDiscipline(),
				$article->getSubjectClass(),
				$article->getSubject(),
				$article->getCoverageGeo(),
				$article->getCoverageChron(),
				$article->getCoverageSample(),
				$article->getType(),
				$article->getLanguage(),
				$article->getSponsor(),
				$article->getCommentsToEditor(),
				$article->getDateSubmitted(),
				$article->getStatus() === null ? 1 : $article->getStatus(),
				$article->getSubmissionProgress() === null ? 1 : $article->getSubmissionProgress(),
				$article->getSubmissionFileId(),
				$article->getRevisedFileId()
			)
		);
		
		$article->setArticleId($this->getInsertArticleId());
		
		// Insert authors for this article
		$authors = &$article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$authors[$i]->setArticleId($article->getArticleId());
			$this->authorDao->insertAuthor(&$authors[$i]);
		}
	}
	
	/**
	 * Update an existing article.
	 * @param $article Article
	 */
	function updateArticle(&$article) {
		$this->update(
			'UPDATE articles
				SET
					section_id = ?,
					title = ?,
					title_alt1 = ?,
					title_alt2 = ?,
					abstract = ?,
					abstract_alt1 = ?,
					abstract_alt2 = ?,
					discipline = ?,
					subject_class = ?,
					subject = ?,
					coverage_geo = ?,
					coverage_chron = ?,
					coverage_sample = ?,
					type = ?,
					language = ?,
					sponsor = ?,
					comments_to_ed = ?,
					date_submitted = ?,
					status = ?,
					submission_progress = ?,
					submission_file_id = ?,
					revised_file_id = ?
				WHERE article_id = ?',
			array(
				$article->getSectionId(),
				$article->getTitle(),
				$article->getTitleAlt1(),
				$article->getTitleAlt2(),
				$article->getAbstract(),
				$article->getAbstractAlt1(),
				$article->getAbstractAlt2(),
				$article->getDiscipline(),
				$article->getSubjectClass(),
				$article->getSubject(),
				$article->getCoverageGeo(),
				$article->getCoverageChron(),
				$article->getCoverageSample(),
				$article->getType(),
				$article->getLanguage(),
				$article->getSponsor(),
				$article->getCommentsToEditor(),
				$article->getDateSubmitted(),
				$article->getStatus(),
				$article->getSubmissionProgress(),
				$article->getSubmissionFileId(),
				$article->getRevisedFileId(),
				$article->getArticleId()
			)
		);
		
		// update authors for this article
		$authors = &$article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]->getAuthorId() > 0) {
				$this->authorDao->updateAuthor(&$authors[$i]);
			} else {
				$this->authorDao->insertAuthor(&$authors[$i]);
			}
		}
		
		// Remove deleted authors
		$removedAuthors = $article->getRemovedAuthors();
		for ($i=0, $count=count($removedAuthors); $i < $count; $i++) {
			$this->authorDao->deleteAuthorById($removedAuthors[$i], $article->getArticleId());
		}
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
		$this->update(
			'DELETE FROM articles WHERE article_id = ?', $articleId
		);
		return $this->authorDao->deleteAuthorsByArticle($articleId);
	}
	
	/**
	 * Get all articles for a user.
	 * @param $userId int
	 * @param $journalId int
	 * @return array Articles
	 */
	function &getArticlesByUserId($userId, $journalId) {
		$articles = array();
		
		$result = &$this->retrieve(
			'SELECT a.*, s.title AS section_title FROM articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE a.user_id = ? AND a.journal_id = ?',
			array($userId, $journalId)
		);
		
		while (!$result->EOF) {
			$articles[] = $this->_returnArticleFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
		return $articles;
	}
	
	/**
	 * Get the ID of the journal an article is in.
	 * @param $articleId int
	 * @return int
	 */
	function &getArticleJournalId($articleId) {
		$result = &$this->retrieve(
			'SELECT journal_id FROM articles WHERE article_id = ?', $articleId
		);
		return isset($result->fields[0]) ? $result->fields[0] : false;
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
		return isset($result->fields[0]) ? $result->fields[0] : false;
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
