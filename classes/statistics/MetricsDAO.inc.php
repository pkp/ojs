<?php

/**
 * @file classes/statistics/MetricsDAO.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
}
?>
