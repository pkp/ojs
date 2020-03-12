<?php

/**
 * @file classes/plugins/PubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
					$issueEnabled = $this->isObjectTypeEnabled('Issue', $context->getId());
					$submissionEnabled = $this->isObjectTypeEnabled('Publication', $context->getId());
					$representationEnabled = $this->isObjectTypeEnabled('Representation', $context->getId());
					if ($issueEnabled) {
						$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
						$issues = $issueDao->getPublishedIssues($context->getId());
						while ($issue = $issues->next()) {
							$issuePubId = $issue->getStoredPubId($this->getPubIdType());
							if (empty($issuePubId)) {
								$issuePubId = $this->getPubId($issue);
								$issueDao->changePubId($issue->getId(), $this->getPubIdType(), $issuePubId);
							}
						}
					}
					if ($submissionEnabled || $representationEnabled) {
						$publicationDao = DAORegistry::getDAO('PublicationDAO'); /* @var $publicationDao PublicationDAO */
						$representationDao = Application::getRepresentationDAO();
						$submissions = Services::get('submission')->getMany([
							'contextId' => $context->getId(),
							'status' => STATUS_PUBLISHED,
							'count' => 5000, // large upper limit
						]);
						foreach ($submissions as $submission) {
							$publications = $submission->getData('publications');
							if ($submissionEnabled) {
								foreach ($publications as $publication) {
									$publicationPubId = $publication->getStoredPubId($this->getPubIdType());
									if (empty($publicationPubId)) {
										$publicationPubId = $this->getPubId($publication);
										$publicationDao->changePubId($publication->getId(), $this->getPubIdType(), $publicationPubId);
									}
								}
							}
							if ($representationEnabled) {
								foreach ($publications as $publication) {
									$representations = $representationDao->getByPublicationId($publication->getId(), $context->getId());
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
	 * @copydoc PKPPubIdPlugin::getPubObjectTypes()
	 */
	function getPubObjectTypes() {
		$pubObjectTypes = parent::getPubObjectTypes();
		array_push($pubObjectTypes, 'Issue');
		return $pubObjectTypes;
	}

	/**
	 * @copydoc PKPPubIdPlugin::checkDuplicate()
	 */
	function checkDuplicate($pubId, $pubObjectType, $excludeId, $contextId) {
		$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
		foreach ($this->getPubObjectTypes() as $type) {
			if ($type === 'Issue') {
				$excludeTypeId = $type === $pubObjectType ? $excludeId : null;
				if ($issueDao->pubIdExists($type, $pubId, $excludeTypeId, $contextId)) {
					return false;
				}
			}
		}

		return parent::checkDuplicate($pubId, $pubObjectType, $excludeId, $contextId);
	}

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
		$issue = ($pubObjectType == 'Issue' ? $pubObject : null);
		$submission = ($pubObjectType == 'Submission' ? $pubObject : null);
		$representation = ($pubObjectType == 'Representation' ? $pubObject : null);
		$submissionFile = ($pubObjectType == 'SubmissionFile' ? $pubObject : null);

		// Get the context id.
		if ($pubObjectType === 'Issue') {
			$contextId = $pubObject->getJournalId();
		} elseif ($pubObjectType === 'Representation') {
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

		// Retrieve the issue.
		if (!is_a($pubObject, 'Issue')) {
			assert(!is_null($submission));
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue = $issueDao->getBySubmissionId($submission->getId(), $contextId);
		}
		if ($issue && $contextId != $issue->getJournalId()) return null;

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

				// %j - journal initials, remove special characters and uncapitalize
				$pubIdSuffix = PKPString::regexp_replace('/%j/', PKPString::regexp_replace('/[^A-Za-z0-9]/', '', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale()))), $pubIdSuffix);

				// %x - custom identifier
				if ($pubObject->getStoredPubId('publisher-id')) {
					$pubIdSuffix = PKPString::regexp_replace('/%x/', $pubObject->getStoredPubId('publisher-id'), $pubIdSuffix);
				}

				if ($issue) {
					// %v - volume number
					$pubIdSuffix = PKPString::regexp_replace('/%v/', $issue->getVolume(), $pubIdSuffix);
					// %i - issue number
					$pubIdSuffix = PKPString::regexp_replace('/%i/', $issue->getNumber(), $pubIdSuffix);
					// %Y - year
					$pubIdSuffix = PKPString::regexp_replace('/%Y/', $issue->getYear(), $pubIdSuffix);
				}

				if ($submission) {
					// %a - article id
					$pubIdSuffix = PKPString::regexp_replace('/%a/', $submission->getId(), $pubIdSuffix);
					// %p - page number
					if ($submission->getPages()) {
						$pubIdSuffix = PKPString::regexp_replace('/%p/', $submission->getPages(), $pubIdSuffix);
					}
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
				$pubIdSuffix = PKPString::regexp_replace('/[^A-Za-z0-9]/', '', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale())));

				if ($issue) {
					$pubIdSuffix .= '.v' . $issue->getVolume() . 'i' . $issue->getNumber();
				} else {
					$pubIdSuffix .= '.v%vi%i';
				}

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

	//
	// Public API
	//
	/**
	 * Clear pubIds of all issue objects.
	 * @param $issue Issue
	 */
	function clearIssueObjectsPubIds($issue) {
		$submissionPubIdEnabled = $this->isObjectTypeEnabled('Submission', $issue->getJournalId());
		$representationPubIdEnabled = $this->isObjectTypeEnabled('Representation', $issue->getJournalId());
		$filePubIdEnabled = $this->isObjectTypeEnabled('SubmissionFile', $issue->getJournalId());
		if (!$submissionPubIdEnabled && !$representationPubIdEnabled && !$filePubIdEnabled) return false;

		$pubIdType = $this->getPubIdType();
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants

		$submissionIds = Services::get('submission')->getIds([
			'contextId' => $issue->getJournalId(),
			'issueIds' => $issue->getId(),
		]);
		$publicationDao = DAORegistry::getDAO('PublicationDAO'); /* @var $publicationDao PublicationDAO */
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		foreach ($submissionIds as $submissionId) {
			$submission = Services::get('submission')->get($submissionId);
			if ($submissionPubIdEnabled) { // Does this option have to be enabled here for?
				foreach ((array) $submission->getData('publications') as $publication) {
					$publicationDao->deletePubId($publication->getId(), $pubIdType);
				}
			}
			if ($representationPubIdEnabled || $filePubIdEnabled) { // Does this option have to be enabled here for?
				foreach ((array) $submission->getData('publications') as $publication) {
					$representations = Application::getRepresentationDAO()->getByPublicationId($publication->getId(), $submission->getData('contextId'));
					while ($representation = $representations->next()) {
						if ($representationPubIdEnabled) { // Does this option have to be enabled here for?
							Application::getRepresentationDAO()->deletePubId($representation->getId(), $pubIdType);
						}
						if ($filePubIdEnabled) { // Does this option have to be enabled here for?
							$articleProofFiles = $submissionFileDao->getAllRevisionsByAssocId(ASSOC_TYPE_REPRESENTATION, $representation->getId(), SUBMISSION_FILE_PROOF);
							foreach ($articleProofFiles as $articleProofFile) {
								$submissionFileDao->deletePubId($articleProofFile->getFileId(), $pubIdType);
							}
						}
					}
					unset($representations);
				}
			}
		}
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOs()
	 */
	function getDAOs() {
		return array_merge(parent::getDAOs(), array(DAORegistry::getDAO('IssueDAO')));
	}
}

