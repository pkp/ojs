<?php

/**
 * @file classes/services/GalleyService.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
use \PKP\Services\traits\EntityReadTrait;

class GalleyService implements EntityReadInterface, EntityWriteInterface, EntityPropertyInterface {
	use EntityReadTrait;

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::get()
	 */
	public function get($galleyId) {
		return DAORegistry::getDAO('ArticleGalleyDAO')->getById($galleyId);
	}

	/**
	 * Get galleys
	 *
	 * @param array $args {
	 *    @option int|array publicationIds
	 *    @option int count
	 * 	  @option int offset
	 * }
	 * @return array
	 */
	public function getMany($args = []) {
		$galleyQB = $this->_getQueryBuilder($args);
		$galleyQO = $galleyQB->get();
		$range = $this->getRangeByArgs($args);
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$result = $galleyDao->retrieveRange($galleyQO->toSql(), $galleyQO->getBindings(), $range);
		$queryResults = new DAOResultFactory($result, $galleyDao, '_fromRow');

		return $queryResults->toArray();
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityReadInterface::getMax()
	 */
	public function getMax($args = []) {
		$galleyQB = $this->_getQueryBuilder($args);
		$countQO = $galleyQB->countOnly()->get();
		$countRange = new DBResultRange($args['count'], 1);
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$countResult = $galleyDao->retrieveRange($countQO->toSql(), $countQO->getBindings(), $countRange);
		$countQueryResults = new DAOResultFactory($countResult, $galleyDao, '_fromRow');

		return (int) $countQueryResults->getCount();
	}

	/**
	 * Build the query object for getting galleys
	 *
	 * @see self::getMany()
	 * @return object Query object
	 */
	private function _getQueryBuilder($args = []) {

		$defaultArgs = [
			'publicationIds' => null,
		];

		$args = array_merge($defaultArgs, $args);

		$galleyQB = new GalleyQueryBuilder();
		if (!empty($args['publicationIds'])) {
			$galleyQB->filterByPublicationIds($args['publicationIds']);
		}

		\HookRegistry::call('Galley::getMany::queryBuilder', array($galleyQB, $args));

		return $galleyQB;
	}

	/**
	 * @copydoc \PKP\Services\interfaces\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($galley, $props, $args = null) {
		$request = $args['request'];
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();

		$values = [];

		foreach ($props as $prop) {
			switch ($prop) {
				case 'urlPublished':
					// TODO
					$values[$prop] = 'http://...';
					// $values[$prop] = $dispatcher->url(
					// 	$request,
					// 	ROUTE_PAGE,
					// 	$context->getPath(),
					// 	'article',
					// 	'view',
					// 	array($publication->getBestId(), $galley->getBestGalleyId())
					// );
					break;
				// case 'file':
				// 	$values[$prop] = null;
				// 	$file = $galley->getFile();
				// 	if (!$file) {
				// 		break;
				// 	}
				// 	$values[$prop] = array(
				// 		'id' => $file->getFileId(),
				// 		'fileName' => $file->getOriginalFileName(),
				// 	);
				// 	if (is_a($file, 'SubmissionFile')) {
				// 		$values[$prop]['revision'] = $file->getRevision();
				// 		$values[$prop]['fileStage'] = $file->getFileStage();
				// 		$values[$prop]['genreId'] = $file->getGenreId();
				// 		$values[$prop]['fileName'] = $file->getClientFileName();
				// 	}
				// 	if (is_a($file, 'SupplementaryFile')) {
				// 		$values[$prop]['metadata'] = array(
				// 			'description' => $file->getDescription(null),
				// 			'creator' => $file->getCreator(null),
				// 			'publisher' => $file->getPublisher(null),
				// 			'source' => $file->getSource(null),
				// 			'subject' => $file->getSubject(null),
				// 			'sponsor' => $file->getSponsor(null),
				// 			'dateCreated' => $file->getDateCreated(),
				// 			'language' => $file->getLanguage(),
				// 		);
				// 	} elseif (is_a($file, 'SubmissionArtworkFile')) {
				// 		$values[$prop]['metadata'] = array(
				// 			'caption' => $file->getCaption(),
				// 			'credit' => $file->getCredit(),
				// 			'copyrightOwner' => $file->getCopyrightOwner(),
				// 			'terms' => $file->getPermissionTerms(),
				// 			'width' => $file->getWidth(),
				// 			'height' => $file->getHeight(),
				// 			'physicalWidth' => $file->getPhysicalWidth(300),
				// 			'physicalHeight' => $file->getPhysicalHeight(300),
				// 		);
				// 	}

				// 	// Look for dependent files
				// 	if (is_a($file, 'SubmissionFile')) {
				// 		$values['dependentFiles'] = null;
				// 		$submissionFileDao = \DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
				// 		$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $file->getFileId(), $parent->getId(), SUBMISSION_FILE_DEPENDENT);
				// 		if ($dependentFiles) {
				// 			$values['dependentFiles'] = [];
				// 			foreach ($dependentFiles as $dependentFile) {
				// 				$dependentFileProps = array(
				// 					'id' => $dependentFile->getFileId(),
				// 					'fileName' => $dependentFile->getOriginalFileName(),
				// 				);
				// 				if (is_a($dependentFile, 'SubmissionFile')) {
				// 					$dependentFileProps['revision'] = $dependentFile->getRevision();
				// 					$dependentFileProps['fileStage'] = $dependentFile->getFileStage();
				// 					$dependentFileProps['genreId'] = $dependentFile->getGenreId();
				// 					$dependentFileProps['fileName'] = $dependentFile->getClientFileName();
				// 				}
				// 				if (is_a($dependentFile, 'SupplementaryFile')) {
				// 					$dependentFileProps['metadata'] = array(
				// 						'description' => $dependentFile->getDescription(null),
				// 						'creator' => $dependentFile->getCreator(null),
				// 						'publisher' => $dependentFile->getPublisher(null),
				// 						'source' => $dependentFile->getSource(null),
				// 						'subject' => $dependentFile->getSubject(null),
				// 						'sponsor' => $dependentFile->getSponsor(null),
				// 						'dateCreated' => $dependentFile->getDateCreated(),
				// 						'language' => $dependentFile->getLanguage(),
				// 					);
				// 				} elseif (is_a($dependentFile, 'SubmissionArtworkFile')) {
				// 					$dependentFileProps['metadata'] = array(
				// 						'caption' => $dependentFile->getCaption(),
				// 						'credit' => $dependentFile->getCredit(),
				// 						'copyrightOwner' => $dependentFile->getCopyrightOwner(),
				// 						'terms' => $dependentFile->getPermissionTerms(),
				// 						'width' => $dependentFile->getWidth(),
				// 						'height' => $dependentFile->getHeight(),
				// 						'physicalWidth' => $dependentFile->getPhysicalWidth(300),
				// 						'physicalHeight' => $dependentFile->getPhysicalHeight(300),
				// 					);
				// 				}
				// 				$values['dependentFiles'][] = $dependentFileProps;
				// 			}
				// 		}
				// 	}
				// 	break;
				default:
					$values[$prop] = $galley->getData($prop);
					break;
			}
		}

		$values = Services::get('schema')->addMissingMultilingualValues(SCHEMA_GALLEY, $values, $context->getSupportedLocales());

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
			]
		);

		// Check required fields if we're adding the object
		if ($action === VALIDATE_ACTION_ADD) {
			\ValidatorFactory::required(
				$validator,
				$schemaService->getRequiredProps(SCHEMA_GALLEY),
				$schemaService->getMultilingualProps(SCHEMA_GALLEY),
				$primaryLocale
			);
		}

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
		$galleyId = DAORegistry::getDAO('ArticleGalleyDAO')->insertObject($galley);
		$galley = $this->get($galleyId);

		// ...todo: upload file and stuff

		\HookRegistry::call('Galley::add', array($galley, $request));

		return $galley;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::edit()
	 */
	public function edit($galley, $params, $request) {
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO');

		$newGalley = $galleyDao->newDataObject();
		$newGalley->_data = array_merge($galley->_data, $params);

		\HookRegistry::call('Galley::edit', array($newGalley, $galley, $params, $request));

		$galleyDao->updateObject($newGalley);
		$newGalley = $this->get($newGalley->getId());

		return $newGalley;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityWriteInterface::delete()
	 */
	public function delete($galley) {
		\HookRegistry::call('Galley::delete::before', [$galley]);

		DAORegistry::getDAO('ArticleGalleyDAO')->deleteObject($galley);

		// Delete related submission files
		$publication = Services::get('publication')->get($galley->getData('publicationId'));

		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // Import constants
		$galleyFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_GALLEY, $galley->getId(), $publication->getData('submissionId'), SUBMISSION_FILE_PROOF);
		foreach ($galleyFiles as $file) {
			// delete dependent files for each galley file
			$submissionFileDao->deleteAllRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $file->getFileId(), SUBMISSION_FILE_DEPENDENT);
		}
		// delete the galley files.
		$submissionFileDao->deleteAllRevisionsByAssocId(ASSOC_TYPE_GALLEY, $galley->getId(), SUBMISSION_FILE_PROOF);

		\HookRegistry::call('Galley::delete', [$galley]);
	}
}
