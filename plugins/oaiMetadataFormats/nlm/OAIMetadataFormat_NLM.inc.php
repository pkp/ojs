<?php

/**
 * @defgroup oai_format_nlm
 */

/**
 * @file plugins/oaiMetadataFormats/nlm/OAIMetadataFormat_NLM.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAIMetadataFormat_NLM
 * @ingroup oai_format
 * @see OAI
 *
 * @brief OAI metadata format class -- NLM 2.3
 */


class OAIMetadataFormat_NLM extends OAIMetadataFormat {

	/**
	 * @see OAIMetadataFormat#toXml
	 * TODO:
	 *  <copyright-holder>
	 *  In Isabelle's mapping document:
	 *   Article order in the issue's Table of Contents
	 */
	function toXml(&$record, $format = null) {
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		$article =& $record->getData('article');
		$journal =& $record->getData('journal');
		$section =& $record->getData('section');
		$issue =& $record->getData('issue');
		$galleys =& $record->getData('galleys');
		$articleId = $article->getId();

		// Cache issue ordering information.
		static $issueId;
		static $sectionSeq;
		if (!isset($issueId) || $issueId != $issue->getId()) {
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$issueId = $issue->getId();
			$sections =& $sectionDao->getSectionsForIssue($issueId);
			$sectionSeq = array();
			$i=0;
			foreach ($sections as $thisSection) {
				$sectionSeq[$thisSection->getId()] = $i++;
			}
			unset($sections);
		}

		$abbreviation = $journal->getLocalizedSetting('abbreviation');
		$printIssn = $journal->getSetting('printIssn');
		$onlineIssn = $journal->getSetting('onlineIssn');
		$primaryLocale = ($article->getLanguage() != '') ? $article->getLanguage() : $journal->getPrimaryLocale();

		$publisherInstitution = $journal->getSetting('publisherInstitution');
		$datePublished = $article->getDatePublished();
		if (!$datePublished) $datePublished = $issue->getDatePublished();
		if ($datePublished) $datePublished = strtotime($datePublished);

		$response = "<article\n" .
			"\txmlns=\"http://dtd.nlm.nih.gov/publishing/2.3\"\n" .
			"\txmlns:xlink=\"http://www.w3.org/1999/xlink\"\n" .
			"\txmlns:mml=\"http://www.w3.org/1998/Math/MathML\"\n" .
			"\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n" .
			"\txsi:schemaLocation=\"http://dtd.nlm.nih.gov/publishing/2.3\n" .
			"\thttp://dtd.nlm.nih.gov/publishing/2.3/xsd/journalpublishing.xsd\"\n" .
			(($s = $section->getLocalizedIdentifyType())!=''?"\tarticle-type=\"" . htmlspecialchars(Core::cleanVar($s)) . "\"":'') .
			"\txml:lang=\"" . strtoupper(substr($primaryLocale, 0, 2)) . "\">\n" .
			"\t<front>\n" .
			"\t\t<journal-meta>\n" .
			"\t\t\t<journal-id journal-id-type=\"other\">" . htmlspecialchars(Core::cleanVar(($s = Config::getVar('oai', 'nlm_journal_id'))!=''?$s:$journal->getPath())) . "</journal-id>\n" .
			"\t\t\t<journal-title>" . htmlspecialchars(Core::cleanVar($journal->getLocalizedTitle())) . "</journal-title>\n";

		// Include translated journal titles
		foreach ($journal->getTitle(null) as $locale => $title) {
			if ($locale == $primaryLocale) continue;
			$response .= "\t\t\t<trans-title xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">" . htmlspecialchars(Core::cleanVar($title)) . "</trans-title>\n";
		}

		$response .=
			(!empty($onlineIssn)?"\t\t\t<issn pub-type=\"epub\">" . htmlspecialchars(Core::cleanVar($onlineIssn)) . "</issn>":'') .
			(!empty($printIssn)?"\t\t\t<issn pub-type=\"ppub\">" . htmlspecialchars(Core::cleanVar($printIssn)) . "</issn>":'') .
			($publisherInstitution != ''?"\t\t\t<publisher><publisher-name>" . htmlspecialchars(Core::cleanVar($publisherInstitution)) . "</publisher-name></publisher>\n":'') .
			"\t\t</journal-meta>\n" .
			"\t\t<article-meta>\n" .
			"\t\t\t<article-id pub-id-type=\"other\">" . htmlspecialchars(Core::cleanVar($article->getBestArticleId())) . "</article-id>\n" .
			(($s = $article->getPubId('doi'))?"\t\t\t<article-id pub-id-type=\"doi\">" . htmlspecialchars(Core::cleanVar($s)) . "</article-id>\n":'') .
			"\t\t\t<article-categories><subj-group subj-group-type=\"heading\"><subject>" . htmlspecialchars(Core::cleanVar($section->getLocalizedTitle())) . "</subject></subj-group></article-categories>\n" .
			"\t\t\t<title-group>\n" .
			"\t\t\t\t<article-title>" . htmlspecialchars(Core::cleanVar(strip_tags($article->getLocalizedTitle()))) . "</article-title>\n";

		// Include translated journal titles
		foreach ($article->getTitle(null) as $locale => $title) {
			if ($locale == $primaryLocale) continue;
			$response .= "\t\t\t\t<trans-title xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">" . htmlspecialchars(Core::cleanVar(strip_tags($title))) . "</trans-title>\n";
		}

		$response .=
			"\t\t\t</title-group>\n" .
			"\t\t\t<contrib-group>\n";

		// Include authors
		foreach ($article->getAuthors() as $author) {
			$response .=
				"\t\t\t\t<contrib " . ($author->getPrimaryContact()?'corresp="yes" ':'') . "contrib-type=\"author\">\n" .
				"\t\t\t\t\t<name name-style=\"western\">\n" .
				"\t\t\t\t\t\t<surname>" . htmlspecialchars(Core::cleanVar($author->getLastName())) . "</surname>\n" .
				"\t\t\t\t\t\t<given-names>" . htmlspecialchars(Core::cleanVar($author->getFirstName()) . (($s = $author->getMiddleName()) != ''?" $s":'')) . "</given-names>\n" .
				"\t\t\t\t\t</name>\n" .
				(($s = $author->getLocalizedAffiliation()) != ''?"\t\t\t\t\t<aff>" . htmlspecialchars(Core::cleanVar($s)) . "</aff>\n":'') .
				"\t\t\t\t\t<email>" . htmlspecialchars(Core::cleanVar($author->getEmail())) . "</email>\n" .
				(($s = $author->getUrl()) != ''?"\t\t\t\t\t<uri>" . htmlspecialchars(Core::cleanVar($s)) . "</uri>\n":'') .
				"\t\t\t\t</contrib>\n";
		}

		// Include editorships (optimized)
		$response .= $this->getEditorialInfo($journal->getId());

		$response .= "\t\t\t</contrib-group>\n";
		if ($datePublished) $response .=
			"\t\t\t<pub-date pub-type=\"epub\">\n" .
			"\t\t\t\t<day>" . strftime('%d', $datePublished) . "</day>\n" .
			"\t\t\t\t<month>" . strftime('%m', $datePublished) . "</month>\n" .
			"\t\t\t\t<year>" . strftime('%Y', $datePublished) . "</year>\n" .
			"\t\t\t</pub-date>\n";

		$response .=
			($issue->getShowYear()?"\t\t\t<pub-date pub-type=\"collection\"><year>" . htmlspecialchars(Core::cleanVar($issue->getYear())) . "</year></pub-date>\n":'') .
			($issue->getShowVolume()?"\t\t\t<volume>" . htmlspecialchars(Core::cleanVar($issue->getVolume())) . "</volume>\n":'') .
			($issue->getShowNumber()?"\t\t\t<issue seq=\"" . htmlspecialchars(Core::cleanVar(($sectionSeq[$section->getId()]*100) + $article->getSeq())) . "\">" . htmlspecialchars(Core::cleanVar($issue->getNumber())) . "</issue>\n":'') .
			"\t\t\t<issue-id pub-id-type=\"other\">" . htmlspecialchars(Core::cleanVar($issue->getBestIssueId())) . "</issue-id>\n" .
			($issue->getShowTitle()?"\t\t\t<issue-title>" . htmlspecialchars(Core::cleanVar($issue->getLocalizedTitle())) . "</issue-title>\n":'');

		// Include page info, if available and parseable.
		$matches = null;
		if (String::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)$/', $article->getPages(), $matches)) {
			$matchedPage = htmlspecialchars(Core::cleanVar($matches[1]));
			$response .= "\t\t\t\t<fpage>$matchedPage</fpage><lpage>$matchedPage</lpage>\n";
			$pageCount = 1;
		} elseif (String::regexp_match_get('/^[Pp][Pp]?[.]?[ ]?(\d+)[ ]?-[ ]?([Pp][Pp]?[.]?[ ]?)?(\d+)$/', $article->getPages(), $matches)) {
			$matchedPageFrom = htmlspecialchars(Core::cleanVar($matches[1]));
			$matchedPageTo = htmlspecialchars(Core::cleanVar($matches[3]));
			$response .=
				"\t\t\t\t<fpage>$matchedPageFrom</fpage>\n" .
				"\t\t\t\t<lpage>$matchedPageTo</lpage>\n";
			$pageCount = $matchedPageTo - $matchedPageFrom + 1;
		}

