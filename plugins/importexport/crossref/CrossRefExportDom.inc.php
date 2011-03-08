<?php

/**
 * @file plugins/importexport/crossref/CrossRefExportDom.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CrossRefExportDom
 * @ingroup plugins_importexport_crossref
 *
 * @brief CrossRef XML export plugin DOM functions
 */

// $Id$


import('lib.pkp.classes.xml.XMLCustomWriter');

define('CROSSREF_XMLNS_XSI' , 'http://www.w3.org/2001/XMLSchema-instance');
define('CROSSREF_XMLNS' , 'http://www.crossref.org/schema/4.3.0');
define('CROSSREF_VERSION' , '4.3.0');
define('CROSSREF_XSI_SCHEMALOCATION' , 'http://www.crossref.org/schema/4.3.0 http://www.crossref.org/schema/4.3.0/crossref4.3.0.xsd');

class CrossRefExportDom {

	/**
	 * Build article XML using DOM elements
	 * @return XMLNode
	 */
	function &generateCrossRefDom() {
		// create the output XML document in DOM with a root node
		$doc =& XMLCustomWriter::createDocument();
		return $doc;
	}

	/**
	 * Generate DOI batch DOM tree.
	 * @param $doc object
	 * @return XMLNode
	 */
	function &generateDoiBatchDom(&$doc) {

		// Generate the root node for the file first and set its attributes
		$root =& XMLCustomWriter::createElement($doc, 'doi_batch');

		/* Root doi_batch tag attributes
		 * Change to these attributes must be accompanied by a review of entire output
		 */
		XMLCustomWriter::setAttribute($root, 'xmlns:xsi', CROSSREF_XMLNS_XSI);
		XMLCustomWriter::setAttribute($root, 'xmlns', CROSSREF_XMLNS);
		XMLCustomWriter::setAttribute($root, 'version', CROSSREF_VERSION);
		XMLCustomWriter::setAttribute($root, 'xsi:schemaLocation', CROSSREF_XSI_SCHEMALOCATION);

		XMLCustomWriter::appendChild($doc, $root);

		return $root;
	}

	/**
	 * Generate the <head> tag that accompanies each submission
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @return XMLNode
	 */
	function &generateHeadDom(&$doc, &$journal) {
		$head =& XMLCustomWriter::createElement($doc, 'head');

		// DOI batch ID is a simple tracking ID: initials + timestamp
		XMLCustomWriter::createChildWithText($doc, $head, 'doi_batch_id', $journal->getLocalizedSetting('initials') . '_' . time());
		XMLCustomWriter::createChildWithText($doc, $head, 'timestamp', time());

		$journalId = $journal->getId();

		/* Depositor defaults to the Journal's technical Contact */
		$depositorNode =& CrossRefExportDom::generateDepositorDom($doc, $journal->getSetting('supportName'), $journal->getSetting('supportEmail'));
		XMLCustomWriter::appendChild($head, $depositorNode);

		/* The registrant is assumed to be the Publishing institution */
		$publisherInstitution = $journal->getSetting('publisherInstitution');
		XMLCustomWriter::createChildWithText($doc, $head, 'registrant', $publisherInstitution);

		return $head;
	}

	/**
	 * Generate depositor node
	 * @param $doc XMLNode
	 * @param $name string
	 * @param $email string
	 * @return XMLNode
	 */
	function &generateDepositorDom(&$doc, $name, $email) {
		$depositor =& XMLCustomWriter::createElement($doc, 'depositor');
		XMLCustomWriter::createChildWithText($doc, $depositor, 'name', $name);
		XMLCustomWriter::createChildWithText($doc, $depositor, 'email_address', $email);

		return $depositor;
	}

	/**
	 * Generate metadata for journal - accompanies every article
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @return XMLNode
	 */
	function &generateJournalMetadataDom(&$doc, &$journal) {
		$journalMetadataNode =& XMLCustomWriter::createElement($doc, 'journal_metadata');

		/* Full Title of Journal */
		XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'full_title', $journal->getLocalizedTitle());

