<?php

/**
 * PublishedArticleDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package article
 *
 * Class for PublishedArticle DAO.
 * Operations for retrieving and modifying PublishedArticle objects.
 *
 * $Id$
 */

import('article.PublishedArticle');

class PublishedArticleDAO extends DAO {

	var $articleDao;
	var $authorDao;
	var $galleyDao;
	var $suppFileDao;

 	/**
	 * Constructor.
	 */
	function PublishedArticleDAO() {
		parent::DAO();
		$this->articleDao = &DAORegistry::getDAO('ArticleDAO');
		$this->authorDao = DAORegistry::getDAO('AuthorDAO');
		$this->galleyDao = &DAORegistry::getDAO('ArticleGalleyDAO');
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
	}

	/**
	 * Retrieve Published Articles by issue id.  Limit provides number of records to retrieve
	 * @param $issueId int
	 * @param $limit int, default NULL
	 * @return PublishedArticle objects array
	 */
	function &getPublishedArticles($issueId, $limit = NULL) {
		$publishedArticles = array();

		if (isset($limit)) {
			$result = &$this->retrieveLimit(
				'SELECT pa.*, a.*, s.title AS section_title, s.abbrev AS section_abbrev FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND pa.issue_id = ? ORDER BY s.seq ASC, pa.seq ASC', $issueId, $limit
			);
		} else {
			$result = &$this->retrieve(
				'SELECT pa.*, a.*, s.title AS section_title, s.abbrev AS section_abbrev FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND pa.issue_id = ? ORDER BY s.seq ASC, pa.seq ASC', $issueId
			);
		}

		while (!$result->EOF) {
			$publishedArticles[] = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();
		return $publishedArticles;
	}

	/**
	 * Retrieve Published Articles by issue id
	 * @param $issueId int
	 * @return PublishedArticle objects array
	 */
	function &getPublishedArticlesInSections($issueId) {
		$publishedArticles = array();

		$result = &$this->retrieve(
			'SELECT pa.*, a.*, s.title AS section_title, s.abstracts_disabled AS abstracts_disabled, s.abbrev AS section_abbrev, s.hide_title AS section_hide_title FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND pa.issue_id = ? ORDER BY s.seq ASC, pa.seq ASC', $issueId
		);

		$currSectionId = 0;
		while (!$result->EOF) {
			$row = &$result->GetRowAssoc(false);
			$publishedArticle = &$this->_returnPublishedArticleFromRow($row);
			if ($publishedArticle->getSectionId() != $currSectionId) {
				$currSectionId = $publishedArticle->getSectionId();
				$publishedArticles[$currSectionId] = array(
					'articles'=> array(),
					'title' => '',
					'abstractsDisabled' => $row['abstracts_disabled']
				);

				if (!$row['section_hide_title']) {
					$publishedArticles[$currSectionId]['title'] = $publishedArticle->getSectionTitle();
				}
			}
			$publishedArticles[$currSectionId]['articles'][] = $publishedArticle;
			$result->moveNext();
		}
		$result->Close();
		return $publishedArticles;
	}

	/**
	 * Retrieve Published Articles by section id
	 * @param $sectionId int
	 * @return PublishedArticle objects array
	 */
	function &getPublishedArticlesBySectionId($sectionId, $issueId) {
		$publishedArticles = array();

		$result = &$this->retrieve(
			'SELECT pa.*, a.*, s.title AS section_title, s.abbrev AS section_abbrev FROM published_articles pa, articles a, sections s WHERE a.section_id = s.section_id AND pa.article_id = a.article_id AND a.section_id = ? AND pa.issue_id = ? ORDER BY pa.seq ASC', array($sectionId, $issueId)
		);

		$currSectionId = 0;
		while (!$result->EOF) {
			$publishedArticle = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
			$publishedArticles[] = $publishedArticle;
			$result->moveNext();
		}
		$result->Close();
		return $publishedArticles;
	}

	/**
	 * Retrieve Published Article by pub id
	 * @param $pubId int
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleById($pubId) {
		$result = &$this->retrieve(
			'SELECT * FROM published_articles WHERE pub_id = ?', $pubId
		);
		$row = $result->GetRowAssoc(false);

		$publishedArticle = &new PublishedArticle();
		$publishedArticle->setPubId($row['pub_id']);
		$publishedArticle->setArticleId($row['article_id']);
		$publishedArticle->setIssueId($row['issue_id']);
		$publishedArticle->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedArticle->setSeq($row['seq']);
		$publishedArticle->setViews($row['views']);
		$publishedArticle->setAccessStatus($row['access_status']);

		$publishedArticle->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));

		$result->Close();
		return $publishedArticle;
	}

	/**
	 * Retrieve published article by article id
	 * @param $articleId int
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByArticleId($articleId) {
		$result = &$this->retrieve(
			'SELECT pa.*, a.*, s.title AS section_title, s.abbrev AS section_abbrev FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND a.article_id = ?', $articleId
		);

		$publishedArticle = null;
		if ($result->RecordCount() != 0) {
			$publishedArticle = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $publishedArticle;
	}

	/**
	 * Retrieve published article by public article id
	 * @param $publicArticleId int
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByPublicArticleId($publicArticleId) {
		$result = &$this->retrieve(
			'SELECT pa.*, a.*, s.title AS section_title, s.abbrev AS section_abbrev FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND pa.public_article_id = ?', $publicArticleId
		);

		$publishedArticle = null;
		if ($result->RecordCount() != 0) {
			$publishedArticle = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
		}
		$result->Close();
		return $publishedArticle;
	}

	/**
	 * Retrieve published article by public article id or, failing that,
	 * internal article ID; public article ID takes precedence.
	 * @param $articleId int
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByBestArticleId($articleId) {
		$article = &$this->getPublishedArticleByPublicArticleId($articleId);
		if (!isset($article)) $article = &$this->getPublishedArticleByArticleId($articleId);
		return $article;
	}

	/**
	 * Retrieve "article_id"s for published articles for a journal, sorted
	 * alphabetically.
	 * Note that if journalId is null, alphabetized article IDs for all
	 * journals are returned.
	 * @param $journalId int
	 * @return Array
	 */
	function &getPublishedArticleIdsAlphabetizedByJournal($journalId = null, $rangeInfo = null) {
		$articleIds = array();
		
		$result = &$this->retrieveCached(
			'SELECT a.article_id AS pub_id FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id' . (isset($journalId)?' AND a.journal_id = ?':'') . ' ORDER BY a.title',
			isset($journalId)?$journalId:false
		);
		
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$articleIds[] = $row['pub_id'];
			$result->moveNext();
		}
		$result->Close();
		return $articleIds;
	}