		$response .=
			"\t\t\t<permissions>\n" .
			"\t\t\t\t<copyright-statement>" . htmlspecialchars(__('submission.copyrightStatement', array('copyrightYear' => $article->getCopyrightYear(), 'copyrightHolder' => $article->getLocalizedCopyrightHolder()))) . "</copyright-statement>\n" .
			($datePublished?"\t\t\t\t<copyright-year>" . $article->getCopyrightYear() . "</copyright-year>\n":'') .
			"\t\t\t\t<license xlink:href=\"" . $article->getLicenseURL() . "\">\n" .
			(($s = Application::getCCLicenseBadge($article->getLicenseURL()))?"\t\t\t\t\t<license-p>" . strip_tags($s) . "</license-p>\n":'') .
			"\t\t\t\t</license>\n" .
			"\t\t\t</permissions>\n" .
			"\t\t\t<self-uri xlink:href=\"" . htmlspecialchars(Core::cleanVar(Request::url($journal->getPath(), 'article', 'view', $article->getBestArticleId()))) . "\" />\n";

		// Include galley links
		foreach ($article->getGalleys() as $galley) {
			$response .= "\t\t\t<self-uri content-type=\"" . htmlspecialchars(Core::cleanVar($galley->getFileType())) . "\" xlink:href=\"" . htmlspecialchars(Core::cleanVar(Request::url($journal->getPath(), 'article', 'view', array($article->getBestArticleId(), $galley->getId())))) . "\" />\n";
		}

