<?php

/**
 * @file plugins/oaiMetadataFormats/marcxml/OAIMetadataFormat_MARC21.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_MARC21
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- MARC21 (MARCXML).
 */

class OAIMetadataFormat_MARC21 extends OAIMetadataFormat {
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
			$record->relation[] = Request::url($journal->getPath(), 'article', 'download', array($article->getId(), $suppFile->getFileId()));
		}

		// Coverage
		$coverage = array(
			$article->getLocalizedCoverageGeo(),
			$article->getLocalizedCoverageChron(),
			$article->getLocalizedCoverageSample()
		);

		$response = "<record\n" .
			"\txmlns=\"http://www.loc.gov/MARC21/slim\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://www.loc.gov/MARC21/slim\n" .
			"\thttp://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd\">\n" .
			"\t<leader>     cam         3u     </leader>\n" .
			($article->getDatePublished()?"\t<controlfield tag=\"008\">\"" . date('ymd Y', strtotime($article->getDatePublished())) . "                        eng  \"</controlfield>\n":'') .
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
			$this->formatElement('787', '0', ' ', 'n', $record->relation) .
			$this->formatElement('500', ' ', ' ', 'a', $coverage) .
			$this->formatElement('540', ' ', ' ', 'a', __('submission.copyrightStatement', array('copyrightYear' => $article->getCopyrightYear(), 'copyrightHolder' => $article->getLocalizedCopyrightHolder()))) .
			"</record>\n";

		return $response;
	}

	/**
	 * Format XML for single MARC21 element.
	 * @param $tag string
	 * @param $ind1 string
	 * @param $ind2 string
	 * @param $code string
	 * @param $value mixed
	 */
	function formatElement($tag, $ind1, $ind2, $code, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}
		$response = '';
		foreach ($value as $v) {
			$response .= "\t<datafield tag=\"$tag\" ind1=\"$ind1\" ind2=\"$ind2\">\n" .
				"\t\t<subfield code=\"$code\">" . OAIUtils::prepOutput($v) . "</subfield>\n" .
				"\t</datafield>\n";
		}
		return $response;
	}
}

?>
