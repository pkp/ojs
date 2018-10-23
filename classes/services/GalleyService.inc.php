<?php

/**
 * @file classes/services/GalleyService.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleyService
 * @ingroup services
 *
 * @brief Helper class that encapsulates galley business logic
 */

namespace OJS\Services;

use \ServicesContainer;
use \PKP\Services\EntityProperties\PKPBaseEntityPropertyService;

class GalleyService extends PKPBaseEntityPropertyService {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct($this);
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getProperties()
	 */
	public function getProperties($galley, $props, $args = null) {
		\PluginRegistry::loadCategory('pubIds', true);
		$request = $args['request'];
		$parent = $args['parent'];
		$context = $request->getContext();
		$isSubmissionGalley = is_a($galley, 'Representation');
		$isIssueGalley = is_a($galley, 'IssueFile');
		$dispatcher = $request->getDispatcher();
		$router = $request->getRouter();

		$values = array();

		foreach ($props as $prop) {
			switch ($prop) {
				case 'id':
					$values[$prop] = $galley->getId();
					break;
				case '_parent':
					$values[$prop] = null;
					if (!empty($args['slimRequest'])) {
						$route = $args['slimRequest']->getAttribute('route');
						$arguments = $route->getArguments();
						$parentPath = null;
						if ($isSubmissionGalley) {
							$parentPath = 'submissions';
							$parentId = $galley->getSubmissionId();
						} elseif ($isIssueGalley) {
							$parentPath = 'issues';
							$parentId = $galley->getIssueId();
						}
						if ($parentPath) {
							$values[$prop] = $router->getApiUrl(
								$args['request'],
								$arguments['contextPath'],
								$arguments['version'],
								$parentPath,
								$parentId
							);
						}
					}
					break;
				case 'locale':
					$values[$prop] = $galley->getLocale();
					break;
				case 'label':
					$values[$prop] = $galley->getLabel(null);
					break;
				case 'urlRemote':
					$values[$prop] = $isSubmissionGalley ? $galley->getRemoteURL() : '';
					break;
				case 'urlPublished':
					$values[$prop] = '';
					if ($isSubmissionGalley) {
						$parentPath = 'article';
						$parentId = $parent->getBestArticleId();
					} elseif ($isIssueGalley) {
						$parentPath = 'issue';
						$parentId = $parent->getBestIssueId();
					}
					if ($context && $parentPath) {
						$values[$prop] = $dispatcher->url(
							$request,
							ROUTE_PAGE,
							$context->getPath(),
							$parentPath,
							'view',
							array($parentId, $galley->getBestGalleyId())
						);
					}
					break;
				case 'seq':
				case 'sequence':
					$values[$prop] = $galley->getSequence();
					break;
				case 'file':
					$values[$prop] = null;
					$file = $galley->getFile();
					if (!$file) {
						break;
					}
					$values[$prop] = array(
						'id' => $file->getFileId(),
						'fileName' => $file->getOriginalFileName(),
					);
					if (is_a($file, 'SubmissionFile')) {
						$values[$prop]['revision'] = $file->getRevision();
						$values[$prop]['fileStage'] = $file->getFileStage();
						$values[$prop]['genreId'] = $file->getGenreId();
						$values[$prop]['fileName'] = $file->getClientFileName();
					}
					if (is_a($file, 'SupplementaryFile')) {
						$values[$prop]['metadata'] = array(
							'description' => $file->getDescription(null),
							'creator' => $file->getCreator(null),
							'publisher' => $file->getPublisher(null),
							'source' => $file->getSource(null),
							'subject' => $file->getSubject(null),
							'sponsor' => $file->getSponsor(null),
							'dateCreated' => $file->getDateCreated(),
							'language' => $file->getLanguage(),
						);
					} elseif (is_a($file, 'SubmissionArtworkFile')) {
						$values[$prop]['metadata'] = array(
							'caption' => $file->getCaption(),
							'credit' => $file->getCredit(),
							'copyrightOwner' => $file->getCopyrightOwner(),
							'terms' => $file->getPermissionTerms(),
							'width' => $file->getWidth(),
							'height' => $file->getHeight(),
							'physicalWidth' => $file->getPhysicalWidth(300),
							'physicalHeight' => $file->getPhysicalHeight(300),
						);
					}

					// Look for dependent files
					if (is_a($file, 'SubmissionFile')) {
						$values['dependentFiles'] = null;
						$submissionFileDao = \DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
						$dependentFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_SUBMISSION_FILE, $file->getFileId(), $parent->getId(), SUBMISSION_FILE_DEPENDENT);
						if ($dependentFiles) {
							$values['dependentFiles'] = array();
							foreach ($dependentFiles as $dependentFile) {
								$dependentFileProps = array(
									'id' => $dependentFile->getFileId(),
									'fileName' => $dependentFile->getOriginalFileName(),
								);
								if (is_a($dependentFile, 'SubmissionFile')) {
									$dependentFileProps['revision'] = $dependentFile->getRevision();
									$dependentFileProps['fileStage'] = $dependentFile->getFileStage();
									$dependentFileProps['genreId'] = $dependentFile->getGenreId();
									$dependentFileProps['fileName'] = $dependentFile->getClientFileName();
								}
								if (is_a($dependentFile, 'SupplementaryFile')) {
									$dependentFileProps['metadata'] = array(
										'description' => $dependentFile->getDescription(null),
										'creator' => $dependentFile->getCreator(null),
										'publisher' => $dependentFile->getPublisher(null),
										'source' => $dependentFile->getSource(null),
										'subject' => $dependentFile->getSubject(null),
										'sponsor' => $dependentFile->getSponsor(null),
										'dateCreated' => $dependentFile->getDateCreated(),
										'language' => $dependentFile->getLanguage(),
									);
								} elseif (is_a($dependentFile, 'SubmissionArtworkFile')) {
									$dependentFileProps['metadata'] = array(
										'caption' => $dependentFile->getCaption(),
										'credit' => $dependentFile->getCredit(),
										'copyrightOwner' => $dependentFile->getCopyrightOwner(),
										'terms' => $dependentFile->getPermissionTerms(),
										'width' => $dependentFile->getWidth(),
										'height' => $dependentFile->getHeight(),
										'physicalWidth' => $dependentFile->getPhysicalWidth(300),
										'physicalHeight' => $dependentFile->getPhysicalHeight(300),
									);
								}
								$values['dependentFiles'][] = $dependentFileProps;
							}
						}
					}
					break;
			}
		}

