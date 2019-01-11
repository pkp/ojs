<?php

/**
 * @file classes/article/ArticleDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleDAO
 * @ingroup article
 * @see Article
 *
 * @brief Operations for retrieving and modifying Article objects.
 */

import('classes.article.Article');
import('lib.pkp.classes.submission.SubmissionDAO');

class ArticleDAO extends SubmissionDAO {

	/**
	 * Get a list of fields for which localized data is supported
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array_merge(
			parent::getLocaleFieldNames(), array(
				'coverImageAltText', 'coverImage',
		));
	}

	/**
	 * Find articles by querying article settings.
	 * @param $settingName string
	 * @param $settingValue mixed
	 * @param $journalId int optional
	 * @param $rangeInfo DBResultRange optional
	 * @return array The articles identified by setting.
	 * WARNING: This query is selectively indexed for PostgreSQL. Ensure that the
	 * settings you wish to query are specified in dbscripts/xml/indexes.xml to
	 * avoid a potentially costly query.
	 */
	function getBySetting($settingName, $settingValue, $journalId = null, $rangeInfo = null) {
		$params = $this->getFetchParameters();
		$params[] = $settingName;

		$sql = 'SELECT s.*, ps.date_published,
				' . $this->getFetchColumns() . '
			FROM	submissions s
				LEFT JOIN published_submissions ps ON (s.submission_id = ps.submission_id)
				' . $this->getFetchJoins() . ' ';

		if (is_null($settingValue)) {
			$sql .= 'LEFT JOIN submission_settings sst ON a.submission_id = sst.submission_id AND sst.setting_name = ?
				WHERE	(sst.setting_value IS NULL OR sst.setting_value = \'\')';
		} else {
			$params[] = $settingValue;
			$sql .= 'INNER JOIN submission_settings sst ON s.submission_id = sst.submission_id
				WHERE	sst.setting_name = ? AND sst.setting_value = ?';
		}
		if ($journalId) {
			$params[] = (int) $journalId;
			$sql .= ' AND s.context_id = ?';
		}
		$sql .= ' ORDER BY s.context_id, s.submission_id';
		$result = $this->retrieveRange($sql, $params, $rangeInfo);

		return new DAOResultFactory($result, $this, '_fromRow');
	}

	/**
	 * Internal function to return an Article object from a row.
	 * @param $row array
	 * @return Article
	 */
	function _fromRow($row) {
		$article = parent::_fromRow($row);

		$article->setSectionId($row['section_id']);
		$article->setSectionTitle($row['section_title']);
		$article->setSectionAbbrev($row['section_abbrev']);
		$article->setCitations($row['citations']);
		$article->setPages($row['pages']);
		$article->setHideAuthor($row['hide_author']);

		HookRegistry::call('ArticleDAO::_fromRow', array(&$article, &$row));
		return $article;
	}

	/**
	 * Return a new data object.
	 * @return Article
	 */
	function newDataObject() {
		return new Article();
	}

	/**
	 * Insert a new Article.
	 * @param $article Article
	 */
	function insertObject($article) {
		$article->stampModified();
		$this->update(
			sprintf('INSERT INTO submissions
				(locale, context_id, section_id, stage_id, language, citations, date_submitted, date_status_modified, last_modified, status, submission_progress, pages, hide_author)
				VALUES
				(?, ?, ?, ?, ?, ?, %s, %s, %s, ?, ?, ?, ?)',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getLocale(),
				(int) $article->getContextId(),
				(int) $article->getSectionId(),
				(int) $article->getStageId(),
				$article->getLanguage(),
				$article->getCitations(),
				$article->getStatus() === null ? STATUS_QUEUED : $article->getStatus(),
				$article->getSubmissionProgress() === null ? 1 : $article->getSubmissionProgress(),
				$article->getPages(),
				(int) $article->getHideAuthor(),
			)
		);

		$article->setId($this->getInsertId());
		$this->updateLocaleFields($article);

		// Insert authors for this article
		$authors = $article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			$authors[$i]->setSubmissionId($article->getId());
			$this->authorDao->insertObject($authors[$i]);
		}

		return $article->getId();
	}

