<?php

/**
 * @file classes/DepositPackage.inc.php
 *
 * Copyright (c) 2013-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class DepositPackage
 * @brief Represent a PLN deposit package.
 */

import('lib.pkp.classes.file.ContextFileManager');
import('lib.pkp.classes.scheduledTask.ScheduledTask');

class DepositPackage {

	/** @var Deposit */
	var $_deposit;

	/**
	 * If the DepositPackage object was created as part of a scheduled task
	 * run, then save the task so error messages can be logged there.
	 * @var ScheduledTask;
	 */
	var $_task;

	/**
	 * Constructor.
	 * @param $deposit Deposit
	 * @param $task ScheduledTask
	 */
	public function __construct($deposit, $task = null) {
		$this->_deposit = $deposit;
		$this->_task = $task;
	}

	/**
	 * Send a message to a log. If the deposit package is aware of a
	 * a scheduled task, the message will be sent to the task's
	 * log. Otherwise it will be sent to error_log().
	 *
	 * @param $message string Locale-specific message to be logged
	 */
	protected function _logMessage($message) {
		if($this->_task) {
			$this->_task->addExecutionLogEntry($message, SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
		} else {
			error_log($message);
		}
	}

	/**
	 * Get the directory used to store deposit data.
	 * @return string
	 */
	public function getDepositDir() {
		$fileManager = new ContextFileManager($this->_deposit->getJournalId());
		return $fileManager->getBasePath() . PLN_PLUGIN_ARCHIVE_FOLDER . DIRECTORY_SEPARATOR . $this->_deposit->getUUID();
	}

	/**
	 * Get the filename used to store the deposit's atom document.
	 * @return string
	 */
	public function getAtomDocumentPath() {
		return $this->getDepositDir() . DIRECTORY_SEPARATOR . $this->_deposit->getUUID() . '.xml';
	}

	/**
	 * Get the filename used to store the deposit's bag.
	 * @return string
	 */
	public function getPackageFilePath() {
		return $this->getDepositDir() . DIRECTORY_SEPARATOR . $this->_deposit->getUUID() . '.zip';
	}

	/**
	 * Create a DOMElement in the $dom, and set the element name, namespace, and
	 * content. Any invalid UTF-8 characters will be dropped. The
	 * content will be placed inside a CDATA section.
	 *
	 * @param DOMDocument $dom
	 * @param string $elementName
	 * @param string $content
	 * @param string $namespace
	 * @return DOMElement
	 */
	protected function _generateElement($dom, $elementName, $content, $namespace = null) {
		// remove any invalid UTF-8.
		$original = mb_substitute_character();
		mb_substitute_character(0xFFFD);
		$filtered = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
		mb_substitute_character($original);

		// put the filtered content in a CDATA, as it may contain markup that
		// isn't valid XML.
		$node = $dom->createCDATASection($filtered);
		$element = $dom->createElementNS($namespace, $elementName);
		$element->appendChild($node);
		return $element;
	}

	/**
	 * Create an atom document for this deposit.
	 * @return string
	 */
	public function generateAtomDocument() {
		$plnPlugin = PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$journal = $journalDao->getById($this->_deposit->getJournalId());
		$fileManager = new ContextFileManager($this->_deposit->getJournalId());

		// set up folder and file locations
		$atomFile = $this->getAtomDocumentPath();
		$packageFile = $this->getPackageFilePath();

		// make sure our bag is present
		if (!$fileManager->fileExists($packageFile)) {
			$this->_logMessage(__('plugins.generic.pln.error.depositor.missingpackage', array('file' => $packageFile)));
			return false;
		}

		$atom = new DOMDocument('1.0', 'utf-8');
		$entry = $atom->createElementNS('http://www.w3.org/2005/Atom', 'entry');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:dcterms', 'http://purl.org/dc/terms/');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:pkp', 'http://pkp.sfu.ca/SWORD');

		$entry->appendChild($this->_generateElement($atom, 'email', $journal->getData('contactEmail')));
		$entry->appendChild($this->_generateElement($atom, 'title', $journal->getLocalizedName()));

		$request = PKPApplication::getRequest();
		$application = PKPApplication::getApplication();
		$dispatcher = $application->getDispatcher();

		$entry->appendChild($this->_generateElement($atom, 'pkp:journal_url', $dispatcher->url($request, ROUTE_PAGE, $journal->getPath()), 'http://pkp.sfu.ca/SWORD'));

		$entry->appendChild($this->_generateElement($atom, 'pkp:publisherName', $journal->getData('publisherInstitution'), 'http://pkp.sfu.ca/SWORD'));

		$entry->appendChild($this->_generateElement($atom, 'pkp:publisherUrl', $journal->getData('publisherUrl'), 'http://pkp.sfu.ca/SWORD'));

		$issn = '';
		if ($journal->getData('onlineIssn')) {
			$issn = $journal->getData('onlineIssn');
		} else if ($journal->getData('printIssn')) {
			$issn = $journal->getData('printIssn');
		}

		$entry->appendChild($this->_generateElement($atom, 'pkp:issn', $issn, 'http://pkp.sfu.ca/SWORD'));

		$entry->appendChild($this->_generateElement($atom, 'id', 'urn:uuid:'.$this->_deposit->getUUID()));

		$entry->appendChild($this->_generateElement($atom, 'updated', strftime("%Y-%m-%d %H:%M:%S", strtotime($this->_deposit->getDateModified()))));

		$url = $dispatcher->url($request, ROUTE_PAGE, $journal->getPath()) . '/' . PLN_PLUGIN_ARCHIVE_FOLDER . '/deposits/' . $this->_deposit->getUUID();
		$pkpDetails = $this->_generateElement($atom, 'pkp:content', $url, 'http://pkp.sfu.ca/SWORD');
		$pkpDetails->setAttribute('size', ceil(filesize($packageFile)/1000));

		$objectVolume = '';
		$objectIssue = '';
		$objectPublicationDate = 0;

		switch ($this->_deposit->getObjectType()) {
			case 'PublishedArticle': // Legacy (OJS pre-3.2)
			case PLN_PLUGIN_DEPOSIT_OBJECT_SUBMISSION:
				$depositObjects = $this->_deposit->getDepositObjects();
				$submissionDao = DAORegistry::getDAO('SubmissionDAO');
				while ($depositObject = $depositObjects->next()) {
					$submission = $submissionDao->getById($depositObject->getObjectId());
					$publication = $submission->getCurrentPublication();
					$publicationDate = $publication?$publication->getData('publicationDate'):null;
					if ($publicationDate && strtotime($publicationDate) > $objectPublicationDate)
						$objectPublicationDate = strtotime($publicationDate);
				}
				break;
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				$depositObjects = $this->_deposit->getDepositObjects();
				while ($depositObject = $depositObjects->next()) {
					$issueDao = DAORegistry::getDAO('IssueDAO');
					$issue = $issueDao->getById($depositObject->getObjectId());
					$objectVolume = $issue->getVolume();
					$objectIssue = $issue->getNumber();
					if ($issue->getDatePublished() > $objectPublicationDate)
						$objectPublicationDate = $issue->getDatePublished();
				}
				break;
		}

		$pkpDetails->setAttribute('volume', $objectVolume);
		$pkpDetails->setAttribute('issue', $objectIssue);
		$pkpDetails->setAttribute('pubdate', strftime('%Y-%m-%d', strtotime($objectPublicationDate)));

		// Add OJS Version
		$versionDao = DAORegistry::getDAO('VersionDAO');
		$currentVersion = $versionDao->getCurrentVersion();
		$pkpDetails->setAttribute('ojsVersion', $currentVersion->getVersionString());

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

		$locale = $journal->getPrimaryLocale();
		$license = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'license');
		$license->appendChild($this->_generateElement($atom, 'openAccessPolicy', $journal->getLocalizedSetting('openAccessPolicy', $locale), 'http://pkp.sfu.ca/SWORD'));
		$license->appendChild($this->_generateElement($atom, 'licenseURL', $journal->getLocalizedSetting('licenseURL', $locale), 'http://pkp.sfu.ca/SWORD'));

		$mode = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'publishingMode');
		switch($journal->getData('publishingMode')) {
			case PUBLISHING_MODE_OPEN:
				$mode->nodeValue = 'Open';
				break;
			case PUBLISHING_MODE_SUBSCRIPTION:
				$mode->nodeValue = 'Subscription';
				break;
			case PUBLISHING_MODE_NONE:
				$mode->nodeValue = 'None';
				break;
		}
		$license->appendChild($mode);
		$license->appendChild($this->_generateElement($atom, 'copyrightNotice', $journal->getLocalizedSetting('copyrightNotice', $locale), 'http://pkp.sfu.ca/SWORD'));
		$license->appendChild($this->_generateElement($atom, 'copyrightBasis', $journal->getLocalizedSetting('copyrightBasis'), 'http://pkp.sfu.ca/SWORD'));
		$license->appendChild($this->_generateElement($atom, 'copyrightHolder', $journal->getLocalizedSetting('copyrightHolder'), 'http://pkp.sfu.ca/SWORD'));

