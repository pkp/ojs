<?php

/**
 * @file plugins/pubIds/doi/DOIPubIdPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOIPubIdPlugin
 * @ingroup plugins_pubIds_doi
 *
 * @brief DOI plugin class
 */


import('classes.plugins.PubIdPlugin');

class DOIPubIdPlugin extends PubIdPlugin {

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
		return 'DOIPubIdPlugin';
	}

	/**
	 * @see PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.pubIds.doi.displayName');
	}

	/**
	 * @see PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.pubIds.doi.description');
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
			$doi = null;
		if (!$this->isExcluded($pubObject)) {
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

			$journal =& $this->getJournal($journalId);
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
					switch($pubObjectType) {
						case 'Issue':
							$doiSuffix = (string) $pubObject->getBestIssueId($journal);
							break;
						case 'Article':
							$doiSuffix = (string) $pubObject->getBestArticleId($journal);
							break;
						case 'Galley':
							$doiSuffix = (string) $pubObject->getBestGalleyId($journal);
							break;
						case 'SuppFile':
							$doiSuffix = (string) $pubObject->getBestSuppFileId($journal);
							break;
						default:
							assert(false);
					}

					// When the suffix equals the object's ID then
					// require an object-specific prefix to be sure that
					// the suffix is unique.
					if ($pubObjectType != 'Article' && $doiSuffix === (string) $pubObject->getId()) {
						$doiSuffix = strtolower_codesafe($pubObjectType{0}) . $doiSuffix;
					}
					break;

				case 'customId':
					$doiSuffix = $pubObject->getData('doiSuffix');
					break;

				case 'pattern':
					$doiSuffix = $this->getSetting($journalId, "doi${pubObjectType}SuffixPattern");

					// %j - journal initials
					$doiSuffix = String::regexp_replace('/%j/', String::strtolower($journal->getLocalizedSetting('initials', $journal->getPrimaryLocale())), $doiSuffix);

					// %x - custom identifier
					if ($pubObject->getStoredPubId('publisher-id')) {
						$doiSuffix = String::regexp_replace('/%x/', $pubObject->getStoredPubId('publisher-id'), $doiSuffix);
					}
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
						if ($article->getPages()) {
							$doiSuffix = String::regexp_replace('/%p/', $article->getPages(), $doiSuffix);
						}
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
					$doiSuffix = String::strtolower($journal->getLocalizedSetting('initials', $journal->getPrimaryLocale()));

					if ($issue) {
						$doiSuffix .= '.v' . $issue->getVolume() . 'i' . $issue->getNumber();
					} else {
						$doiSuffix .= '.v%vi%i';
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
	function getResolvingURL($journalId, $pubId) {
		return 'http://dx.doi.org/'.$this->_doiURLEncode($pubId);
	}

	/**
	 * @see PubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('doiSuffix', 'excludeDoi');
	}

	/**
	 * @see PubIdPlugin::getExcludeFormFieldName()
	 */
	function getExcludeFormFieldName() {
		return 'excludeDoi';
	}

	/**
	 * @see PubIdPlugin::isEnabled()
	 */
	function isEnabled($pubObjectType, $journalId) {
		return $this->getSetting($journalId, "enable${pubObjectType}Doi") == '1';
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
		return 'classes.form.DOISettingsForm';
	}

	/**
	 * @see PubIdPlugin::verifyData()
	 */
	function verifyData($fieldName, $fieldValue, &$pubObject, $journalId, &$errorMsg) {
		// Verify DOI uniqueness.
		if ($fieldName == 'doiSuffix') {
			if (empty($fieldValue)) return true;

			// Construct the potential new DOI with the posted suffix.
			$doiPrefix = $this->getSetting($journalId, 'doiPrefix');
			if (empty($doiPrefix)) return true;
			$newDoi = $doiPrefix . '/' . $fieldValue;

			if($this->checkDuplicate($newDoi, $pubObject, $journalId)) {
				return true;
			} else {
				$errorMsg = __('plugins.pubIds.doi.editor.doiSuffixCustomIdentifierNotUnique');
				return false;
			}
		}
		return true;
	}

	/**
	 * @see PubIdPlugin::validatePubId()
	 */
	function validatePubId($pubId) {
		return preg_match('/^\d+(.\d+)+\//', $pubId);
	}

	/*
	 * Private methods
	 */

	/**
	 * Encode DOI according to ANSI/NISO Z39.84-2005, Appendix E.
	 * @param $pubId string
	 * @return string
	 */
	function _doiURLEncode($pubId) {
		$search = array ('%', '"', '#', ' ', '<', '>', '{');
		$replace = array ('%25', '%22', '%23', '%20', '%3c', '%3e', '%7b');
		$pubId = str_replace($search, $replace, $pubId);
		return $pubId;
	}

}

?>
