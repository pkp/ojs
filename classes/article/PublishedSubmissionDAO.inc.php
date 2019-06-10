<?php

/**
 * @file classes/article/PublishedSubmissionDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublishedSubmissionDAO
 * @ingroup article
 * @see PublishedSubmission
 *
 * @brief Operations for retrieving and modifying PublishedSubmission objects.
 */

import('classes.article.PublishedSubmission');
import('classes.article.ArticleDAO');

class PublishedSubmissionDAO extends ArticleDAO {
	/** @var ArticleGalleyDAO */
	var $galleyDao;

	/** @var GenericCache */
	var $articleCache;

	/** @var GenericCache */
	var $articlesInSectionsCache;

 	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
		$this->galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
	}

	/**
	 * Handle an article cache miss
	 * @param $cache GenericCache
	 * @param $id mixed Article ID (potentially non-numeric)
	 * @return PublishedSubmission
	 */
	function _articleCacheMiss($cache, $id) {
		$publishedSubmission = $this->getPublishedSubmissionByBestArticleId(null, $id, null);
		$cache->setCache($id, $publishedSubmission);
		return $publishedSubmission;
	}

	/**
	 * Get a the published submission cache
	 * @return GenericCache
	 */
	function _getPublishedSubmissionCache() {
		if (!isset($this->articleCache)) {
			$cacheManager = CacheManager::getManager();
			$this->articleCache = $cacheManager->getObjectCache('publishedSubmissions', 0, array($this, '_articleCacheMiss'));
		}
		return $this->articleCache;
	}

	/**
	 * Handle a cache miss from the "articles in sections" cache
	 * @param $cache GenericCache
	 * @param $id int Issue ID
	 * @return array
	 */
	function _articlesInSectionsCacheMiss($cache, $id) {
		$articlesInSections = $this->getPublishedSubmissionsInSections($id, null);
		$cache->setCache($id, $articlesInSections);
		return $articlesInSections;
	}

	/**
	 * Get a the "articles in sections" article cache
	 * @return GenericCache
	 */
	function _getArticlesInSectionsCache() {
		if (!isset($this->articlesInSectionsCache)) {
			$cacheManager = CacheManager::getManager();
			$this->articlesInSectionsCache = $cacheManager->getObjectCache('articlesInSections', 0, array($this, '_articlesInSectionsCacheMiss'));
		}
		return $this->articlesInSectionsCache;
	}

	/**
	 * Retrieve Published Articles by issue id.  Limit provides number of records to retrieve
	 * @param $issueId int
	 * @return PublishedSubmission objects array
	 */
	function getPublishedSubmissions($issueId) {
		$params = array_merge(
			$this->getFetchParameters(),
			array(
				(int) $issueId,
				(int) $issueId
			)
		);

		$sql = 'SELECT DISTINCT
				ps.*,
				s.*,
				COALESCE(o.seq, sec.seq) AS section_seq,
				ps.seq,
				' . $this->getFetchColumns() . '
			FROM	published_submissions ps
				JOIN submissions s ON ps.submission_id = s.submission_id
				JOIN sections sec ON (s.section_id = sec.section_id)
				' . $this->getFetchJoins() . '
				LEFT JOIN custom_section_orders o ON (s.section_id = o.section_id AND o.issue_id = ?)
			WHERE ps.is_current_submission_version=1 AND ps.issue_id = ?
				AND s.status <> ' . STATUS_DECLINED . '
			ORDER BY section_seq ASC, ps.seq ASC';

		$result = $this->retrieve($sql, $params);

		$publishedSubmissions = array();
		while (!$result->EOF) {
			$publishedSubmissions[] = $this->_fromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		return $publishedSubmissions;
	}

	/**
	 * Retrieve a count of published submissions in a journal.
	 * @param $journalId int
	 */
	function getPublishedSubmissionCountByJournalId($journalId) {
		$result = $this->retrieve(
			'SELECT count(*)
			FROM published_submissions ps,
			submissions s
			WHERE
			ps.submission_id = s.submission_id
			AND is_current_submission_version = 1
			AND s.context_id = ?
			AND s.status <> ' . STATUS_DECLINED,
			(int) $journalId
		);
		list($count) = $result->fields;
		$result->Close();
		return $count;
	}

	/**
	 * Retrieve all published submissions in a journal.
	 * @param $journalId int
	 * @param $rangeInfo object
	 * @param $reverse boolean Whether to reverse the sort order
	 * @return DAOResultFactory
	 */
	function getPublishedSubmissionsByJournalId($journalId = null, $rangeInfo = null, $reverse = false) {
		$params = $this->getFetchParameters();
		if ($journalId) $params[] = (int) $journalId;
		$result = $this->retrieveRange(
			'SELECT	ps.*,
				s.*,
				' . $this->getFetchColumns() . '
			FROM	published_submissions ps
				LEFT JOIN submissions s ON ps.submission_id = s.submission_id
				LEFT JOIN issues i ON ps.issue_id = i.issue_id
				' . $this->getFetchJoins() . '
			WHERE 	i.published = 1 AND ps.is_current_submission_version = 1
				' . ($journalId?'AND s.context_id = ?':'') . '
				AND s.status <> ' . STATUS_DECLINED . '
			ORDER BY ps.date_published '. ($reverse?'DESC':'ASC'),
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Retrieve Published Articles by issue id
	 * @param $issueId int
	 * @param $useCache boolean optional
	 * @return array Array of PublishedSubmission objects
	 */
	function getPublishedSubmissionsInSections($issueId, $useCache = false) {
		if ($useCache) {
			$cache = $this->_getArticlesInSectionsCache();
			$returner = $cache->get($issueId);
			return $returner;
		}

		$result = $this->retrieve(
			'SELECT DISTINCT
				ps.*,
				s.*,
				se.abstracts_not_required AS abstracts_not_required,
				se.hide_title AS section_hide_title,
				se.hide_author AS section_hide_author,
				COALESCE(o.seq, se.seq) AS section_seq,
				ps.seq,
				' . $this->getFetchColumns() . '
			FROM	published_submissions ps
				JOIN submissions s ON (ps.submission_id = s.submission_id)
				' . $this->getFetchJoins() . '
				LEFT JOIN custom_section_orders o ON (s.section_id = o.section_id AND ps.issue_id = o.issue_id)
			WHERE	ps.issue_id = ? AND ps.is_current_submission_version = 1
				AND s.status <> ' . STATUS_DECLINED . '
			ORDER BY section_seq ASC, ps.seq ASC',
			array_merge(
				$this->getFetchParameters(),
				array((int) $issueId)
			)
		);

		$currSectionId = 0;
		$publishedSubmissions = array();
		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$publishedSubmission = $this->_fromRow($row);
			if ($publishedSubmission->getSectionId() != $currSectionId && !isset($publishedSubmissions[$publishedSubmission->getSectionId()])) {
				$currSectionId = $publishedSubmission->getSectionId();
				$publishedSubmissions[$currSectionId] = array(
					'articles' => array(),
					'title' => '',
					'abstractsNotRequired' => $row['abstracts_not_required'],
					'hideAuthor' => $row['section_hide_author']
				);

				if (!$row['section_hide_title']) {
					$publishedSubmissions[$currSectionId]['title'] = $publishedSubmission->getSectionTitle();
				}
			}
			$publishedSubmissions[$currSectionId]['articles'][] = $publishedSubmission;
			$result->MoveNext();
		}

		$result->Close();
		return $publishedSubmissions;
	}

	/**
	 * Retrieve Published Articles by section id
	 * @param $sectionId int
	 * @param $issueId int
	 * @return PublishedSubmission objects array
	 */
	function getPublishedSubmissionsBySectionId($sectionId, $issueId) {
		$result = $this->retrieve(
			'SELECT	ps.*,
				s.*,
				' . $this->getFetchColumns() . '
			FROM	published_submissions ps
				JOIN submissions s ON (ps.submission_id = s.submission_id)
				' . $this->getFetchJoins() . '
			WHERE	se.section_id = ?
				AND ps.issue_id = ?
				AND ps.is_current_submission_version = 1
				AND s.status <> ' . STATUS_DECLINED . '
			ORDER BY ps.seq ASC',
			array_merge(
				$this->getFetchParameters(),
				array(
					(int) $sectionId,
					(int) $issueId
				)
			)
		);

		$publishedSubmissions = array();
		while (!$result->EOF) {
			$publishedSubmissions[] = $this->_fromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		return $publishedSubmissions;
	}

	/**
	 * Retrieve Published Article by pub id
	 * @param $publishedSubmissionId int
	 * @return PublishedSubmission object
	 */
	function getPublishedSubmissionById($publishedSubmissionId) {
		$params = array (
			(int) $publishedSubmissionId
		);

		$result = $this->retrieve(
			'SELECT * FROM published_submissions WHERE published_submission_id = ? ',
			$params
		);
		$row = $result->GetRowAssoc(false);

		$publishedSubmission = $this->newDataObject();
		$publishedSubmission->setPublishedSubmissionId($row['published_submission_id']);
		$publishedSubmission->setId($row['submission_id']);
		$publishedSubmission->setIssueId($row['issue_id']);
		$publishedSubmission->setDatePublished($this->datetimeFromDB($row['date_published']));
		$publishedSubmission->setSequence($row['seq']);
		$publishedSubmission->setAccessStatus($row['access_status']);
		$publishedSubmission->setSubmissionVersion($row['published_submission_version']);
		$publishedSubmission->setCurrentSubmissionVersion($row['published_submission_version']);
		$publishedSubmission->setIsCurrentSubmissionVersion($row['is_current_submission_version']);

		$result->Close();
		return $publishedSubmission;
	}

	/**
	 * Retrieve published submission by article id
	 * @param $articleId int
	 * @param $journalId int optional
	 * @param $useCache boolean optional
	 * @return PublishedSubmission object
	 */
	function getBySubmissionId($articleId, $journalId = null, $useCache = false, $submissionVersion = null) {

		if ($useCache) {
			$cache = $this->_getPublishedSubmissionCache();
			$returner = $cache->get($articleId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		$params = $this->getFetchParameters();
		$params[] = (int) $articleId;
		if ($journalId) $params[] = (int) $journalId;
		if ($submissionVersion) $params[] = (int) $submissionVersion;

		$result = $this->retrieve(
			'SELECT	ps.*,
				s.*,
				' . $this->getFetchColumns() . '
			FROM	published_submissions ps
				JOIN submissions s ON (ps.submission_id = s.submission_id)
				' . $this->getFetchJoins() . '
			WHERE	s.submission_id = ?' .
				($journalId?' AND s.context_id = ?':'') .
				($submissionVersion?' AND ps.published_submission_version = ?' : ' AND ps.is_current_submission_version = 1'),
			$params
		);

		$publishedSubmission = null;
		if ($result->RecordCount() != 0) {
			$publishedSubmission = $this->_fromRow($result->GetRowAssoc(false), true, $submissionVersion);
		}

		$result->Close();
		return $publishedSubmission;
	}

	/**
	 * Retrieve published submission by public article id
	 * @param $pubIdType string One of the NLM pub-id-type values or
	 * 'other::something' if not part of the official NLM list
	 * (see <http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html>).
	 * @param $pubId string
	 * @param $journalId int
	 * @param $useCache boolean optional
	 * @return PublishedSubmission object
	 */
	function getPublishedSubmissionByPubId($pubIdType, $pubId, $journalId = null, $useCache = false) {
		if ($useCache && $pubIdType == 'publisher-id') {
			$cache = $this->_getPublishedSubmissionCache();
			$returner = $cache->get($pubId);
			if ($returner && $journalId != null && $journalId != $returner->getJournalId()) $returner = null;
			return $returner;
		}

		$publishedSubmission = null;
		if (!empty($pubId)) {
			$publishedSubmissions = $this->getBySetting('pub-id::'.$pubIdType, $pubId, $journalId);
			if ($publishedSubmissions->getCount()) {
				assert($publishedSubmissions->getCount() == 1);
				$publishedSubmission = $publishedSubmissions->next();
			}
		}
		return $publishedSubmission;
	}

	/**
	 * @copydoc ArticleDAO::getBySetting()
	 */
	function getBySetting($settingName, $settingValue, $journalId = null, $rangeInfo = null) {
		$params = $this->getFetchParameters();
		$params[] = $settingName;

		$sql = 'SELECT	ps.*,
				s.*,
				' . $this->getFetchColumns() . '
			FROM	published_submissions ps
				JOIN submissions s ON ps.submission_id = s.submission_id
				' . $this->getFetchJoins();

		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN submission_settings sst ON s.submission_id = sst.submission_id AND sst.setting_name = ?
				WHERE	(sst.setting_value IS NULL OR sst.setting_value = \'\')';
		} else {
			$params[] = (string) $settingValue;
			$sql .= 'INNER JOIN submission_settings sst ON s.submission_id = sst.submission_id
				WHERE	sst.setting_name = ? AND sst.setting_value = ? ';
		}
		$sql .= ' AND ps.is_current_submission_version = 1';
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND s.context_id = ?';
		}
		$sql .= ' ORDER BY ps.issue_id, s.submission_id';
		return new DAOResultFactory($this->retrieveRange($sql, $params, $rangeInfo), $this, '_fromRow');
	}

	/**
	 * Retrieve published submission by public article id or, failing that,
	 * internal article ID; public article ID takes precedence.
	 * @param $journalId int
	 * @param $articleId string
	 * @param $useCache boolean optional
	 * @return PublishedSubmission object
	 */
	function getPublishedSubmissionByBestArticleId($journalId, $articleId, $useCache = false) {
		$article = $this->getPublishedSubmissionByPubId('publisher-id', $articleId, $journalId, $useCache);
		if (!$article && ctype_digit("$articleId")) {
			return $this->getBySubmissionId($articleId, $journalId, $useCache);
		}
		return $article;
	}

	/**
	 * Retrieve "submission_id"s for published submissions for a journal, sorted
	 * alphabetically.
	 * Note that if journalId is null, alphabetized article IDs for all
	 * enabled journals are returned.
	 * @param $journalId int Optional journal ID to restrict results to
	 * @param $useCache boolean optional
	 * @return Array
	 */
	function getPublishedSubmissionIdsAlphabetizedByJournal($journalId = null, $useCache = true) {
		$params = array(
			'cleanTitle', AppLocale::getLocale(),
			'cleanTitle'
		);
		if ($journalId) $params[] = (int) $journalId;

		$functionName = $useCache?'retrieveCached':'retrieve';
		$result = $this->$functionName(
			'SELECT	s.submission_id AS pub_id,
				COALESCE(stl.setting_value, stpl.setting_value) AS submission_title
			FROM	submissions s
				JOIN journals j ON (s.context_id = j.journal_id)
				JOIN published_submissions ps ON (s.submission_id = ps.submission_id)
				JOIN issues i ON (i.issue_id = ps.issue_id)
				JOIN sections se ON se.section_id = s.section_id
				LEFT JOIN submission_settings stl ON (s.submission_id = stl.submission_id AND stl.setting_name = ? AND stl.locale = ?)
				LEFT JOIN submission_settings stpl ON (s.submission_id = stpl.submission_id AND stpl.setting_name = ? AND stpl.locale = s.locale)
			WHERE	i.published = 1 AND ps.is_current_submission_version = 1' .
				($journalId?' AND j.journal_id = ?':' AND j.enabled = 1') . '
			ORDER BY submission_title',
			$params
		);

		$articleIds = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$articleIds[] = $row['pub_id'];
			$result->MoveNext();
		}

		$result->Close();
		return $articleIds;
	}

	/**
	 * Retrieve "submission_id"s for published submissions for a journal, sorted
	 * by reverse publish date.
	 * Note that if journalId is null, alphabetized article IDs for all
	 * journals are returned.
	 * @param $journalId int Journal ID (optional)
	 * @param $useCache boolean (optional; default true)
	 * @return array
	 */
	function getPublishedSubmissionIdsByJournal($journalId = null, $useCache = true) {
		$functionName = $useCache?'retrieveCached':'retrieve';
		$result = $this->$functionName(
			'SELECT	s.submission_id AS pub_id
			FROM	published_submissions ps
				JOIN submissions s ON ps.submission_id = s.submission_id
				JOIN sections se ON s.section_id = se.section_id
				JOIN issues i ON ps.issue_id = i.issue_id
			WHERE	i.published = 1 AND ps.is_current_submission_version = 1
				' . (isset($journalId)?' AND s.context_id = ?':'') . '
			ORDER BY ps.date_published DESC',
			isset($journalId)?(int) $journalId:false
		);

		$articleIds = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$articleIds[] = $row['pub_id'];
			$result->MoveNext();
		}

		$result->Close();
		return $articleIds;
	}
	/**
	 * Retrieve "submission_id"s for published submissions for a journal section, sorted
	 * by reverse publish date.
	 * @param $sectionId int
	 * @param $useCache boolean Optional (default true)
	 * @return array
	 */
	function getPublishedSubmissionIdsBySection($sectionId, $useCache = true) {
		$functionName = $useCache?'retrieveCached':'retrieve';
		$result = $this->$functionName(
			'SELECT	s.submission_id
			FROM published_submissions ps
				JOIN submissions s ON s.submission_id = ps.submission_id
				JOIN issues i ON ps.issue_id = i.issue_id
			WHERE	i.published = 1 AND ps.is_current_submission_version = 1 AND
				s.section_id = ?
			ORDER BY ps.date_published DESC',
			(int) $sectionId
		);

		$articleIds = array();
		while (!$result->EOF) {
			$row = $result->getRowAssoc(false);
			$articleIds[] = $row['submission_id'];
			$result->MoveNext();
		}

		$result->Close();
		return $articleIds;
	}

	/**
	 * Get a new data object.
	 * @return PublishedSubmission
	 */
	function newDataObject() {
		return new PublishedSubmission();
	}

	/**
	 * creates and returns a published submission object from a row
	 * @param $row array
	 * @param $callHooks boolean Whether or not to call hooks
	 * @return PublishedSubmission object
	 */
	function _fromRow($row, $callHooks = true, $submissionVersion = null) {
		$publishedSubmission = parent::_fromRow($row);
		$publishedSubmission->setPublishedSubmissionId($row['published_submission_id']);
		$publishedSubmission->setIssueId($row['issue_id']);
		$publishedSubmission->setSequence($row['seq']);
		$publishedSubmission->setAccessStatus($row['access_status']);
		$publishedSubmission->setDatePublished($row['date_published']);
		$publishedSubmission->setSubmissionVersion($row['published_submission_version']);
		$publishedSubmission->setCurrentSubmissionVersion($row['published_submission_version']);
		$publishedSubmission->setIsCurrentSubmissionVersion($row['is_current_submission_version']);

		$publishedSubmission->setGalleys($this->galleyDao->getBySubmissionId($row['submission_id'], null, $publishedSubmission->getSubmissionVersion())->toArray());
		$this->getDataObjectSettings('submission_settings', 'submission_id', $publishedSubmission->getId(), $publishedSubmission, $publishedSubmission->getSubmissionVersion());

		if ($callHooks) HookRegistry::call('PublishedSubmissionDAO::_returnPublishedSubmissionFromRow', array(&$publishedSubmission, &$row));
		return $publishedSubmission;
	}


	/**
	 * inserts a new published submission into published_submissions table
	 * @param PublishedSubmission object
	 * @return pubId int
	 */
	function insertObject($publishedSubmission) {
		$this->update(
			sprintf('INSERT INTO published_submissions
				(submission_id, issue_id, date_published, seq, access_status, published_submission_version, is_current_submission_version)
				VALUES
				(?, ?, %s, ?, ?, ?, ?)',
				$this->datetimeToDB($publishedSubmission->getDatePublished())),
			array(
				(int) $publishedSubmission->getId(),
				(int) $publishedSubmission->getIssueId(),
				$publishedSubmission->getSequence(),
				$publishedSubmission->getAccessStatus(),
				(int) $publishedSubmission->getSubmissionVersion(),
				(int) $publishedSubmission->getIsCurrentSubmissionVersion(),
			)
		);

		$publishedSubmission->setPublishedSubmissionId($this->getInsertId());
		return $publishedSubmission->getPublishedSubmissionId();
	}

	/**
	 * Get the ID of the last inserted published submission.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('published_submissions', 'published_submission_id');
	}

	/**
	 * removes an published Article by id
	 * @param $publishedSubmissionId int
	 */
	function deletePublishedSubmissionById($publishedSubmissionId) {
		$this->update(
			'DELETE FROM published_submissions WHERE published_submission_id = ?', (int) $publishedSubmissionId
		);

		$this->flushCache();
	}

	/**
	 * Delete published submission by article ID
	 * NOTE: This does not delete the related Article or any dependent entities
	 * @param $articleId int
	 */
	function deletePublishedSubmissionByArticleId($articleId) {
		$this->update(
			'DELETE FROM published_submissions WHERE submission_id = ?', (int) $articleId
		);
		$this->flushCache();
	}

	/**
	 * Delete published submissions by section ID
	 * @param $sectionId int
	 */
	function deletePublishedSubmissionsBySectionId($sectionId) {
		$result = $this->retrieve(
			'SELECT	ps.submission_id AS submission_id
			FROM	published_submissions ps
				JOIN submissions s ON ps.submission_id = s.submission_id
			WHERE	s.section_id = ? AND ps.is_current_submission_version = 1 ',
			(int) $sectionId
		);

		while (!$result->EOF) {
			$row = $result->GetRowAssoc(false);
			$this->update(
				'DELETE FROM published_submissions WHERE submission_id = ? AND is_current_submission_version = 1', $row['submission_id']
			);
		}

		$result->Close();
		$this->flushCache();
	}

	/**
	 * Delete published submissions by issue ID
	 * @param $issueId int
	 */
	function deletePublishedSubmissionsByIssueId($issueId) {
		$this->update(
			'DELETE FROM published_submissions WHERE issue_id = ?', (int) $issueId
		);

		$this->flushCache();
	}

	/**
	 * updates a published submission
	 * @param PublishedSubmission object
	 */
	function updatePublishedSubmission($publishedSubmission) {
		$this->update(
			sprintf('UPDATE published_submissions
				SET
					submission_id = ?,
					issue_id = ?,
					date_published = %s,
					seq = ?,
					access_status = ?,
					is_current_submission_version = ?
				WHERE published_submission_id = ? AND published_submission_version = ?',
				$this->datetimeToDB($publishedSubmission->getDatePublished())),
			array(
				(int) $publishedSubmission->getId(),
				(int) $publishedSubmission->getIssueId(),
				$publishedSubmission->getSequence(),
				$publishedSubmission->getAccessStatus(),
				(int) $publishedSubmission->getIsCurrentSubmissionVersion(),
				(int) $publishedSubmission->getPublishedSubmissionId(),
				(int) $publishedSubmission->getSubmissionVersion()
			)
		);

		$this->flushCache();
	}

	/**
	 * Updates a published submission field
	 * @param $publishedSubmissionId int
	 * @param $field string
	 * @param $value mixed
	 */
	function updatePublishedSubmissionField($publishedSubmissionId, $field, $value) {
		$this->update(
			"UPDATE published_submissions SET $field = ? WHERE published_submission_id = ? AND is_current_submission_version = 1", array($value, (int) $publishedSubmissionId)
		);

		$this->flushCache();
	}

	/**
	 * Sequentially renumber published submissions in their sequence order.
	 * @param $sectionId int
	 * @param $issueId int
	 */
	function resequencePublishedSubmissions($sectionId, $issueId) {
		$result = $this->retrieve(
			'SELECT ps.published_submission_id FROM published_submissions ps, submissions s WHERE s.section_id = ? AND s.submission_id = ps.submission_id AND ps.issue_id = ? AND ps.is_current_submission_version = 1 ORDER BY ps.seq',
			array((int) $sectionId, (int) $issueId)
		);

		for ($i=1; !$result->EOF; $i++) {
			list($publishedSubmissionId) = $result->fields;
			$this->update(
				'UPDATE published_submissions SET seq = ? WHERE published_submission_id = ? AND is_current_submission_version = 1',
				array($i, $publishedSubmissionId)
			);

			$result->MoveNext();
		}
		$result->Close();
		$this->flushCache();
	}

	/**
	 * Return years of oldest/youngest published submission on site or within a journal
	 * @param $journalId int Optional
	 * @return array (maximum date published, minimum date published)
	 */
	function getArticleYearRange($journalId = null) {
		$result = $this->retrieve(
			'SELECT	MAX(ps.date_published),
				MIN(ps.date_published)
			FROM	published_submissions ps,
				submissions s
			WHERE	ps.submission_id = s.submission_id AND is_current_submission_version = 1
				' . (isset($journalId)?' AND s.context_id = ?':''),
			isset($journalId)?(int) $journalId:false
		);
		$returner = array($result->fields[0], $result->fields[1]);

		$result->Close();
		return $returner;
	}


	/**
	 * Get all published submissions (eventually with a pubId assigned and) matching the specified settings.
	 * @param $contextId integer optional
	 * @param $pubIdType string
	 * @param $title string optional
	 * @param $author string optional
	 * @param $issueId integer optional
	 * @param $pubIdSettingName string optional
	 * (e.g. crossref::status or crossref::registeredDoi)
	 * @param $pubIdSettingValue string optional
	 * @param $rangeInfo DBResultRange optional
	 * @return DAOResultFactory
	 */
	function getExportable($contextId, $pubIdType = null, $title = null, $author = null, $issueId = null, $pubIdSettingName = null, $pubIdSettingValue = null, $rangeInfo = null) {
		$params = array();
		if ($pubIdSettingName) {
			$params[] = $pubIdSettingName;
		}
		$params = array_merge($params, $this->getFetchParameters()); // because of the necessary section row names in _fromRow
		$params[] = (int) $contextId;
		if ($pubIdType) {
			$params[] = 'pub-id::'.$pubIdType;
		}
		if ($title) {
			$params[] = 'title';
			$params[] = '%' . $title . '%';
		}
		if ($author) array_push($params, $authorQuery = '%' . $author . '%', $authorQuery);
		if ($issueId) {
			$params[] = (int) $issueId;
		}
		import('classes.plugins.PubObjectsExportPlugin');
		if ($pubIdSettingName && $pubIdSettingValue && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED) {
			$params[] = $pubIdSettingValue;
		}

		$result = $this->retrieveRange(
			'SELECT	s.*, ps.*,
				' . $this->getFetchColumns() . '
			FROM	published_submissions ps
				JOIN issues i ON (ps.issue_id = i.issue_id)
				LEFT JOIN submissions s ON (s.submission_id = ps.submission_id)
				' . ($pubIdType != null?' LEFT JOIN submission_settings ss ON (s.submission_id = ss.submission_id)':'')
				. ($title != null?' LEFT JOIN submission_settings sst ON (s.submission_id = sst.submission_id)':'')
				. ($author != null?' LEFT JOIN authors au ON (s.submission_id = au.submission_id)
						LEFT JOIN author_settings asgs ON (asgs.author_id = au.author_id AND asgs.setting_name = \''.IDENTITY_SETTING_GIVENNAME.'\')
						LEFT JOIN author_settings asfs ON (asfs.author_id = au.author_id AND asfs.setting_name = \''.IDENTITY_SETTING_FAMILYNAME.'\')
					':'')
				. ($pubIdSettingName != null?' LEFT JOIN submission_settings sss ON (s.submission_id = sss.submission_id AND sss.setting_name = ?)':'')
				. ' ' . $this->getFetchJoins() .'
			WHERE
				i.published = 1 AND ps.is_current_submission_version = 1 AND s.context_id = ? AND s.status <> ' . STATUS_DECLINED
				. ($pubIdType != null?' AND ss.setting_name = ? AND ss.setting_value IS NOT NULL':'')
				. ($title != null?' AND (sst.setting_name = ? AND sst.setting_value LIKE ?)':'')
				. ($author != null?' AND (asgs.setting_value LIKE ? OR asfs.setting_value LIKE ?)':'')
				. ($issueId != null?' AND ps.issue_id = ?':'')
				. (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue == EXPORT_STATUS_NOT_DEPOSITED)?' AND sss.setting_value IS NULL':'')
				. (($pubIdSettingName != null && $pubIdSettingValue != null && $pubIdSettingValue != EXPORT_STATUS_NOT_DEPOSITED)?' AND sss.setting_value = ?':'')
				. (($pubIdSettingName != null && is_null($pubIdSettingValue))?' AND (sss.setting_value IS NULL OR sss.setting_value = \'\')':'')
			. ' ORDER BY ps.date_published DESC, s.submission_id DESC',
			$params,
			$rangeInfo
		);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

}