		// Include abstract(s)
		$abstract = htmlspecialchars(Core::cleanVar(strip_tags($article->getLocalizedAbstract())));
		if (!empty($abstract)) {
			$abstract = "<p>$abstract</p>";
			// $abstract = '<p>' . String::regexp_replace('/\n+/', '</p><p>', $abstract) . '</p>';
			$response .= "\t\t\t<abstract xml:lang=\"" . strtoupper(substr($primaryLocale, 0, 2)) . "\">$abstract</abstract>\n";
		}
		if (is_array($article->getAbstract(null))) foreach ($article->getAbstract(null) as $locale => $abstract) {
			if ($locale == $primaryLocale || empty($abstract)) continue;
			$abstract = htmlspecialchars(Core::cleanVar(strip_tags($abstract)));
			if (empty($abstract)) continue;
			$abstract = "<p>$abstract</p>";
			//$abstract = '<p>' . String::regexp_replace('/\n+/', '</p><p>', $abstract) . '</p>';
			$response .= "\t\t\t<abstract-trans xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">$abstract</abstract-trans>\n";
		}

		$subjects = array();
		if (is_array($article->getSubject(null))) foreach ($article->getSubject(null) as $locale => $subject) {
			$s = array_map('trim', explode(';', Core::cleanVar($subject)));
			if (!empty($s)) $subjects[$locale] = $s;
		}
		if (!empty($subjects)) foreach ($subjects as $locale => $s) {
			$response .= "\t\t\t<kwd-group xml:lang=\"" . strtoupper(substr($locale, 0, 2)) . "\">\n";
			foreach ($s as $subject) $response .= "\t\t\t\t<kwd>" . htmlspecialchars($subject) . "</kwd>\n";
			$response .= "\t\t\t</kwd-group>\n";
		}