		$entry->appendChild($license);
		$atom->save($atomFile);

		return $atomFile;
	}

	/**
	 * Create a package containing the serialized deposit objects. If the
	 * bagit library fails to load, null will be returned.
	 *
	 * @return string The full path of the created zip archive
	 */
	public function generatePackage() {
		require_once(dirname(__FILE__) . '/../vendor/autoload.php');

		// get DAOs, plugins and settings
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$issueDao = DAORegistry::getDAO('IssueDAO');
		$sectionDao = DAORegistry::getDAO('SectionDAO');
		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		PluginRegistry::loadCategory('importexport');
		$exportPlugin = PluginRegistry::getPlugin('importexport', 'NativeImportExportPlugin');
		$supportsOptions = in_array('parseOpts', get_class_methods($exportPlugin));
		@ini_set('memory_limit', -1);
		$plnPlugin = PluginRegistry::getPlugin('generic', PLN_PLUGIN_NAME);

		$journal = $journalDao->getById($this->_deposit->getJournalId());
		$depositObjects = $this->_deposit->getDepositObjects();

		// set up folder and file locations
		$bagDir = $this->getDepositDir() . DIRECTORY_SEPARATOR . $this->_deposit->getUUID();
		$packageFile = $this->getPackageFilePath();
		$exportFile = tempnam(sys_get_temp_dir(), 'ojs-pln-export-');
		$termsFile = tempnam(sys_get_temp_dir(), 'ojs-pln-terms-');

		$bag = \whikloj\BagItTools\Bag::create($bagDir);

		$fileList = array();
		import('lib.pkp.classes.file.FileManager');
		$fileManager = new FileManager();

		switch ($this->_deposit->getObjectType()) {
			case 'PublishedArticle': // Legacy (OJS pre-3.2)
			case PLN_PLUGIN_DEPOSIT_OBJECT_SUBMISSION:
				$submissionIds = array();

				// we need to add all of the relevant submissions to an array to export as a batch
				while ($depositObject = $depositObjects->next()) {
					$submission = $submissionDao->getById($this->_deposit->getObjectId());
					$currentPublication = $submission->getCurrentPublication();
					if ($submission->getContextId() != $journal->getId()) continue;
					if (!$currentPublication || $currentPublication->getStatus() != STATUS_PUBLISHED) continue;

					$submissionIds[] = $submission->getId();
				}

				// export all of the submissions together
				$exportXml = $exportPlugin->exportSubmissions($submissionIds, $journal, null, ['no-embed' => 1]);
				if (!$exportXml) {
					$this->_logMessage(__('plugins.generic.pln.error.depositor.export.articles.error'));
					return false;
				}
				if ($supportsOptions) $exportXml = $this->_cleanFileList($exportXml, $fileList);
				$fileManager->writeFile($exportFile, $exportXml);
				break;
			case PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE:
				// we only ever do one issue at a time, so get that issue
				$application = Application::getApplication();
				$request = $application->getRequest();
				$depositObject = $depositObjects->next();
				$issue = $issueDao->getByBestId($depositObject->getObjectId(), $journal->getId());

				$callback = new DepositUnregisterableErrorCallback($depositObject->getDepositId(), $this);

				try {
					$exportXml = $exportPlugin->exportIssues(
						(array) $issue->getId(),
						$journal,
						$user = $request->getUser(),
						['no-embed' => 1]
					);

					if (!$exportXml) {
						$this->_logMessage(__('plugins.generic.pln.error.depositor.export.issue.error'));
						$this->importExportErrorHandler($depositObject->getDepositId(), __('plugins.generic.pln.error.depositor.export.issue.error'));
					}
				}
				catch (Exception $exception) {
					$this->_logMessage(__('plugins.generic.pln.error.depositor.export.issue.exception') . $exception->getMessage());
					$this->importExportErrorHandler($depositObject->getDepositId(), $exception->getMessage());
				}
				
				$callback->unregister();
				if ($supportsOptions) $exportXml = $this->_cleanFileList($exportXml, $fileList);
				$fileManager->writeFile($exportFile, $exportXml);
				break;
			default: throw new Exception('Unknown deposit type!');
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
		foreach ($fileList as $sourcePath => $targetPath) {
			// $sourcePath is a relative path to the files directory; add the files directory to the front
			$sourcePath = rtrim(Config::getVar('files', 'files_dir'), '/') . '/' . $sourcePath;
			$bag->addFile($sourcePath, $targetPath);
		}

		// Add the schema files to the bag (adjusting the XSD references to flatten them)
		$bag->createFile(
			preg_replace(
				'/schemaLocation="[^"]+pkp-native.xsd"/',
				'schemaLocation="pkp-native.xsd"',
				file_get_contents('plugins/importexport/native/native.xsd')
			),
			'native.xsd'
		);
		$bag->createFile(
			preg_replace(
				'/schemaLocation="[^"]+importexport.xsd"/',
				'schemaLocation="importexport.xsd"',
				file_get_contents('lib/pkp/plugins/importexport/native/pkp-native.xsd')
			),
			'pkp-native.xsd'
		);
		$bag->createFile(file_get_contents('lib/pkp/xml/importexport.xsd'), 'importexport.xsd');

		// add the exported content to the bag
		$bag->addFile($termsFile, 'terms' . $this->_deposit->getUUID() . '.xml');

		// Add OJS Version
		$versionDao = DAORegistry::getDAO('VersionDAO');
		$currentVersion = $versionDao->getCurrentVersion();
		$bag->setExtended(true);
		$bag->addBagInfoTag('PKP-PLN-OJS-Version', $currentVersion->getVersionString());

		$bag->update();

		// create the bag
		$bag->package($packageFile);

		// remove the temporary bag directory and temp files
		$fileManager->rmtree($bagDir);
		$fileManager->deleteByPath($exportFile);
		$fileManager->deleteByPath($termsFile);
		return $packageFile;
	}

	/**
	 * Read a list of file paths from the specified native XML string and clean up the XML's pathnames.
	 * @param $xml string
	 * @param $fileList array Reference to array to receive file list
	 * @return array
	 */
	function _cleanFileList($xml, &$fileList) {
		$doc = new DOMDocument();
		$doc->loadXML($xml);
		$xpath = new DOMXPath($doc);
		$xpath->registerNameSpace('pkp', 'http://pkp.sfu.ca');
		foreach($xpath->query('//pkp:submission_file//pkp:href') as $hrefNode ) {
			$filePath = $hrefNode->getAttribute('src');
			$targetPath = 'files/' . basename($filePath);
			$fileList[$filePath] = $targetPath;
			$hrefNode->setAttribute('src', $targetPath);
		}
		return $doc->saveXML();
	}

	/**
	 * Transfer the atom document to the PLN.
	 */
	public function transferDeposit() {
		$journalId = $this->_deposit->getJournalId();
		$depositDao = DAORegistry::getDAO('DepositDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$plnPlugin = PluginRegistry::getPlugin('generic',PLN_PLUGIN_NAME);
		$fileManager = new ContextFileManager($journalId);
		$plnDir = $fileManager->getBasePath() . PLN_PLUGIN_ARCHIVE_FOLDER;

		// post the atom document
		$url = $plnPlugin->getSetting($journalId, 'pln_network');
		$atomPath = $this->getAtomDocumentPath();

		if ($this->_deposit->getLockssAgreementStatus()) {
			$url .= PLN_PLUGIN_CONT_IRI . '/' . $plnPlugin->getSetting($journalId, 'journal_uuid');
			$url .= '/' . $this->_deposit->getUUID() . '/edit';
			
			$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.transferringdeposits.processing.postAtom', 
				array('depositId' => $this->_deposit->getId(), 
					'statusLocal' => $this->_deposit->getLocalStatus(), 
					'statusProcessing' => $this->_deposit->getProcessingStatus(), 
					'statusLockss' => $this->_deposit->getLockssStatus(),
					'url' => $url,
					'atomPath' => $atomPath,
					'method' => 'PutFile')), 
				SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

			$result = $plnPlugin->curlPutFile(
				$url,
				$atomPath
			);
		} else {
			$url .= PLN_PLUGIN_COL_IRI . '/' . $plnPlugin->getSetting($journalId, 'journal_uuid');

			$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.transferringdeposits.processing.postAtom', 
				array('depositId' => $this->_deposit->getId(), 
					'statusLocal' => $this->_deposit->getLocalStatus(), 
					'statusProcessing' => $this->_deposit->getProcessingStatus(), 
					'statusLockss' => $this->_deposit->getLockssStatus(),
					'url' => $url,
					'atomPath' => $atomPath,
					'method' => 'PostFile')), 
				SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

			$result = $plnPlugin->curlPostFile(
				$url,
				$atomPath
			);
		}

		// if we get the OK, set the status as transferred
		if (($result['status'] == PLN_PLUGIN_HTTP_STATUS_OK) || ($result['status'] == PLN_PLUGIN_HTTP_STATUS_CREATED)) {
			$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.transferringdeposits.processing.resultSucceeded', 
				array('depositId' => $this->_deposit->getId())), 
				SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

			$this->_deposit->setTransferredStatus();
			// unset a remote error if this worked
			$this->_deposit->setLockssReceivedStatus(false);
			// if this was an update, unset the update flag
			$this->_deposit->setLockssAgreementStatus(false);
			$this->_deposit->setLastStatusDate(time());
			$depositDao->updateObject($this->_deposit);
		} else {
			// we got an error back from the staging server
			if($result['status'] == FALSE) {
				$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.transferringdeposits.processing.resultFailed', 
					array('depositId' => $this->_deposit->getId(), 
						'error' => $result['error'],
						'result' => $result['result'])), 
					SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

				$this->_logMessage(__('plugins.generic.pln.error.network.deposit', array('error' => $result['error'])));

				$this->_deposit->setExportDepositError(__('plugins.generic.pln.error.network.deposit', array('error' => $result['error'])));
			} else {
				$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.transferringdeposits.processing.resultFailed', 
					array('depositId' => $this->_deposit->getId(), 
						'error' => $result['status'],
						'result' => $result['result'])), 
					SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

				$this->_logMessage(__('plugins.generic.pln.error.http.deposit', array('error' => $result['status'])));

				$this->_deposit->setExportDepositError(__('plugins.generic.pln.error.http.deposit', array('error' => $result['status'])));
			}

			$this->_deposit->setLockssReceivedStatus();
			$this->_deposit->setLastStatusDate(time());
			$depositDao->updateObject($this->_deposit);
		}
	}

	/**
	 * Package a deposit for transfer to and retrieval by the PLN.
	 */
	public function packageDeposit() {
		$depositDao = DAORegistry::getDAO('DepositDAO');
		$journalDao = DAORegistry::getDAO('JournalDAO');
		$fileManager = new ContextFileManager($this->_deposit->getJournalId());
		$plnDir = $fileManager->getBasePath() . PLN_PLUGIN_ARCHIVE_FOLDER;

		// make sure the pln work directory exists
		if (!$fileManager->fileExists($plnDir, 'dir')) {
			$fileManager->mkdir($plnDir);
		}

		// make a location for our work and clear it out if it's there
		$depositDir = $plnDir . DIRECTORY_SEPARATOR . $this->_deposit->getUUID();
		if ($fileManager->fileExists($depositDir, 'dir')) {
			$fileManager->rmtree($depositDir);
		}

		$fileManager->mkdir($depositDir);

		$packagePath = $this->generatePackage();
		if (!$packagePath) {
			return;
		}

		if (!$fileManager->fileExists($packagePath)) {
			$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.packagingdeposits.processing.packageFailed', 
				array('depositId' => $this->_deposit->getId())), 
				SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

			$this->_deposit->setPackagedStatus(false);
			$this->_deposit->setLastStatusDate(time());
			$depositDao->updateObject($this->_deposit);
			return;
		}

		if (!$fileManager->fileExists($this->generateAtomDocument())) {
			$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.packagingdeposits.processing.packageFailed', 
				array('depositId' => $this->_deposit->getId())), 
				SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

			$this->_deposit->setPackagedStatus(false);
			$this->_deposit->setLastStatusDate(time());
			$depositDao->updateObject($this->_deposit);
			return;
		}

		$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.packagingdeposits.processing.packageSucceeded', 
				array('depositId' => $this->_deposit->getId())), 
				SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

		// update the deposit's status
		$this->_deposit->setPackagedStatus();
		$this->_deposit->setLastStatusDate(time());
		$depositDao->updateObject($this->_deposit);
	}

	/**
	 * Update the deposit's status by checking with the PLN.
	 */
	public function updateDepositStatus() {
		$journalId = $this->_deposit->getJournalId();
		$depositDao = DAORegistry::getDAO('DepositDAO');
		$plnPlugin = PluginRegistry::getPlugin('generic', 'plnplugin');

		$url = $plnPlugin->getSetting($journalId, 'pln_network') . PLN_PLUGIN_CONT_IRI;
		$url .= '/' . $plnPlugin->getSetting($journalId, 'journal_uuid');
		$url .= '/' . $this->_deposit->getUUID() . '/state';

		// retrieve the content document
		$result = $plnPlugin->curlGet($url);

		if ($result['status'] != PLN_PLUGIN_HTTP_STATUS_OK) {
			// stop here if we didn't get an OK
			if($result['status'] === FALSE) {
				error_log(__('plugins.generic.pln.error.network.swordstatement', array('error' => $result['error'])));
			} else {
				error_log(__('plugins.generic.pln.error.http.swordstatement', array('error' => $result['status'])));
			}

			return;
		}

		$contentDOM = new DOMDocument();
		$contentDOM->preserveWhiteSpace = false;
		$contentDOM->loadXML($result['result']);

		// get the remote deposit state
		$processingState = $contentDOM->getElementsByTagName('category')->item(0)->getAttribute('term');
		$this->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.statusupdates.processing.processingState', 
				array('depositId' => $this->_deposit->getId(),
					'processingState' => $processingState)), 
				SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
				
		switch ($processingState) {
			case 'depositedByJournal':
				$this->_deposit->setTransferredStatus(true);
				break;
			case 'harvested':
			case 'xml-validated':
			case 'payload-validated':
			case 'virus-checked':
				$this->_deposit->setReceivedStatus(true);
				break;
			case 'bag-validated':
			case 'reserialized':
			case 'hold':
				$this->_deposit->setValidatedStatus(true);
				break;
			case 'deposited':
				$this->_deposit->setSentStatus(true);
				break;
			default:
				$this->_deposit->setExportDepositError('Unknown processing state ' . $processingState);
				$this->_logMessage('Deposit ' . $this->_deposit->getId() . ' has unknown processing state ' . $processingState);
				break;
		}

		$lockssState = $contentDOM->getElementsByTagName('category')->item(1)->getAttribute('term');
		switch($lockssState) {
			case '':
				// do nothing.
				break;
			case 'received':
				$this->_deposit->setLockssReceivedStatus();
				break;
			case 'syncing':
				$this->_deposit->setLockssSyncingStatus();
				break;
			case 'agreement':
				if(!$this->_deposit->getLockssAgreementStatus()) {
					$journalDao = DAORegistry::getDAO('JournalDAO');
					$fileManager = new ContextFileManager($this->_deposit->getJournalId());
					$depositDir = $this->getDepositDir();
					$fileManager->rmtree($depositDir);
				}
				$this->_deposit->setLockssAgreementStatus(true);
				break;
			default:
				$this->_deposit->setExportDepositError('Unknown LOCKSS state ' . $lockssState);
				$this->_logMessage('Deposit ' . $this->_deposit->getId() . ' has unknown LOCKSS state ' . $lockssState);
				break;
		}

		$this->_deposit->setLastStatusDate(time());
		$depositDao->updateObject($this->_deposit);
	}

	/**
	 * Handle an error during the import/export process.
	 * @param $depositId int Deposit ID
	 * @param $message string Error message
	 */
	public function importExportErrorHandler($depositId, $message) {
		$this->_depositPackageErrored = true;

		$depositDao = DAORegistry::getDAO('DepositDAO'); /** @var $depositDao DepositDAO */
		$deposit = $depositDao->getById($depositId);
		if ($deposit) {
			$deposit->setExportDepositError($message);
			$deposit->setPackagingFailedStatus();
			$depositDao->updateObject($deposit);
		}
	}
}

/**
 * Callback class used to work around error handling quirks.
 * This is a hack slated for destruction when no longer needed!
 */
class DepositUnregisterableErrorCallback {
	private $_depositId;
	private $_depositPackage;
	private $_isUnregistered = false;

	public function __construct($depositId, $depositPackage) {
		$this->_depositId = $depositId;
		$this->_depositPackage = $depositPackage;
	}

	public function __destruct() {
		if (!$this->_isUnregistered) {
			$this->_depositPackage->importExportErrorHandler($this->_depositId, "Deposit Import/export error");
			$taskDao = DAORegistry::getDao('ScheduledTaskDAO'); /** @var $taskDao ScheduledTaskDAO */

			$taskDao->updateLastRunTime('plugins.generic.pln.classes.tasks.Depositor', 0);

			$this->_depositPackage->_task->addExecutionLogEntry(__('plugins.generic.pln.depositor.packagingdeposits.processing.error', array('depositId' => $this->_depositId)), SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);

			$this->unregister();
		}
	}

	/**
	 * Unregister the callback.
	 */
	public function unregister() {
		$this->_isUnregistered = true;
	}
}
