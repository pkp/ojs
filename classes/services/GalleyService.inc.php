<?php

/**
 * @file classes/services/GalleyService.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GalleyService
 * @ingroup services
 *
 * @brief Helper class that encapsulates galley business logic
 */

namespace APP\Services;

use \DBResultRange;
use \DAOResultFactory;
use \DAORegistry;
use \Services;
use \PKP\Services\interfaces\EntityPropertyInterface;
use \PKP\Services\interfaces\EntityReadInterface;
use \PKP\Services\interfaces\EntityWriteInterface;
use \APP\Services\QueryBuilders\GalleyQueryBuilder;

class GalleyService implements EntityReadInterface, EntityWriteInterface, EntityPropertyInterface {

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::get()
	 */
	public function get($galleyId) {
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
		return $articleGalleyDao->getById($galleyId);
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getCount()
	 */
	public function getCount($args = []) {
		return $this->getQueryBuilder($args)->getCount();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getIds()
	 */
	public function getIds($args = []) {
		return $this->getQueryBuilder($args)->getIds();
	}

	/**
	 * Get a collection of Galley objects limited, filtered
	 * and sorted by $args
	 *
	 * @param array $args {
	 *    @option int|array publicationIds
	 * }
	 * @return \Iterator
	 */
	public function getMany($args = []) {
		$galleyQO = $this->getQueryBuilder($args)->getQuery();
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$result = $galleyDao->retrieveRange($galleyQO->toSql(), $galleyQO->getBindings());
		$queryResults = new DAOResultFactory($result, $galleyDao, '_fromRow');

		return $queryResults->toIterator();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getMax()
	 */
	public function getMax($args = []) {
		// Count/offset is not supported so getMax is always
		// the same as getCount
		return $this->getCount();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getQueryBuilder()
	 * @return GalleyQueryBuilder
	 */
	public function getQueryBuilder($args = []) {
		$galleyQB = new GalleyQueryBuilder();
		if (!empty($args['publicationIds'])) {
			$galleyQB->filterByPublicationIds($args['publicationIds']);
		}

		\HookRegistry::call('Galley::getMany::queryBuilder', array(&$galleyQB, $args));

		return $galleyQB;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($galley, $props, $args = null) {
		$request = $args['request'];
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();

		if (is_a($galley, 'ArticleGalley')) {
			$publication = !empty($args['publication'])
				? $args['publication']
				: $args['publication'] = Services::get('publication')->get($galley->getData('publicationId'));

			$submission = !empty($args['submission'])
				? $args['submission']
				: $args['submission'] = Services::get('submission')->get($publication->getData('submissionId'));
		}


		$values = [];

		foreach ($props as $prop) {
			switch ($prop) {
				case 'urlPublished':
					if (is_a($galley, 'IssueGalley')) {
						$values[$prop] = $dispatcher->url(
							$request,
							ROUTE_PAGE,
							$context->getPath(),
							'issue',
							'view',
							[
								$galley->getIssueId(),
								$galley->getId()
							]
						);
					} else {
						$values[$prop] = $dispatcher->url(
							$request,
							ROUTE_PAGE,
							$context->getPath(),
							'article',
							'view',
							[
								$submission->getBestId(),
								'version',
								$publication->getId(),
								$galley->getBestGalleyId(),
							]
						);
					}
					break;
				case 'file':
					$values[$prop] = null;
					if (is_a($galley, 'ArticleGalley')) {
						$submissionFile = Services::get('submissionFile')->get($galley->getData('submissionFileId'));
						if (empty($submissionFile)) {
							break;
						}
						$values[$prop] = Services::get('submissionFile')->getFullProperties($submissionFile, [
							'request' => $request,
							'submission' => $submission,
						]);
					}
					break;
				default:
					$values[$prop] = $galley->getData($prop);
					break;
			}
		}

		$values = Services::get('schema')->addMissingMultilingualValues(SCHEMA_GALLEY, $values, $context->getSupportedSubmissionLocales());

		\HookRegistry::call('Galley::getProperties::values', array(&$values, $galley, $props, $args));

		ksort($values);

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getSummaryProperties()
	 */
	public function getSummaryProperties($galley, $args = null) {
		$props = Services::get('schema')->getSummaryProps(SCHEMA_GALLEY);

		return $this->getProperties($galley, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getFullProperties()
	 */
	public function getFullProperties($galley, $args = null) {
		$props = Services::get('schema')->getFullProps(SCHEMA_GALLEY);

		return $this->getProperties($galley, $props, $args);
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::validate()
	 */
	public function validate($action, $props, $allowedLocales, $primaryLocale) {
		$schemaService = Services::get('schema');

		import('lib.pkp.classes.validation.ValidatorFactory');
		$validator = \ValidatorFactory::make(
			$props,
			$schemaService->getValidationRules(SCHEMA_GALLEY, $allowedLocales),
			[
				'locale.regex' => __('validator.localeKey'),
				'urlPath.regex' => __('validator.alpha_dash'),
			]
		);

		// Check required fields
		\ValidatorFactory::required(
			$validator,
			$action,
			$schemaService->getRequiredProps(SCHEMA_GALLEY),
			$schemaService->getMultilingualProps(SCHEMA_GALLEY),
			$allowedLocales,
			$primaryLocale
		);

		// Check for input from disallowed locales
		\ValidatorFactory::allowedLocales($validator, $schemaService->getMultilingualProps(SCHEMA_GALLEY), $allowedLocales);

		// The publicationId must match an existing publication that is not yet published
		$validator->after(function($validator) use ($props) {
			if (isset($props['publicationId']) && !$validator->errors()->get('publicationId')) {
				$publication = Services::get('publication')->get($props['publicationId']);
				if (!$publication) {
					$validator->errors()->add('publicationId', __('galley.publicationNotFound'));
				} else if (Services::get('publication')->isPublished($publication)) {
					$validator->errors()->add('publicationId', __('galley.editPublishedDisabled'));
				}
			}
		});

		if ($validator->fails()) {
			$errors = $schemaService->formatValidationErrors($validator->errors(), $schemaService->get(SCHEMA_GALLEY), $allowedLocales);
		}

		\HookRegistry::call('Galley::validate', array(&$errors, $action, $props, $allowedLocales, $primaryLocale));

		return $errors;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::add()
	 */
	public function add($galley, $request) {
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
		$galleyId = $articleGalleyDao->insertObject($galley);
		$galley = $this->get($galleyId);

		\HookRegistry::call('Galley::add', array(&$galley, $request));

		return $galley;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::edit()
	 */
	public function edit($galley, $params, $request) {
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */

		$newGalley = $galleyDao->newDataObject();
		$newGalley->_data = array_merge($galley->_data, $params);

		\HookRegistry::call('Galley::edit', array(&$newGalley, $galley, $params, $request));

		$galleyDao->updateObject($newGalley);
		$newGalley = $this->get($newGalley->getId());

		return $newGalley;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::delete()
	 */
	public function delete($galley) {
		\HookRegistry::call('Galley::delete::before', [&$galley]);

		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $articleGalleyDao ArticleGalleyDAO */
		$articleGalleyDao->deleteObject($galley);

		// Delete related submission files
		$submissionFilesIterator = Services::get('submissionFile')->getMany([
			'assocTypes' => [ASSOC_TYPE_GALLEY],
			'assocIds' => [$galley->getId()],
		]);
		foreach ($submissionFilesIterator as $submissionFile) {
			Services::get('submissionFile')->delete($submissionFile);
		}

		\HookRegistry::call('Galley::delete', [&$galley]);
	}
}
