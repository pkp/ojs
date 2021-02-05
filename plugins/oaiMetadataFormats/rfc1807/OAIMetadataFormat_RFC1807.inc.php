<?php

/**
 * @file plugins/oaiMetadataFormats/rfc1807/OAIMetadataFormat_RFC1807.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_RFC1807
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- RFC 1807.
 */

class OAIMetadataFormat_RFC1807 extends OAIMetadataFormat {
	/**
	 * @see OAIMetadataFormat#toXml
	 */
	function toXml($record, $format = null) {
		$article =& $record->getData('article');
		$journal =& $record->getData('journal');
		$section =& $record->getData('section');
		$issue =& $record->getData('issue');
		$galleys =& $record->getData('galleys');

		// Publisher
		$publisher = $journal->getLocalizedName(); // Default
		$publisherInstitution = $journal->getData('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$publisher = $publisherInstitution;
		}

		// Sources contains journal title, issue ID, and pages
		$source = $issue->getIssueIdentification();
		$pages = $article->getPages();
		if (!empty($pages)) $source .= '; ' . $pages;

		// Format creators
		$creators = array();
		$authors = $article->getAuthors();
		for ($i = 0, $num = count($authors); $i < $num; $i++) {
			$authorName = $authors[$i]->getFullName(false, true);
			$affiliation = $authors[$i]->getLocalizedAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$creators[] = $authorName;
		}

		// Subject
		$supportedLocales = array_keys(AppLocale::getSupportedFormLocales());
		$submissionKeywordDao = DAORegistry::getDAO('SubmissionKeywordDAO'); /* @var $submissionKeywordDao SubmissionKeywordDAO */
		$submissionSubjectDao = DAORegistry::getDAO('SubmissionSubjectDAO'); /* @var $submissionSubjectDao SubmissionSubjectDAO */
		$subjects = array_merge_recursive(
			(array) $submissionKeywordDao->getKeywords($article->getCurrentPublication()->getId(), $supportedLocales),
			(array) $submissionSubjectDao->getSubjects($article->getCurrentPublication()->getId(), $supportedLocales)
		);
		$subject = $subjects[$journal->getPrimaryLocale()] ?? '';

		// Coverage
		$coverage = $article->getCoverage($article->getData('locale'));

		import('classes.issue.IssueAction');
		$issueAction = new IssueAction();
		$request = Application::get()->getRequest();
		$url = $request->url($journal->getPath(), 'article', 'view', array($article->getBestId()));
		$includeUrls = $journal->getSetting('publishingMode') != PUBLISHING_MODE_NONE || $issueAction->subscribedUser($request->getUser(), $journal, null, $article->getId());
		$response = "<rfc1807\n" .
			"\txmlns=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://info.internet.isi.edu:80/in-notes/rfc/files/rfc1807.txt\n" .
			"\thttp://www.openarchives.org/OAI/1.1/rfc1807.xsd\">\n" .
			"\t<bib-version>v2</bib-version>\n" .
			$this->formatElement('id', $url) .
			$this->formatElement('entry', $record->datestamp) .
			$this->formatElement('organization', $publisher) .
			$this->formatElement('organization', $source) .
			$this->formatElement('title', $article->getLocalizedTitle()) .
			$this->formatElement('type', $section->getLocalizedIdentifyType()) .

			$this->formatElement('author', $creators) .
			($article->getDatePublished()?$this->formatElement('date', $article->getDatePublished()):'') .
			$this->formatElement('copyright', strip_tags($journal->getLocalizedData('licenseTerms'))) .
			($includeUrls?$this->formatElement('other_access', "url:$url"):'') .
			$this->formatElement('keyword', $subject) .
			$this->formatElement('period', $coverage) .
			$this->formatElement('monitoring', $article->getLocalizedSponsor()) .
			$this->formatElement('language', $article->getData('locale')) .
			$this->formatElement('abstract', strip_tags($article->getLocalizedAbstract())) .
			"</rfc1807>\n";

		return $response;
	}

	/**
	 * Format XML for single RFC 1807 element.
	 * @param $name string
	 * @param $value mixed
	 */
	function formatElement($name, $value) {
		$response = '';
		foreach ((array) $value as $v) {
			$response .= "\t<$name>" . OAIUtils::prepOutput($v) . "</$name>\n";
		}
		return $response;
	}
}