	/**
	 * Update an existing article.
	 * @param $article Article
	 */
	function updateObject($article) {
		$article->stampModified();
		$this->update(
			sprintf('UPDATE submissions
				SET	locale = ?,
					section_id = ?,
					stage_id = ?,
					language = ?,
					citations = ?,
					date_submitted = %s,
					date_status_modified = %s,
					last_modified = %s,
					status = ?,
					submission_progress = ?,
					pages = ?,
					hide_author = ?
				WHERE submission_id = ?',
				$this->datetimeToDB($article->getDateSubmitted()), $this->datetimeToDB($article->getDateStatusModified()), $this->datetimeToDB($article->getLastModified())),
			array(
				$article->getLocale(),
				(int) $article->getSectionId(),
				(int) $article->getStageId(),
				$article->getLanguage(),
				$article->getCitations(),
				(int) $article->getStatus(),
				(int) $article->getSubmissionProgress(),
				$article->getPages(),
				(int) $article->getHideAuthor(),
				(int) $article->getId()
			)
		);

		$this->updateLocaleFields($article);

		// update authors for this article
		$authors = $article->getAuthors();
		for ($i=0, $count=count($authors); $i < $count; $i++) {
			if ($authors[$i]->getId() > 0) {
				$this->authorDao->updateObject($authors[$i]);
			} else {
				$this->authorDao->insertObject($authors[$i]);
			}
		}

		// Update author sequence numbers
		$this->authorDao->resequenceAuthors($article->getId());

		$this->flushCache();
	}

	/**
	 * @copydoc Submission::deleteById
	 */
	function deleteById($submissionId) {
		parent::deleteById($submissionId);

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticleDao->deletePublishedArticleByArticleId($submissionId);

		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$articleGalleyDao->deleteByArticleId($submissionId);

		$articleSearchDao = DAORegistry::getDAO('ArticleSearchDAO');
		$articleSearchDao->deleteSubmissionKeywords($submissionId);

		// Delete article citations.
		$citationDao = DAORegistry::getDAO('CitationDAO');
		$citationDao->deleteBySubmissionId($submissionId);

		import('classes.search.ArticleSearchIndex');
		$articleSearchIndex = new ArticleSearchIndex();
		$articleSearchIndex->articleDeleted($submissionId);
		$articleSearchIndex->articleChangesFinished();

		$this->flushCache();
	}

	/**
	 * Get the ID of the journal an article is in.
	 * @param $articleId int
	 * @return int
	 */
	function getJournalId($articleId) {
		$result = $this->retrieve(
			'SELECT context_id FROM submissions WHERE submission_id = ?', (int) $articleId
		);
		$returner = isset($result->fields[0]) ? $result->fields[0] : false;

		$result->Close();
		return $returner;
	}

	/**
	 * Change the status of the article
	 * @param $articleId int
	 * @param $status int
	 */
	function changeStatus($articleId, $status) {
		$this->update(
			'UPDATE submissions SET status = ? WHERE submission_id = ?',
			array((int) $status, (int) $articleId)
		);

		$this->flushCache();
	}

