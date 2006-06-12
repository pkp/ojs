<?php

/**
 * CrossRefExportDom.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * CrossRef XML export plugin DOM functions
 *
 * $Id$
 */

import('xml.XMLCustomWriter');

 /* NOTE: these values reflect the specifications for which this plugin were written
  *       any changes to these values must be accompanied by a review of the XML output
  */

define('CROSSREF_XMLNS_XSI' , 'http://www.w3.org/2001/XMLSchema-instance');
define('CROSSREF_XMLNS' , 'http://www.crossref.org/schema/3.0.3'); 
define('CROSSREF_VERSION' , '3.0.3'); 
define('CROSSREF_XSI_SCHEMALOCATION' , 'http://www.crossref.org/schema/3.0.3 http://www.crossref.org/schema/3.0.3/crossref3.0.3.xsd');


class CrossRefExportDom {

	/**
	 * Build article XML using DOM elements
	 *
	 * The DOM for this XML was developed according
	 * http://www.crossref.org/schema/3.0.3
	 */ 

	function &generateCrossRefDom() {
		// create the output XML document in DOM with a root node
		$doc = &XMLCustomWriter::createDocument('', '', '');
		
		return $doc;
	}

	function &generateDoiBatchDom(&$doc) {

		// Generate the root node for the file first and set its attributes 
		$root = &XMLCustomWriter::createElement($doc, 'doi_batch');

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

	/* Generate the <head> tag that accompanies each submission */	
	function &generateHeadDom(&$doc, &$journal ) {
		$head = &XMLCustomWriter::createElement($doc, 'head');
		
		/* TODO: fix some DOI batch ID */
		XMLCustomWriter::createChildWithText($doc, $head, 'doi_batch_id', 'some DOI batch ID');
		XMLCustomWriter::createChildWithText($doc, $head, 'timestamp', time());

		$journalId = $journal->getJournalId();

		/* Depositor defaults to the Journal's technical Contact */
		$depositorNode = &CrossRefExportDom::generateDepositorDom($doc, $journal->getSetting('supportName'), $journal->getSetting('supportEmail'));
		XMLCustomWriter::appendChild($head, $depositorNode);
		
		/* The registrant is assumed to be the Primary Contact for the journal */
		XMLCustomWriter::createChildWithText($doc, $head, 'registrant', $journal->getSetting('contactName') );


		return $head;
	}
	
	/* Depositor Node */
	function &generateDepositorDom( &$doc, $name, $email ) {
		$depositor = &XMLCustomWriter::createElement($doc, 'depositor');
		XMLCustomWriter::createChildWithText($doc, $depositor, 'name', $name);
		XMLCustomWriter::createChildWithText($doc, $depositor, 'email_address', $email);
		
		return $depositor;
	}
	
	/* Metadata for journal - accompanies every article */
	function &generateJournalMetadataDom( &$doc, &$journal ) {
		$journalMetadataNode = &XMLCustomWriter::createElement($doc, 'journal_metadata');


		/* Full Title of Journal */
		XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'full_title', $journal->getTitle());

		/* Abbreviated title - defaulting to initials if no abbreviation found */
		XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'abbrev_title', ($journal->getSetting('journalAbbreviation') == '' )?$journal->getSetting('journalInitials'):$journal->getSetting('journalAbbreviation'));


