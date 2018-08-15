<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlIssueFilter.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlIssueFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a Native XML document to a set of issues
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportFilter');

class NativeXmlIssueFilter extends NativeImportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML issue import');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlIssueFilter';
	}


	//
	// Implement template methods from NativeImportFilter
	//
	/**
	 * Return the plural element name
	 * @return string
	 */
	function getPluralElementName() {
		return 'issues';
	}

	/**
	 * Get the singular element name
	 * @return string
	 */
	function getSingularElementName() {
		return 'issue';
	}

	/**
	 * Handle a singular element import.
	 * @param $node DOMElement
	 * @return Issue
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		$issueDao = DAORegistry::getDAO('IssueDAO');
		// if the issue identification matches an existing issue, flag to process only child objects
		$issueExists = false;
		$issue = $this->_issueExists($node);
		if ($issue) {
			$issueExists = true;
		} else {
			// Create and insert the issue (ID needed for other entities)
			$issue = $issueDao->newDataObject();
			$issue->setJournalId($context->getId());
			$issue->setPublished($node->getAttribute('published'));
			$issue->setCurrent($node->getAttribute('current'));
			$issue->setAccessStatus($node->getAttribute('access_status'));
			$issueDao->insertObject($issue);
			$deployment->addProcessedObjectId(ASSOC_TYPE_ISSUE, $issue->getId());
		}
		$deployment->setIssue($issue);

		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				$this->handleChildElement($n, $issue, $issueExists);
			}
		}
		if (!$issueExists) {
			$issueDao->updateObject($issue); // Persist setters
		}
		return $issue;
	}

	/**
	 * Handle an element whose parent is the issue element.
	 * @param $n DOMElement
	 * @param $issue Issue
	 * @param $processOnlyChildren boolean Do not modify the issue itself, only generate child objects
	 */
	function handleChildElement($n, $issue, $processOnlyChildren) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		$localizedSetterMappings = $this->_getLocalizedIssueSetterMappings();
		$dateSetterMappings = $this->_getDateIssueSetterMappings();

		if (isset($localizedSetterMappings[$n->tagName])) {
			if (!$processOnlyChildren) {
				// If applicable, call a setter for localized content.
				$setterFunction = $localizedSetterMappings[$n->tagName];
				list($locale, $value) = $this->parseLocalizedContent($n);
				if (empty($locale)) $locale = $context->getPrimaryLocale();
				$issue->$setterFunction($value, $locale);
			}
		} else if (isset($dateSetterMappings[$n->tagName])) {
			if (!$processOnlyChildren) {
				// Not a localized element?  Check for a date.
				$setterFunction = $dateSetterMappings[$n->tagName];
				$issue->$setterFunction(strtotime($n->textContent));
			}
		} else switch ($n->tagName) {
			// Otherwise, delegate to specific parsing code
			case 'id':
				if (!$processOnlyChildren) {
					$this->parseIdentifier($n, $issue);
				}
				break;
			case 'articles':
				$this->parseArticles($n, $issue);
				break;
			case 'issue_galleys':
				if (!$processOnlyChildren) {
					$this->parseIssueGalleys($n, $issue);
				}
				break;
			case 'sections':
				$this->parseSections($n, $issue);
				break;
			case 'issue_covers':
				if (!$processOnlyChildren) {
					$this->parseIssueCovers($n, $issue);
				}
				break;
			case 'issue_identification':
				if (!$processOnlyChildren) {
					$this->parseIssueIdentification($n, $issue);
				}
				break;
			default:
				$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
		}
	}

	//
	// Element parsing
	//
	/**
	 * Parse an identifier node and set up the issue object accordingly
	 * @param $element DOMElement
	 * @param $issue Issue
	 */
	function parseIdentifier($element, $issue) {
		$deployment = $this->getDeployment();
		$advice = $element->getAttribute('advice');
		switch ($element->getAttribute('type')) {
			case 'internal':
				// "update" advice not supported yet.
				assert(!$advice || $advice == 'ignore');
				break;
			case 'public':
				if ($advice == 'update') {
					$issue->setStoredPubId('publisher-id', $element->textContent);
				}
				break;
			default:
				if ($advice == 'update') {
					// Load pub id plugins
					$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $deployment->getContext()->getId());
					$issue->setStoredPubId($element->getAttribute('type'), $element->textContent);
				}
		}
	}

	/**
	 * Parse an articles element
	 * @param $node DOMElement
	 * @param $issue Issue
	 */
	function parseIssueGalleys($node, $issue) {
		$deployment = $this->getDeployment();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'issue_galley':
						$this->parseIssueGalley($n, $issue);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
	}

	/**
	 * Parse an issue galley and add it to the issue.
	 * @param $n DOMElement
	 * @param $issue Issue
	 */
	function parseIssueGalley($n, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$importFilters = $filterDao->getObjectsByGroup('native-xml=>IssueGalley');
		assert(count($importFilters)==1); // Assert only a single unserialization filter
		$importFilter = array_shift($importFilters);
		$importFilter->setDeployment($this->getDeployment());
		$issueGalleyDoc = new DOMDocument();
		$issueGalleyDoc->appendChild($issueGalleyDoc->importNode($n, true));
		return $importFilter->execute($issueGalleyDoc);
	}

	/**
	 * Parse an articles element
	 * @param $node DOMElement
	 * @param $issue Issue
	 */
	function parseArticles($node, $issue) {
		$deployment = $this->getDeployment();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'article':
						$this->parseArticle($n, $issue);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
	}

	/**
	 * Parse an article and add it to the issue.
	 * @param $n DOMElement
	 * @param $issue Issue
	 */
	function parseArticle($n, $issue) {
		$filterDao = DAORegistry::getDAO('FilterDAO');
		$importFilters = $filterDao->getObjectsByGroup('native-xml=>article');
		assert(count($importFilters)==1); // Assert only a single unserialization filter
		$importFilter = array_shift($importFilters);
		$importFilter->setDeployment($this->getDeployment());
		$articleDoc = new DOMDocument();
		$articleDoc->appendChild($articleDoc->importNode($n, true));
		return $importFilter->execute($articleDoc);
	}

	/**
	 * Parse a submission file and add it to the submission.
	 * @param $node DOMElement
	 * @param $issue Issue
	 */
	function parseSections($node, $issue) {
		$deployment = $this->getDeployment();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'section':
						$this->parseSection($n);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
	}

	/**
	 * Parse a section stored in an issue.
	 * @param $node DOMElement
	 */
	function parseSection($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		// Create the data object
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$section = $sectionDao->newDataObject();
		$section->setContextId($context->getId());
		$section->setReviewFormId($node->getAttribute('review_form_id'));
		$section->setSequence($node->getAttribute('seq'));
		$section->setEditorRestricted($node->getAttribute('editor_restricted'));
		$section->setMetaIndexed($node->getAttribute('meta_indexed'));
		$section->setMetaReviewed($node->getAttribute('meta_reviewed'));
		$section->setAbstractsNotRequired($node->getAttribute('abstracts_not_required'));
		$section->setHideAuthor($node->getAttribute('hide_author'));
		$section->setHideTitle($node->getAttribute('hide_title'));
		$section->setAbstractWordCount($node->getAttribute('abstract_word_count'));

		$unknownNodes = array();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'id':
						// Only support "ignore" advice for now
						$advice = $n->getAttribute('advice');
						assert(!$advice || $advice == 'ignore');
						break;
					case 'abbrev':
						list($locale, $value) = $this->parseLocalizedContent($n);
						if (empty($locale)) $locale = $context->getPrimaryLocale();
						$section->setAbbrev($value, $locale);
						break;
					case 'policy':
						list($locale, $value) = $this->parseLocalizedContent($n);
						if (empty($locale)) $locale = $context->getPrimaryLocale();
						$section->setPolicy($value, $locale);
						break;
					case 'title':
						list($locale, $value) = $this->parseLocalizedContent($n);
						if (empty($locale)) $locale = $context->getPrimaryLocale();
						$section->setTitle($value, $locale);
						break;
					default:
						$unknownNodes[] = $n->tagName;
				}
			}
		}

		if (!$this->_sectionExist($section)) {
			$sectionId = $sectionDao->insertObject($section);
			if (count($unknownNodes)) {
				foreach ($unknownNodes as $tagName) {
					$deployment->addWarning(ASSOC_TYPE_SECTION, $sectionId, __('plugins.importexport.common.error.unknownElement', array('param' => $tagName)));
				}
			}
			$deployment->addProcessedObjectId(ASSOC_TYPE_SECTION, $sectionId);
		}
	}

	/**
	 * Parse out the object covers.
	 * @param $node DOMElement
	 * @param $object Issue
	 */
	function parseIssueCovers($node, $object) {
		$deployment = $this->getDeployment();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover':
						$this->parseCover($n, $object);
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
	}

	/**
	 * Parse out the cover and store it in the object.
	 * @param $node DOMElement
	 * @param $object Issue
	 */
	function parseCover($node, $object) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$locale = $node->getAttribute('locale');
		if (empty($locale)) $locale = $context->getPrimaryLocale();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'cover_image': $object->setCoverImage($n->textContent, $locale); break;
					case 'cover_image_alt_text': $object->setCoverImageAltText($n->textContent, $locale); break;
					case 'embed':
						import('classes.file.PublicFileManager');
						$publicFileManager = new PublicFileManager();
						$filePath = $publicFileManager->getContextFilesPath(ASSOC_TYPE_JOURNAL, $context->getId()) . '/' . $object->getCoverImage($locale);
						file_put_contents($filePath, base64_decode($n->textContent));
						break;
					default:
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $object->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
				}
			}
		}
	}

	/**
	 * Parse out the issue identification and store it in an issue.
	 * @param $node DOMElement
	 * @param $issue Issue
	 * @param $allowWarnings boolean Warnings should be suppressed if this function is not being used to populate a new issue
	 */
	function parseIssueIdentification($node, $issue, $allowWarnings = true) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) {
			if (is_a($n, 'DOMElement')) {
				switch ($n->tagName) {
					case 'volume':
						$issue->setVolume($n->textContent);
						$issue->setShowVolume(1);
						break;
					case 'number':
						$issue->setNumber($n->textContent);
						$issue->setShowNumber(1);
						break;
					case 'year':
						$issue->setYear($n->textContent);
						$issue->setShowYear(1);
						break;
					case 'title':
						list($locale, $value) = $this->parseLocalizedContent($n);
						if (empty($locale)) $locale = $context->getPrimaryLocale();
						$issue->setTitle($value, $locale);
						$issue->setShowTitle(1);
						break;
					default:
						if ($allowWarnings) {
							$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.common.error.unknownElement', array('param' => $n->tagName)));
						}
				}
			}
		}
	}

	//
	// Helper functions
	//
	/**
	 * Get node name to setter function mapping for localized data.
	 * @return array
	 */
	function _getLocalizedIssueSetterMappings() {
		return array(
			'description' => 'setDescription',
		);
	}

	/**
	 * Get node name to setter function mapping for issue date fields.
	 * @return array
	 */
	function _getDateIssueSetterMappings() {
		return array(
			'date_published'	=> 'setDatePublished',
			'date_notified'		=> 'setDateNotified',
			'last_modified'		=> 'setLastModified',
			'open_access_date'	=> 'setOpenAccessDate',
		);
	}

	/**
	 * Check if the issue already exists.
	 * @param $node DOMNode issue node
	 * return Issue|null matching issue, or null if no match
	 */
	function _issueExists($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$issue = null;
		$issueDao = DAORegistry::getDAO('IssueDAO');
		foreach ($node->getElementsByTagName('issue_identification') as $n) {
			$searchIssue = $issueDao->newDataObject();
			$this->parseIssueIdentification($n, $searchIssue, false);
			$foundIssues = $issueDao->getIssuesByIdentification($context->getId(), $searchIssue->getVolume(), $searchIssue->getNumber(), $searchIssue->getYear(), (array) $searchIssue->getTitle(null));
			foreach ($foundIssues->toArray() as $issue) {
				$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.issueIdentificationDuplicate', array('issueId' => $issue->getId(), 'issueIdentification' => $n->ownerDocument->saveXML($n))));
			}
		}
		return $issue;
	}

	/**
	 * Check if the section already exists.
	 * @param $importSection Section New created section
	 * @return boolean
	 */
	function _sectionExist($importSection) {
		$deployment = $this->getDeployment();
		$issue = $deployment->getIssue();
		// title and, optionally, abbrev contain information that can
		// be used to locate an existing section. If title and abbrev each match an
		// existing section, but not the same section, throw an error.
		$sectionDao  = DAORegistry::getDAO('SectionDAO');
		$contextId = $importSection->getContextId();
		$section = null;
		$foundSectionId = $foundSectionTitle = null;
		$index = 0;
		$titles = $importSection->getTitle(null);
		foreach($titles as $locale => $title) {
			$section = $sectionDao->getByTitle($title, $contextId);
			if ($section) {
				$sectionId = $section->getId();
				if ($foundSectionId) {
					if ($foundSectionId != $sectionId) {
						// Mismatching sections found.
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionTitleMismatch', array('section1Title' => $title, 'section2Title' => $foundSectionTitle, 'issueTitle' => $issue->getIssueIdentification())));
					}
				} else if ($index > 0) {
					// the current title matches, but the prev titles didn't
					$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionTitleMatch', array('sectionTitle' => $title, 'issueTitle' => $issue->getIssueIdentification())));
				}
				$foundSectionId = $sectionId;
				$foundSectionTitle = $title;
			} else {
				if ($foundSectionId) {
					// a prev title matched, but the current doesn't
					$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionTitleMatch', array('sectionTitle' => $foundSectionTitle, 'issueTitle' => $issue->getIssueIdentification())));
				}
			}
			$index++;
		}
		// check abbrevs:
		$abbrevSection = null;
		$foundSectionId = $foundSectionAbbrev = null;
		$index = 0;
		$abbrevs = $importSection->getAbbrev(null);
		foreach($abbrevs as $locale => $abbrev) {
			$abbrevSection = $sectionDao->getByAbbrev($abbrev, $contextId);
			if ($abbrevSection) {
				$sectionId = $abbrevSection->getId();
				if ($foundSectionId) {
					if ($foundSectionId != $sectionId) {
						// Mismatching sections found.
						$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionAbbrevMismatch', array('section1Abbrev' => $abbrev, 'section2Abbrev' => $foundSectionAbbrev, 'issueTitle' => $issue->getIssueIdentification())));
					}
				} else if ($index > 0) {
					// the current abbrev matches, but the prev abbrevs didn't
					$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionAbbrevMatch', array('sectionAbbrev' => $abbrev, 'issueTitle' => $issue->getIssueIdentification())));
				}
				$foundSectionId = $sectionId;
				$foundSectionAbbrev = $abbrev;
			} else {
				if ($foundSectionId) {
					// a prev abbrev matched, but the current doesn't
					$deployment->addWarning(ASSOC_TYPE_ISSUE, $issue->getId(), __('plugins.importexport.native.import.error.sectionAbbrevMatch', array('sectionAbbrev' => $foundSectionAbbrev, 'issueTitle' => $issue->getIssueIdentification())));
				}
			}
			$index++;
		}
		if (isset($section) && isset($abbrevSection)) {
			return $section->getId() == $abbrevSection->getId();
		} else {
			return isset($section) || isset($abbrevSection);
		}
	}

}


