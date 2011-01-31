<?php

/**
 * @file classes/article/PublishedArticleDAO.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedArticleDAO
 * @ingroup article
 * @see PublishedArticle
 *
 * @brief Operations for retrieving and modifying PublishedArticle objects.
 */

// $Id$


import('classes.article.PublishedArticle');

class PublishedArticleDAO extends DAO {
	var $articleDao;
	var $authorDao;
	var $galleyDao;
	var $suppFileDao;

	var $articleCache;
	var $articlesInSectionsCache;

	function _articleCacheMiss(&$cache, $id) {
		$publishedArticle =& $this->getPublishedArticleByBestArticleId(null, $id, null);
		$cache->setCache($id, $publishedArticle);
		return $publishedArticle;
	}

	function &_getPublishedArticleCache() {
		if (!isset($this->articleCache)) {
			$cacheManager =& CacheManager::getManager();
			$this->articleCache =& $cacheManager->getObjectCache('publishedArticles', 0, array(&$this, '_articleCacheMiss'));
		}
		return $this->articleCache;
	}

	function _articlesInSectionsCacheMiss(&$cache, $id) {
		$articlesInSections =& $this->getPublishedArticlesInSections($id, null);
		$cache->setCache($id, $articlesInSections);
		return $articlesInSections;
	}

	function &_getArticlesInSectionsCache() {
		if (!isset($this->articlesInSectionsCache)) {
			$cacheManager =& CacheManager::getManager();
			$this->articlesInSectionsCache =& $cacheManager->getObjectCache('articlesInSections', 0, array(&$this, '_articlesInSectionsCacheMiss'));
		}
		return $this->articlesInSectionsCache;
	}
 	/**
	 * Constructor.
	 */
	function PublishedArticleDAO() {
		parent::DAO();
		$this->articleDao =& DAORegistry::getDAO('ArticleDAO');
		$this->authorDao =& DAORegistry::getDAO('AuthorDAO');
		$this->galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO');
		$this->suppFileDao =& DAORegistry::getDAO('SuppFileDAO');
	}

