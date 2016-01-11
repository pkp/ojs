<?php

/**
 * @file pages/management/ToolsHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	 * @copydoc PKPToolsHandler::getObjectTitle()
	 */
	protected function getObjectTitle($assocId, $assocType) {
		$objectTitle = parent::getObjectTitle($assocId, $assocType);

		switch ($assocType) {
			case ASSOC_TYPE_ISSUE:
				$issueDao = DAORegistry::getDAO('IssueDAO');
				$issue = $issueDao->getById($assocId);
				if ($issue) {
					$objectTitle = $issue->getLocalizedTitle();
				}
				break;
		}

		return $objectTitle;
	}
}

?>
