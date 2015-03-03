<?php

/**
 * @file plugins/generic/pln/classes/DepositPackage.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DepositPackage
 * @ingroup plugins_generic_pln
 *
 * @brief Handle PLN requests
 */

import('classes.file.JournalFileManager');

require_once(dirname(__FILE__).'/../lib/bagit.php');

class DepositPackage {

	/**
	 * @var $deposit Deposit
	 */
	var $_deposit;

	/**
	 * Constructor
	 * @param $deposit Deposit
	 * @return DepositPackage
	 */
	function DepositPackage($deposit) {
		$this->_deposit = $deposit;
	}

	/**
	 * Get the directory used to store deposit data.
	 * @return string
	 */
	function getDepositDir() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$fileManager =& new JournalFileManager($journalDao->getById($this->_deposit->getJournalId()));
		return $fileManager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER . DIRECTORY_SEPARATOR . $this->_deposit->getUUID();
	}

	/**
	 * Get the filename used to store the deposit's atom document.
	 * @return string
	 */
	function getAtomDocumentPath() {
		return $this->getDepositDir() . DIRECTORY_SEPARATOR . $this->_deposit->getUUID() . ".xml";
	}

	/**
	 * Get the filename used to store the deposit's bag.
	 * @return string
	 */
	 function getPackageFilePath() {
		return $this->getDepositDir() . DIRECTORY_SEPARATOR . $this->_deposit->getUUID() . ".zip";
	}

	/**
	 * Create an atom document for this deposit.
	 * @return string
	 */
	function generateAtomDocument() {
		
		$plnPlugin =& PluginRegistry::getPlugin('generic',PLN_PLUGIN_NAME);
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getById($this->_deposit->getJournalId());
		$fileManager = new JournalFileManager($journal);
		
		// set up folder and file locations
		$atomFile = $this->getAtomDocumentPath();
		$packageFile = $this->getPackageFilePath();
		
		// make sure our bag is present
		if (!$fileManager->fileExists($packageFile)) return false;
		
		$atom  = new DOMDocument('1.0', 'utf-8');
		$entry = $atom->createElementNS('http://www.w3.org/2005/Atom', 'entry');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:dcterms', 'http://purl.org/dc/terms/');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:pkp', 'http://pkp.sfu.ca/SWORD');
		
		$email = $atom->createElement('email', $journal->getSetting('contactEmail'));
		$entry->appendChild($email);
		
		$title = $atom->createElement('title', $journal->getLocalizedTitle());
		$entry->appendChild($title);
		
		$pkpJournalUrl = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'pkp:journal_url', $journal->getUrl());
		$entry->appendChild($pkpJournalUrl);

		$pkpPublisher = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'pkp:publisherName', $journal->getSetting('publisherInstitution'));
		$entry->appendChild($pkpPublisher);

		$pkpPublisherUrl = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'pkp:publisherUrl', $journal->getSetting('publisherUrl'));
		$entry->appendChild($pkpPublisherUrl);

		$issn = '';
		
		if ($journal->getSetting('onlineIssn')) {
			$issn = $journal->getSetting('onlineIssn');
		} else if ($journal->getSetting('printIssn')) {
			$issn = $journal->getSetting('printIssn');
		}
		
		$pkpIssn = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'pkp:issn', $issn);
		$entry->appendChild($pkpIssn);
		
		$id = $atom->createElement('id', 'urn:uuid:'.$this->_deposit->getUUID());
		$entry->appendChild($id);
		
		$updated = $atom->createElement('updated', strftime("%FT%TZ",strtotime($this->_deposit->getDateModified())));
		$entry->appendChild($updated);
		
		$url = $journal->getUrl() . '/' . PLN_PLUGIN_ARCHIVE_FOLDER . '/deposits/' . $this->_deposit->getUUID();
		$pkpDetails = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'pkp:content', $url);
		$pkpDetails->setAttribute('size', ceil(filesize($packageFile)/1000));
		
		$objectVolume = "";
		$objectIssue = "";
		$objectPublicationDate = 0;
		
		switch ($this->_deposit->getObjectType()) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
				$depositObjects = $this->_deposit->getDepositObjects();
				while ($depositObject =& $depositObjects->next()) {
					$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
					$article = $publishedArticleDao->getPublishedArticleByArticleId($depositObject->getObjectId());
					if ($article->getDatePublished() > $objectPublicationDate)
						$objectPublicationDate = $article->getDatePublished();
					unset($depositObject);
				}
				break;
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				$depositObjects = $this->_deposit->getDepositObjects();
				while ($depositObject =& $depositObjects->next()) {
					$issueDao =& DAORegistry::getDAO('IssueDAO');
					$issue = $issueDao->getIssueById($depositObject->getObjectId());
					$objectVolume = $issue->getVolume();
					$objectIssue = $issue->getNumber();
					if ($issue->getDatePublished() > $objectPublicationDate)
						$objectPublicationDate = $issue->getDatePublished();
					unset($depositObject);
				}
				break;
		}
		
		$pkpDetails->setAttribute('volume', $objectVolume);
		$pkpDetails->setAttribute('issue', $objectIssue);
		$pkpDetails->setAttribute('pubdate', strftime("%F",strtotime($objectPublicationDate)));
		
		switch ($plnPlugin->getSetting($journal->getId(), 'checksum_type')) {
			case 'SHA-1':
				$pkpDetails->setAttribute('checksumType', 'SHA-1');
				$pkpDetails->setAttribute('checksumValue', sha1_file($packageFile));
				break;
			case 'MD5':
				$pkpDetails->setAttribute('checksumType', 'MD5');
				$pkpDetails->setAttribute('checksumValue', md5_file($packageFile));
				break;
		}

		$entry->appendChild($pkpDetails);
		$atom->appendChild($entry);
		$atom->save($atomFile);
		
		return $atomFile;
		
	}
	
	/**
	 * Create a package containing the serialized deposit objects 
	 * @return string The full path of the created zip archive
	 */
	function generatePackage() {
		
		// get DAOs, plugins and settings
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$issueDao =& DAORegistry::getDAO('IssueDAO');
		$sectionDao =& DAORegistry::getDAO('SectionDAO');
		$publishedArticleDao =& DAORegistry::getDAO('PublishedArticleDAO');
		PluginRegistry::loadCategory('importexport');
		$exportPlugin =& PluginRegistry::getPlugin('importexport','NativeImportExportPlugin');
		$plnPlugin =& PluginRegistry::getPlugin('generic',PLN_PLUGIN_NAME);
		$fileManager = new JournalFileManager($journalDao->getById($this->_deposit->getJournalId()));
		
		$journal =& $journalDao->getById($this->_deposit->getJournalId());
		$depositObjects = $this->_deposit->getDepositObjects();
		
		// set up folder and file locations
		$bagDir = $this->getDepositDir() . DIRECTORY_SEPARATOR . 'bag';
		$packageFile = $this->getPackageFilePath();
		$exportFile =  tempnam(sys_get_temp_dir(), 'ojs-pln-export-');
		$termsFile =  tempnam(sys_get_temp_dir(), 'ojs-pln-terms-');
		
		$bag = new BagIt($bagDir);
		
		switch ($this->_deposit->getObjectType()) {
			case PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE:
				$articles = array();
				
				// we need to add all of the relevant articles to an array to export as a batch
				while ($depositObject =& $depositObjects->next()) {
					$article =& $publishedArticleDao->getPublishedArticleByArticleId($this->_deposit->getObjectId(), $journal->getId());
					$issue =& $issueDao->getIssueById($article->getIssueId(), $journal->getId());
					$section =& $sectionDao->getSection($article->getSectionId());
					
					// add the article to the array we'll pass for export
					$articles[] = array(
						'publishedArticle' => $article,
						'section' => $section,
						'issue' => $issue,
						'journal' => $journal
					);
					unset($depositObject);
				}
				
				// export all of the articles together
				if ($exportPlugin->exportArticles($articles, $exportFile) !== true) return false;
				break;
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
			
				// we only ever do one issue at a time, so get that issue
				$depositObject =& $depositObjects->next();
				$issue =& $issueDao->getIssueByBestIssueId($depositObject->getObjectId(),$journal->getId());
				
				// export the issue
				if ($exportPlugin->exportIssue($journal, $issue, $exportFile) !== true) return false;
				break;
			default:
		}
		
		// add the current terms to the bag
		$termsXml = new DOMDocument('1.0', 'utf-8');
		$entry = $termsXml->createElementNS('http://www.w3.org/2005/Atom', 'entry');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:dcterms', 'http://purl.org/dc/terms/');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:pkp', PLN_PLUGIN_NAME);

		$terms = unserialize($plnPlugin->getSetting($this->_deposit->getJournalId(), 'terms_of_use'));
		$agreement = unserialize($plnPlugin->getSetting($this->_deposit->getJournalId(), 'terms_of_use_agreement'));
		
		$pkpTermsOfUse = $termsXml->createElementNS(PLN_PLUGIN_NAME, 'pkp:terms_of_use');
		foreach ($terms as $termName => $termData) {
			$element = $termsXml->createElementNS(PLN_PLUGIN_NAME, $termName, $termData['term']);
			$element->setAttribute('updated',$termData['updated']);
			$element->setAttribute('agreed', $agreement[$termName]);
			$pkpTermsOfUse->appendChild($element);
		}

		$entry->appendChild($pkpTermsOfUse);
		$termsXml->appendChild($entry);
		$termsXml->save($termsFile);

		// add the exported content to the bag
		$bag->addFile($exportFile, $this->_deposit->getObjectType() . $this->_deposit->getUUID() . '.xml');
		// add the exported content to the bag
		$bag->addFile($termsFile, 'terms' . $this->_deposit->getUUID() . '.xml');
		$bag->update();
		
		// create the bag
		$bag->package($packageFile,'zip');
		
		// remove the temporary bag directory
		$fileManager->rmtree($bagDir);
		
		return $packageFile;
	}

	/**
	 * Transfer the atom document to the PLN.
	 */
	function transferDeposit() {
			
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$plnPlugin =& PluginRegistry::getPlugin('generic',PLN_PLUGIN_NAME);
		$fileManager = new JournalFileManager($journalDao->getById($this->_deposit->getJournalId()));
		$plnDir = $fileManager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER;
		
		// post the atom document
		$url = PLN_PLUGIN_NETWORK;
		if ($this->_deposit->getUpdateStatus()) {
			$url .= PLN_PLUGIN_CONT_IRI . '/' . $plnPlugin->getSetting($this->_deposit->getJournalID(), 'journal_uuid');
			$url .= '/' . $this->_deposit->getUUID() . '/edit';
			$result = $plnPlugin->_curlPutFile(
				$url,
				$this->getAtomDocumentPath()
			);
		} else {
			$url .= PLN_PLUGIN_COL_IRI . '/' . $plnPlugin->getSetting($this->_deposit->getJournalID(), 'journal_uuid');
			$result = $plnPlugin->_curlPostFile(
				$url,
				$this->getAtomDocumentPath()
			);
		}
						
		// if we get the OK, set the status as transferred
		if (($result['status'] == PLN_PLUGIN_HTTP_STATUS_OK) || ($result['status'] == PLN_PLUGIN_HTTP_STATUS_CREATED)) {
			$this->_deposit->setTransferredStatus();
			// unset a remote error if this worked
			$this->_deposit->setRemoteFailureStatus(false);
			// if this was an update, unset the update flag
			$this->_deposit->setUpdateStatus(false);
			$this->_deposit->setLastStatusDate(time());
			$depositDao->updateDeposit($this->_deposit);
		} else {
			// we got an error back from the staging server
			$this->_deposit->setRemoteFailureStatus();
			$this->_deposit->setLastStatusDate(time());
			$depositDao->updateDeposit($this->_deposit);
		}
		
	}

	/**
	 * Package a deposit for transfer to and retrieval by the PLN.
	 */
	function packageDeposit() {
				
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$fileManager = new JournalFileManager($journalDao->getById($this->_deposit->getJournalId()));
		$plnDir = $fileManager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER;
		
		// make sure the pln work directory exists
		if ($fileManager->fileExists($plnDir,'dir') !== true) { $fileManager->mkdir($plnDir); }
		
		// make a location for our work and clear it out if it's there
		$depositDir = $plnDir . DIRECTORY_SEPARATOR . $this->_deposit->getUUID();
		if ($fileManager->fileExists($depositDir,'dir')) $fileManager->rmtree($depositDir);
		$fileManager->mkdir($depositDir);

		if (!$fileManager->fileExists($this->generatePackage())) {
			$this->_deposit->setLocalFailureStatus();
			$depositDao->updateDeposit($this->_deposit);
			return;
		}
		
		if (!$fileManager->fileExists($this->generateAtomDocument())) {
			$this->_deposit->setLocalFailureStatus();
			$depositDao->updateDeposit($this->_deposit);
			return;
		}
		
		// update the deposit's status
		$this->_deposit->setPackagedStatus();
		$depositDao->updateDeposit($this->_deposit);
		
	}

	/**
	 * Update the deposit's status by checking with the PLN.
	 */
	function updateDepositStatus() {
			
		$depositDao =& DAORegistry::getDAO('DepositDAO');
		$plnPlugin =& PluginRegistry::getPlugin('generic','plnplugin');
		
		$url = PLN_PLUGIN_NETWORK . PLN_PLUGIN_CONT_IRI;
		$url .= '/' . $plnPlugin->getSetting($this->_deposit->getJournalID(), 'journal_uuid');
		$url .= '/' . $this->_deposit->getUUID() . '/state';
		
		// retrieve the content document
		$result = $plnPlugin->_curlGet($url);
		
		// stop here if we didn't get an OK
		if ($result['status'] != PLN_PLUGIN_HTTP_STATUS_OK) {
			$this->_deposit->setRemoteFailureStatus();
			$depositDao->updateDeposit($this->_deposit);
		}

		$contentState = new DOMDocument();
		$contentState->preserveWhiteSpace = false;
		$contentState->loadXML($result['result']);
		
		// get the remote deposit state
		$element = $contentState->getElementsByTagName('category')->item(0);
		$state = $element->getAttribute('term');
		
		switch ($state) {
			case 'agreement':
				$this->_deposit->setSyncedStatus();
			case 'disagreement':
				$this->_deposit->setSyncingStatus();
			case 'in_progress':
				$this->_deposit->setReceivedStatus();
				break;
			case 'failed':
				$this->_deposit->setRemoteFailureStatus();
				break;
		}
		
		$depositDao->updateDeposit($this->_deposit);
		
	}
}
?>