		/* Abbreviated title - defaulting to initials if no abbreviation found */
		if ($journal->getLocalizedSetting('abbreviation') != '' ) {
			XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'abbrev_title', $journal->getLocalizedSetting('abbreviation'));
		}

		/* Both ISSNs are permitted for CrossRef, so sending whichever one (or both) */
		if ( $ISSN = $journal->getSetting('onlineIssn') ) {
			$onlineISSN =& XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'issn', $ISSN);
			XMLCustomWriter::setAttribute($onlineISSN, 'media_type', 'electronic');
		}

		/* Both ISSNs are permitted for CrossRef so sending whichever one (or both) */
		if ( $ISSN = $journal->getSetting('printIssn') ) {
			$printISSN =& XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'issn', $ISSN);
			XMLCustomWriter::setAttribute($printISSN, 'media_type', 'print');
		}

		return $journalMetadataNode;
	}

	/**
	 * Generate journal issue tag to accompany every article
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $section Section
	 * @param $article Article
	 * @return XMLNode
	 */
	function &generateJournalIssueDom(&$doc, &$journal, &$issue, &$section, &$article) {
		$journalIssueNode =& XMLCustomWriter::createElement($doc, 'journal_issue');

		if ($issue->getDatePublished()) {
			$publicationDateNode =& CrossRefExportDom::generatePublisherDateDom($doc, $issue->getDatePublished());
			XMLCustomWriter::appendChild($journalIssueNode, $publicationDateNode);
		}

		$journalVolumeNode =& XMLCustomWriter::createElement($doc, 'journal_volume');
		XMLCustomWriter::appendChild($journalIssueNode, $journalVolumeNode);
		XMLCustomWriter::createChildWithText($doc, $journalVolumeNode, 'volume', $issue->getVolume());

		XMLCustomWriter::createChildWithText($doc, $journalIssueNode, 'issue', $issue->getNumber());

		return $journalIssueNode;
	}

	/**
	 * Generate the journal_article node (the heart of the file).
	 * @param $doc XMLNode
	 * @param $journal Journal
	 * @param $issue Issue
	 * @param $section Section
	 * @param $article Article
	 * @return XMLNode
	 */
	function &generateJournalArticleDom(&$doc, &$journal, &$issue, &$section, &$article) {
		// Create the base node
		$journalArticleNode =& XMLCustomWriter::createElement($doc, 'journal_article');
		XMLCustomWriter::setAttribute($journalArticleNode, 'publication_type', 'full_text');

		/* Titles */
		$titlesNode =& XMLCustomWriter::createElement($doc, 'titles');
		XMLCustomWriter::createChildWithText($doc, $titlesNode, 'title', $article->getLocalizedTitle());
		XMLCustomWriter::appendChild($journalArticleNode, $titlesNode);
		$contributorsNode =& XMLCustomWriter::createElement($doc, 'contributors');

		/* AuthorList */
		$isFirst = true;
		foreach ($article->getAuthors() as $author) {
			$authorNode =& CrossRefExportDom::generateAuthorDom($doc, $author, $isFirst);
			$isFirst = false;
			XMLCustomWriter::appendChild($contributorsNode, $authorNode);
		}
		XMLCustomWriter::appendChild($journalArticleNode, $contributorsNode);

		/* publication date of issue */
		if ($issue->getDatePublished()) {
			$publicationDateNode =& CrossRefExportDom::generatePublisherDateDom($doc, $issue->getDatePublished());
			XMLCustomWriter::appendChild($journalArticleNode, $publicationDateNode);
		}

		/* publisher_item is the article pages */
		if ($article->getPages() != '') {
			$publisherItemNode =& XMLCustomWriter::createElement($doc, 'publisher_item');
			XMLCustomWriter::createChildWithText($doc, $publisherItemNode, 'item_number', $article->getPages());
			XMLCustomWriter::appendChild($journalArticleNode, $publisherItemNode);
		}

		return $journalArticleNode;
	}

	/**
	 * Generate doi_data element - this is what assigns the DOI
	 * @param $doc XMLNode
	 * @param $DOI string
	 * @param $url string
	 */
	function &generateDOIdataDom(&$doc, $DOI, $url) {
		$DOIdataNode =& XMLCustomWriter::createElement($doc, 'doi_data');
		XMLCustomWriter::createChildWithText($doc, $DOIdataNode, 'doi', $DOI);
		XMLCustomWriter::createChildWithText($doc, $DOIdataNode, 'resource', $url);

		return $DOIdataNode;
	}

	/**
	 * Generate author node
	 * @param $doc XMLNode
	 * @param $author Author
	 * @return XMLNode
	 */
	function &generateAuthorDom(&$doc, &$author, $isFirst = false) {
		$authorNode =& XMLCustomWriter::createElement($doc, 'person_name');
		XMLCustomWriter::setAttribute($authorNode, 'contributor_role', 'author');

		/* there should only be 1 primary contact per article */
		if ($isFirst) {
			XMLCustomWriter::setAttribute($authorNode, 'sequence', 'first');
		} else {
			XMLCustomWriter::setAttribute($authorNode, 'sequence', 'additional');
		}

		XMLCustomWriter::createChildWithText($doc, $authorNode, 'given_name', ucfirst($author->getFirstName()).(($author->getMiddleName())?' '.ucfirst($author->getMiddleName()):''));
		XMLCustomWriter::createChildWithText($doc, $authorNode, 'surname', ucfirst($author->getLastName()));

		return $authorNode;
	}

	/**
	 * Generate publisher date - order matters
	 * @param $doc XMLNode
	 * @param $pubdate string
	 * @return XMLNode
	 */
	function &generatePublisherDateDom(&$doc, $pubdate) {
		$publicationDateNode =& XMLCustomWriter::createElement($doc, 'publication_date');
		XMLCustomWriter::setAttribute($publicationDateNode, 'media_type', 'online');

		$parsedPubdate = strtotime($pubdate);
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'month', date('m', $parsedPubdate), false);
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'day', date('d', $parsedPubdate), false);
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'year', date('Y', $parsedPubdate));

		return $publicationDateNode;
	}
}

?>
