<?php

/**
 * @file plugins/generic/pln/classes/Deposit.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Deposit
 * @ingroup plugins_generic_pln
 *
 * @brief Packages deposit objects for submission to a PLN
 */

class Deposit extends DataObject {

	function Deposit() {
		parent::DataObject();

		//Set up new deposits with a UUID
		$this->setUUID($this->_newUUID());
	}
	
	function getDepositDir() {
		$journal_dao =& DAORegistry::getDAO('JournalDAO');
		$file_manager =& new JournalFileManager($journal_dao->getById($this->getJournalId()));
		return $file_manager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER . DIRECTORY_SEPARATOR . $this->getUUID();
	}
	
	function getAtomDocumentPath() {
		return $this->getDepositDir() . DIRECTORY_SEPARATOR . $this->getUUID() . ".xml";
	}
	
	function getPackageFilePath() {
		return $this->getDepositDir() . DIRECTORY_SEPARATOR . $this->getUUID() . ".zip";
	}

 	function generateAtomDocument() {
 			
		$pln_plugin =& PluginRegistry::getPlugin('generic','plnplugin');
		$journal_dao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journal_dao->getById($this->getJournalId());
		
		// set up folder and file locations
		$atom_file = $this->getAtomDocumentPath();
		$package_file = $this->getPackageFilePath();
		
		// make sure our bag is present
		if (!file_exists($package_file)) return FALSE;
		
		$atom  = new DOMDocument('1.0', 'utf-8');
		$entry = $atom->createElementNS('http://www.w3.org/2005/Atom', 'entry');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:dcterms', 'http://purl.org/dc/terms/');
		$entry->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:pkp', 'http://pkp.sfu.ca/SWORD');
		
		$email = $atom->createElement('email', $journal->getSetting('contactEmail'));
		$entry->appendChild($email);
		
		$title = $atom->createElement('title', $journal->getLocalizedTitle());
		$entry->appendChild($title);
		
		$issn = '';

		if ($journal->getSetting('onlineIssn')) {
			$issn = $journal->getSetting('onlineIssn');
		} else if ($journal->getSetting('printIssn')) {
			$issn = $journal->getSetting('printIssn');
		} else if ($journal->getSetting('issn')) {
			$issn = $journal->getSetting('issn');
		}
		
		$pkp_issn = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'pkp:issn', $issn);
		$entry->appendChild($pkp_issn);
		
		$id = $atom->createElement('id', 'urn:uuid:'.$this->getUUID());
		$entry->appendChild($id);
		
		$updated = $atom->createElement('updated', strftime("%FT%TZ",strtotime($this->getDateModified())));
		$entry->appendChild($updated);
		
		
		$url = $journal->getUrl() . '/' . PLN_PLUGIN_ARCHIVE_FOLDER . '/deposits/' . $this->getUUID();
		$pkp_details = $atom->createElementNS('http://pkp.sfu.ca/SWORD', 'pkp:content', $url);
		$pkp_details->setAttribute('size', ceil(filesize($package_file)/1024));
		
		$object_volume = "";
		$object_issue = "";
		$object_publication_date = 0;
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
		
