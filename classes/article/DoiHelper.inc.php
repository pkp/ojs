<?php

/**
 * @file classes/article/DoiHelper.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DoiHelper
 * @ingroup article
 *
 * @brief A helper class to deal with public IDs. This class only exists
 *        to collect PID-related code in a central place before it is moved
 *        to plug-ins.
 *
 * FIXME: This code must be moved to a PID plug-ins in the next release.
 */


class DoiHelper {
	/**
	 * Get a DOI for the given publishing object.
	 * @param $pubObject object An Article, Issue,
	 *   ArticleGalley, IssueGalley or SuppFile
	 * @param $preview boolean If true, generate a non-persisted preview only.
	 * @return string|null
	 */
	function getDOI(&$pubObject, $preview = false) {
		// Determine the type of the publishing object.
		$allowedTypes = array(
			'Issue' => 'Issue',
			'Article' => 'Article',
			'ArticleGalley' => 'Galley',
			'SuppFile' => 'SuppFile'
		);
		$pubObjectType = null;
		foreach ($allowedTypes as $allowedType => $pubObjectTypeCandidate) {
			if (is_a($pubObject, $allowedType)) {
				$pubObjectType = $pubObjectTypeCandidate;
				break;
			}
		}
		if (is_null($pubObjectType)) {
			// This must be a dev error, so bail with an assertion.
			assert(false);
			return null;
		}

		// Initialize variables for publication objects.
		$issue = ($pubObjectType == 'Issue' ? $pubObject : null);
		$article = ($pubObjectType == 'Article' ? $pubObject : null);
		$galley = ($pubObjectType == 'Galley' ? $pubObject : null);
		$suppFile = ($pubObjectType == 'SuppFile' ? $pubObject : null);

		// Get the journal id of the object.
		if (in_array($pubObjectType, array('Issue', 'Article'))) {
			$journalId = $pubObject->getJournalId();
		} else {
			// Retrieve the published article.
			assert(is_a($pubObject, 'ArticleFile'));
			$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$article =& $articleDao->getPublishedArticleByArticleId($pubObject->getArticleId(), null, true);
			if (!$article) return null;

			// Now we can identify the journal.
			$journalId = $article->getJournalId();
		}

		$journal =& $this->_getJournal($journalId);
		if (!$journal) return null;

		// Check whether DOIs are enabled for the given object type.
		$doiEnabled = ($journal->getSetting("enable${pubObjectType}Doi") == '1');
		if (!$doiEnabled) return null;

		// If we already have an assigned DOI, use it.
		$storedDOI = $pubObject->getStoredPubId('doi');
		if ($storedDOI) return $storedDOI;

		// Retrieve the issue.
		if (!is_a($pubObject, 'Issue')) {
			assert(!is_null($article));
			$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueByArticleId($article->getId(), $journal->getId(), true);
		}
		if ($issue && $journal->getId() != $issue->getJournalId()) return null;

		// Retrieve the DOI prefix.
		$doiPrefix = $journal->getSetting('doiPrefix');
		if (empty($doiPrefix)) return null;

		// Generate the DOI suffix.
		$doiSuffixGenerationStrategy = $journal->getSetting('doiSuffix');
		switch ($doiSuffixGenerationStrategy) {
			case 'publisherId':
				// FIXME: Find a better solution when we work with Articles rather
				// than PublishedArticles.
				if (is_a($pubObject, 'PublishedArticle') && !is_a($pubObject, 'PublishedArticle')) {
					$doiSuffix = null;
				} else {
					$doiSuffix = (string) call_user_func_array(array($pubObject, "getBest${pubObjectType}Id"), array(&$journal));
					// When the suffix equals the object's ID then
					// require an object-specific pre-fix to be sure that
					// the suffix is unique.
					if ($pubObjectType != 'Article' && $doiSuffix === (string) $pubObject->getId()) {
						$doiSuffix = strtolower($pubObjectType{0}) . $doiSuffix;
					}
				}
				break;

			case 'customId':
				$doiSuffix = $pubObject->getData('doiSuffix');
				break;

			case 'pattern':
				if (!$issue) {
					$doiSuffix = null;
					break;
				}
				$doiSuffix = $journal->getSetting("doi${pubObjectType}SuffixPattern");

				// %j - journal initials
				$doiSuffix = String::regexp_replace('/%j/', String::strtolower($journal->getLocalizedSetting('initials')), $doiSuffix);

				if ($issue) {
					// %v - volume number
					$doiSuffix = String::regexp_replace('/%v/', $issue->getVolume(), $doiSuffix);
					// %i - issue number
					$doiSuffix = String::regexp_replace('/%i/', $issue->getNumber(), $doiSuffix);
					// %Y - year
					$doiSuffix = String::regexp_replace('/%Y/', $issue->getYear(), $doiSuffix);
				}

				if ($article) {
					// %a - article id
					$doiSuffix = String::regexp_replace('/%a/', $article->getId(), $doiSuffix);
					// %p - page number
					$doiSuffix = String::regexp_replace('/%p/', $article->getPages(), $doiSuffix);
				}

				if ($galley) {
					// %g - galley id
					$doiSuffix = String::regexp_replace('/%g/', $galley->getId(), $doiSuffix);
				}

				if ($suppFile) {
					// %s - supp file id
					$doiSuffix = String::regexp_replace('/%s/', $suppFile->getId(), $doiSuffix);
				}
				break;

			default:
				if (!$issue) {
					$doiSuffix = null;
					break;
				}
				$doiSuffix = String::strtolower($journal->getLocalizedSetting('initials'));

				if ($issue) {
					$doiSuffix .= '.v' . $issue->getVolume() . 'i' . $issue->getNumber();
				}

				if ($article) {
 					$doiSuffix .= '.' . $article->getId();
				}

				if ($galley) {
					$doiSuffix .= '.g' . $galley->getId();
				}

				if ($suppFile) {
					$doiSuffix .= '.s' . $suppFile->getId();
				}
		}
		if (empty($doiSuffix)) return null;

		// Join prefix and suffix.
		$doi = $doiPrefix . '/' . $doiSuffix;

		if (!$preview) {
			// Save the generated DOI.
			$pubObject->setStoredPubId('doi', $doi);
			foreach($this->_getDAOs() as $objectType => $daoName) {
				if (is_a($pubObject, $objectType)) {
					$dao =& DAORegistry::getDAO($daoName);
					$dao->changePubId($pubObject->getId(), 'doi', $doi);
					break;
				}
			}
		}

		return $doi;
	}

