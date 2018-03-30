<?php

/**
 * @file plugins/generic/usageStats/UsageStatsLoader.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class UsageStatsLoader
 * @ingroup plugins_generic_usageStats
 *
 * @brief Scheduled task to extract transform and load usage statistics data into database.
 */

import('lib.pkp.plugins.generic.usageStats.PKPUsageStatsLoader');

class UsageStatsLoader extends PKPUsageStatsLoader {

	/**
	 * Constructor.
	 */
	function __construct($args) {
		parent::__construct($args);
	}


	//
	// Protected methods.
	//
	/**
	 * @see PKPUsageStatsLoader::getExpectedPageAndOp()
	 */
	protected function getExpectedPageAndOp() {
		$pageAndOp = parent::getExpectedPageAndOp();

		$pageAndOp = $pageAndOp + array(
			ASSOC_TYPE_SUBMISSION_FILE => array(
				'article/download'),
			ASSOC_TYPE_ARTICLE => array(
				'article/view'),
			ASSOC_TYPE_ISSUE => array(
				'issue/view'),
			ASSOC_TYPE_ISSUE_GALLEY => array(
				'issue/download')
		);

		$pageAndOp[Application::getContextAssocType()][] = 'index';

		return $pageAndOp;
	}

	/**
	 * @see PKPUsageStatsLoader::getAssoc()
	 */
	protected function getAssoc($assocType, $contextPaths, $page, $op, $args) {
		list($assocTypeToReturn, $assocId) = parent::getAssoc($assocType, $contextPaths, $page, $op, $args);

		if (!$assocId && !$assocTypeToReturn) {
			switch ($assocType) {
				case ASSOC_TYPE_SUBMISSION_FILE:
					if (!isset($args[0])) break;
					$submissionId = $args[0];
					$submissionDao = DAORegistry::getDAO('ArticleDAO');
					$article = $submissionDao->getById($submissionId);
					if (!$article) break;

					if (!isset($args[2])) break;
					$fileId = $args[2];
					$articleFileDao = DAORegistry::getDAO('SubmissionFileDAO');
					$articleFile = $articleFileDao->getLatestRevision($fileId);
					if (!$articleFile) break;

					$assocId = $articleFile->getFileId();

					// is the file article full text
					$genreDao = DAORegistry::getDAO('GenreDAO');
					$genre = $genreDao->getById($articleFile->getGenreId());
					if ($genre->getCategory() != GENRE_CATEGORY_DOCUMENT || $genre->getSupplementary() || $genre->getDependent()) {
						$assocTypeToReturn = ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER;
					} else {
						$assocTypeToReturn = $assocType;
					}
					break;
				case ASSOC_TYPE_ISSUE:
				case ASSOC_TYPE_ISSUE_GALLEY:
					if (!isset($args[0])) break;
					$issueId = $args[0];
					$issueDao = DAORegistry::getDAO('IssueDAO');
					if (isset($this->_contextsByPath[current($contextPaths)])) {
						$context =  $this->_contextsByPath[current($contextPaths)];
						$issue = $issueDao->getById($issueId, $context->getId());
						if ($issue) {
							$assocId = $issue->getId();
						} else {
							break;
						}
					} else {
						break;
					}

					$assocTypeToReturn = $assocType;
					// Allows next case.
				case ASSOC_TYPE_ISSUE_GALLEY:
					if (!isset($issue) || !isset($args[1])) break;
					$issueGalleyId = $args[1];
					$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
					$issueGalley = $issueGalleyDao->getById($issueGalleyId, $issue->getId());
					if ($issueGalley) {
						$assocId = $issueGalley->getId();
					} else {
						// Make sure we clean up values from the above case.
						$assocId = $assocTypeToReturn = null;
					}
					break;
			}
		}

		return array($assocId, $assocTypeToReturn);
	}

	/**
	 * @copydoc PKPUsageStatsLoader::getFileType()
	 */
	protected function getFileTypeFromAssoc($assocType, $assocId) {
		$type = parent::getFileTypeFromAssoc($assocType, $assocId);
		if (!$type) {
			switch ($assocType) {
				case ASSOC_TYPE_ISSUE_GALLEY:
					$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
					$issueGalley = $issueGalleyDao->getById($assocId);
					$type = $this->getFileTypeFromFile($issueGalley);
					break;
			}
		}

		return $type;
	}

	/**
	 * @see PKPUsageStatsLoader::getMetricType()
	 */
	protected function getMetricType() {
		return OJS_METRIC_TYPE_COUNTER;
	}
}
?>
