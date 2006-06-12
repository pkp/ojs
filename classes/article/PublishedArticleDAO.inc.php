<?php

/**
 * PublishedArticleDAO.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
		$this->authorDao = &DAORegistry::getDAO('AuthorDAO');
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
				'SELECT DISTINCT pa.*, a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2, COALESCE(o.seq, s.seq) AS section_seq, pa.seq FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id LEFT JOIN custom_section_orders o ON (a.section_id = o.section_id AND o.issue_id = ?) WHERE pa.article_id = a.article_id AND pa.issue_id = ? AND a.status <> ' . STATUS_ARCHIVED . ' ORDER BY section_seq ASC, pa.seq ASC', array($issueId, $issueId), $limit
			);
		} else {
			$result = &$this->retrieve(
				'SELECT DISTINCT pa.*, a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2, COALESCE(o.seq, s.seq) AS section_seq, pa.seq FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id LEFT JOIN custom_section_orders o ON (a.section_id = o.section_id AND o.issue_id = ?) WHERE pa.article_id = a.article_id AND pa.issue_id = ? AND a.status <> ' . STATUS_ARCHIVED . ' ORDER BY section_seq ASC, pa.seq ASC', array($issueId, $issueId)
			);
		}

		while (!$result->EOF) {
			$publishedArticles[] = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}

		$result->Close();
		unset($result);

		return $publishedArticles;
	}

	/**
	 * Retrieve a count of published articles in a journal.
	 */
	function getPublishedArticleCountByJournalId($journalId) {
		$result =& $this->retrieve(
			'SELECT count(*) FROM published_articles pa, articles a WHERE pa.article_id = a.article_id AND a.journal_id = ? AND a.status <> ' . STATUS_ARCHIVED,
			$journalId
		);
		list($count) = $result->fields;
		$result->Close();
		return $count;
	}

	/**
	 * Retrieve all published articles in a journal.
	 * @param $journalId int
	 * @param $rangeInfo object
	 */
	function &getPublishedArticlesByJournalId($journalId, $rangeInfo = null) {
		$result =& $this->retrieveRange(
			'SELECT pa.*, a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2 FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND a.journal_id = ? AND a.status <> ' . STATUS_ARCHIVED,
			$journalId,
			$rangeInfo
		);

		$returner =& new DAOResultFactory($result, $this, '_returnPublishedArticleFromRow');
		return $returner;
	}
	
	/**
	 * Retrieve Published Articles by issue id
	 * @param $issueId int
	 * @return PublishedArticle objects array
	 */
	function &getPublishedArticlesInSections($issueId) {
		$publishedArticles = array();

		$result = &$this->retrieve(
			'SELECT DISTINCT pa.*, a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2, s.abstracts_disabled AS abstracts_disabled, s.hide_title AS section_hide_title, COALESCE(o.seq, s.seq) AS section_seq, pa.seq FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id LEFT JOIN custom_section_orders o ON (a.section_id = o.section_id AND o.issue_id = ?) WHERE pa.article_id = a.article_id AND pa.issue_id = ? AND a.status <> ' . STATUS_ARCHIVED . ' ORDER BY section_seq ASC, pa.seq ASC', array($issueId, $issueId)
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
		unset($result);

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
			'SELECT pa.*, a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2 FROM published_articles pa, articles a, sections s WHERE a.section_id = s.section_id AND pa.article_id = a.article_id AND a.section_id = ? AND pa.issue_id = ? AND a.status <> ' . STATUS_ARCHIVED . ' ORDER BY pa.seq ASC', array($sectionId, $issueId)
		);

		$currSectionId = 0;
		while (!$result->EOF) {
			$publishedArticle = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
			$publishedArticles[] = $publishedArticle;
			$result->moveNext();
		}

		$result->Close();
		unset($result);

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
		unset($result);

		return $publishedArticle;
	}

	/**
	 * Retrieve published article by article id
	 * @param $articleId int
	 * @param $journalId int optional
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByArticleId($articleId, $journalId = null) {
		$result = &$this->retrieve(
			'SELECT pa.*, a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2 FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND a.article_id = ?' . (isset($journalId)?' AND a.journal_id = ?':''),
			isset($journalId)?
				array($articleId, $journalId):
				$articleId
		);

		$publishedArticle = null;
		if ($result->RecordCount() != 0) {
			$publishedArticle = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $publishedArticle;
	}

	/**
	 * Retrieve published article by public article id
	 * @param $journalId int
	 * @param $publicArticleId string
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByPublicArticleId($journalId, $publicArticleId) {
		$result = &$this->retrieve(
			'SELECT pa.*, a.*, s.title AS section_title, s.title_alt1 AS section_title_alt1, s.title_alt2 AS section_title_alt2, s.abbrev AS section_abbrev, s.abbrev_alt1 AS section_abbrev_alt1, s.abbrev_alt2 AS section_abbrev_alt2 FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id AND pa.public_article_id = ? AND a.journal_id = ?',
			array($publicArticleId, $journalId)
		);

		$publishedArticle = null;
		if ($result->RecordCount() != 0) {
			$publishedArticle = &$this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $publishedArticle;
	}

	/**
	 * Retrieve published article by public article id or, failing that,
	 * internal article ID; public article ID takes precedence.
	 * @param $journalId int
	 * @param $articleId string
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByBestArticleId($journalId, $articleId) {
		$article = &$this->getPublishedArticleByPublicArticleId($journalId, $articleId);
		if (!isset($article)) $article = &$this->getPublishedArticleByArticleId((int) $articleId, $journalId);
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
		unset($result);

		return $articleIds;
	}

	/**
	 * Retrieve "article_id"s for published articles for a journal, sorted
	 * by reverse publish date.
	 * Note that if journalId is null, alphabetized article IDs for all
	 * journals are returned.
	 * @param $journalId int
	 * @return Array
	 */
	function &getPublishedArticleIdsByJournal($journalId = null, $rangeInfo = null) {
		$articleIds = array();
		
		$result = &$this->retrieveCached(
			'SELECT a.article_id AS pub_id FROM published_articles pa, articles a LEFT JOIN sections s ON s.section_id = a.section_id WHERE pa.article_id = a.article_id' . (isset($journalId)?' AND a.journal_id = ?':'') . ' ORDER BY pa.date_published DESC',
			isset($journalId)?$journalId:false
		);
		
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$articleIds[] = $row['pub_id'];
			$result->moveNext();
		}

		$result->Close();
		unset($result);

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

		HookRegistry::call('PublishedArticleDAO::_returnPublishedArticleFromRow', array(&$publishedArticle, &$row));

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

		$result->Close();
		unset($result);
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
		unset($result);
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
		unset($result);

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
		$returner = $result->fields[0] ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
