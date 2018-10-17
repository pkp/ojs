<?php

/**
 * @file pages/management/ToolsHandler.inc.php
 *
 * Copyright (c) 2013-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ToolsHandler
 * @ingroup pages_management
 *
 * @brief Implement application specifics on handling requests for Tool pages.
 */

// Import the base ManagementHandler.
import('lib.pkp.pages.management.PKPToolsHandler');

class ToolsHandler extends PKPToolsHandler {

	/**
	 * @copydoc PKPToolsHandler::getReportRowValue()
	 */
	protected function getReportRowValue($key, $record) {
		$returnValue = parent::getReportRowValue($key, $record);

		if (!$returnValue && $key == STATISTICS_DIMENSION_ISSUE_ID) {
			$assocId = $record[STATISTICS_DIMENSION_ISSUE_ID];
			$assocType = ASSOC_TYPE_ISSUE;
			$returnValue = $this->getObjectTitle($assocId, $assocType);
		}

		return $returnValue;
	}


	/**
	 * @copydoc PKPToolsHandler::getObjectTitle()
	 */
	protected function getObjectTitle($assocId, $assocType) {
		$objectTitle = parent::getObjectTitle($assocId, $assocType);

		switch ($assocType) {
			case ASSOC_TYPE_ISSUE:
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issue = $issueDao->getById($assocId);
				if ($issue) {
					$objectTitle = $issue->getIssueIdentification();
				}
				break;
		}

		return $objectTitle;
	}
}


