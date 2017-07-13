<?php

/**
 * @file classes/services/SubmissionService.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionService
 * @ingroup services
 *
 * @brief Extends the base submission helper service class with app-specific
 *  requirements.
 */

namespace OJS\Services;

class SubmissionService extends \PKP\Services\PKPSubmissionService {

	/**
	 * Initialize hooks for extending PKPSubmissionService
	 */
    public function __construct() {
		parent::__construct();

		\HookRegistry::register('Submission::getSubmissionList::queryBuilder', array($this, 'getSubmissionListQueryBuilder'));
		\HookRegistry::register('Submission::listQueryBuilder::get', array($this, 'getSubmissionListQueryObject'));
		\HookRegistry::register('Submission::toArray::defaultParams', array($this, 'toArrayDefaultParams'));
		\HookRegistry::register('Submission::toArray::output', array($this, 'toArrayOutput'));
	}

	/**
	 * Run app-specific query builder methods for getSubmissionList
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option QueryBuilders\SubmissionListQueryBuilder $submissionListQB
	 *		@option int $contextId
	 *		@option array $args
	 * ]
	 *
	 * @return QueryBuilders\SubmissionListQueryBuilder
	 */
	public function getSubmissionListQueryBuilder($hookName, $args) {
		$submissionListQB =& $args[0];
		$contextId = $args[1];
		$args = $args[2];

		if (!empty($args['sectionIds'])) {
			$submissionListQB->filterBySections($args['sectionIds']);
		}

		return $submissionListQB;
	}

	/**
	 * Add app-specific query statements to the list get query
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option object $queryObject
	 *		@option QueryBuilders\SubmissionListQueryBuilder $queryBuilder
	 * ]
	 *
	 * @return object
	 */
	public function getSubmissionListQueryObject($hookName, $args) {
		$queryObject =& $args[0];
		$queryBuilder = $args[1];

		$queryObject = $queryBuilder->appGet($queryObject);

		return true;
	}

	/**
	 * Add app-specific default params when converting a submission to an array
	 *
	 * @param $hookName string
	 * @param $args array [
	 * 		@option $defaultParams array Default param settings
	 * 		@option $params array Params requested for this conversion
	 * 		@option $submissions array Submissions to convert to array
	 * ]
	 *
	 * @return array
	 */
	public function toArrayDefaultParams($hookName, $args) {
		$defaultParams =& $args[0];
		$params = $args[1];
		$submissions = $args[2];

		$defaultParams['section'] = true;

		return true;
	}

	/**
	 * Add app-specific output when converting a submission to an array
	 *
	 * @param $hookName string
	 * @param $args array [
	 * 		@option $output array All submissions converted to array
	 * 		@option $params array Params requested for this conversion
	 * 		@option $submissions array Array of Submission objects
	 * ]
	 *
	 * @return array
	 */
	public function toArrayOutput($hookName, $args) {
		$output =& $args[0];
		$params = $args[1];
		$submissions = $args[2];

		// Create array of Submission objects with keys matching the $output
		// array
		$submissionObjects = array();
		foreach ($submissions as $submission) {

			if (!is_a($submission, 'Submission')) {
				error_log('Could not convert item to array because it is not a submission. ' . __LINE__);
			}

			$id = $submission->getId();
			foreach ($output as $key => $submissionArray) {
				if ($submissionArray['id'] === $id) {
					$submissionObjects[$key] = $submission;
				}
			}
		}

		foreach ($submissionObjects as $key => $submission) {

			if (!empty($params['section'])) {
				$output[$key]['section'] = (int) $submission->getSectionId();
			}

			if (!empty($params['urlPublished'])) {
				$request = \Application::getRequest();
				$dispatcher = $request->getDispatcher();
				$output[$key]['urlPublished'] = $dispatcher->url(
					$request,
					ROUTE_PAGE,
					null,
					'article',
					'view',
					$submission->getId()
				);
			}
		}

		return true;
	}
}
