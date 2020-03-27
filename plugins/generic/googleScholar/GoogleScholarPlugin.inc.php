<?php

/**
 * @file plugins/generic/googleScholar/GoogleScholarPlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class GoogleScholarPlugin
 * @ingroup plugins_generic_googleScholar
 *
 * @brief Inject Google Scholar meta tags into submission views to facilitate indexing.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class GoogleScholarPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				HookRegistry::register('ArticleHandler::view', array(&$this, 'submissionView'));
				HookRegistry::register('PreprintHandler::view', array(&$this, 'submissionView'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new context
	 * creation.
	 * @return string
	 */
	function getContextSpecificPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Inject Google Scholar metadata into submission landing page view
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function submissionView($hookName, $args) {
		$application = Application::get();
		$applicationName = $application->getName();
		$request = $args[0];
		if ($applicationName == "ojs2"){
			$issue = $args[1];
			$submission = $args[2];
			$submissionPath = 'article';
		}
		if ($applicationName == "ops"){
			$submission = $args[1];
			$submissionPath = 'preprint';
		}		
		$requestArgs = $request->getRequestedArgs();
		$context = $request->getContext();

		// Only add Google Scholar metadata tags to the canonical URL for the latest version
		// See discussion: https://github.com/pkp/pkp-lib/issues/4870
		if (count($requestArgs) > 1 && $requestArgs[1] === 'version') {
			return;
		}

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->addHeader('googleScholarRevision', '<meta name="gs_meta_revision" content="1.1"/>');

		// Context identification
		if ($applicationName == "ojs2"){
			$templateMgr->addHeader('googleScholarJournalTitle', '<meta name="citation_journal_title" content="' . htmlspecialchars($context->getName($context->getPrimaryLocale())) . '"/>');
			if (($abbreviation = $context->getData('abbreviation', $context->getPrimaryLocale())) || ($abbreviation = $context->getData('acronym', $context->getPrimaryLocale()))) {
				$templateMgr->addHeader('googleScholarJournalAbbrev', '<meta name="citation_journal_abbrev" content="' . htmlspecialchars($abbreviation) . '"/>');
			}
			if ( ($issn = $context->getData('onlineIssn')) || ($issn = $context->getData('printIssn')) || ($issn = $context->getData('issn'))) {
				$templateMgr->addHeader('googleScholarIssn', '<meta name="citation_issn" content="' . htmlspecialchars($issn) . '"/> ');
			}
		}
		if ($applicationName == "ops"){
			$templateMgr->addHeader('googleScholarPublisher', '<meta name="citation_publisher" content="' . htmlspecialchars($context->getName($context->getPrimaryLocale())) . '"/>');
		}


		// Contributors
		foreach ($submission->getAuthors() as $i => $author) {
			$templateMgr->addHeader('googleScholarAuthor' . $i, '<meta name="citation_author" content="' . htmlspecialchars($author->getFullName(false)) .'"/>');
			if ($affiliation = htmlspecialchars($author->getAffiliation($submission->getLocale()))) {
				$templateMgr->addHeader('googleScholarAuthor' . $i . 'Affiliation', '<meta name="citation_author_institution" content="' . $affiliation . '"/>');
			}
		}

		// Submission title
		$templateMgr->addHeader('googleScholarTitle', '<meta name="citation_title" content="' . htmlspecialchars($submission->getFullTitle($submission->getLocale())) . '"/>');
		if ($locale = $submission->getLocale()) $templateMgr->addHeader('googleScholarLanguage', '<meta name="citation_language" content="' . htmlspecialchars(substr($locale, 0, 2)) . '"/>');

		// Submission publish date and issue information
		if ($applicationName == "ojs2"){
			if (is_a($submission, 'Submission') && ($datePublished = $submission->getDatePublished()) && (!$issue->getYear() || $issue->getYear() == strftime('%Y', strtotime($datePublished)))) {
				$templateMgr->addHeader('googleScholarDate', '<meta name="citation_date" content="' . strftime('%Y/%m/%d', strtotime($datePublished)) . '"/>');
			} elseif ($issue && $issue->getYear()) {
				$templateMgr->addHeader('googleScholarDate', '<meta name="citation_date" content="' . htmlspecialchars($issue->getYear()) . '"/>');
			} elseif ($issue && ($datePublished = $issue->getDatePublished())) {
				$templateMgr->addHeader('googleScholarDate', '<meta name="citation_date" content="' . strftime('%Y/%m/%d', strtotime($datePublished)) . '"/>');
			}
			if ($issue) {
				if ($issue->getShowVolume()) $templateMgr->addHeader('googleScholarVolume', '<meta name="citation_volume" content="' . htmlspecialchars($issue->getVolume()) . '"/>');
				if ($issue->getShowNumber()) $templateMgr->addHeader('googleScholarNumber', '<meta name="citation_issue" content="' . htmlspecialchars($issue->getNumber()) . '"/>');
			}
			if ($submission->getPages()) {
				if ($startPage = $submission->getStartingPage()) $templateMgr->addHeader('googleScholarStartPage', '<meta name="citation_firstpage" content="' . htmlspecialchars($startPage) . '"/>');
				if ($endPage = $submission->getEndingPage()) $templateMgr->addHeader('googleScholarEndPage', '<meta name="citation_lastpage" content="' . htmlspecialchars($endPage) . '"/>');
			}
		}
		if ($applicationName == "ops"){
			$templateMgr->addHeader('googleScholarDate', '<meta name="citation_online_date" content="' . strftime('%Y/%m/%d', strtotime($submission->getDatePublished())) . '"/>');
		}

		// Identifiers: DOI, URN
		foreach((array) $templateMgr->getTemplateVars('pubIdPlugins') as $pubIdPlugin) {
			if ($pubId = $submission->getStoredPubId($pubIdPlugin->getPubIdType())) {
				$templateMgr->addHeader('googleScholarPubId' . $pubIdPlugin->getPubIdDisplayType(), '<meta name="citation_' . htmlspecialchars(strtolower($pubIdPlugin->getPubIdDisplayType())) . '" content="' . htmlspecialchars($pubId) . '"/>');
			}
		}

		// Abstract url and keywords
		$templateMgr->addHeader('googleScholarHtmlUrl', '<meta name="citation_abstract_html_url" content="' . $request->url(null, $submissionPath, 'view', array($submission->getBestId())) . '"/>');

		$i=0;
		$dao = DAORegistry::getDAO('SubmissionKeywordDAO');
		$keywords = $dao->getKeywords($submission->getCurrentPublication()->getId(), array(AppLocale::getLocale()));
		foreach ($keywords as $locale => $localeKeywords) {
			foreach ($localeKeywords as $keyword) {
				$templateMgr->addHeader('googleScholarKeyword' . $i++, '<meta name="citation_keywords" xml:lang="' . htmlspecialchars(substr($locale, 0, 2)) . '" content="' . htmlspecialchars($keyword) . '"/>');
			}
		}

		// Galley links
		$i=$j=0;
		if (is_a($submission, 'Submission')) foreach ($submission->getGalleys() as $galley) {
			if (is_a($galley->getFile(), 'SupplementaryFile')) continue;
			if ($galley->getFileType()=='application/pdf') {
				$templateMgr->addHeader('googleScholarPdfUrl' . $i++, '<meta name="citation_pdf_url" content="' . $request->url(null, $submissionPath, 'download', array($submission->getBestId(), $galley->getBestGalleyId())) . '"/>');
			} elseif ($galley->getFileType()=='text/html') {
				$templateMgr->addHeader('googleScholarHtmlUrl' . $i++, '<meta name="citation_fulltext_html_url" content="' . $request->url(null, $submissionPath, 'view', array($submission->getBestId(), $galley->getBestGalleyId())) . '"/>');
			}
		}

		// Citations
		$outputReferences = array();
		$citationDao = DAORegistry::getDAO('CitationDAO'); /* @var $citationDao CitationDAO */
		$parsedCitations = $citationDao->getByPublicationId($submission->getCurrentPublication()->getId());
		if ($parsedCitations->getCount()){
			while ($citation = $parsedCitations->next()) {
				$outputReferences[] = $citation->getRawCitation();
			}
		}
		HookRegistry::call('GoogleScholarPlugin::references', array(&$outputReferences, $submission->getId()));

		if (!empty($outputReferences)){
			$i=0;
			foreach ($outputReferences as $outputReference) {
				$templateMgr->addHeader('googleScholarReference' . $i++, '<meta name="citation_reference" content="' . htmlspecialchars($outputReference) . '"/>');
			}
		}

		return false;
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.googleScholar.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.googleScholar.description');
	}
}


