<?php

/**
 * @file plugins/importexport/crossref/filter/ArticleCrossrefXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
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
	function ArticleCrossrefXmlFilter($filterGroup) {
		$this->setDisplayName('Crossref XML article export');
		parent::IssueCrossrefXmlFilter($filterGroup);
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
		assert(is_a($pubObject, 'PublishedArticle'));
		$journalNode->appendChild($this->createJournalArticleNode($doc, $pubObject));
		return $journalNode;
	}

	/**
	 * Create and return the journal issue node 'journal_issue'.
	 * @param $doc DOMDocument
	 * @param $submission PublishedArticle
	 * @return DOMElement
	 */
	function createJournalIssueNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$cache = $deployment->getCache();
		assert(is_a($submission, 'PublishedArticle'));
		$issueId = $submission->getIssueId();
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
	 * @param $submission PublishedArticle
	 * @return DOMElement
	 */
	function createJournalArticleNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::getRequest();
		// Issue shoulld be set by now
		$issue = $deployment->getIssue();

		$journalArticleNode = $doc->createElementNS($deployment->getNamespace(), 'journal_article');
		$journalArticleNode->setAttribute('publication_type', 'full_text');
		$journalArticleNode->setAttribute('metadata_distribution_opts', 'any');

		// title
		$titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
		$titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', $submission->getTitle($submission->getLocale())));
		$journalArticleNode->appendChild($titlesNode);

		// contributors
		$contributorsNode = $doc->createElementNS($deployment->getNamespace(), 'contributors');
		$authors = $submission->getAuthors();
		$isFirst = true;
		foreach ($authors as $author) {
			$personNameNode = $doc->createElementNS($deployment->getNamespace(), 'person_name');
			$personNameNode->setAttribute('contributor_role', 'author');
			if ($isFirst) {
				$personNameNode->setAttribute('sequence', 'first');
			} else {
				$personNameNode->setAttribute('sequence', 'additional');
			}
			$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'given_name', ucfirst($author->getFirstName()).(($author->getMiddleName())?' '.ucfirst($author->getMiddleName()):'')));
			$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'surname', ucfirst($author->getLastName())));
			if ($author->getData('orcid')) {
				$personNameNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'ORCID', $author->getData('orcid')));
			}
			$contributorsNode->appendChild($personNameNode);
		}
		$journalArticleNode->appendChild($contributorsNode);

		// abstract
		if ($submission->getAbstract($submission->getLocale())) {
			$abstractNode = $doc->createElementNS($deployment->getJATSNamespace(), 'jats:abstract');
			$abstractNode->appendChild($node = $doc->createElementNS($deployment->getJATSNamespace(), 'jats:p', html_entity_decode(strip_tags($submission->getAbstract($submission->getLocale())), ENT_COMPAT, 'UTF-8')));
			$journalArticleNode->appendChild($abstractNode);
		}

		// publication date
		$datePublished = $submission->getDatePublished() ? $submission->getDatePublished() : $issue->getDatePublished();
		if ($datePublished) {
			$journalArticleNode->appendChild($this->createPublicationDateNode($doc, $submission->getDatePublished()));
		}

		// pages
		if ($submission->getPages() != '') {
			// extract the first page for the first_page element, store the remaining bits in otherPages,
			// after removing any preceding non-numerical characters.
			if (preg_match('/^[^\d]*(\d+)\D*(.*)$/', $submission->getPages(), $matches)) {
				$pagesNode = $doc->createElementNS($deployment->getNamespace(), 'pages');
				$firstPage = $matches[1];
				$otherPages = $matches[2];
				$pagesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'first_page', $firstPage));
				if ($otherPages != '') {
					$pagesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'other_pages', $otherPages));
				}
				$journalArticleNode->appendChild($pagesNode);
			}
		}

		// license
		if ($submission->getLicenseUrl()) {
			$licenseNode = $doc->createElementNS($deployment->getAINamespace(), 'ai:program');
			$licenseNode->setAttribute('name', 'AccessIndicators');
			$licenseNode->appendChild($node = $doc->createElementNS($deployment->getAINamespace(), 'ai:license_ref', $submission->getLicenseUrl()));
			$journalArticleNode->appendChild($licenseNode);
		}

		// DOI data
		$doiDataNode = $this->createDOIDataNode($doc, $submission->getStoredPubId('doi'), $request->url($context->getPath(), 'article', 'view', $submission->getBestArticleId()));
		// append galleys files and collection nodes to the DOI data node
		// galley can contain several files
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		$galleys = $articleGalleyDao->getBySubmissionId($submission->getId());
		import('lib.pkp.classes.submission.SubmissionFile'); // SUBMISSION_FILE_... constants
		$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO'); /* @var $submissionFileDao SubmissionFileDAO */
		$submissionGalleyFiles = array();
		// get immediatelly also supplementary files for component list
		$componentFiles = array();
		while ($galley = $galleys->next()) {
			$galleyFiles = $submissionFileDao->getLatestRevisionsByAssocId(ASSOC_TYPE_REPRESENTATION, $galley->getId(), $submission->getId(), SUBMISSION_FILE_PROOF);
			// filter supp files with DOI
			$genreDao = DAORegistry::getDAO('GenreDAO');
			$suppGalleyFiles = array();
			foreach ($galleyFiles as $galleyFile) {
				$genre = $genreDao->getById($galleyFile->getGenreId());
				if ($genre->getSupplementary()) {
					if ($galleyFile->getStoredPubid('doi')) {
						$suppGalleyFiles[] = $galleyFile;
					}
				} else {
					$submissionGalleyFiles[$galley->getBestGalleyId()] = $galleyFile;
				}
			}
			if (!empty($suppGalleyFiles)) {
				// construct the array key with galley best ID and locale needed for the component node
				$componentFiles[$galley->getBestGalleyId().'-'.$galley->getLocale()] = $suppGalleyFiles;
			}
		}
		// submission galley files - colelction nodes
		if (!empty($submissionGalleyFiles)) {
			$this->appendCollectionNodes($doc, $doiDataNode, $submission, $submissionGalleyFiles);
		}
		$journalArticleNode->appendChild($doiDataNode);

		// component list (supplementary files)
		if (!empty($componentFiles)) {
			$journalArticleNode->appendChild($this->createComponentListNode($doc, $submission, $componentFiles));
		}

		return $journalArticleNode;
	}

	/**
	 * Append all collection nodes 'collection' to the doi data node.
	 * @param $doc DOMDocument
	 * @param $doiDataNode DOMElement
	 * @param $submission PublishedArticle
	 * @param $submissionFiles array (best galley ID => submission file)
	 */
	function appendCollectionNodes($doc, $doiDataNode, $submission, $submissionFiles) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::getRequest();

		// start of the text-mining collection element
		$textMiningCollectionNode = $doc->createElementNS($deployment->getNamespace(), 'collection');
		$textMiningCollectionNode->setAttribute('property', 'text-mining');
		foreach ($submissionFiles as $betGalleyId => $submissionFile) {
			$resourceURL = $request->url($context->getPath(), 'article', 'download', array($submission->getBestArticleId(), $betGalleyId, $submissionFile->getFileId()));
			// iParadigms crawler based collection element
			$crawlerBasedCollectionNode = $doc->createElementNS($deployment->getNamespace(), 'collection');
			$crawlerBasedCollectionNode->setAttribute('property', 'crawler-based');
			$iParadigmsItemNode = $doc->createElementNS($deployment->getNamespace(), 'item');
			$iParadigmsItemNode->setAttribute('crawler', 'iParadigms');
			$iParadigmsItemNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'resource', $resourceURL));
			$crawlerBasedCollectionNode->appendChild($iParadigmsItemNode);
			$doiDataNode->appendChild($crawlerBasedCollectionNode);
			// end iParadigms
			// text-mining collection item
			$textMiningItemNode = $doc->createElementNS($deployment->getNamespace(), 'item');
			$resourceNode = $doc->createElementNS($deployment->getNamespace(), 'resource', $resourceURL);
			$resourceNode->setAttribute('mime_type', $submissionFile->getFileType());
			$textMiningItemNode->appendChild($resourceNode);
			$textMiningCollectionNode->appendChild($textMiningItemNode);
		}
		$doiDataNode->appendChild($textMiningCollectionNode);
	}

	/**
	 * Create and return component list node 'component_list'.
	 * @param $doc DOMDocument
	 * @param $submission PublishedArticle
	 * @param $componentFiles array
	 * @return DOMElement
	 */
	function createComponentListNode($doc, $submission, $componentFiles) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$request = Application::getRequest();

		// Create the base node
		$componentListNode =$doc->createElementNS($deployment->getNamespace(), 'component_list');
		// Run through supp files and add component nodes.
		foreach($componentFiles as $key => $componentFilesArray) {
			// get galley best ID and locale
			$keyParts = explode('-', $key);
			foreach ($componentFilesArray as $componentFile) {
				$componentNode = $doc->createElementNS($deployment->getNamespace(), 'component');
				$componentNode->setAttribute('parent_relation', 'isPartOf');
				/* Titles */
				$componentFileTitle = $componentFile->getName($keyParts[1]);
				if (!empty($componentFileTitle)) {
					$titlesNode = $doc->createElementNS($deployment->getNamespace(), 'titles');
					$titlesNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'title', $componentFileTitle));
					$componentNode->appendChild($titlesNode);
				}
				// DOI data node
				// TO-DO: bestId is missing for files
				$resourceURL = $request->url($context->getPath(), 'article', 'download', array($submission->getBestArticleId(), $keyParts[0], $componentFile->getFileId()));
				$componentNode->appendChild($this->createDOIDataNode($doc, $componentFile->getStoredPubId('doi'), $resourceURL));
				$componentListNode->appendChild($componentNode);
			}
		}
		return $componentListNode;
	}


}

?>
