<?php

/**
 * @file classes/services/SubmissionService.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
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
		\HookRegistry::register('API::submissions::params', array($this, 'modifyAPISubmissionsParams'));
		\HookRegistry::register('Submission::getMany::queryBuilder', array($this, 'modifySubmissionQueryBuilder'));
		\HookRegistry::register('Submission::getMany::queryObject', array($this, 'modifySubmissionListQueryObject'));
		\HookRegistry::register('Submission::getProperties::values', array($this, 'modifyPropertyValues'));
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

		foreach ($requestParams as $param => $value) {
			switch ($param) {
				case 'sectionIds':
					if (is_string($value) && strpos($value, ',') > -1) {
						$value = explode(',', $value);
					} elseif (!is_array($value)) {
						$value = array($value);
					}
					$returnParams[$param] = array_map('intval', $value);
			}
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
		$request = $args[3]['request'];
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();

		foreach ($props as $prop) {
			switch ($prop) {
				case 'urlPublished':
					$values[$prop] = $dispatcher->url(
						$request,
						ROUTE_PAGE,
						$context->getPath(),
						'article',
						'view',
						$submission->getBestId()
					);
					break;
			}
		}
	}

	/**
	 * Get submissions ordered by section id
	 *
	 * This method replaces PublishedSubmissionDAO::getPublishedSubmissionsInSections()
	 * which was removed with v3.2.
	 *
	 * @param int $issueId
	 * @param int $contextId
	 * @return array submissions keyed to a section with some section details
	 */
	public function getInSections($issueId, $contextId) {

		$submissions = iterator_to_array($this->getMany(['contextId' => $contextId, 'issueIds' => $issueId]));
		usort($submissions, function($a, $b) {
			return $a->getCurrentPublication()->getData('seq') <= $b->getCurrentPublication()->getData('seq');
		});

		$bySections = [];
		foreach ($submissions as $submission) {
			$sectionId = $submission->getCurrentPublication()->getData('sectionId');
			if (empty($bySections[$sectionId])) {
				$section = \Application::get()->getSectionDao()->getById($sectionId);
				$bySections[$sectionId] = [
					'articles' => [],
					'title' => $section->getData('hideTitle') ? '' : $section->getLocalizedData('title'),
					'abstractsNotRequired' => $section->getData('abstractsNotRequired'),
					'hideAuthor' => $section->getData('hideAuthor'),
				];
			}
			$bySections[$sectionId]['articles'][] = $submission;
		}

		return $bySections;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::add()
	 */
	public function add($submission, $request) {
		$submission->setData('sectionId', $submission->getSectionId());
		return parent::add($submission, $request);
	}
}
