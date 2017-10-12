<?php

/**
 * @file classes/services/GalleyService.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GalleyService
 * @ingroup services
 *
 * @brief Helper class that encapsulates galley business logic
 */

namespace OJS\Services;

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
							$values[$prop] = $this->getAPIHref(
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
					$values[$prop] = $isSubmissionGalley ? $galley->getRemoteURL() : null;
					break;
				case 'urlPublished':
					$values[$prop] = null;
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
					if ($file) {
						$values[$prop] = array(
							'id' => $file->getId(),
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
					}
					break;
			}
		}

		\HookRegistry::call('Galley::getProperties::values', array(&$values, $galley, $props, $args));

		return $values;
	}

	/**
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getSummaryProperties()
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
	 * @copydoc \PKP\Services\EntityProperties\EntityPropertyInterface::getFullProperties()
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