	/**
	 * Retrieve Published Articles by issue id.  Limit provides number of records to retrieve
	 * @param $issueId int
	 * @return PublishedArticle objects array
	 */
	function &getPublishedArticles($issueId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$publishedArticles = array();

		$params = array(
			$issueId,
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$issueId
		);

		$sql = 'SELECT DISTINCT
				pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				COALESCE(o.seq, s.seq) AS section_seq,
				pa.seq
			FROM	published_articles pa,
				articles a LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN custom_section_orders o ON (a.section_id = o.section_id AND o.issue_id = ?)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	pa.article_id = a.article_id
				AND pa.issue_id = ?
				AND a.status <> ' . STATUS_ARCHIVED . '
			ORDER BY section_seq ASC, pa.seq ASC';

		$result =& $this->retrieve($sql, $params);

		while (!$result->EOF) {
			$publishedArticles[] =& $this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
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
	 * @param $reverse boolean Whether to reverse the sort order
	 * @return object
	 */
	function &getPublishedArticlesByJournalId($journalId = null, $rangeInfo = null, $reverse = false) {
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
			$locale
		);
		if ($journalId !== null) $params[] = (int) $journalId;
		$result =& $this->retrieveRange(
			'SELECT	pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	published_articles pa
				LEFT JOIN articles a ON pa.article_id = a.article_id
				LEFT JOIN issues i ON pa.issue_id = i.issue_id
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE 	i.published = 1
				' . ($journalId !== null?'AND a.journal_id = ?':'') . '
				AND a.status <> ' . STATUS_ARCHIVED . '
			ORDER BY date_published '. ($reverse?'DESC':'ASC'),
			$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnPublishedArticleFromRow');
		return $returner;
	}

	/**
	 * Retrieve all published articles in a journal by DOI.
	 * @param $doi string
	 * @param $journalId int
	 * @param $rangeInfo object
	 * @return object
	 */
	function &getPublishedArticlesByDOI($doi, $journalId = null, $rangeInfo = null) {
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
			$doi
		);
		if ($journalId !== null) $params[] = (int) $journalId;
		$result =& $this->retrieveRange(
			'SELECT	pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	published_articles pa
				LEFT JOIN articles a ON pa.article_id = a.article_id
				LEFT JOIN issues i ON pa.issue_id = i.issue_id
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE 	i.published = 1
				AND a.doi = ?
				' . ($journalId !== null?'AND a.journal_id = ?':'') . '
				AND a.status <> ' . STATUS_ARCHIVED . '
			ORDER BY date_published '. ($reverse?'DESC':'ASC'),
			$params,
			$rangeInfo
		);

		$returner = new DAOResultFactory($result, $this, '_returnPublishedArticleFromRow');
		return $returner;
	}

	/**
	 * Retrieve Published Articles by issue id
	 * @param $issueId int
	 * @param $useCache boolean optional
	 * @return PublishedArticle objects array
	 */
	function &getPublishedArticlesInSections($issueId, $useCache = false) {
		if ($useCache) {
			$cache =& $this->_getArticlesInSectionsCache();
			$returner = $cache->get($issueId);
			return $returner;
		}

		$primaryLocale = Locale::getPrimaryLocale();
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$publishedArticles = array();

		$result =& $this->retrieve(
			'SELECT DISTINCT
				pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev,
				s.abstracts_not_required AS abstracts_not_required,
				s.hide_title AS section_hide_title,
				s.hide_author AS section_hide_author,
				COALESCE(o.seq, s.seq) AS section_seq,
				pa.seq
			FROM	published_articles pa,
				articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN custom_section_orders o ON (a.section_id = o.section_id AND o.issue_id = ?)
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	pa.article_id = a.article_id
				AND pa.issue_id = ?
				AND a.status <> ' . STATUS_ARCHIVED . '
			ORDER BY section_seq ASC, pa.seq ASC',
			array(
				$issueId,
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$issueId
			)
		);

		$currSectionId = 0;
		while (!$result->EOF) {
			$row =& $result->GetRowAssoc(false);
			$publishedArticle =& $this->_returnPublishedArticleFromRow($row);
			if ($publishedArticle->getSectionId() != $currSectionId) {
				$currSectionId = $publishedArticle->getSectionId();
				$publishedArticles[$currSectionId] = array(
					'articles'=> array(),
					'title' => '',
					'abstractsNotRequired' => $row['abstracts_not_required'],
					'hideAuthor' => $row['section_hide_author']
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
	 * @param $issueId int
	 * @param $simple boolean Whether or not to skip fetching dependent objects; default false
	 * @return PublishedArticle objects array
	 */
	function &getPublishedArticlesBySectionId($sectionId, $issueId, $simple = false) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();
		$func = $simple?'_returnSimplePublishedArticleFromRow':'_returnPublishedArticleFromRow';
		$publishedArticles = array();

		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	published_articles pa,
				articles a,
				sections s
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	a.section_id = s.section_id
				AND pa.article_id = a.article_id
				AND a.section_id = ?
				AND pa.issue_id = ?
				AND a.status <> ' . STATUS_ARCHIVED . '
			ORDER BY pa.seq ASC',
			array(
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				$sectionId,
				$issueId
			)
		);

		$currSectionId = 0;
		while (!$result->EOF) {
			$publishedArticle =& $this->$func($result->GetRowAssoc(false));
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
	 * @param $simple boolean Whether or not to skip fetching dependent objects; default false
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleById($pubId, $simple = false) {
		$result =& $this->retrieve(
			'SELECT * FROM published_articles WHERE pub_id = ?', $pubId
		);
		$row = $result->GetRowAssoc(false);

		$publishedArticle = new PublishedArticle();
		$publishedArticle->setPubId($row['pub_id']);
		$publishedArticle->setId($row['article_id']);
		$publishedArticle->setIssueId($row['issue_id']);
		$publishedArticle->setPublicArticleId($row['public_article_id']);
		$publishedArticle->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedArticle->setSeq($row['seq']);
		$publishedArticle->setViews($row['views']);
		$publishedArticle->setAccessStatus($row['access_status']);

		if (!$simple) $publishedArticle->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));

		$result->Close();
		unset($result);

		return $publishedArticle;
	}

	/**
	 * Retrieve published article by article id
	 * @param $articleId int
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByArticleId($articleId, $journalId = null, $useCache = false) {
		if ($useCache) {
			$cache =& $this->_getPublishedArticleCache();
			$returner = $cache->get($articleId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

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
		if ($journalId) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	published_articles pa,
				articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	pa.article_id = a.article_id
				AND a.article_id = ?' .
				($journalId?' AND a.journal_id = ?':''),
			$params
		);

		$publishedArticle = null;
		if ($result->RecordCount() != 0) {
			$publishedArticle =& $this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $publishedArticle;
	}

	/**
	 * Retrieve published article by public article id
	 * @param $journalId int
	 * @param $publicArticleId string
	 * @param $useCache boolean optional
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByPublicArticleId($journalId, $publicArticleId, $useCache = false) {
		if ($useCache) {
			$cache =& $this->_getPublishedArticleCache();
			$returner = $cache->get($publicArticleId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

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
			$publicArticleId
		);
		if ($journalId) $params[] = (int) $journalId;

		$result =& $this->retrieve(
			'SELECT	pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	published_articles pa,
				articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)
			WHERE	pa.article_id = a.article_id
				AND pa.public_article_id = ?
				' . ($journalId?' AND a.journal_id = ?':''),
			$params
		);

		$publishedArticle = null;
		if ($result->RecordCount() != 0) {
			$publishedArticle =& $this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
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
	 * @param $useCache boolean optional
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByBestArticleId($journalId, $articleId, $useCache = false) {
		$article =& $this->getPublishedArticleByPublicArticleId((int) $journalId, $articleId, $useCache);
		if (!isset($article)) $article =& $this->getPublishedArticleByArticleId((int) $articleId, (int) $journalId, $useCache);
		return $article;
	}

	/**
	 * Retrieve "article_id"s for published articles for a journal, sorted
	 * alphabetically.
	 * Note that if journalId is null, alphabetized article IDs for all
	 * journals are returned.
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return Array
	 */
	function &getPublishedArticleIdsAlphabetizedByJournal($journalId = null, $useCache = true) {
		$params = array(
			'cleanTitle', Locale::getLocale(),
			'cleanTitle'
		);
		if (isset($journalId)) $params[] = $journalId;

		$articleIds = array();
		$functionName = $useCache?'retrieveCached':'retrieve';
		$result =& $this->$functionName(
			'SELECT	a.article_id AS pub_id,
				COALESCE(atl.setting_value, atpl.setting_value) AS article_title
			FROM	published_articles pa,
				issues i,
				articles a
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN article_settings atl ON (a.article_id = atl.article_id AND atl.setting_name = ? AND atl.locale = ?)
				LEFT JOIN article_settings atpl ON (a.article_id = atpl.article_id AND atpl.setting_name = ? AND atpl.locale = a.locale)
			WHERE	pa.article_id = a.article_id
				AND i.issue_id = pa.issue_id
				AND i.published = 1
				AND s.section_id IS NOT NULL' .
				(isset($journalId)?' AND a.journal_id = ?':'') . ' ORDER BY article_title',
			$params
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
	function &getPublishedArticleIdsByJournal($journalId = null, $useCache = true) {
		$articleIds = array();
		$functionName = $useCache?'retrieveCached':'retrieve';
		$result =& $this->$functionName(
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
	 * creates and returns a published article object from a row, including all supp files etc.
	 * @param $row array
	 * @param $callHooks boolean Whether or not to call hooks
	 * @return PublishedArticle object
	 */
	function &_returnPublishedArticleFromRow($row, $callHooks = true) {
		$publishedArticle = new PublishedArticle();
		$publishedArticle->setPubId($row['pub_id']);
		$publishedArticle->setIssueId($row['issue_id']);
		$publishedArticle->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedArticle->setSeq($row['seq']);
		$publishedArticle->setViews($row['views']);
		$publishedArticle->setAccessStatus($row['access_status']);
		$publishedArticle->setPublicArticleId($row['public_article_id']);

		$publishedArticle->setGalleys($this->galleyDao->getGalleysByArticle($row['article_id']));

		// Article attributes
		$this->articleDao->_articleFromRow($publishedArticle, $row);

		$publishedArticle->setSuppFiles($this->suppFileDao->getSuppFilesByArticle($row['article_id']));

		if ($callHooks) HookRegistry::call('PublishedArticleDAO::_returnPublishedArticleFromRow', array(&$publishedArticle, &$row));
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
				$publishedArticle->getId(),
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

		$this->flushCache();
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
		$this->flushCache();
	}

	/**
	 * Delete published articles by section ID
	 * @param $sectionId int
	 */
	function deletePublishedArticlesBySectionId($sectionId) {
		$result =& $this->retrieve(
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

		$this->flushCache();
	}

	/**
	 * Delete published articles by issue ID
	 * @param $issueId int
	 */
	function deletePublishedArticlesByIssueId($issueId) {
		$this->update(
			'DELETE FROM published_articles WHERE issue_id = ?', $issueId
		);

		$this->flushCache();
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
				$publishedArticle->getId(),
				$publishedArticle->getIssueId(),
				$publishedArticle->getSeq(),
				$publishedArticle->getAccessStatus(),
				$publishedArticle->getPublicArticleId(),
				$publishedArticle->getPubId()
			)
		);

		$this->flushCache();
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

		$this->flushCache();
	}

	/**
	 * Sequentially renumber published articles in their sequence order.
	 */
	function resequencePublishedArticles($sectionId, $issueId) {
		$result =& $this->retrieve(
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

		$this->flushCache();
	}

	/**
	 * Retrieve all authors from published articles
	 * @param $issueId int
	 * @return $authors array Author Objects
	 */
	function getPublishedArticleAuthors($issueId) {
		$primaryLocale = Locale::getPrimaryLocale();
		$locale = Locale::getLocale();

		$authors = array();
		$result =& $this->retrieve(
			'SELECT	aa.*,
				aspl.setting_value AS affiliation_pl,
				asl.setting_value AS affiliation_l
			FROM	authors aa
				LEFT JOIN published_articles pa ON (pa.article_id = aa.submission_id)
				LEFT JOIN author_settings aspl ON (aspl.author_id = aa.author_id AND aspl.setting_name = ? AND aspl.locale = ?)
				LEFT JOIN author_settings asl ON (asl.author_id = aa.author_id AND asl.setting_name = ? AND asl.locale = ?)
			WHERE	pa.issue_id = ? ORDER BY pa.issue_id',
			array(
				'affiliation', $primaryLocale,
				'affiliation', $locale,
				(int) $issueId
			)
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$author = new Author();
			$author->setId($row['author_id']);
			$author->setSubmissionId($row['article_id']);
			$author->setFirstName($row['first_name']);
			$author->setMiddleName($row['middle_name']);
			$author->setLastName($row['last_name']);
			$author->setAffiliation($row['affiliation_pl'], $primaryLocale);
			$author->setAffiliation($row['affiliation_l'], $locale);
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
		$result =& $this->retrieve(
			'SELECT COUNT(*) FROM published_articles pa, articles a WHERE pa.article_id = a.article_id AND a.journal_id = ? AND pa.public_article_id = ? AND pa.article_id <> ?',
			array($journalId, $publicArticleId, $articleId)
		);
		$returner = $result->fields[0] ? true : false;

		$result->Close();
		unset($result);

		return $returner;
	}

	/**
	 * Return years of oldest/youngest published article on site or within a journal
	 * @param $journalId int
	 * @return array
	 */
	function getArticleYearRange($journalId = null) {
		$result =& $this->retrieve(
			'SELECT MAX(pa.date_published), MIN(pa.date_published) FROM published_articles pa, articles a WHERE pa.article_id = a.article_id' . (isset($journalId)?' AND a.journal_id = ?':''),
			isset($journalId)?$journalId:false
		);
		$returner = array($result->fields[0], $result->fields[1]);

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