		$response .=
			(isset($pageCount)?"\t\t\t<counts><page-count count=\"" . (int) $pageCount. "\" /></counts>\n":'') .
			"\t\t</article-meta>\n" .
			"\t</front>\n";

		// Include body text (for search indexing only)
		import('classes.search.ArticleSearchIndex');
		$text = '';
		$galleys = $article->getGalleys();

		// Give precedence to HTML galleys, as they're quickest to parse
		usort($galleys, create_function('$a, $b', 'return $a->isHtmlGalley()?-1:1;'));

		// Determine any access limitations. If there are, do not
		// provide the full-text.
		import('classes.issue.IssueAction');
		$subscriptionRequired = IssueAction::subscriptionRequired($issue);
		$isSubscribedDomain = IssueAction::subscribedDomain($journal, $issue->getId(), $article->getId());

		if (!$subscriptionRequired || $isSubscribedDomain) foreach ($galleys as $galley) {
			$parser =& SearchFileParser::fromFile($galley);
			if ($parser && $parser->open()) {
				while(($s = $parser->read()) !== false) $text .= $s;
				$parser->close();
			}

			if ($galley->isHtmlGalley()) $text = strip_tags($text);
			unset($galley);
			// Use the first parseable galley.
			if (!empty($text)) break;
		}
		if (!empty($text)) $response .= "\t<body><p>" . htmlspecialchars(Core::cleanVar(Core::cleanVar($text))) . "</p></body>\n";

		// Add NLM citation info
		$filterDao =& DAORegistry::getDAO('FilterDAO'); /* @var $filterDao FilterDAO */
		$nlmFilters = $filterDao->getObjectsByGroup('submission=>nlm23-article-xml');
		assert(count($nlmFilters) == 1);
		$nlmFilter = array_pop($nlmFilters);
		$nlmXmlDom = new DOMDocument();
		$nlmXmlDom->loadXML($nlmFilter->execute($article));
		$documentElement =& $nlmXmlDom->documentElement;

		// Work-around for hasChildNodes being stupid about whitespace.
		$hasChildren = false;
		if (isset($documentElement->childNodes)) foreach ($documentElement->childNodes as $c) {
			if ($c->nodeType == XML_ELEMENT_NODE) $hasChildren = true;
		}

		// If there were any citations, include them.
		if ($hasChildren) {
			$innerXml = $nlmXmlDom->saveXML($documentElement);
			$response .= "<back>$innerXml</back>\n";
		}

		$response .= "</article>";

		return $response;
	}

	function getEditorialInfo($journalId) {
		static $editorialInfo = array();
		if (isset($editorialInfo[$journalId])) return $editorialInfo[$journalId];

		$response = '';
		$roleDao =& DAORegistry::getDAO('RoleDAO');
		$roleMap = array(ROLE_ID_EDITOR => 'editor', ROLE_ID_SECTION_EDITOR> 'secteditor', ROLE_ID_JOURNAL_MANAGER => 'jmanager');
		foreach ($roleMap as $roleId => $roleName) {
			$users =& $roleDao->getUsersByRoleId($roleId, $journalId);
			$isFirst = true;
			while ($user =& $users->next()) {
				$response .= "\t\t\t\t<contrib contrib-type=\"$roleName\">\n" .
					"\t\t\t\t\t<name>\n" .
					"\t\t\t\t\t\t<surname>" . htmlspecialchars(Core::cleanVar($user->getLastName())) . "</surname>\n" .
					"\t\t\t\t\t\t<given-names>" . htmlspecialchars(Core::cleanVar($user->getFirstName() . ($user->getMiddleName() != ''?' ' . $user->getMiddleName():''))) . "</given-names>\n" .
					"\t\t\t\t\t</name>\n" .
					"\t\t\t\t</contrib>\n";
				unset($user);
			}
			unset($users);
		}
		$editorialInfo[$journalId] =& $response;
		return $response;
	}
}

?>