	/**
	 * Add/update an article setting.
	 * @param $articleId int
	 * @param $name string
	 * @param $value mixed
	 * @param $type string Data type of the setting.
	 * @param $isLocalized boolean
	 */
	function updateSetting($articleId, $name, $value, $type, $isLocalized = false) {
		// Check and prepare setting data.
		if ($isLocalized) {
			if (is_array($value)) {
				$values = $value;
			} else {
				// We expect localized data to come in as an array.
				assert(false);
				return;
			}
		} else {
			// Normalize non-localized data to an array so that
			// we can treat updates uniformly.
			$values = array('' => $value);
		}

		// Update setting values.
		$keyFields = array('setting_name', 'locale', 'submission_id');
		foreach ($values as $locale => $value) {
			// Locale-specific entries will be deleted when no value exists.
			// Non-localized settings will always be set.
			if ($isLocalized) {
				$this->update(
					'DELETE FROM submission_settings WHERE submission_id = ? AND setting_name = ? AND locale = ?',
					array((int) $articleId, $name, $locale)
				);
				if (empty($value)) continue;
			}

			// Convert the new value to the correct type.
			$value = $this->convertToDB($value, $type);

			// Update the database.
			$this->replace('submission_settings',
				array(
					'submission_id' => $articleId,
					'setting_name' => $name,
					'setting_value' => $value,
					'setting_type' => $type,
					'locale' => $locale
				),
				$keyFields
			);
		}
		$this->flushCache();
	}

	/**
	 * Removes articles from a section by section ID
	 * @param $sectionId int
	 */
	function removeArticlesFromSection($sectionId) {
		$this->update(
			'UPDATE submissions SET section_id = null WHERE section_id = ?', (int) $sectionId
		);

		$this->flushCache();
	}

	function flushCache() {
		// Because both publishedArticles and articles are cached by
		// article ID, flush both caches on update.
		parent::flushCache();

		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$cache = $publishedArticleDao->_getPublishedArticleCache();
		$cache->flush();
	}


	//
	// Protected functions
	//
	/**
	 * @copydoc SubmissionDAO::getFetchParameters()
	 */
	protected function getFetchParameters() {
		$primaryLocale = AppLocale::getPrimaryLocale();
		$locale = AppLocale::getLocale();
		return array(
			'title', $primaryLocale, // Section title
			'title', $locale,
			'abbrev', $primaryLocale, // Section abbrevation
			'abbrev', $locale,
		);
	}

	/**
	 * @copydoc SubmissionDAO::getFetchColumns()
	 */
	protected function getFetchColumns() {
		return 'COALESCE(stl.setting_value, stpl.setting_value) AS section_title,
			COALESCE(sal.setting_value, sapl.setting_value) AS section_abbrev';
	}

	/**
	 * @copydoc SubmissionDAO::getFetchJoins()
	 */
	protected function getFetchJoins() {
		return 'JOIN sections se ON se.section_id = s.section_id
			LEFT JOIN section_settings stpl ON (se.section_id = stpl.section_id AND stpl.setting_name = ? AND stpl.locale = ?)
			LEFT JOIN section_settings stl ON (se.section_id = stl.section_id AND stl.setting_name = ? AND stl.locale = ?)
			LEFT JOIN section_settings sapl ON (se.section_id = sapl.section_id AND sapl.setting_name = ? AND sapl.locale = ?)
			LEFT JOIN section_settings sal ON (se.section_id = sal.section_id AND sal.setting_name = ? AND sal.locale = ?)';
	}

	/**
	 * @copydoc SubmissionDAO::getSubEditorJoin()
 	 */
	protected function getSubEditorJoin() {
		return 'JOIN section_editors see ON (see.journal_id = s.context_id AND see.user_id = ? AND see.section_id = s.section_id)';
	}

	/**
	 * @copydoc SubmissionDAO::getGroupByColumns()
	 */
	protected function getGroupByColumns() {
		return 's.submission_id, ps.date_published, stl.setting_value, stpl.setting_value, sal.setting_value, sapl.setting_value';
	}

	/**
	 * @copydoc SubmissionDAO::getCompletionJoins()
	 */
	protected function getCompletionJoins() {
		return 'LEFT JOIN issues i ON (ps.issue_id = i.issue_id)';
	}

	/**
	 * @copydoc SubmissionDAO::getCompletionConditions()
	 */
	protected function getCompletionConditions($completed) {
		return ' i.date_published IS ' . ($completed?'NOT ':'') . 'NULL ';
	}
}


