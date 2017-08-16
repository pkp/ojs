<?php

/**
 * @file classes/services/IssueService.php
*
* Copyright (c) 2014-2017 Simon Fraser University
* Copyright (c) 2000-2017 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class IssueService
* @ingroup services
*
* @brief Helper class that encapsulates issue business logic
*/

namespace OJS\Services;

use \Journal;
use \PKP\Services\EntityProperties\PKPBaseEntityPropertyService;

class IssueService extends PKPBaseEntityPropertyService {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct($this);
	}

	/**
	 * Determine if a user can access galleys for a specific issue
	 *
	 * @param \Journal $journal
	 * @param \Issue $issue
	 *
	 * @return boolean
	 */
	public function userHasAccessToGalleys(\Journal $journal, \Issue $issue) {
		import('classes.issue.IssueAction');
		$issueAction = new \IssueAction();

		$subscriptionRequired = $issueAction->subscriptionRequired($issue, $journal);
		$subscribedUser = $issueAction->subscribedUser($journal, $issue);
		$subscribedDomain = $issueAction->subscribedDomain($journal, $issue);

		return !$subscriptionRequired || $issue->getAccessStatus() == ISSUE_ACCESS_OPEN || $subscribedUser || $subscribedDomain;
	}

	/**
	 * Determine issue access status based on journal publishing mode
	 * @param \Journal $journal
	 *
	 * @return int
	 */
	public function determineAccessStatus(Journal $journal) {
		import('classes.issue.Issue');
		$accessStatus = null;

		switch ($journal->getSetting('publishingMode')) {
			case PUBLISHING_MODE_SUBSCRIPTION:
			case PUBLISHING_MODE_NONE:
				$accessStatus = ISSUE_ACCESS_SUBSCRIPTION;
				break;
			case PUBLISHING_MODE_OPEN:
			default:
				$accessStatus = ISSUE_ACCESS_OPEN;
				break;
		}

		return $accessStatus;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($issue, $props, $args = null) {
		$values = array();
		foreach ($props as $prop) {
			switch ($prop) {
				case 'id':
					$values[$prop] = (int) $issue->getId();
					break;
				case '_href':
					$values[$prop] = null;
					$slimRequest = $args['slimRequest'];
					if ($slimRequest) {
						$route = $slimRequest->getAttribute('route');
						$arguments = $route->getArguments();
						$href = "/{$arguments['contextPath']}/api/{$arguments['version']}/issues/" . $issue->getIssue();
						$values[$prop] = $href;
					}
					break;
				case 'title':
					$values[$prop] = $issue->getTitle(null);
					break;
				case 'description':
					$values[$prop] = $issue->getDescription(null);
					break;
				case 'identification':
					$values[$prop] = $issue->getIssueIdentification();
					break;
				case 'volume':
					$values[$prop] = (int) $issue->getVolume();
					break;
				case 'number':
					$values[$prop] = $issue->getNumber();
					break;
				case 'year':
					$values[$prop] = (int) $issue->getYear();
					break;
				case 'isPublished':
					$values[$prop] = (bool) $issue->isPublished();
					break;
				case 'isCurrent':
					$values[$prop] = (bool) $issue->getCurrent();
					break;
				case 'datePublished':
					$values[$prop] = $issue->getDatePublished();
					break;
				case 'dateNotified':
					$values[$prop] = $issue->getDateNotified();
					break;
				case 'lastModified':
					$values[$prop] = $issue->getLastModified();
					break;
// 				case 'publishedUrl':
// 					$values[$prop] = (int) $issue->getYear();
// 					break;
// 				case 'articles':
// 					$values[$prop] = $issue->getIssueIdentification();
// 					break;
// 				case 'sections':
// 					$values[$prop] = (int) $issue->getVolume();
// 					break;
// 				case 'tableOfContents':
// 					$values[$prop] = $issue->getNumber();
// 					break;
// 				case 'galleys':
// 					$values[$prop] = (int) $issue->getYear();
// 					break;
// 				case 'doi':
// 					$values[$prop] = $issue->getId();
// 					break;
				case 'coverImageUrl':
					$values[$prop] = $issue->getCoverImageUrl(null);
					break;
				case 'coverImageAltText':
					$values[$prop] = $issue->getCoverImageAltText(null);
					break;
// 				case 'galleys':
// 				case 'galleysSummary':
// 					$values[$prop] = $issue->getId();
// 					break;
				default:
					$this->getUnknownProperty($author, $prop, $values);
			}
		}

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($author, $args = null) {
		$props = array (
			'id','_href','title','description','identification','volume','number','year','doi','coverImageUrl',
			'coverImageAltText','galleysSummary'
		);
		$props = $this->getSummaryPropertyList($author, $props);
		return $this->getProperties($author, $props);
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($author, $args = null) {
		$props = array (
			'id'
		);
		$props = $this->getFullPropertyList($author, $props);
		return $this->getProperties($author, $props);
	}
}