	/**
	 * Check whether the given suffix may lead to
	 * a duplicate DOI.
	 * @param $doiSuffix string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @return boolean
	 */
	function postedSuffixIsAdmissible($postedSuffix, &$pubObject, $journalId) {
		if (empty($postedSuffix)) return true;

		// FIXME: Hack to ensure that we get a published article if possible.
		// Remove this when we have migrated getBest...(), etc. to Article.
		if (is_a($pubObject, 'SectionEditorSubmission')) {
			$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$pubArticle =& $articleDao->getPublishedArticleByArticleId($pubObject->getId());
			if (is_a($pubArticle, 'PublishedArticle')) {
				unset($pubObject);
				$pubObject =& $pubArticle;
			}
		}

		// Construct the potential new DOI with the posted suffix.
		$journal =& $this->_getJournal($journalId);
		$doiPrefix = $journal->getSetting('doiPrefix');
		if (empty($doiPrefix)) return true;
		$newDoi = $doiPrefix . '/' . $postedSuffix;

		// Check all objects of the journal whether they have
		// the same DOI. This includes DOIs that are not yet generated
		// but could be generated at any moment if someone accessed
		// the object publicly.
		$typesToCheck = array('Issue', 'PublishedArticle', 'ArticleGalley', 'SuppFile');
		foreach($typesToCheck as $pubObjectType) {
			switch($pubObjectType) {
				case 'Issue':
					$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
					$objectsToCheck =& $issueDao->getIssues($journalId);
					break;

				case 'PublishedArticle':
					// FIXME: We temporarily have to use the published article
					// DAO here until we've moved DOI-generation to the Article
					// class.
					$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
					$objectsToCheck =& $articleDao->getPublishedArticlesByJournalId($journalId);
					break;

				case 'ArticleGalley':
					$galleyDao =& DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
					$objectsToCheck =& $galleyDao->getGalleysByJournalId($journalId);
					break;

				case 'SuppFile':
					$suppFileDao =& DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
					$objectsToCheck =& $suppFileDao->getSuppFilesByJournalId($journalId);
					break;
			}

			$excludedId = (is_a($pubObject, $pubObjectType) ? $pubObject->getId() : null);
			while ($objectToCheck =& $objectsToCheck->next()) {
				// The publication object for which the new DOI
				// should be admissible is to be ignored. Otherwise
				// we might get false positives by checking against
				// a DOI that we're about to change anyway.
				if ($objectToCheck->getId() == $excludedId) continue;

				// Check for ID clashes.
				$existingDoi = $this->getDOI($objectToCheck, true);
				if ($newDoi == $existingDoi) return false;

				unset($objectToCheck);
			}

			unset($objectsToCheck);
		}

		// We did not find any ID collision, so go ahead.
		return true;
	}


	/*
	 * Private helper methods
	 */
	/**
	 * Return an array that assigns object types
	 * to their corresponding DAOs.
	 * @return array
	 */
	function _getDAOs() {
		return array(
			'Issue' => 'IssueDAO',
			'Article' => 'ArticleDAO',
			'ArticleGalley' => 'ArticleGalleyDAO',
			'SuppFile' => 'SuppFileDAO'
		);
	}

	/**
	 * Get the journal object.
	 * @param $journalId integer
	 * @return Journal
	 */
	function &_getJournal($journalId) {
		assert(is_numeric($journalId));

		// Get the journal object from the context (optimized).
		$request =& Application::getRequest();
		$router =& $request->getRouter();
		$journal =& $router->getContext($request); /* @var $journal Journal */

		// Check whether we still have to retrieve the journal from the database.
		if (!$journal || $journal->getId() != $journalId) {
			unset($journal);
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getJournal($journalId);
		}

		return $journal;
	}
}

?>