		switch ($this->getObjectType()) {
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE]:
				foreach ($this->getDepositObjects() as $deposit_object) {
					$published_article_dao =& DAORegistry::getDAO('PublishedArticleDAO');
					$article = $published_article_dao->getPublishedArticleByArticleId($deposit_object->getObjectId());
					if ($article->getDatePublished() > $object_publication_date)
						$object_publication_date = $article->getDatePublished();
				}
				break;
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE]:
				foreach ($this->getDepositObjects() as $deposit_object) {
					$issue_dao =& DAORegistry::getDAO('IssueDAO');
					$issue = $issue_dao->getIssueById($deposit_object->getObjectId());
					$object_volume = $issue->getVolume();
					$object_issue = $issue->getNumber();
					if ($issue->getDatePublished() > $object_publication_date)
						$object_publication_date = $issue->getDatePublished();
				}
				break;
			default:
		}
		
		$pkp_details->setAttribute('volume', $object_volume);
		$pkp_details->setAttribute('issue', $object_issue);
		$pkp_details->setAttribute('pubdate', strftime("%F",strtotime($object_publication_date)));
		
		switch ($pln_plugin->getSetting($journal->getId(), 'checksum_type')) {
			case PLN_PLUGIN_DEPOSIT_CHECKSUM_SHA1:
				$pkp_details->setAttribute('checksumType', PLN_PLUGIN_DEPOSIT_CHECKSUM_SHA1);
				$pkp_details->setAttribute('checksumValue', sha1_file($package_file));
				break;
			case PLN_PLUGIN_DEPOSIT_CHECKSUM_MD5:
				$pkp_details->setAttribute('checksumType', PLN_PLUGIN_DEPOSIT_CHECKSUM_MD5);
				$pkp_details->setAttribute('checksumValue', md5_file($package_file));
				break;
			default:
		}

		$entry->appendChild($pkp_details);
		$atom->appendChild($entry);
		$atom->save($atom_file);
		
		return $atom_file;
		
	}
	
	/**
	 * Create a package containing the serialized deposit objects 
	 * @return string The full path of the created zip archive
	 */
	function generatePackage() {
		
		// get DAOs, plugins and settings
		$journal_dao =& DAORegistry::getDAO('JournalDAO');
		$issue_dao =& DAORegistry::getDAO('IssueDAO');
		$section_dao =& DAORegistry::getDAO('SectionDAO');
		$published_article_dao =& DAORegistry::getDAO('PublishedArticleDAO');
		$export_plugin =& PluginRegistry::getPlugin('importexport','NativeImportExportPlugin');
		$file_manager = new JournalFileManager($journal_dao->getById($this->getJournalId()));
		$object_types = unserialize(PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS);
		
		$journal =& $journal_dao->getById($this->getJournalId());
		$deposit_objects = $this->getDepositObjects();
		
		// set up folder and file locations
		$bag_dir = $this->getDepositDir() . DIRECTORY_SEPARATOR . 'bag';
		$package_file = $this->getPackageFilePath();
		$export_file =  $bag_dir . DIRECTORY_SEPARATOR . $this->getObjectType() . '.xml';
		
		$bag = new BagIt($bag_dir);
		
		switch ($this->getObjectType()) {
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ARTICLE]:
				$articles = array();
				
				// we need to add all of the relevant articles to an array to export as a batch
				foreach ($deposit_objects as $deposit_object) {
					$article =& $published_article_dao->getPublishedArticleByArticleId($this->getObjectId(), $journal->getId());
					$issue =& $issue_dao->getIssueById($article->getIssueId(), $journal->getId());
					$section =& $section_dao->getSection($article->getSectionId());
					
					// add the article to the array we'll pass for export
					$articles[] = array(
						'publishedArticle' => $article,
						'section' => $section,
						'issue' => $issue,
						'journal' => $journal
					);
				}
				
				// export all of the articles together
				if ($export_plugin->exportArticles($articles, $export_file) !== TRUE) return FALSE;
				break;
			case $object_types[PLN_PLUGIN_DEPOSIT_OBJECT_ISSUE]:
			
				// we only ever do one issue at a time, so get that issue
				$deposit_object = array_pop($deposit_objects);
				$issue =& $issue_dao->getIssueByBestIssueId($deposit_object->getObjectId(),$journal->getId());
				
				// export the issue
				if ($export_plugin->exportIssue($journal, $issue, $export_file) !== TRUE) return FALSE;
				break;
			default:
		}
		
		// add the exported content to the bag
		$bag->addFile($export_file, $this->getObjectType() . $this->getUUID() . '.xml');
		$bag->update();
		
		// delete the export file
		unlink($export_file);

		// create the bag
		$bag->package($package_file,'zip');
		
		// remove the temporary bag directory
		$file_manager->rmtree($bag_dir);
		
		return $package_file;
	}
	
	function transferDeposit() {
			
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$journal_dao =& DAORegistry::getDAO('JournalDAO');
		$pln_plugin =& PluginRegistry::getPlugin('generic','plnplugin');
		$pln_networks = unserialize(PLN_PLUGIN_NETWORKS);
		$file_manager = new JournalFileManager($journal_dao->getById($this->getJournalId()));
		$pln_dir = $file_manager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER;
		
		// post the atom document
		$url = 'http://' . $pln_networks[$pln_plugin->getSetting($this->getJournalID(), 'pln_network')];
		if ($this->getUpdateStatus()) {
			$url .= PLN_PLUGIN_CONT_IRI . '/' . $pln_plugin->getSetting($this->getJournalID(), 'journal_uuid');
			$url .= '/' . $this->getUUID() . '/edit';
			$result = $pln_plugin->_curlPutFile(
				$url,
				$this->getAtomDocumentPath()
			);
		} else {
			$url .= PLN_PLUGIN_COL_IRI . '/' . $pln_plugin->getSetting($this->getJournalID(), 'journal_uuid');
			$result = $pln_plugin->_curlPostFile(
				$url,
				$this->getAtomDocumentPath()
			);
		}
				
		// if we get the OK, set the status as transferred
		if (($result['status'] == PLN_PLUGIN_HTTP_STATUS_OK) || ($result['status'] == PLN_PLUGIN_HTTP_STATUS_CREATED)) {
			$this->setTransferredStatus();
			// if this was an update, unset the update flag
			$this->setUpdateStatus(FALSE);
			$this->setLastStatusDate(time());
			$deposit_dao->updateDeposit($this);
		}
		
	}
	
	function packageDeposit() {
				
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$journal_dao =& DAORegistry::getDAO('JournalDAO');
		$file_manager = new JournalFileManager($journal_dao->getById($this->getJournalId()));
		$pln_dir = $file_manager->filesDir . PLN_PLUGIN_ARCHIVE_FOLDER;
		
		// make sure the pln work directory exists
		if (is_dir($pln_dir) !== TRUE) { mkdir($pln_dir); }
		
		// make a location for our work and clear it out if it's there
		$deposit_dir = $pln_dir . DIRECTORY_SEPARATOR . $this->getUUID();
		if (is_dir($deposit_dir)) $file_manager->rmtree($deposit_dir);
		mkdir($deposit_dir);

		if (!file_exists($this->generatePackage())) {
			$this->setLocalFailureStatus();
			$deposit_dao->updateDeposit($this);
		}
		
		if (!file_exists($this->generateAtomDocument())) {
			$this->setLocalFailureStatus();
			$deposit_dao->updateDeposit($this);
		}
		
		// update the deposit's status
		$this->setPackagedStatus();
		$deposit_dao->updateDeposit($this);
		
	}

	function updateDepositStatus() {
			
		$deposit_dao =& DAORegistry::getDAO('DepositDAO');
		$pln_plugin =& PluginRegistry::getPlugin('generic','plnplugin');
		$pln_networks = unserialize(PLN_PLUGIN_NETWORKS);
		
		$url = 'http://' . $pln_networks[$pln_plugin->getSetting($this->getJournalID(), 'pln_network')] . PLN_PLUGIN_CONT_IRI;
		$url .= '/' . $pln_plugin->getSetting($this->getJournalID(), 'journal_uuid');
		$url .= '/' . $this->getUUID() . '/state';
		
		// retrieve the content document
		$result = $pln_plugin->_curlGet($url);
		
		// stop here if we didn't get an OK
		if ($result['status'] != PLN_PLUGIN_HTTP_STATUS_OK) {
			$this->setRemoteFailureStatus();
			$deposit_dao->updateDeposit($this);
		}

		$content_state = new DOMDocument();
		$content_state->preserveWhiteSpace = FALSE;
		$content_state->loadXML($result['result']);
		
		// get the remote deposit state
		$element = $content_state->getElementsByTagName('category')->item(0);
		$state = $element->getAttribute('term');
		
		switch ($state) {
			case 'agreement':
				$this->setSyncedStatus();
			case 'disagreement':
				$this->setSyncingStatus();
			case 'in_progress':
				$this->setReceivedStatus();
				break;
			case 'failed':
				$this->setRemoteFailureStatus();
				break;
			default:
		}
		
		$deposit_dao->updateDeposit($this);
		
	}

	/**
	 * Get the type of deposit objects in this deposit.
	 * @return string One of PLN_PLUGIN_DEPOSIT_SUPPORTED_OBJECTS
	 */
	function getObjectType() {
		$deposit_objects = $this->getDepositObjects();
		if (count($deposit_objects) == 0) return null;
		$deposit_object = $deposit_objects[0];
		return $deposit_object->getObjectType();
	}
	
	/**
	 * Get all deposit objects of this deposit.
	 * @return array of DepositObject
	 */
	function &getDepositObjects() {
		$depositObjectDao =& DAORegistry::getDAO('DepositObjectDAO');
		return $depositObjectDao->getByDepositId($this->getJournalID(), $this->getId());
	}
	
	/**
	* Get/Set deposit uuid
	*/
	function getUUID() {
		return $this->getData('uuid');
	}
	function setUUID($uuid) {
		$this->setData('uuid', $uuid);
	}
	
	/**
	* Get/Set journal id
	*/
	function getJournalId() {
		return $this->getData('journal_id');
	}
	function setJournalId($journal_id) {
		$this->setData('journal_id', $journal_id);
	}

	/**
	* Get/Set deposit status
	*/
	function getStatus() {
		return $this->getData('status');
	}
	function setStatus($status) {
		$this->setData('status', $status);
	}
	
	function getNewStatus() {
		return $this->getStatus() == PLN_PLUGIN_DEPOSIT_STATUS_NEW;
	}
	function setNewStatus() {
		return $this->setStatus(PLN_PLUGIN_DEPOSIT_STATUS_NEW);
	}
	
	function getPackagedStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED;
	}
	function setPackagedStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_PACKAGED);
	}
	
	function getTransferredStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED;
	}
	function setTransferredStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_TRANSFERRED);
	}
	
	function getReceivedStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED;
	}
	function setReceivedStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_RECEIVED);
	}
	
	function getSyncingStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_SYNCING;
	}
	function setSyncingStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_SYNCING : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_SYNCING);
	}
	
	function getSyncedStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_SYNCED;
	}
	function setSyncedStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_SYNCED : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_SYNCED);
	}
	
	function getRemoteFailureStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_REMOTE_FAILURE;
	}
	function setRemoteFailureStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_REMOTE_FAILURE : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_REMOTE_FAILURE);
	}
	
	function getLocalFailureStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE;
	}
	function setLocalFailureStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_LOCAL_FAILURE);
	}

	function getUpdateStatus() {
		return $this->getStatus() & PLN_PLUGIN_DEPOSIT_STATUS_UPDATE;
	}
	function setUpdateStatus($status = TRUE) {
		$this->setStatus($status ? $this->getStatus() | PLN_PLUGIN_DEPOSIT_STATUS_UPDATE : $this->getStatus() & ~PLN_PLUGIN_DEPOSIT_STATUS_UPDATE);
	}

	/**
	* Get/Set last status check date
	*/
	function getLastStatusDate() {
		return $this->getData('date_status');
	}
	function setLastStatusDate($date_last_status) {
		$this->setData('date_status', $date_last_status);
	}

	/**
	* Get/Set deposit creation date
	*/
	function getDateCreated() {
		return $this->getData('date_created');
	}
	function setDateCreated($date_created) {
		$this->setData('date_created', $date_created);
	}

	/**
	* Get/Set deposit modification date
	*/
	function getDateModified() {
		return $this->getData('date_modified');
	}
	function setDateModified($date_modified) {
		$this->setData('date_modified', $date_modified);
	}

	/**
	 * Create a new UUID
	 */
	function _newUUID() {
		mt_srand((double)microtime()*10000);
		$charid = strtoupper(md5(uniqid(rand(), true)));
		$hyphen = '-';
		$uuid = substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid,12, 4).$hyphen
				.substr($charid,16, 4).$hyphen
				.substr($charid,20,12);
        return $uuid;
	}
}

?>
