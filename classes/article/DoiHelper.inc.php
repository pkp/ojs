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
 * @brief A helper class to generate DOIs for a number of OJS publishing objects.
 *
 * FIXME: This code will be moved to a DOI PID plug-in in the next release.
 */


class DoiHelper {
	/**
	 * Get a DOI for the given publishing object.
	 * @param $pubObject object A PublishedArticle, Issue,
	 *   ArticleGalley, IssueGalley or SuppFile
	 * @param $preview boolean If true, generate a non-persisted preview only.
	 */
	function getDOI(&$pubObject, $preview = false) {
		// Determine the type of the publishing object.
		$allowedTypes = array(
			'Issue' => 'Issue',
			'PublishedArticle' => 'Article',
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

		// Get the journal object (optimized).
		if (in_array($pubObjectType, array('Issue', 'Article'))) {
			$journalId = $pubObject->getJournalId();
		} else {
			$journalId = null;
		}
		$request =& PKPApplication::getRequest();
		$journal =& $request->getJournal(); /* @var $journal Journal */
		if (!is_null($journalId) && (!$journal || $journal->getId() != $journalId)) {
			unset($journal);
			$journalDao =& DAORegistry::getDAO('JournalDAO');
			$journal =& $journalDao->getJournal($journalId);
		}
		if (!$journal) return null;

		// Check whether DOIs are enabled for the given object type.
		$doiEnabled = ($journal->getSetting("enable${pubObjectType}Doi") == '1');
		if (!$doiEnabled) return null;

		// If we already have an assigned DOI, use it.
		$storedDOI = $pubObject->getStoredDOI();
		if ($storedDOI) return $storedDOI;

		// Identify published objects.
		$issue = ($pubObjectType == 'Issue' ? $pubObject : null);
		$article = ($pubObjectType == 'Article' ? $pubObject : null);
		$galley = ($pubObjectType == 'Galley' ? $pubObject : null);
		$suppFile = ($pubObjectType == 'SuppFile' ? $pubObject : null);

		// Retrieve the published article.
		if (is_a($pubObject, 'ArticleFile')) {
			$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
			$article =& $articleDao->getPublishedArticleByArticleId($pubObject->getArticleId(), $journal->getId(), true);
			if (!$article) return null;
		}

		// Retrieve the issue.
		if (!is_a($pubObject, 'Issue')) {
			assert(!is_null($article));
			$issueDao =& DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue =& $issueDao->getIssueByArticleId($article->getId(), $journal->getId(), true);
		}
		if (!$issue || $journal->getId() != $issue->getJournalId()) return null;

		// Retrieve the DOI prefix.
		$doiPrefix = $journal->getSetting('doiPrefix');
		if (empty($doiPrefix)) return null;

		// Generate the DOI suffix.
		$doiSuffixGenerationStrategy = $journal->getSetting('doiSuffix');
		switch ($doiSuffixGenerationStrategy) {
			case 'publisherId':
				$doiSuffix = (string) call_user_method("getBest${pubObjectType}Id", $pubObject, $journal);
				// When the suffix equals the object's ID then
				// require an object-specific pre-fix to be sure that
				// the suffix is unique.
				if ($pubObjectType != 'Article' && $doiSuffix === (string) $pubObject->getId()) {
					$doiSuffix = strtolower($pubObjectType{0}) . $doiSuffix;
				}
				break;

			case 'customId':
				$doiSuffix = $pubObject->getData('doiSuffix');
				break;

			case 'pattern':
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
			$pubObject->setStoredDOI($doi);
			foreach($this->_getDAOs() as $objectType => $daoName) {
				if (is_a($pubObject, $objectType)) {
					$dao =& DAORegistry::getDAO($daoName);
					$dao->changeDOI($pubObject->getId(), $doi);
					break;
				}
			}
		}

		return $doi;
	}

	/**
	 * Check whether the given suffix already exists for
	 * any object of this journal.
	 * @param $doiSuffix string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @return boolean
	 */
	function doiSuffixExists($doiSuffix, &$pubObject, $journalId) {
		if (!empty($doiSuffix)) {
			foreach($this->_getDaos() as $pubObjectType => $daoName) {
				$dao =& DAORegistry::getDAO($daoName);
				if (is_a($pubObject, $pubObjectType)) {
					$excludedId = $pubObject->getId();
				} else {
					$excludedId = null;
				}
				if($dao->doiSuffixExists($doiSuffix, $excludedId, $journalId)) {
					return true;
				}
				unset($dao);
			}
		}
		return false;
	}

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
}

?>
