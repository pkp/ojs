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
					$issueEnabled = $this->isObjectTypeEnabled('Issue', $context->getId());
					$submissionEnabled = $this->isObjectTypeEnabled('Submission', $context->getId());
					$representationEnabled = $this->isObjectTypeEnabled('Representation', $context->getId());
					if ($issueEnabled) {
						$issueDao = DAORegistry::getDAO('IssueDAO');
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
	 * @copydoc PKPPubIdPlugin::getPubObjectTypes()
	 */
	function getPubObjectTypes() {
		$pubObjectTypes = parent::getPubObjectTypes();
		array_push($pubObjectTypes, 'Issue');
		return $pubObjectTypes;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubObjects()
	 */
	function getPubObjects($pubObjectType, $contextId) {
		$objectsToCheck = null;
		switch($pubObjectType) {
			case 'Issue':
				$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
				$issues = $issueDao->getIssues($contextId);
				$objectsToCheck = $issues->toArray();
				break;
			default:
				$objectsToCheck = parent::getPubObjects($pubObjectType, $contextId);
				break;
		}
		return $objectsToCheck;
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
		if (in_array($pubObjectType, array('Issue', 'Submission'))) {
			$contextId = $pubObject->getJournalId();
		} else {
			// Retrieve the submission.
			assert(is_a($pubObject, 'Representation') || is_a($pubObject, 'SubmissionFile'));
			$submissionDao = Application::getSubmissionDAO();
			$submission = $submissionDao->getById($pubObject->getSubmissionId(), null, true);
			if (!$submission) return null;
			// Now we can identify the context.
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

				// %j - journal initials
				$pubIdSuffix = PKPString::regexp_replace('/%j/', PKPString::strtolower($context->getAcronym($context->getPrimaryLocale())), $pubIdSuffix);

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
				$pubIdSuffix = PKPString::strtolower($context->getAcronym($context->getPrimaryLocale()));

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
		$issueId = $issue->getId();
		$submissionPubIdEnabled = $this->isObjectTypeEnabled('Submission', $issue->getJournalId());
		$representationPubIdEnabled = $this->isObjectTypeEnabled('Representation', $issue->getJournalId());
		$filePubIdEnabled = $this->isObjectTypeEnabled('SubmissionFile', $issue->getJournalId());
		if (!$submissionPubIdEnabled && !$representationPubIdEnabled && !$filePubIdEnabled) return false;

		$pubIdType = $this->getPubIdType();
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$representationDao = Application::getRepresentationDAO();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants

		$submissions = Services::get('submission')->getMany([
			'contextId' => $issue->getJournalId(),
			'issueIds' => $issue->getId(),
			'count' => 5000, // large upper limit
		]);
		foreach ($submissions as $submission) {
			if ($submissionPubIdEnabled) { // Does this option have to be enabled here for?
				$submissionDao->deletePubId($submission->getId(), $pubIdType);
			}
			if ($representationPubIdEnabled || $filePubIdEnabled) { // Does this option have to be enabled here for?
				$representations = $representationDao->getBySubmissionId($submission->getId());
				while ($representation = $representations->next()) {
					if ($representationPubIdEnabled) { // Does this option have to be enabled here for?
						$representationDao->deletePubId($representation->getId(), $pubIdType);
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

	/**
	 * @copydoc PKPPubIdPlugin::getDAOs()
	 */
	function getDAOs() {
		return array_merge(parent::getDAOs(), array('Issue' => DAORegistry::getDAO('IssueDAO')));
	}

}