	/**
	 * creates and returns a published article object from a row
	 * @param $row array
	 * @return PublishedArticle object
	 */
	function &_returnPublishedArticleFromRow($row) {
		$publishedArticle = &new PublishedArticle();
		$publishedArticle->setPubId($row['pub_id']);
		$publishedArticle->setIssueId($row['issue_id']);
		$publishedArticle->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedArticle->setSeq($row['seq']);
		$publishedArticle->setViews($row['views']);
		$publishedArticle->setAccessStatus($row['access_status']);
		$publishedArticle->setPublicArticleId($row['public_article_id']);

		// Article attributes
		$this->articleDao->_articleFromRow($publishedArticle, $row);

		$publishedArticle->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		$publishedArticle->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));

		return $publishedArticle;
	}

	/**
	 * inserts a new published article into published_articles table
	 * @param PublishedArticle object
	 * @return pubId int
	 */

	function insertPublishedArticle(&$publishedArticle) {
		$this->update(
			sprintf('INSERT INTO published_articles
				(article_id, issue_id, date_published, seq, access_status, public_article_id)
				VALUES
				(?, ?, %s, ?, ?, ?)',
				$this->datetimeToDB($publishedArticle->getDatePublished())),
			array(
				$publishedArticle->getArticleId(),
				$publishedArticle->getIssueId(),
				$publishedArticle->getSeq(),
				$publishedArticle->getAccessStatus(),
				$publishedArticle->getPublicArticleId()
			)
		);

		$publishedArticle->setPubId($this->getInsertPublishedArticleId());
		return $publishedArticle->getPubId();
	}

	/**
	 * Get the ID of the last inserted published article.
	 * @return int
	 */
	function getInsertPublishedArticleId() {
		return $this->getInsertId('published_articles', 'pub_id');
	}

	/**
	 * removes an published Article by id
	 * @param pubId int
	 */
	function deletePublishedArticleById($pubId) {
		$this->update(
			'DELETE FROM published_articles WHERE pub_id = ?', $pubId
		);
	}

	/**
	 * Delete published article by article ID
	 * NOTE: This does not delete the related Article or any dependent entities
	 * @param $articleId int
	 */
	function deletePublishedArticleByArticleId($articleId) {
		return $this->update(
			'DELETE FROM published_articles WHERE article_id = ?', $articleId
		);
	}

	/**
	 * Delete published articles by section ID
	 * @param $sectionId int
	 */
	function deletePublishedArticlesBySectionId($sectionId) {
		$result = &$this->retrieve(
			'SELECT pa.article_id AS article_id FROM published_articles pa, articles a WHERE pa.article_id = a.article_id AND a.section_id = ?', $sectionId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$this->update(
				'DELETE FROM published_articles WHERE article_id = ?', $row['article_id']
			);
		}
	}

	/**
	 * Delete published articles by issue ID
	 * @param $issueId int
	 */
	function deletePublishedArticlesByIssueId($issueId) {
		return $this->update(
			'DELETE FROM published_articles WHERE issue_id = ?', $issueId
		);
	}

	/**
	 * updates a published article
	 * @param PublishedArticle object
	 */
	function updatePublishedArticle($publishedArticle) {
		$this->update(
			sprintf('UPDATE published_articles
				SET
					article_id = ?,
					issue_id = ?,
					date_published = %s,
					seq = ?,
					access_status = ?,
					public_article_id = ?
				WHERE pub_id = ?',
				$this->datetimeToDB($publishedArticle->getDatePublished())),
			array(
				$publishedArticle->getArticleId(),
				$publishedArticle->getIssueId(),
				$publishedArticle->getSeq(),
				$publishedArticle->getAccessStatus(),
				$publishedArticle->getPublicArticleId(),
				$publishedArticle->getPubId()
			)
		);
	}

	/**
	 * updates a published article field
	 * @param $pubId int
	 * @param $field string
	 * @param $value mixed
	 */
	function updatePublishedArticleField($pubId, $field, $value) {
		$this->update(
			"UPDATE published_articles SET $field = ? WHERE pub_id = ?", array($value, $pubId)
		);
	}

	/**
	 * Sequentially renumber published articles in their sequence order.
	 */
	function resequencePublishedArticles($sectionId, $issueId) {
		$result = &$this->retrieve(
			'SELECT pa.pub_id FROM published_articles pa, articles a WHERE a.section_id = ? AND a.article_id = pa.article_id AND pa.issue_id = ? ORDER BY pa.seq',
			array($sectionId, $issueId)
		);

		for ($i=1; !$result->EOF; $i++) {
			list($pubId) = $result->fields;
			$this->update(
				'UPDATE published_articles SET seq = ? WHERE pub_id = ?',
				array($i, $pubId)
			);

			$result->moveNext();
		}

		$result->close();
	}

	/**
	 * Retrieve all authors from published articles
	 * @param $issueId int
	 * @return $authors array Author Objects
	 */
	function getPublishedArticleAuthors($issueId) {
		$authors = array();
		$result = &$this->retrieve(
			'SELECT aa.* FROM article_authors aa, published_articles pa WHERE aa.article_id = pa.article_id AND pa.issue_id = ? ORDER BY pa.issue_id', $issueId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$author = &new Author();
			$author->setAuthorId($row['author_id']);
			$author->setArticleId($row['article_id']);
			$author->setFirstName($row['first_name']);
			$author->setMiddleName($row['middle_name']);
			$author->setLastName($row['last_name']);
			$author->setAffiliation($row['affiliation']);
			$author->setEmail($row['email']);
			$author->setBiography($row['biography']);
			$author->setPrimaryContact($row['primary_contact']);
			$author->setSequence($row['seq']);
			$authors[] = $author;
			$result->moveNext();
		}
		$result->Close();

		return $authors;
	}

	/**
	 * Increment the views count for a galley.
	 * @param $articleId int
	 */
	function incrementViewsByArticleId($articleId) {
		return $this->update(
			'UPDATE published_articles SET views = views + 1 WHERE article_id = ?',
			$articleId
		);
	}

	/**
	 * Checks if public identifier exists
	 * @param $publicIssueId string
	 * @return boolean
	 */
	function publicArticleIdExists($publicArticleId, $articleId, $journalId) {
		$result = &$this->retrieve(
			'SELECT COUNT(*) FROM published_articles pa, articles a WHERE pa.article_id = a.article_id AND a.journal_id = ? AND pa.public_article_id = ? AND pa.article_id <> ?',
			array($journalId, $publicArticleId, $articleId)
		);
		return $result->fields[0] ? true : false;
	}
 }

?>