		/* Both ISSNs are permitted for CrossRef, so sending whichever one (or both) */
		if ( $ISSN = $journal->getSetting('onlineIssn') ) {
			$onlineISSN = &XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'issn', $ISSN);
			XMLCustomWriter::setAttribute($onlineISSN, 'media_type', 'electronic');
		}


		/* Both ISSNs are permitted for CrossRef so sending whichever one (or both) */
		if ( $ISSN = $journal->getSetting('printIssn') ) {
			$printISSN = &XMLCustomWriter::createChildWithText($doc, $journalMetadataNode, 'issn', $ISSN);
			XMLCustomWriter::setAttribute($printISSN, 'media_type', 'print');
		}
		 
			
		return $journalMetadataNode;
	}

	/* Journal Issue tag to accompany every article */
	function &generateJournalIssueDom( &$doc, &$journal, &$issue, &$section, &$article) {
		$journalIssueNode = &XMLCustomWriter::createElement($doc, 'journal_issue');

		$publicationDateNode = &XMLCustomWriter::createElement($doc, 'publication_date');

		/* default to online type */
		XMLCustomWriter::setAttribute($publicationDateNode, 'media_type', 'online');
		XMLCustomWriter::appendChild($journalIssueNode, $publicationDateNode);		
		
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'year', $issue->getYear());
		
		$journalVolumeNode = &XMLCustomWriter::createElement($doc, 'journal_volume');
		XMLCustomWriter::appendChild($journalIssueNode, $journalVolumeNode);
		XMLCustomWriter::createChildWithText($doc, $journalVolumeNode, 'volume', $issue->getVolume());
		
		XMLCustomWriter::createChildWithText($doc, $journalIssueNode, 'issue', $issue->getNumber());		

		return $journalIssueNode;
	}

	/* The heart of the file.  The Journal Article itself */
	function &generateJournalArticleDom(&$doc, &$journal, &$issue, &$section, &$article) {

		// Create the base node
		$journalArticleNode = &XMLCustomWriter::createElement($doc, 'journal_article');
		XMLCustomWriter::setAttribute($journalArticleNode, 'publication_type', 'full_text');

		/* Titles */
		$titlesNode = &XMLCustomWriter::createElement($doc, 'titles');
		XMLCustomWriter::createChildWithText($doc, $titlesNode, 'title', $article->getTitle());
		XMLCustomWriter::appendChild($journalArticleNode, $titlesNode);
		
		$contributorsNode = &XMLCustomWriter::createElement($doc, 'contributors');
		
		/* AuthorList */
		foreach ($article->getAuthors() as $author) {
			$authorNode =& CrossRefExportDom::generateAuthorDom($doc, $author);
			XMLCustomWriter::appendChild($contributorsNode, $authorNode);
		}		
		XMLCustomWriter::appendChild($journalArticleNode, $contributorsNode);
		
		/* publication date of article */
		$publicationDateNode = &CrossRefExportDom::generatePublisherDateDom($doc, $article->getDatePublished());
		XMLCustomWriter::appendChild($journalArticleNode, $publicationDateNode);

		/* the article ID is used as the publisher_item */
		$publisherItemNode = &XMLCustomWriter::createElement($doc, 'publisher_item');
		XMLCustomWriter::createChildWithText($doc, $publisherItemNode, 'item_number', $article->getArticleId());
		XMLCustomWriter::appendChild($journalArticleNode, $publisherItemNode);

		return $journalArticleNode;
	}

	/* DOI data part - this is what assigns the DOI */
	function &generateDOIdataDom( &$doc, $DOI, $url ) {
		$DOIdataNode = & XMLCustomWriter::createElement($doc, 'doi_data');
		XMLCustomWriter::createChildWithText($doc, $DOIdataNode, 'doi', $DOI);
		XMLCustomWriter::createChildWithText($doc, $DOIdataNode, 'resource', $url);
		
		return $DOIdataNode;
	}

	/* author node */
	function &generateAuthorDom(&$doc, &$author) {
		$authorNode = &XMLCustomWriter::createElement($doc, 'person_name');
		XMLCustomWriter::setAttribute($authorNode, 'contributor_role', 'author');
		
		/* there should only be 1 primary contact per article */
		if ($author->getPrimaryContact()) {
			XMLCustomWriter::setAttribute($authorNode, 'sequence', 'first');			
		} else {
			XMLCustomWriter::setAttribute($authorNode, 'sequence', 'additional');
		}

		XMLCustomWriter::createChildWithText($doc, $authorNode, 'given_name', ucfirst($author->getFirstName()).(($author->getMiddleName())?' '.ucfirst($author->getMiddleName()):''));
		XMLCustomWriter::createChildWithText($doc, $authorNode, 'surname', ucfirst($author->getLastName()));

		return $authorNode;
	}

	/* publisher date - order matters */
	function &generatePublisherDateDom(&$doc, $pubdate) {
		$publicationDateNode = &XMLCustomWriter::createElement($doc, 'publication_date');
		XMLCustomWriter::setAttribute($publicationDateNode, 'media_type', 'online');

		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'month', date('m', strtotime($pubdate)), false );
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'day', date('d', strtotime($pubdate)), false );
		XMLCustomWriter::createChildWithText($doc, $publicationDateNode, 'year', date('Y', strtotime($pubdate)) );


		return $publicationDateNode;
	}

}

?>
