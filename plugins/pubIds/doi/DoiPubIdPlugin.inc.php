<?php

/**
 * @file plugins/pubIds/doi/DoiPubIdPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DoiPubIdPlugin
 * @ingroup plugins_pubIds_doi
 *
 * @brief DOI plugin class
 */


import('classes.plugins.PubIdPlugin');

class DoiPubIdPlugin extends PubIdPlugin {

	//
	// Implement template methods from PKPPlugin.
	//
	/**
	 * @see PubIdPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @see PKPPlugin::getName()
	 */
	function getName() {
		return 'DoiPubIdPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return Locale::translate('plugins.pubIds.doi.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return Locale::translate('plugins.pubIds.doi.description');
	}

	/**
	 * @see PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}


	//
	// Implement template methods from PubIdPlugin.
	//
	/**
	 * @see PubIdPlugin::getPubId()
	 */
	function getPubId(&$pubObject, $preview = false) {
		// Determine the type of the publishing object.
		$pubObjectType = $this->getPubObjectType($pubObject);

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
		$journalId = $journal->getId();

		// Check whether DOIs are enabled for the given object type.
		$doiEnabled = ($this->getSetting($journalId, "enable${pubObjectType}Doi") == '1');
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
		if ($issue && $journalId != $issue->getJournalId()) return null;

		// Retrieve the DOI prefix.
		$doiPrefix = $this->getSetting($journalId, 'doiPrefix');
		if (empty($doiPrefix)) return null;

		// Generate the DOI suffix.
		$doiSuffixGenerationStrategy = $this->getSetting($journalId, 'doiSuffix');
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
				$doiSuffix = $this->getSetting($journalId, "doi${pubObjectType}SuffixPattern");

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
			$this->setStoredPubId($pubObject, $pubObjectType, $doi);
		}

		return $doi;
	}

	/**
	 * @see PubIdPlugin::getPubIdType()
	 */
	function getPubIdType() {
		return 'doi';
	}

	/**
	 * @see PubIdPlugin::getPubIdDisplayType()
	 */
	function getPubIdDisplayType() {
		return 'DOI';
	}

	/**
	 * @see PubIdPlugin::getPubIdFullName()
	 */
	function getPubIdFullName() {
		return 'Digital Object Identifier';
	}

	/**
	 * @see PubIdPlugin::getResolvingURL()
	 */
	function getResolvingURL($pubId) {
		return 'http://dx.doi.org/'.$pubId;
	}

	/**
	 * @see PubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('doiSuffix');
	}

	/**
	 * @see PubIdPlugin::getDAOFieldNames()
	 */
	function getDAOFieldNames() {
		return array('pub-id::doi');
	}

	/**
	 * @see PubIdPlugin::getPubIdMetadataFile()
	 */
	function getPubIdMetadataFile() {
		return $this->getTemplatePath().'doiSuffixEdit.tpl';
	}

	/**
	 * @see PubIdPlugin::getSettingsFormName()
	 */
	function getSettingsFormName() {
		return 'classes.form.DoiSettingsForm';
	}

	/**
	 * @see PubIdPlugin::verifyData()
	 */
	function verifyData($fieldName, $fieldValue, &$pubObject, $journalId, &$errorMsg) {
		// Verify DOI uniqueness.
		assert($fieldName == 'doiSuffix');
		if($this->_suffixIsAdmissible($fieldValue, $pubObject, $journalId)) {
			return true;
		} else {
			$errorMsg = AppLocale::translate('plugins.pubIds.doi.editor.doiSuffixCustomIdentifierNotUnique');
			return false;
		}
	}

	/**
	 * @see PubIdPlugin::checkDuplicate()
	 */
	function checkDuplicate($pubId, &$pubObject, $journalId) {
		$doiParts = explode('/', $pubId, 2);
		$doiSuffix = array_pop($doiParts);
		return $this->_suffixIsAdmissible($doiSuffix, $pubObject, $journalId);
	}

	/**
	 * @see PubIdPlugin::validatePubId()
	 */
	function validatePubId($pubId) {
		$doiParts = explode('/', $pubId, 2);
		return count($doiParts) == 2;
	}


	//
	// Private helper methods
	//
	/**
	 * Check whether the given suffix may lead to
	 * a duplicate DOI.
	 * @param $newSuffix string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @return boolean
	 */
	function _suffixIsAdmissible($newSuffix, &$pubObject, $journalId) {
		if (empty($newSuffix)) return true;

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
		$doiPrefix = $this->getSetting($journalId, 'doiPrefix');
		if (empty($doiPrefix)) return true;
		$newDoi = $doiPrefix . '/' . $newSuffix;

		// Check all objects of the journal whether they have
		// the same DOI. This includes DOIs that are not yet generated
		// but could be generated at any moment if someone accessed
		// the object publicly. We have to check "real" DOIs rather than
		// the DOI suffixes only as a DOI with the given suffix may exist
		// (e.g. through import) even if the suffix itself is not in the
		// database.
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
				$existingDoi = $this->getPubId($objectToCheck, true);
				if ($newDoi == $existingDoi) return false;

				unset($objectToCheck);
			}

			unset($objectsToCheck);
		}

		// We did not find any ID collision, so go ahead.
		return true;
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
