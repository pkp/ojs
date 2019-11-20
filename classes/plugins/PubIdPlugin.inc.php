<?php

/**
 * @file classes/plugins/PubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PubIdPlugin
 * @ingroup plugins
 *
 * @brief Public identifiers plugins common functions
 */

import('lib.pkp.classes.plugins.PKPPubIdPlugin');

abstract class PubIdPlugin extends PKPPubIdPlugin {

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		$user = $request->getUser();
		$router = $request->getRouter();
		$context = $router->getContext($request);

		$notificationManager = new NotificationManager();
		switch ($request->getUserVar('verb')) {
			case 'assignPubIds':
				if (!$request->checkCSRF()) return new JSONMessage(false);
				$suffixFieldName = $this->getSuffixFieldName();
				$suffixGenerationStrategy = $this->getSetting($context->getId(), $suffixFieldName);
				if ($suffixGenerationStrategy != 'customId') {
					$submissionEnabled = $this->isObjectTypeEnabled('Submission', $context->getId());
					$representationEnabled = $this->isObjectTypeEnabled('Representation', $context->getId());
					if ($submissionEnabled || $representationEnabled) {
						$submissionDao = Application::getSubmissionDAO();
						$representationDao = Application::getRepresentationDAO();
						$submissions = Services::getMany([
							'contextId' => $context->getId(),
							'status' => STATUS_PUBLISHED,
							'count' => 5000, // large upper limit
						]);
						foreach ($submissions as $submission) {
							if ($submissionEnabled) {
								$submissionPubId = $submission->getStoredPubId($this->getPubIdType());
								if (empty($submissionPubId)) {
									$submissionPubId = $this->getPubId($submission);
									$submissionDao->changePubId($submission->getId(), $this->getPubIdType(), $submissionPubId);
								}
							}
							if ($representationEnabled) {
								$representations = $representationDao->getBySubmissionId($submission->getid(), $context->getId());
								while ($representation = $representations->next()) {
									$representationPubId = $representation->getStoredPubId($this->getPubIdType());
									if (empty($representationPubId)) {
										$representationPubId = $this->getPubId($representation);
										$representationDao->changePubId($representation->getId(), $this->getPubIdType(), $representationPubId);
									}
								}
							}
						}
					}
				}
				return new JSONMessage(true);
			default:
				return parent::manage($args, $request);
		}
	}

	//
	// Protected template methods from PKPPlubIdPlugin
	//

	/**
	 * @copydoc PKPPubIdPlugin::getPubId()
	 */
	function getPubId($pubObject) {
		// Get the pub id type
		$pubIdType = $this->getPubIdType();

		// If we already have an assigned pub id, use it.
		$storedPubId = $pubObject->getStoredPubId($pubIdType);
		if ($storedPubId) return $storedPubId;

		// Determine the type of the publishing object.
		$pubObjectType = $this->getPubObjectType($pubObject);

		// Initialize variables for publication objects.
		$submission = ($pubObjectType == 'Submission' ? $pubObject : null);
		$representation = ($pubObjectType == 'Representation' ? $pubObject : null);
		$submissionFile = ($pubObjectType == 'SubmissionFile' ? $pubObject : null);

		// Get the context id.
		if ($pubObjectType === 'Representation') {
			$publication = Services::get('publication')->get($pubObject->getData('publicationId'));
			$submission = Services::get('submission')->get($publication->getData('submissionId'));
			$contextId = $submission->getData('contextId');
		} elseif (in_array($pubObjectType, ['Publication', 'SubmissionFile'])) {
			$submission = Services::get('submission')->get($pubObject->getData('submissionId'));
			$contextId = $submission->getData('contextId');
		}

		// Check the context
		$context = $this->getContext($contextId);
		if (!$context) return null;
		$contextId = $context->getId();

		// Check whether pub ids are enabled for the given object type.
		$objectTypeEnabled = $this->isObjectTypeEnabled($pubObjectType, $contextId);
		if (!$objectTypeEnabled) return null;

		// Retrieve the pub id prefix.
		$pubIdPrefix = $this->getSetting($contextId, $this->getPrefixFieldName());
		if (empty($pubIdPrefix)) return null;

		// Generate the pub id suffix.
		$suffixFieldName = $this->getSuffixFieldName();
		$suffixGenerationStrategy = $this->getSetting($contextId, $suffixFieldName);
		switch ($suffixGenerationStrategy) {
			case 'customId':
				$pubIdSuffix = $pubObject->getData($suffixFieldName);
				break;

			case 'pattern':
				$suffixPatternsFieldNames = $this->getSuffixPatternsFieldNames();
				$pubIdSuffix = $this->getSetting($contextId, $suffixPatternsFieldNames[$pubObjectType]);

				// %j - journal initials
				$pubIdSuffix = PKPString::regexp_replace('/%j/', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale())), $pubIdSuffix);

				// %x - custom identifier
				if ($pubObject->getStoredPubId('publisher-id')) {
					$pubIdSuffix = PKPString::regexp_replace('/%x/', $pubObject->getStoredPubId('publisher-id'), $pubIdSuffix);
				}

				if ($submission) {
					// %a - article id
					$pubIdSuffix = PKPString::regexp_replace('/%a/', $submission->getId(), $pubIdSuffix);
				}

				if ($representation) {
					// %g - galley id
					$pubIdSuffix = PKPString::regexp_replace('/%g/', $representation->getId(), $pubIdSuffix);
				}

				if ($submissionFile) {
					// %f - file id
					$pubIdSuffix = PKPString::regexp_replace('/%f/', $submissionFile->getFileId(), $pubIdSuffix);
				}

				break;

			default:
				$pubIdSuffix = PKPString::strtolower($context->getAcronym($context->getPrimaryLocale()));

				if ($submission) {
					$pubIdSuffix .= '.' . $submission->getId();
				}

				if ($representation) {
					$pubIdSuffix .= '.g' . $representation->getId();
				}

				if ($submissionFile) {
					$pubIdSuffix .= '.f' . $submissionFile->getFileId();
				}
		}
		if (empty($pubIdSuffix)) return null;

		// Costruct the pub id from prefix and suffix.
		$pubId = $this->constructPubId($pubIdPrefix, $pubIdSuffix, $contextId);

		return $pubId;
	}

}


