<?php

/**
 * @file classes/statistics/MetricsDAO.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetricsDAO
 * @ingroup statistics
 *
 * @brief Operations for retrieving and adding statistics data.
 */

import('lib.pkp.classes.statistics.PKPMetricsDAO');

class MetricsDAO extends PKPMetricsDAO {

	/**
	 * @copydoc PKPMetricsDAO::getMetrics()
	 */
	function &getMetrics($metricType, $columns = array(), $filters = array(), $orderBy = array(), $range = null, $nonAdditive = true) {
		// Translate the issue dimension to a generic one used in pkp library.
		// Do not move this into foreach: https://github.com/pkp/pkp-lib/issues/1615
		$worker = array(&$columns, &$filters, &$orderBy);
		foreach ($worker as &$parameter) { // Reference needed.
			if ($parameter === $filters && array_key_exists(STATISTICS_DIMENSION_ISSUE_ID, $parameter)) {
				$parameter[STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE] = ASSOC_TYPE_ISSUE;
			}

			$key = array_search(STATISTICS_DIMENSION_ISSUE_ID, $parameter);
			if ($key !== false) {
				$parameter[] = STATISTICS_DIMENSION_ASSOC_OBJECT_TYPE;
			}
			unset($parameter);
		}

		return parent::getMetrics($metricType, $columns, $filters, $orderBy, $range, $nonAdditive);
	}

	/**
	 * @copydoc PKPMetricsDAO::foreignKeyLookup()
	 */
	protected function foreignKeyLookup($assocType, $assocId) {
		list($contextId, $sectionId, $assocObjType,
			$assocObjId, $submissionId, $representationId) = parent::foreignKeyLookup($assocType, $assocId);

		$isFile = false;

		if (!$contextId) {
			switch ($assocType) {
				case ASSOC_TYPE_ISSUE_GALLEY:
					$issueGalleyDao = DAORegistry::getDAO('IssueGalleyDAO');
					$issueGalley = $issueGalleyDao->getById($assocId);
					if (!$issueGalley) {
						throw new Exception('Cannot load record: invalid issue galley id.');
					}

					$assocObjType = ASSOC_TYPE_ISSUE;
					$assocObjId = $issueGalley->getIssueId();
					$isFile = true;
					// Don't break but go on to retrieve the issue.
				case ASSOC_TYPE_ISSUE:
					if (!$isFile) {
						$assocObjType = $assocObjId = null;
						$issueId = $assocId;
					} else {
						$issueId = $assocObjId;
					}

					$issueDao = DAORegistry::getDAO('IssueDAO');
					$issue = $issueDao->getById($issueId);

					if (!$issue) {
						throw new Exception('Cannot load record: invalid issue id.');
					}

					$contextId = $issue->getJournalId();
					break;
			}
		}

		return array($contextId, $sectionId, $assocObjType, $assocObjId, $submissionId, $representationId);
	}

	/**
	 * @copydoc PKPMetricsDAO::getAssocObjectInfo()
	 */
	protected function getAssocObjectInfo($submissionId, $contextId) {
		$returnArray = parent::getAssocObjectInfo($submissionId, $contextId);

		// Submissions in OJS are associated with an Issue.
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$publishedArticle = $publishedArticleDao->getBySubmissionId($submissionId, $contextId, true);
		if ($publishedArticle) {
			$returnArray = array(ASSOC_TYPE_ISSUE, $publishedArticle->getIssueId());
		}
		return $returnArray;
	}
}

