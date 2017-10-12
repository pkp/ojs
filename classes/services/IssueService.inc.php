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
		\PluginRegistry::loadCategory('pubIds', true);
		$request = $args['request'];
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();
		$values = array();

		foreach ($props as $prop) {
			switch ($prop) {
				case 'id':
					$values[$prop] = (int) $issue->getId();
					break;
				case '_href':
					$values[$prop] = null;
					if (!empty($args['slimRequest'])) {
						$route = $args['slimRequest']->getAttribute('route');
						$arguments = $route->getArguments();
						$href = "{$arguments['contextPath']}/api/{$arguments['version']}/issues/" . $issue->getId();
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
				case 'publishedUrl':
					$values[$prop] = null;
					if ($context) {
						$values[$prop] = $dispatcher->url(
							$request,
							ROUTE_PAGE,
							$context->getPath(),
							'issue',
							'view',
							$issue->getBestIssueId()
						);
					}
					break;
				case 'articles':
					$values[$prop] = array();
					$publishedArticleDao = \DAORegistry::getDAO('PublishedArticleDAO');
					$publishedArticles = $publishedArticleDao->getPublishedArticles($issue->getId());
					if (!empty($publishedArticles)) {
						foreach ($publishedArticles as $article) {
							$values[$prop][] = \ServicesContainer::instance()
								->get('submission')
								->getSummaryProperties($article, $args);
						}
					}
					break;
				case 'sections':
					$values[$prop] = array();
					$sectionDao = \DAORegistry::getDAO('SectionDAO');
					$sections = $sectionDao->getByIssueId($issue->getId());
					if (!empty($sections)) {
						foreach ($sections as $section) {
							$sectionProperties = \ServicesContainer::instance()
								->get('section')
								->getSummaryProperties($section, $args);
							$customSequence = $sectionDao->getCustomSectionOrder($issue->getId(), $section->getId());
							if ($customSequence) {
								$sectionProperties['seq'] = $customSequence;
							}
							$values[$prop][] = $sectionProperties;
						}
					}
					break;
				case 'coverImageUrl':
					$values[$prop] = $issue->getCoverImage(null);
					break;
				case 'coverImageAltText':
					$values[$prop] = $issue->getCoverImageAltText(null);
					break;
				case 'galleys':
				case 'galleysSummary';
					$data = array();
					$issueGalleyDao = \DAORegistry::getDAO('IssueGalleyDAO');
					$galleys = $issueGalleyDao->getByIssueId($issue->getId());
					if ($galleys) {
						$galleyService = \ServicesContainer::instance()->get('galley');
						$galleyArgs = array_merge($args, array('parent' => $issue));
						foreach ($galleys as $galley) {
							$data[] = ($prop === 'galleys')
								? $galleyService->getFullProperties($galley, $galleyArgs)
								: $galleyService->getSummaryProperties($galley, $galleyArgs);
						}
					}
					$values['galleys'] = $data;
					break;
			}
		}

		\HookRegistry::call('Issue::getProperties::values', array(&$values, $issue, $props, $args));

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($issue, $args = null) {
		\PluginRegistry::loadCategory('pubIds', true);

		$props = array (
			'id','_href','title','description','identification','volume','number','year',
			'datePublished', 'publishedUrl', 'coverImageUrl','coverImageAltText','galleysSummary',
		);

		\HookRegistry::call('Issue::getProperties::summaryProperties', array(&$props, $issue, $args));

		return $this->getProperties($issue, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($issue, $args = null) {
		\PluginRegistry::loadCategory('pubIds', true);

		$props = array (
			'id','_href','title','description','identification','volume','number','year','isPublished',
			'isCurrent','datePublished','dateNotified','lastModified','publishedUrl','coverImageUrl',
			'coverImageAltText','articles','sections','tableOfContetnts','galleysSummary',
		);

		\HookRegistry::call('Issue::getProperties::fullProperties', array(&$props, $issue, $args));

		return $this->getProperties($issue, $props, $args);
	}
}
