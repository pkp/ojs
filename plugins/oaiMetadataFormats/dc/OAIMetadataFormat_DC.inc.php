<?php

/**
 * @defgroup oai_format
 */

/**
 * @file plugins/oaiMetadata/dc/OAIMetadataFormat_DC.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_DC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- Dublin Core.
 */
 
class OAIMetadataFormat_DC extends OAIMetadataFormat {
	/**
	 * @see OAIMetadataFormat#toXML
	 */
	function toXml(&$record, $format = null) {
        $response = null;
        if (!HookRegistry::call('OAIMetadataFormat_DC::toXml', array(&$this, $record, &$response))) {
			$article =& $record->getData('article');
			$journal =& $record->getData('journal');
			$section =& $record->getData('section');
			$issue =& $record->getData('issue');
			$galleys =& $record->getData('galleys');
	
			Locale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
	
			// Sources contains journal title, issue ID, and pages
			$sources = $this->stripAssocArray((array) $journal->getTitle(null));
			$pages = $article->getPages();
			if (!empty($pages)) $pages = '; ' . $pages;
			foreach ($sources as $key => $source) {
				$sources[$key] .= '; ' . $issue->getIssueIdentification() . $pages;
			}
	
			// Format creators
			$creators = array();
			$authors = $article->getAuthors();
			for ($i = 0, $num = count($authors); $i < $num; $i++) {
				$authorName = $authors[$i]->getFullName(true);
				$affiliation = $authors[$i]->getLocalizedAffiliation();
				if (!empty($affiliation)) {
					$authorName .= '; ' . $affiliation;
				}
				$creators[] = $authorName;
			}
	
			// Publisher
			$publishers = $this->stripAssocArray((array) $journal->getTitle(null)); // Default
			$publisherInstitution = $journal->getSetting('publisherInstitution');
			if (!empty($publisherInstitution)) {
				$publishers = array($journal->getPrimaryLocale() => $publisherInstitution);
			}
	
			// Types
			$types = $this->stripAssocArray((array) $section->getIdentifyType(null));
			$types = array_merge_recursive(
				empty($types)?array(Locale::getLocale() => Locale::translate('rt.metadata.pkp.peerReviewed')):$types,
				$this->stripAssocArray((array) $article->getType(null))
			);
	
			// Formats
			$formats = array();
			foreach ($galleys as $galley) {
				$formats[] = $galley->getFileType();
			}
	
			// Relation
			$relation = array();
			foreach ($article->getSuppFiles() as $suppFile) {
				$relation[] = Request::url($journal->getPath(), 'article', 'downloadSuppFile', array($article->getId(), $suppFile->getFileId()));
			}
	
			$response = "<oai_dc:dc\n" .
				"\txmlns:oai_dc=\"http://www.openarchives.org/OAI/2.0/oai_dc/\"\n" .
				"\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n" .
				"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
				"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/2.0/oai_dc/\n" .
				"\thttp://www.openarchives.org/OAI/2.0/oai_dc.xsd\">\n" .
				$this->formatElement('title', $this->stripAssocArray((array) $article->getTitle(null)), true) .
				$this->formatElement('creator', $creators) .
				$this->formatElement(
					'subject',
					array_merge_recursive(
						$this->stripAssocArray((array) $article->getDiscipline(null)),
						$this->stripAssocArray((array) $article->getSubject(null)),
						$this->stripAssocArray((array) $article->getSubjectClass(null))
					),
					true
				) .
				$this->formatElement('description', $this->stripAssocArray((array) $article->getAbstract(null)), true) .
				$this->formatElement('publisher', $publishers, true) .
				$this->formatElement('contributor', $this->stripAssocArray((array) $article->getSponsor(null)), true) .
				$this->formatElement('date', date('Y-m-d', strtotime($issue->getDatePublished()))) .
				$this->formatElement('type', $types, true) .
				$this->formatElement('format', $formats) .
				$this->formatElement('identifier', Request::url($journal->getPath(), 'article', 'view', array($article->getBestArticleId()))) .
				(($doi = $article->getDOI())?$this->formatElement('identifier', $doi, false, array('xsi:type' => 'dcterms:DOI')):'') .
				$this->formatElement('source', $sources, true) .
				$this->formatElement('language', strip_tags($article->getLanguage())) .
				$this->formatElement('relation', $relation) .
				$this->formatElement(
					'coverage',
					array_merge_recursive(
						$this->stripAssocArray((array) $article->getCoverageGeo(null)),
						$this->stripAssocArray((array) $article->getCoverageChron(null)),
						$this->stripAssocArray((array) $article->getCoverageSample(null))
					),
					true
				) .
				$this->formatElement('rights', $this->stripAssocArray((array) $journal->getSetting('copyrightNotice'))) .
				"</oai_dc:dc>\n";
        }
		return $response;
	}

	/**
	 * Format XML for single DC element.
	 * @param $name string
	 * @param $value mixed
	 * @param $multilingual boolean optional
	 */
	function formatElement($name, $value, $multilingual = false, $attribs = array()) {
		if (!is_array($value)) {
			$value = array($value);
		}

		$attribText = '';
		foreach ($attribs as $n => $v) {
			$attribText .= " $n=\"" . htmlspecialchars($v) . "\"";
		}

		$response = '';
		foreach ($value as $key => $v) {
			$key = str_replace('_', '-', $key);
			if (!$multilingual) $response .= "\t<dc:$name$attribText>" . OAIUtils::prepOutput($v) . "</dc:$name>\n";
			else {
				if (is_array($v)) {
					foreach ($v as $subV) {
						$response .= "\t<dc:$name xml:lang=\"$key\">" . OAIUtils::prepOutput($subV) . "</dc:$name>\n";
					}
				} else {
					$response .= "\t<dc:$name xml:lang=\"$key\">" . OAIUtils::prepOutput($v) . "</dc:$name>\n";
				}
			}
		}
		return $response;
	}
}

?>
