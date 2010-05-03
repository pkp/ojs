<?php

/**
 * @file DOAJPlugin.inc.php
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DOAJPlugin
 * @ingroup plugins_importexport_doaj
 *
 * @brief DOAJ import/export plugin
 */

// $Id$


import('lib.pkp.classes.xml.XMLCustomWriter');

import('classes.plugins.ImportExportPlugin');

define('DOAJ_XSD_URL', 'http://www.doaj.org/schemas/doajArticles.xsd');

class DOAJPlugin extends ImportExportPlugin {
	/**
	 * Called as a plugin is registered to the registry
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True iff plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * Get the name of this plugin. The name must be unique within
	 * its category.
	 * @return String name of plugin
	 */
	function getName() {
		return 'DOAJPlugin';
	}

	/**
	 * Get the display name for this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.importexport.doaj.displayName');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return Locale::translate('plugins.importexport.doaj.description');
	}

	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . DIRECTORY_SEPARATOR . 'emailTemplates.xml');
	}

	function getInstallEmailTemplateDataFile() {
		return ($this->getPluginPath() . '/locale/{$installedLocale}/emailTemplates.xml');
	}
	/**
	 * Display the plugin
	 * @param $args array
	 */
	function display(&$args) {
		$templateMgr =& TemplateManager::getManager();
		parent::display($args);
		$journal =& Request::getJournal();
		
		switch (array_shift($args)) {
			case 'export':
				// export an xml file with the journal's information
				$this->exportJournal($journal);
				break;
			case 'email':
				// present a form autofilled with journal information to send to the DOAJ representative
				$this->emailRep($journal, Request::getUserVar('send'));
				break;
			default:
				$this->setBreadcrumbs();
				$templateMgr->display($this->getTemplatePath() . 'index.tpl');
		}
	}

	/**
	 * Export a journal's content
	 * @param $journal object
	 * @param $outputFile string
	 */
	function exportJournal(&$journal, $outputFile = null) {
		$this->import('DOAJExportDom');
		$doc =& XMLCustomWriter::createDocument();
		
		$journalNode =& DOAJExportDom::generateJournalDom($doc, $journal);
		$journalNode->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$journalNode->setAttribute('xsi:noNamespaceSchemaLocation', DOAJ_XSD_URL);
		XMLCustomWriter::appendChild($doc, $journalNode);

		if (!empty($outputFile)) {
			if (($h = fopen($outputFile, 'wb'))===false) return false;
			fwrite($h, XMLCustomWriter::getXML($doc));
			fclose($h);
		} else {
			header("Content-Type: application/xml");
			header("Cache-Control: private");
			header("Content-Disposition: attachment; filename=\"journal-" . $journal->getId() . ".xml\"");
			XMLCustomWriter::printXML($doc);
		}
		return true;
	}

	/**
	 * Send the request email to DOAJ.
	 * @param $journal object
	 */
	function emailRep(&$journal, $send = false) {
		$user =& Request::getUser();

		$issn = $journal->getSetting('printIssn');

		import('classes.mail.MailTemplate');
		$mail = new MailTemplate('DOAJ_EMAIL_REP');

		if ($send && !$mail->hasErrors()) {
			$mail->send();
			Request::redirect(null, 'manager', 'importexport');
		} else {
			$paramArray = array(
				'username' => $user->getFirstName() . ' ' . $user->getLastName(),
				'journalName' => $journal->getLocalizedTitle(),
				'isOpenAccess' => $journal->getSetting('publishingMode') == PUBLISHING_MODE_OPEN ? 'Yes' : 'No',
				'altTitle' => $journal->getLocalizedSetting('abbreviation'),
				'journalURL' => $journal->getUrl(),
				'hasAuthorFee' => $journal->getSetting('submissionFee') > 0 ? 'Yes' : 'No',
				'infoURL' => $journal->getUrl(),
				'isPeerReviewed' => 'Yes',
				'isOriginalResearch' => '',
				'isAcademic' => '',
				'isActive' => $this->compareToCurDate($journal->getId()) ? 'Yes' : 'No',
				'hasPrintedForm' => $issn != '' ? 'Yes' : 'No',
				'hasEmbargo' => '',
				'accessFrom' => $journal->getSetting('initialYear'),
				'firstVolume' => $journal->getSetting('initialVolume'),
				'firstIssue' => $journal->getSetting('initialNumber'),
				'issn' => $issn,
				'eissn' => $journal->getSetting('onlineIssn'),
				'publisherName' => $journal->getSetting('publisherInstitution'),
				'country' => $user->getCountry(),
				'languages' => Locale::getLocale(),
				'keywords' => $journal->getLocalizedSetting('searchKeywords'),
				'contactName' => $journal->getSetting('contactName'),
				'contactEmail' => $journal->getSetting('contactEmail'),
				'frequency' => ($journal->getSetting('volumePerYear'))*($journal->getSetting('issuePerVolume')),
				'articlesPerIssue' => $this->getArticlesPerIssue($journal->getId())
			);
			$mail->assignParams($paramArray);
			$mail->addRecipient('Sonja.Brage@lub.lu.se', 'Sonja Brage');
			$mail->displayEditForm(Request::url(null, 'manager', 'importexport', array('plugin', $this->getName(), 'email')));
		}
	}

	/**
	 * Get the number of articles per issue.
	 * @param $journalId int
	 * @return int
	 */
	function getArticlesPerIssue ($journalId) {
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		$journalStatsDao =& DAORegistry::getDAO('JournalStatisticsDAO');	
		
		$articleCount = $publishedArticleDao->getPublishedArticleCountByJournalId($journalId);
		$numIssues = $journalStatsDao->getIssueStatistics($journalId);
		
		if ($numIssues['numPublishedIssues'] > 0) 
			return round($articleCount / $numIssues['numPublishedIssues'], 2);
		else return 0;		
	}

	/**
	 * See if the most recent issue was more than a year old. If so,
	 * return false. Else return true. (No published issues returns false.)
	 * @param $journalId int
	 * @return boolean
	 */
	function compareToCurDate ($journalId) {
		// Get date from latest issue of the journal
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$lastIssue = $issueDao->getLastCreatedIssue($journalId);

		if (!$lastIssue) return false;

		$issueDate = strtotime($lastIssue->getDatePublished());

		return ($issueDate > time() - (60 * 60 * 24 * 365));
	}
}

?>
