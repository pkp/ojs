<?php

/**
 * @file classes/article/PublishedArticleDAO.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedArticleDAO
 * @ingroup article
 * @see PublishedArticle
 *
 * @brief Operations for retrieving and modifying PublishedArticle objects.
 */

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
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$publishedArticles = array();

		$params = array(
			(int) $issueId,
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			(int) $issueId
		);

		$sql = 'SELECT DISTINCT
				pa.*,
				a.*,
				SUBSTRING(COALESCE(stl.setting_value, stpl.setting_value) FROM 1 FOR 255) AS section_title,
				SUBSTRING(COALESCE(sal.setting_value, sapl.setting_value) FROM 1 FOR 255) AS section_abbrev,
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
			(int) $journalId
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
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
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
			WHERE	i.published = 1
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

		$primaryLocale = AppLocale::getPrimaryLocale();
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$publishedArticles = array();

		$result =& $this->retrieve(
			'SELECT DISTINCT
				pa.*,
				a.*,
				SUBSTRING(COALESCE(stl.setting_value, stpl.setting_value) FROM 1 FOR 255) AS section_title,
				SUBSTRING(COALESCE(sal.setting_value, sapl.setting_value) FROM 1 FOR 255) AS section_abbrev,
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
				(int) $issueId,
				'title',
				$primaryLocale,
				'title',
				$locale,
				'abbrev',
				$primaryLocale,
				'abbrev',
				$locale,
				(int) $issueId
			)
		);

		$currSectionId = 0;
		while (!$result->EOF) {
			$row =& $result->GetRowAssoc(false);
			$publishedArticle =& $this->_returnPublishedArticleFromRow($row);
			if ($publishedArticle->getSectionId() != $currSectionId && !isset($publishedArticles[$publishedArticle->getSectionId()])) {
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
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
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
				(int) $sectionId,
				(int) $issueId
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
	 * @param $publishedArticleId int
	 * @param $simple boolean Whether or not to skip fetching dependent objects; default false
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleById($publishedArticleId, $simple = false) {
		$result =& $this->retrieve(
			'SELECT * FROM published_articles WHERE published_article_id = ?', (int) $publishedArticleId
		);
		$row = $result->GetRowAssoc(false);

		$publishedArticle = new PublishedArticle();
		$publishedArticle->setPublishedArticleId($row['published_article_id']);
		$publishedArticle->setId($row['article_id']);
		$publishedArticle->setIssueId($row['issue_id']);
		$publishedArticle->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedArticle->setSeq($row['seq']);
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

		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			(int) $articleId
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
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $journalId int
	 * @param $useCache boolean optional
	 * @return PublishedArticle object
	 */
	function &getPublishedArticleByPubId($pubIdType, $pubId, $journalId = null, $useCache = false) {
		if ($useCache && $pubIdType == 'publisher-id') {
			$cache =& $this->_getPublishedArticleCache();
			$returner = $cache->get($pubId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		$publishedArticle = null;
		if (!empty($pubId)) {
			$publishedArticles =& $this->getBySetting('pub-id::'.$pubIdType, $pubId, $journalId);
			if (!empty($publishedArticles)) {
				assert(count($publishedArticles) == 1);
				$publishedArticle =& $publishedArticles[0];
			}
		}
		return $publishedArticle;
	}

	/**
	 * Find published articles by querying article settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $journalId int optional
	 * @return array The articles identified by setting.
	 */
	function &getBySetting($settingName, $settingValue, $journalId = null) {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();

		$params = array(
			'title',
			$primaryLocale,
			'title',
			$locale,
			'abbrev',
			$primaryLocale,
			'abbrev',
			$locale,
			$settingName
		);

		$sql = 'SELECT	pa.*,
				a.*,
				COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
				COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev
			FROM	published_articles pa
				INNER JOIN articles a ON pa.article_id = a.article_id
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN section_settings stpl ON (s.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
				LEFT JOIN section_settings stl ON (s.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN section_settings sapl ON (s.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
				LEFT JOIN section_settings sal ON (s.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?) ';
		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN article_settings ast ON a.article_id = ast.article_id AND ast.setting_name = ?
				WHERE	(ast.setting_value IS NULL OR ast.setting_value = \'\')';
		} else {
			$params[] = (string) $settingValue; // Bug #8853
			$sql .= 'INNER JOIN article_settings ast ON a.article_id = ast.article_id
				WHERE	ast.setting_name = ? AND ast.setting_value = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND a.journal_id = ?';
		}
		$sql .= ' ORDER BY pa.issue_id, a.article_id';
		$result =& $this->retrieve($sql, $params);

		$publishedArticles = array();
		while (!$result->EOF) {
			$publishedArticles[] =& $this->_returnPublishedArticleFromRow($result->GetRowAssoc(false));
			$result->moveNext();
		}
		$result->Close();

		return $publishedArticles;
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
		$article =& $this->getPublishedArticleByPubId('publisher-id', $articleId, (int) $journalId, $useCache);
		if (!isset($article) && ctype_digit("$articleId")) $article =& $this->getPublishedArticleByArticleId((int) $articleId, (int) $journalId, $useCache);
		return $article;
	}

	/**
	 * Retrieve "article_id"s for published articles for a journal, sorted
	 * alphabetically.
	 * Note that if journalId is null, alphabetized article IDs for all
	 * enabled journals are returned.
	 * @param $journalId int Optional journal ID to use in restricting results
	 * @param $useCache boolean optional
	 * @return Array
	 */
	function &getPublishedArticleIdsAlphabetizedByJournal($journalId = null, $useCache = true) {
		$params = array(
			'cleanTitle', AppLocale::getLocale(),
			'cleanTitle'
		);
		if (isset($journalId)) $params[] = (int) $journalId;

		$articleIds = array();
		$functionName = $useCache?'retrieveCached':'retrieve';
		$result =& $this->$functionName(
			'SELECT	a.article_id AS pub_id,
				COALESCE(atl.setting_value, atpl.setting_value) AS article_title
			FROM	published_articles pa,
				issues i,
				articles a
				JOIN journals j ON (a.journal_id = j.journal_id)
				LEFT JOIN sections s ON s.section_id = a.section_id
				LEFT JOIN article_settings atl ON (a.article_id = atl.article_id AND atl.setting_name = ? AND atl.locale = ?)
				LEFT JOIN article_settings atpl ON (a.article_id = atpl.article_id AND atpl.setting_name = ? AND atpl.locale = a.locale)
			WHERE	pa.article_id = a.article_id
				AND i.issue_id = pa.issue_id
				AND i.published = 1
				AND s.section_id IS NOT NULL' .
				(isset($journalId)?' AND a.journal_id = ?':' AND j.enabled = 1') . ' ORDER BY article_title',
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
			isset($journalId)?(int) $journalId:false
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
	 * Retrieve "article_id"s for published articles for a journal section, sorted
	 * by reverse publish date.
	 * @param $sectionId int
	 * @return Array
	 */
	function getPublishedArticleIdsBySection($sectionId, $useCache = true) {
		$articleIds = array();
		$functionName = $useCache?'retrieveCached':'retrieve';
		$result =& $this->$functionName(
			'SELECT a.article_id FROM published_articles pa, articles a, issues i WHERE pa.issue_id = i.issue_id AND i.published = 1 AND pa.article_id = a.article_id AND a.section_id = ? ORDER BY pa.date_published DESC',
			(int) $sectionId
		);

		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$articleIds[] = $row['article_id'];
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
		$publishedArticle->setPublishedArticleId($row['published_article_id']);
		$publishedArticle->setIssueId($row['issue_id']);
		$publishedArticle->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedArticle->setSeq($row['seq']);
		$publishedArticle->setAccessStatus($row['access_status']);

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
				(article_id, issue_id, date_published, seq, access_status)
				VALUES
				(?, ?, %s, ?, ?)',
				$this->datetimeToDB($publishedArticle->getDatePublished())),
			array(
				(int) $publishedArticle->getId(),
				(int) $publishedArticle->getIssueId(),
				$publishedArticle->getSeq(),
				$publishedArticle->getAccessStatus()
			)
		);

		$publishedArticle->setPublishedArticleId($this->getInsertPublishedArticleId());
		return $publishedArticle->getPublishedArticleId();
	}

	/**
	 * Get the ID of the last inserted published article.
	 * @return int
	 */
	function getInsertPublishedArticleId() {
		return $this->getInsertId('published_articles', 'published_article_id');
	}

	/**
	 * removes an published Article by id
	 * @param $publishedArticleId int
	 */
	function deletePublishedArticleById($publishedArticleId) {
		$this->update(
			'DELETE FROM published_articles WHERE published_article_id = ?', (int) $publishedArticleId
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
			'DELETE FROM published_articles WHERE article_id = ?', (int) $articleId
		);
		$this->flushCache();
	}

	/**
	 * Delete published articles by section ID
	 * @param $sectionId int
	 */
	function deletePublishedArticlesBySectionId($sectionId) {
		$result =& $this->retrieve(
			'SELECT pa.article_id AS article_id FROM published_articles pa, articles a WHERE pa.article_id = a.article_id AND a.section_id = ?', (int) $sectionId
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
			'DELETE FROM published_articles WHERE issue_id = ?', (int) $issueId
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
					access_status = ?
				WHERE published_article_id = ?',
				$this->datetimeToDB($publishedArticle->getDatePublished())),
			array(
				(int) $publishedArticle->getId(),
				(int) $publishedArticle->getIssueId(),
				$publishedArticle->getSeq(),
				$publishedArticle->getAccessStatus(),
				(int) $publishedArticle->getPublishedArticleId()
			)
		);

		$this->flushCache();
	}

	/**
	 * updates a published article field
	 * @param $publishedArticleId int
	 * @param $field string
	 * @param $value mixed
	 */
	function updatePublishedArticleField($publishedArticleId, $field, $value) {
		$this->update(
			"UPDATE published_articles SET $field = ? WHERE published_article_id = ?", array($value, (int) $publishedArticleId)
		);

		$this->flushCache();
	}

	/**
	 * Sequentially renumber published articles in their sequence order.
	 */
	function resequencePublishedArticles($sectionId, $issueId) {
		$result =& $this->retrieve(
			'SELECT pa.published_article_id FROM published_articles pa, articles a WHERE a.section_id = ? AND a.article_id = pa.article_id AND pa.issue_id = ? ORDER BY pa.seq',
			array((int) $sectionId, (int) $issueId)
		);

		for ($i=1; !$result->EOF; $i++) {
			list($publishedArticleId) = $result->fields;
			$this->update(
				'UPDATE published_articles SET seq = ? WHERE published_article_id = ?',
				array($i, $publishedArticleId)
			);

			$result->moveNext();
		}

		$result->close();
		unset($result);

		$this->flushCache();
	}

	/**
	 * Increment the views count for a galley.
	 * @param $articleId int
	 */
	function incrementViewsByArticleId($articleId) {
		return $this->update(
			'UPDATE published_articles SET views = views + 1 WHERE article_id = ?',
			(int) $articleId
		);
	}

	/**
	 * Return years of oldest/youngest published article on site or within a journal
	 * @param $journalId int
	 * @return array
	 */
	function getArticleYearRange($journalId = null) {
		$result =& $this->retrieve(
			'SELECT MAX(pa.date_published), MIN(pa.date_published) FROM published_articles pa, articles a WHERE pa.article_id = a.article_id' . (isset($journalId)?' AND a.journal_id = ?':''),
			isset($journalId)?(int) $journalId:false
		);
		$returner = array($result->fields[0], $result->fields[1]);

		$result->Close();
		unset($result);

		return $returner;
	}
}

?>
