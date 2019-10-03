<?php

/**
 * @file classes/services/IssueService.php
*
* Copyright (c) 2014-2019 Simon Fraser University
* Copyright (c) 2000-2019 John Willinsky
* Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
*
* @class IssueService
* @ingroup services
*
* @brief Helper class that encapsulates issue business logic
*/

namespace APP\Services;

use \Journal;
use \Services;
use \DBResultRange;
use \DAORegistry;
use \DAOResultFactory;
use \PKP\Services\interfaces\EntityPropertyInterface;
use \PKP\Services\interfaces\EntityReadInterface;
use \PKP\Services\traits\EntityReadTrait;
use \APP\Services\QueryBuilders\IssueQueryBuilder;

class IssueService implements EntityPropertyInterface, EntityReadInterface {
	use EntityReadTrait;

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::get()
	 */
	public function get($issueId) {
		return DAORegistry::getDAO('IssueDAO')->getById($issueId);
	}

	/**
	 * Get a collection of issues limited, filtered and sorted by $args
	 *
	 * @param array $args {
	 *		@option int contextId If not supplied, CONTEXT_ID_NONE will be used and
	 *			no submissions will be returned. To retrieve submissions from all
	 *			contexts, use CONTEXT_ID_ALL.
	 * 		@option int volumes
	 * 		@option int numbers
	 * 		@option int years
	 * 		@option boolean isPublished
	 * 		@option int count
	 * 		@option int offset
	 * 		@option string orderBy
	 * 		@option string orderDirection
	 * }
	 *
	 * @return \Iterator
	 */
	public function getMany($args = array()) {
		$issueListQB = $this->_getQueryBuilder($args);
		$issueListQO = $issueListQB->get();
		$range = $this->getRangeByArgs($args);
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$result = $issueDao->retrieveRange($issueListQO->toSql(), $issueListQO->getBindings(), $range);
		$queryResults = new DAOResultFactory($result, $issueDao, '_fromRow');

		return $queryResults->toIterator();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getMax()
	 */
	public function getMax($args = array()) {
		$issueListQB = $this->_getQueryBuilder($args);
		$countQO = $issueListQB->countOnly()->get();
		$countRange = new DBResultRange($args['count'], 1);
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$countResult = $issueDao->retrieveRange($countQO->toSql(), $countQO->getBindings(), $countRange);
		$countQueryResults = new DAOResultFactory($countResult, $issueDao, '_fromRow');

		return (int) $countQueryResults->getCount();
	}

	/**
	 * Build the issue query object for getMany requests
	 *
	 * @see self::getMany()
	 * @return object Query object
	 */
	private function _getQueryBuilder($args = array()) {

		$defaultArgs = array(
			'contextId' => CONTEXT_ID_NONE,
			'orderBy' => 'datePublished',
			'orderDirection' => 'DESC',
			'count' => 20,
			'offset' => 0,
			'isPublished' => null,
			'volumes' => null,
			'numbers' => null,
			'years' => null,
		);

		$args = array_merge($defaultArgs, $args);

		$issueListQB = new IssueQueryBuilder();
		$issueListQB
			->filterByContext($args['contextId'])
			->orderBy($args['orderBy'], $args['orderDirection'])
			->filterByPublished($args['isPublished'])
			->filterByVolumes($args['volumes'])
			->filterByNumbers($args['numbers'])
			->filterByYears($args['years']);

		\HookRegistry::call('Issue::getMany::queryBuilder', array($issueListQB, $args));

		return $issueListQB;
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

		switch ($journal->getData('publishingMode')) {
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
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($issue, $props, $args = null) {
		\PluginRegistry::loadCategory('pubIds', true);
		$request = $args['request'];
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();
		$router = $request->getRouter();
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
						$values[$prop] = $dispatcher->url(
							$args['request'],
							ROUTE_API,
							$arguments['contextPath'],
							'issues/' . $issue->getId()
						);
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
					$submissions = Services::get('submission')->getMany([
						'contextId' => $issue->getJournalId(),
						'issueIds' => $issue->getId(),
						'count' => 1000, // large upper limit
					]);
					if (!empty($submissions)) {
						foreach ($submissions as $submission) {
							$values[$prop][] = \Services::get('submission')->getSummaryProperties($submission, $args);
						}
					}
					break;
				case 'sections':
					$values[$prop] = array();
					$sectionDao = \DAORegistry::getDAO('SectionDAO');
					$sections = $sectionDao->getByIssueId($issue->getId());
					if (!empty($sections)) {
						foreach ($sections as $section) {
							$sectionProperties = \Services::get('section')->getSummaryProperties($section, $args);
							$customSequence = $sectionDao->getCustomSectionOrder($issue->getId(), $section->getId());
							if ($customSequence) {
								$sectionProperties['seq'] = $customSequence;
							}
							$values[$prop][] = $sectionProperties;
						}
					}
					break;
				case 'coverImageUrl':
					$values[$prop] = $issue->getCoverImageUrls(null);
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
						$galleyService = \Services::get('galley');
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

		$values = Services::get('schema')->addMissingMultilingualValues(SCHEMA_ISSUE, $values, $context->getSupportedLocales());

		\HookRegistry::call('Issue::getProperties::values', array(&$values, $issue, $props, $args));

		ksort($values);

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getSummaryProperties()
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
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getFullProperties()
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
