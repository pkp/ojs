<?php

/**
 * @file plugins/importexport/crossref/filter/ArticleCrossrefXmlFilter.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2000-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleCrossrefXmlFilter
 * @ingroup plugins_importexport_crossref
 *
 * @brief Class that converts an Article to a Crossref XML document.
 */

import('plugins.importexport.crossref.filter.IssueCrossrefXmlFilter');

class ArticleCrossrefXmlFilter extends IssueCrossrefXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Crossref XML article export');
		parent::__construct($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.crossref.filter.ArticleCrossrefXmlFilter';
	}


	//
	// Submission conversion functions
	//
	/**
	 * @copydoc IssueCrossrefXmlFilter::createJournalNode()
	 */
	function createJournalNode($doc, $pubObject) {
		$deployment = $this->getDeployment();
		$journalNode = parent::createJournalNode($doc, $pubObject);
		assert(is_a($pubObject, 'Submission'));
		$journalNode->appendChild($this->createJournalArticleNode($doc, $pubObject));
		return $journalNode;
	}

	/**
	 * Create and return the journal issue node 'journal_issue'.
	 * @param $doc DOMDocument
	 * @param $submission Submission
	 * @return DOMElement
	 */
	function createJournalIssueNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$cache = $deployment->getCache();
		assert(is_a($submission, 'Submission'));
		$issueId = $submission->getCurrentPublication()->getData('issueId');
		if ($cache->isCached('issues', $issueId)) {
			$issue = $cache->get('issues', $issueId);
		} else {
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issue = $issueDao->getById($issueId, $context->getId());
			if ($issue) $cache->add($issue, null);
		}
		$journalIssueNode = parent::createJournalIssueNode($doc, $issue);
		return $journalIssueNode;
	}

	/**
	 * Create and return the journal article node 'journal_article'.
	 * @param $doc DOMDocument
	 * @param $submission Submission
	 * @return DOMElement
	 */
	function createJournalArticleNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::get()->getRequest();

		$publication = $submission->getCurrentPublication();
		$locale = $publication->getData('locale');

		// Issue shoulld be set by now
		$issue = $deployment->getIssue();

		$journalArticleNode = $doc->createElementNS($deployment->getNamespace(), 'journal_article');
		$journalArticleNode->setAttribute('publication_type', 'full_text');
		$journalArticleNode->setAttribute('metadata_distribution_opts', 'any');


		// title
		$titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
		$titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', htmlspecialchars($publication->getData('title', $locale), ENT_COMPAT, 'UTF-8')));
		if ($subtitle = $publication->getData('subtitle', $locale)) $titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'subtitle', htmlspecialchars($subtitle, ENT_COMPAT, 'UTF-8')));
		$journalArticleNode->appendChild($titlesNode);

		// contributors
		$contributorsNode = $doc->createElementNS($deployment->getNamespace(), 'contributors');
		$authors = $publication->getData('authors');
		$isFirst = true;
		foreach ($authors as $author) { /** @var $author Author */
			$personNameNode = $doc->createElementNS($deployment->getNamespace(), 'person_name');
			$personNameNode->setAttribute('contributor_role', 'author');

			if ($isFirst) {
				$personNameNode->setAttribute('sequence', 'first');
			} else {
				$personNameNode->setAttribute('sequence', 'additional');
			}

			$familyNames = $author->getFamilyName(null);
			$givenNames = $author->getGivenName(null);

			// Check if both givenName and familyName is set for the submission language.
			if (isset($familyNames[$locale]) && isset($givenNames[$locale])) {
				$personNameNode->setAttribute('language', PKPLocale::getIso1FromLocale($locale));
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'given_name', htmlspecialchars(ucfirst($givenNames[$locale]), ENT_COMPAT, 'UTF-8')));
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'surname', htmlspecialchars(ucfirst($familyNames[$locale]), ENT_COMPAT, 'UTF-8')));

				$hasAltName = false;
				foreach($familyNames as $otherLocal => $familyName) {
					if ($otherLocal != $locale && isset($familyName) && !empty($familyName)) {
						if (!$hasAltName) {
							$altNameNode = $doc->createElementNS($deployment->getNamespace(), 'alt-name');
							$personNameNode->appendChild($altNameNode);

							$hasAltName = true;
						}

						$nameNode = $doc->createElementNS($deployment->getNamespace(), 'name');
						$nameNode->setAttribute('language', PKPLocale::getIso1FromLocale($otherLocal));

						$nameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'surname', htmlspecialchars(ucfirst($familyName), ENT_COMPAT, 'UTF-8')));
						if (isset($givenNames[$otherLocal]) && !empty($givenNames[$otherLocal])) {
							$nameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'given_name', htmlspecialchars(ucfirst($givenNames[$otherLocal]), ENT_COMPAT, 'UTF-8')));
						}

						$altNameNode->appendChild($nameNode);
					}
				}

			} else {
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'surname', htmlspecialchars(ucfirst($author->getFullName(false)), ENT_COMPAT, 'UTF-8')));
			}

			if ($author->getData('orcid')) {
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ORCID', $author->getData('orcid')));
			}
			
			$contributorsNode->appendChild($personNameNode);
			$isFirst = false;
		}
		$journalArticleNode->appendChild($contributorsNode);

		// abstract
		if ($abstract = $publication->getData('abstract', $locale)) {
			$abstractNode = $doc->createElementNS($deployment->getJATSNamespace(), 'jats:abstract');
			$abstractNode->appendChild($node = $doc->createElementNS($deployment->getJATSNamespace(), 'jats:p', htmlspecialchars(html_entity_decode(strip_tags($abstract), ENT_COMPAT, 'UTF-8'), ENT_COMPAT, 'UTF-8')));
			$journalArticleNode->appendChild($abstractNode);
		}

		// publication date
		if ($datePublished = $publication->getData('datePublished')) {
			$journalArticleNode->appendChild($this->createPublicationDateNode($doc, $datePublished));
		}

		// pages
		// CrossRef requires first_page and last_page of any contiguous range, then any other ranges go in other_pages
		$pages = $publication->getPageArray();
		if (!empty($pages)) {
			$firstRange = array_shift($pages);
			$firstPage = array_shift($firstRange);
			if (count($firstRange)) {
				// There is a first page and last page for the first range
				$lastPage = array_shift($firstRange);
			} else {
				// There is not a range in the first segment
				$lastPage = '';
			}
			// CrossRef accepts no punctuation in first_page or last_page
			if ((!empty($firstPage) || $firstPage === "0") && !preg_match('/[^[:alnum:]]/', $firstPage) && !preg_match('/[^[:alnum:]]/', $lastPage)) {
				$pagesNode = $doc->createElementNS($deployment->getNamespace(), 'pages');
				$pagesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'first_page', $firstPage));
				if ($lastPage != '') {
					$pagesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'last_page', $lastPage));
				}
				$otherPages = '';
				foreach ($pages as $range) {
					$otherPages .= ($otherPages ? ',' : '').implode('-', $range);
				}
				if ($otherPages != '') {
					$pagesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'other_pages', $otherPages));
				}
				$journalArticleNode->appendChild($pagesNode);
			}
		}

		// license
		if ($publication->getData('licenseUrl')) {
			$licenseNode = $doc->createElementNS($deployment->getAINamespace(), 'ai:program');
			$licenseNode->setAttribute('name', 'AccessIndicators');
			$licenseNode->appendChild($node = $doc->createElementNS($deployment->getAINamespace(), 'ai:license_ref', htmlspecialchars($publication->getData('licenseUrl'), ENT_COMPAT, 'UTF-8')));
			$journalArticleNode->appendChild($licenseNode);
		}

		// DOI data
		$doiDataNode = $this->createDOIDataNode($doc, $publication->getStoredPubId('doi'), $request->url($context->getPath(), 'article', 'view', $submission->getBestId(), null, null, true));
		// append galleys files and collection nodes to the DOI data node
		$galleys = $publication->getData('galleys');
		// All full-texts, PDF full-texts and remote galleys for text-mining and as-crawled URL
		$submissionGalleys = $pdfGalleys = $remoteGalleys = array();
		// preferred PDF full-text for the as-crawled URL
		$pdfGalleyInArticleLocale = null;
		// get immediatelly also supplementary files for component list
		$componentGalleys = array();
		$genreDao = DAORegistry::getDAO('GenreDAO'); /* @var $genreDao GenreDAO */
		foreach ($galleys as $galley) {
			// filter supp files with DOI
			if (!$galley->getRemoteURL()) {
				$galleyFile = $galley->getFile();
				if ($galleyFile) {
					$genre = $genreDao->getById($galleyFile->getGenreId());
					if ($genre->getSupplementary()) {
						if ($galley->getStoredPubid('doi')) {
							// construct the array key with galley best ID and locale needed for the component node
							$componentGalleys[] = $galley;
						}
					} else {
						$submissionGalleys[] = $galley;
						if ($galley->isPdfGalley()) {
							$pdfGalleys[] = $galley;
							if (!$pdfGalleyInArticleLocale && $galley->getLocale() == $locale) {
								$pdfGalleyInArticleLocale = $galley;
							}
						}
					}
				}
			} else {
				$remoteGalleys[] = $galley;
			}
		}
		// as-crawled URLs
		$asCrawledGalleys = array();
		if ($pdfGalleyInArticleLocale) {
			$asCrawledGalleys = array($pdfGalleyInArticleLocale);
		} elseif (!empty($pdfGalleys)) {
			$asCrawledGalleys = array($pdfGalleys[0]);
		} else {
			$asCrawledGalleys = $submissionGalleys;
		}
		// as-crawled URL - collection nodes
		$this->appendAsCrawledCollectionNodes($doc, $doiDataNode, $submission, $asCrawledGalleys);
		// text-mining - collection nodes
		$submissionGalleys = array_merge($submissionGalleys, $remoteGalleys);
		$this->appendTextMiningCollectionNodes($doc, $doiDataNode, $submission, $submissionGalleys);
		$journalArticleNode->appendChild($doiDataNode);

		// component list (supplementary files)
		if (!empty($componentGalleys)) {
			$journalArticleNode->appendChild($this->createComponentListNode($doc, $submission, $componentGalleys));
		}

		return $journalArticleNode;
	}

	/**
	 * Append the collection node 'collection property="crawler-based"' to the doi data node.
	 * @param $doc DOMDocument
	 * @param $doiDataNode DOMElement
	 * @param $submission Submission
	 * @param $galleys array of galleys
	 */
	function appendAsCrawledCollectionNodes($doc, $doiDataNode, $submission, $galleys) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::get()->getRequest();

		if (empty($galleys)) {
			$crawlerBasedCollectionNode = $doc->createElementNS($deployment->getNamespace(), 'collection');
			$crawlerBasedCollectionNode->setAttribute('property', 'crawler-based');
			$doiDataNode->appendChild($crawlerBasedCollectionNode);
		}
		foreach ($galleys as $galley) {
			$resourceURL = $request->url($context->getPath(), 'article', 'download', array($submission->getBestId(), $galley->getBestGalleyId()), null, null, true);
			// iParadigms crawler based collection element
			$crawlerBasedCollectionNode = $doc->createElementNS($deployment->getNamespace(), 'collection');
			$crawlerBasedCollectionNode->setAttribute('property', 'crawler-based');
			$iParadigmsItemNode = $doc->createElementNS($deployment->getNamespace(), 'item');
			$iParadigmsItemNode->setAttribute('crawler', 'iParadigms');
			$iParadigmsItemNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'resource', $resourceURL));
			$crawlerBasedCollectionNode->appendChild($iParadigmsItemNode);
			$doiDataNode->appendChild($crawlerBasedCollectionNode);
		}
	}

	/**
	 * Append the collection node 'collection property="text-mining"' to the doi data node.
	 * @param $doc DOMDocument
	 * @param $doiDataNode DOMElement
	 * @param $submission Submission
	 * @param $galleys array of galleys
	 */
	function appendTextMiningCollectionNodes($doc, $doiDataNode, $submission, $galleys) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::get()->getRequest();

		// start of the text-mining collection element
		$textMiningCollectionNode = $doc->createElementNS($deployment->getNamespace(), 'collection');
		$textMiningCollectionNode->setAttribute('property', 'text-mining');
		foreach ($galleys as $galley) {
			$resourceURL = $request->url($context->getPath(), 'article', 'download', array($submission->getBestId(), $galley->getBestGalleyId()), null, null, true);
			// text-mining collection item
			$textMiningItemNode = $doc->createElementNS($deployment->getNamespace(), 'item');
			$resourceNode = $doc->createElementNS($deployment->getNamespace(), 'resource', $resourceURL);
			if (!$galley->getRemoteURL()) $resourceNode->setAttribute('mime_type', $galley->getFileType());
			$textMiningItemNode->appendChild($resourceNode);
			$textMiningCollectionNode->appendChild($textMiningItemNode);
		}
		$doiDataNode->appendChild($textMiningCollectionNode);
	}

	/**
	 * Create and return component list node 'component_list'.
	 * @param $doc DOMDocument
	 * @param $submission Submission
	 * @param $componentGalleys array
	 * @return DOMElement
	 */
	function createComponentListNode($doc, $submission, $componentGalleys) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::get()->getRequest();

		// Create the base node
		$componentListNode =$doc->createElementNS($deployment->getNamespace(), 'component_list');
		// Run through supp files and add component nodes.
		foreach($componentGalleys as $componentGalley) {
			$componentFile = $componentGalley->getFile();
			$componentNode = $doc->createElementNS($deployment->getNamespace(), 'component');
			$componentNode->setAttribute('parent_relation', 'isPartOf');
			/* Titles */
			$componentFileTitle = $componentFile->getName($componentGalley->getLocale());
			if (!empty($componentFileTitle)) {
				$titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
				$titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', htmlspecialchars($componentFileTitle, ENT_COMPAT, 'UTF-8')));
				$componentNode->appendChild($titlesNode);
			}
			// DOI data node
			$resourceURL = $request->url($context->getPath(), 'article', 'download', array($submission->getBestId(), $componentGalley->getBestGalleyId()), null, null, true);
			$componentNode->appendChild($this->createDOIDataNode($doc, $componentGalley->getStoredPubId('doi'), $resourceURL));
			$componentListNode->appendChild($componentNode);
		}
		return $componentListNode;
	}
}


