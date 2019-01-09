<?php

/**
 * @file classes/services/SubmissionService.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionService
 * @ingroup services
 *
 * @brief Extends the base submission helper service class with app-specific
 *  requirements.
 */

namespace APP\Services;

class SubmissionService extends \PKP\Services\PKPSubmissionService {

	/**
	 * Initialize hooks for extending PKPSubmissionService
	 */
	public function __construct() {
		\HookRegistry::register('Submission::isPublic', array($this, 'modifyIsPublic'));
		\HookRegistry::register('API::submissions::params', array($this, 'modifyAPISubmissionsParams'));
		\HookRegistry::register('Submission::getMany::queryBuilder', array($this, 'modifySubmissionQueryBuilder'));
		\HookRegistry::register('Submission::getMany::queryObject', array($this, 'modifySubmissionListQueryObject'));
		\HookRegistry::register('Submission::getProperties::summaryProperties', array($this, 'modifyProperties'));
		\HookRegistry::register('Submission::getProperties::fullProperties', array($this, 'modifyProperties'));
		\HookRegistry::register('Submission::getProperties::values', array($this, 'modifyPropertyValues'));
	}

	/**
	 * Modify the isPublic check on a submission, based on whether it is scheduled
	 * for publication in an issue and that issue is published.
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option boolean Is it public?
	 *		@option Submission
 	 * ]
	 */
	public function modifyIsPublic($hookName, $args) {
		$isPublic =& $args[0];
		$submission = $args[1];

		if (is_a($submission, 'PublishedArticle')) {
			$publishedArticle = $submission;
		} else {
			$publishedArticleDao = \DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId(
				$submission->getContextId(),
				$submission->getId(),
				true
			);
		}

		if (empty($publishedArticle)) {
			return;
		}

		$issueId = $publishedArticle->getIssueId();
		$issueDao = \DAORegistry::getDAO('IssueDAO');
		$issue = $issueDao->getById(
			$publishedArticle->getIssueId(),
			$publishedArticle->getJournalId(),
			true
		);

		if (!$issue || !$issue->getPublished()) {
			return;
		}

		$isPublic = true;
	}

	/**
	 * Collect and sanitize request params for submissions API endpoint
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option array $returnParams
	 *		@option SlimRequest $slimRequest
	 * ]
	 *
	 * @return array
	 */
	public function modifyAPISubmissionsParams($hookName, $args) {
		$returnParams =& $args[0];
		$slimRequest = $args[1];
		$requestParams = $slimRequest->getQueryParams();

		if (!empty($requestParams['sectionIds'])) {
			$sectionIds = $requestParams['sectionIds'];
			if (is_string($sectionIds) && strpos($sectionIds, ',') > -1) {
				$sectionIds = explode(',', $sectionIds);
			} elseif (!is_array($sectionIds)) {
				$sectionIds = array($sectionIds);
			}
			$returnParams['sectionIds'] = array_map('intval', $sectionIds);
		}
	}

	/**
	 * Run app-specific query builder methods for getMany
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option \APP\Services\QueryBuilders\SubmissionQueryBuilder
	 *		@option int Context ID
	 *		@option array Request args
	 * ]
	 *
	 * @return \APP\Services\QueryBuilders\SubmissionQueryBuilder
	 */
	public function modifySubmissionQueryBuilder($hookName, $args) {
		$submissionQB =& $args[0];
		$requestArgs = $args[1];

		if (!empty($requestArgs['sectionIds'])) {
			$submissionQB->filterBySections($requestArgs['sectionIds']);
		}
	}

	/**
	 * Add app-specific query statements to the list get query
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option object $queryObject
	 *		@option \APP\Services\QueryBuilders\SubmissionQueryBuilder $queryBuilder
	 * ]
	 *
	 * @return object
	 */
	public function modifySubmissionListQueryObject($hookName, $args) {
		$queryObject =& $args[0];
		$queryBuilder = $args[1];

		$queryObject = $queryBuilder->appGet($queryObject);
	}

	/**
	 * Add app-specific properties to submissions
	 *
	 * @param $hookName string Submission::getProperties::summaryProperties or
	 *  Submission::getProperties::fullProperties
	 * @param $args array [
	 * 		@option $props array Existing properties
	 * 		@option $submission Submission The associated submission
	 * 		@option $args array Request args
	 * ]
	 *
	 * @return array
	 */
	public function modifyProperties($hookName, $args) {
		$props =& $args[0];

		$props[] = 'issueSummary';
		$props[] = 'sectionSummary';
		$props[] = 'coverImageUrl';
		$props[] = 'coverImageAltText';

		return $props;
	}

	/**
	 * Add app-specific property values to a submission
	 *
	 * @param $hookName string Submission::getProperties::values
	 * @param $args array [
	 *    @option $values array Key/value store of property values
	 * 		@option $submission Submission The associated submission
	 * 		@option $props array Requested properties
	 * 		@option $args array Request args
	 * ]
	 *
	 * @return array
	 */
	public function modifyPropertyValues($hookName, $args) {
		$values =& $args[0];
		$submission = $args[1];
		$props = $args[2];
		$propertyArgs = $args[3];
		$request = $args[3]['request'];
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();

		$publishedArticle = null;
		if ($context) {
			$publishedArticleDao = \DAORegistry::getDAO('PublishedArticleDAO');
			$publishedArticle = $publishedArticleDao->getPublishedArticleByBestArticleId(
				(int) $context->getId(),
				$submission->getId(),
				true
			);
		}

		$issue = null;
		if ($publishedArticle) {
			$issueDao = \DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getById(
				$publishedArticle->getIssueId(),
				$publishedArticle->getJournalId(),
				true
			);
		}

		foreach ($props as $prop) {
			switch ($prop) {
				case 'urlPublished':
					$values[$prop] = $dispatcher->url(
						$request,
						ROUTE_PAGE,
						$context->getPath(),
						'article',
						'view',
						$submission->getBestArticleId()
					);
					break;
				case 'coverImageUrl':
					$values[$prop] = $submission->getCoverImageUrls(null);
					break;
				case 'coverImageAltText':
					$values[$prop] = $submission->getCoverImageAltText(null);
					break;
				case 'issue':
				case 'issueSummary':
					$values['issue'] = null;
					if ($issue) {
						$issueService = \Services::get('issue');
						$values['issue'] = ($prop === 'issue')
						? $issueService->getFullProperties($issue, $propertyArgs)
						: $issueService->getSummaryProperties($issue, $propertyArgs);
					}
					break;
				case 'section':
				case 'sectionSummary':
					$values['section'] = array();
					if ($context) {
						$sectionDao = \DAORegistry::getDAO('SectionDAO');
						$section = $sectionDao->getById($submission->getSectionId(), $context->getId());
						if (!empty($section)) {
							$sectionService = \Services::get('section');
							$values['section'] = ($prop === 'section')
								? $sectionService->getSummaryProperties($section, $propertyArgs)
								: $sectionService->getFullProperties($section, $propertyArgs);
						}
					}
					break;
				case 'galleys':
				case 'galleysSummary';
					$values['galleys'] = null;
					if ($publishedArticle) {
						$values['galleys'] = [];
						$galleyService = \Services::get('galley');
						$galleyArgs = array_merge($propertyArgs, array('parent' => $publishedArticle));
						$galleys = $publishedArticle->getGalleys();
						foreach ($galleys as $galley) {
							$values['galleys'][] = ($prop === 'galleys')
								? $galleyService->getFullProperties($galley, $galleyArgs)
								: $galleyService->getSummaryProperties($galley, $galleyArgs);
						}
					}
					break;
			}
		}
	}
}
