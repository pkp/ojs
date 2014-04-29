<?php

/**
 * @file plugins/oaiMetadataFormats/marc/OAIMetadataFormat_MARC.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC.
 */

class OAIMetadataFormat_MARC extends OAIMetadataFormat {
	/**
	 * @see OAIMetadataFormat#toXml
	 */
	function toXml(&$record, $format = null) {
		$article =& $record->getData('article');
		$issue =& $record->getData('issue');
		$journal =& $record->getData('journal');
		$section =& $record->getData('section');
		$galleys =& $record->getData('galleys');

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

		$subjects = array_merge_recursive(
			$this->stripAssocArray((array) $article->getDiscipline(null)),
			$this->stripAssocArray((array) $article->getSubject(null)),
			$this->stripAssocArray((array) $article->getSubjectClass(null))
		);
		$subject = isset($subjects[$journal->getPrimaryLocale()])?$subjects[$journal->getPrimaryLocale()]:'';

		$publisher = $journal->getLocalizedTitle(); // Default
		$publisherInstitution = $journal->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publisher = $publisherInstitution;
		}

		// Format
		$format = array();
		foreach ($galleys as $galley) {
			$format[] = $galley->getFileType();
		}

		// Sources contains journal title, issue ID, and pages
		$source = $journal->getLocalizedTitle() . '; ' . $issue->getIssueIdentification();
		$pages = $article->getPages();

		// Relation
		$relation = array();
		foreach ($article->getSuppFiles() as $suppFile) {
			$relation[] = Request::url($journal->getPath(), 'article', 'download', array($article->getId(), $suppFile->getFileId()));
		}

		// Coverage
		$coverage = array(
			$article->getLocalizedCoverageGeo(),
			$article->getLocalizedCoverageChron(),
			$article->getLocalizedCoverageSample()
		);

		$response = "<oai_marc status=\"c\" type=\"a\" level=\"m\" encLvl=\"3\" catForm=\"u\"\n" .
			"\txmlns=\"http://www.openarchives.org/OAI/1.1/oai_marc\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.openarchives.org/OAI/1.1/oai_marc\n" .
			"\thttp://www.openarchives.org/OAI/1.1/oai_marc.xsd\">\n" .
			($article->getDatePublished()?"\t<fixfield id=\"008\">\"" . date('ymd Y', strtotime($article->getDatePublished())) . '												eng  "</fixfield>' . "\n":'') .
			$this->formatElement('042', ' ', ' ', 'a', 'dc') .
			$this->formatElement('245', '0', '0', 'a', $article->getTitle($journal->getPrimaryLocale())) .
			$this->formatElement('720', ' ', ' ', 'a', $creators) .
			$this->formatElement('653', ' ', ' ', 'a', $subject) .
			$this->formatElement('520', ' ', ' ', 'a', $article->getLocalizedAbstract()) .
			$this->formatElement('260', ' ', ' ', 'b', $publisher) .
			$this->formatElement('720', ' ', ' ', 'a', strip_tags($article->getLocalizedSponsor())) .
			($issue->getDatePublished()?$this->formatElement('260', ' ', ' ', 'c', $issue->getDatePublished()):'') .
			$this->formatElement('655', ' ', '7', 'a', $section->getLocalizedIdentifyType()) .
			$this->formatElement('856', ' ', ' ', 'q', $format) .
			$this->formatElement('856', '4', '0', 'u', Request::url($journal->getPath(), 'article', 'view', array($article->getBestArticleId()))) .
			$this->formatElement('786', '0', ' ', 'n', $source) .

			$this->formatElement('546', ' ', ' ', 'a', $article->getLanguage()) .
			$this->formatElement('787', '0', ' ', 'n', $relation) .
			$this->formatElement('500', ' ', ' ', 'a', $coverage) .
			$this->formatElement('540', ' ', ' ', 'a', __('submission.copyrightStatement', array('copyrightYear' => $article->getCopyrightYear(), 'copyrightHolder' => $article->getLocalizedCopyrightHolder()))) .
			"</oai_marc>\n";

		return $response;
	}

	/**
	 * Format XML for single MARC element.
	 * @param $id string
	 * @param $i1 string
	 * @param $i2 string
	 * @param $label string
	 * @param $value mixed
	 */
	function formatElement($id, $i1, $i2, $label, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}

		$response = '';
		foreach ($value as $v) {
			$response .= "\t<varfield id=\"$id\" i1=\"$i1\" i2=\"$i2\">\n" .
				"\t\t<subfield label=\"$label\">" . OAIUtils::prepOutput($v) . "</subfield>\n" .
				"\t</varfield>\n";
		}
		return $response;
	}
}

?>