		$values = ServicesContainer::instance()->get('schema')->addMissingMultilingualValues(SCHEMA_GALLEY, $values, $context->getSupportedLocales());

		\HookRegistry::call('Galley::getProperties::values', array(&$values, $galley, $props, $args));

		ksort($values);

		return $values;
	}

	/**
	 * Returns summary properties for a galley
	 * @param ArticleGalley|IssueGalley $galley
	 * @param array extra arguments
	 *		$args['request'] PKPRequest Required
	 *		$args['parent'] Submission|Issue Required
	 *		$args['slimRequest'] SlimRequest
	 * @return array
	 */
	public function getSummaryProperties($galley, $args = null) {
		\PluginRegistry::loadCategory('pubIds', true);

		$props = array (
			'id','_parent','locale','label','seq','urlRemote','urlPublished'
		);

		\HookRegistry::call('Galley::getProperties::summaryProperties', array(&$props, $galley, $args));

		return $this->getProperties($galley, $props, $args);
	}

	/**
	 * Returns full properties for a galley
	 * @param ArticleGalley|IssueGalley $galley
	 * @param array extra arguments
	 *		$args['request'] PKPRequest Required
	 *		$args['parent'] Submission|Issue Required
	 *		$args['slimRequest'] SlimRequest
	 * @return array
	 */
	public function getFullProperties($galley, $args = null) {
		\PluginRegistry::loadCategory('pubIds', true);

		$props = array (
			'id','_parent','locale','label','seq','urlRemote','urlPublished','file'
		);

		\HookRegistry::call('Galley::getProperties::fullProperties', array(&$props, $galley, $args));

		return $this->getProperties($galley, $props, $args);
	}
}
