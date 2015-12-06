<?php

/**
 * @file classes/plugins/PubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
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
	 * Constructor
	 */
	function PubIdPlugin() {
		parent::PKPPubIdPlugin();
	}

	//
	// Implement template methods from Plugin
	//
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path) {
		return parent::register($category, $path);
	}

	//
	// Protected template methods from PKPPlubIdPlugin
	//
	/**
	 * @copydoc PKPPubIdPlugin::getPubObjectTypes()
	 */
	function getPubObjectTypes() {
		return array('Issue', 'Article', 'Representation', 'SubmissionFile');
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

			case 'Article':
				$articleDao = Application::getSubmissionDAO(); /* @var $articleDao PublishedArticleDAO */
				$articles = $articleDao->getByContextId($contextId);
				$objectsToCheck = $articles->toArray();
				break;

			case 'Representation':
				$representationDao = Application::getRepresentationDAO(); /* @var $representationDao ArticleGalleyDAO */
				$representations = $representationDao->getByJournalId($contextId);
				$objectsToCheck = $representations->toArray();
				break;

			case 'SubmissionFile':
				$representationDao = Application::getRepresentationDAO();
				$representations = $representationDao->getByJournalId($contextId);
				$objectsToCheck = array();
				$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
				while ($representation = $representations->next()) {
					$objectsToCheck = array_merge($objectsToCheck, $submissionFileDao->getAllRevisionsByAssocId(ASSOC_TYPE_REPRESENTATION, $representation->getId(), SUBMISSION_FILE_PROOF));
				}
				unset($representations);
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
		$article = ($pubObjectType == 'Article' ? $pubObject : null);
		$representation = ($pubObjectType == 'Representation' ? $pubObject : null);
		$submissionFile = ($pubObjectType == 'SubmissionFile' ? $pubObject : null);

		// Get the context id.
		if (in_array($pubObjectType, array('Issue', 'Article'))) {
			$contextId = $pubObject->getJournalId();
		} else {
			// Retrieve the article.
			assert(is_a($pubObject, 'Representation') || is_a($pubObject, 'SubmissionFile'));
			$articleDao = DAORegistry::getDAO('ArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$article = $articleDao->getById($pubObject->getSubmissionId(), null, true);
			if (!$article) return null;
			// Now we can identify the context.
			$contextId = $article->getJournalId();
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
			assert(!is_null($article));
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue = $issueDao->getIssueByArticleId($article->getId(), $contextId);
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

				if ($article) {
					// %a - article id
					$pubIdSuffix = PKPString::regexp_replace('/%a/', $article->getId(), $pubIdSuffix);
					// %p - page number
					if ($article->getPages()) {
						$pubIdSuffix = PKPString::regexp_replace('/%p/', $article->getPages(), $pubIdSuffix);
					}
				}

				if ($representation) {
					// %g - galley id
					$pubIdSuffix = PKPString::regexp_replace('/%g/', $representation->getId(), $pubIdSuffix);
				}

				if ($submissionFile) {
					// %f - file id
					$pubIdSuffix = PKPString::regexp_replace('/%f/', $submissionFile->getId(), $pubIdSuffix);
				}

				break;

			default:
				$pubIdSuffix = PKPString::strtolower($context->getAcronym($context->getPrimaryLocale()));

				if ($issue) {
					$pubIdSuffix .= '.v' . $issue->getVolume() . 'i' . $issue->getNumber();
				} else {
					$pubIdSuffix .= '.v%vi%i';
				}

				if ($article) {
					$pubIdSuffix .= '.' . $article->getId();
				}

				if ($representation) {
					$pubIdSuffix .= '.g' . $representation->getId();
				}

				if ($submissionFile) {
					$pubIdSuffix .= '.f' . $submissionFile->getId();
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
		$articlePubIdEnabled = $this->isObjectTypeEnabled('Article', $issue->getJournalId());
		$representationPubIdEnabled = $this->isObjectTypeEnabled('Representation', $issue->getJournalId());
		$filePubIdEnabled = $this->isObjectTypeEnabled('SubmissionFile', $issue->getJournalId());
		if (!$articlePubIdEnabled && !$representationPubIdEnabled && !$filePubIdEnabled) return false;

		$pubIdType = $this->getPubIdType();
		$articleDao = DAORegistry::getDAO('ArticleDAO');
		$publishedArticleDao = DAORegistry::getDAO('PublishedArticleDAO');
		$representationDao = Application::getRepresentationDAO();
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants
		import('lib.pkp.classes.submission.SubmissionFileDAODelegate');
		$submissionFileDaoDelegate = new SubmissionFileDAODelegate();

		$publishedArticles = $publishedArticleDao->getPublishedArticles($issueId);
		foreach ($publishedArticles as $publishedArticle) {
			if ($articlePubIdEnabled) { // Does this option have to be enabled here for?
				$articleDao->deletePubId($publishedArticle->getId(), $pubIdType);
			}
			if ($representationPubIdEnabled || $filePubIdEnabled) { // Does this option have to be enabled here for?
				$representations = $representationDao->getBySubmissionId($publishedArticle->getId());
				while ($representation = $representations->next()) {
					if ($representationPubIdEnabled) { // Does this option have to be enabled here for?
						$representationDao->deletePubId($representation->getId(), $pubIdType);
					}
					if ($filePubIdEnabled) { // Does this option have to be enabled here for?
						$articleProofFiles = $submissionFileDao->getAllRevisionsByAssocId(ASSOC_TYPE_REPRESENTATION, $representation->getId(), SUBMISSION_FILE_PROOF);
						foreach ($articleProofFiles as $articleProofFile) {
							$submissionFileDaoDelegate->deletePubId($articleProofFile->getFileId(), $pubIdType);
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
		$representationDao = Application::getRepresentationDAO();
		import('lib.pkp.classes.submission.SubmissionFileDAODelegate');
		$submissionFileDAODelegete = new SubmissionFileDAODelegate();
		return  array(
			'Issue' => DAORegistry::getDAO('IssueDAO'),
			'Article' => DAORegistry::getDAO('ArticleDAO'),
			'Representation' => $representationDao,
			'SubmissionFile' => $submissionFileDAODelegete,
		);
	}

}

?>
