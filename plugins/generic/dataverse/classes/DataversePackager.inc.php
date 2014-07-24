<?php

/**
 * @file plugins/generic/dataverse/classes/DataversePackager.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataversePackager
 * @ingroup plugins_generic_dataverse
 *
 * @brief Packages article metadata and suppfiles for deposit in Dataverse
 */
require_once('lib/pkp/lib/swordappv2/packager_atom_twostep.php');

class DataversePackager extends PackagerAtomTwoStep {
	
	/** @var string output path for packager files */
	var $_outPath;
	
	/** @var string file directory for packager files */
	var $_fileDir = 'files';
	
	/** @var array of SuppFile objects to be deposited */
	var $_files	 = array();
	
	/** @var string Atom entry filename */
	var $_atomEntryFileName = 'atom';
	
	/** @var string deposit package filename */
	var $_packageFileName = 'deposit.zip';
	
	/** @var string packaging */
	var $_packaging = 'http://purl.org/net/sword/package/SimpleZip';
	
	/** @var string package content type */
	var $_contentType = 'application/zip';

	/**
	 * Constructor.
	 */
	function DataversePackager() {
		// Create temporary directory for Atom entry & deposit files
		$this->_outPath = tempnam('/tmp', 'dataverse');
		unlink($this->_outPath);
		mkdir($this->_outPath);
		mkdir($this->_outPath .'/'. $this->_fileDir);		 		
		parent::__construct($this->_outPath, $this->_fileDir, $this->_outPath, '');
	}

	/**
	 * Add file to deposit package.
	 * @param $suppFile 
	 */
	function addFile($suppFile) {
		$this->_files[] = $suppFile;
	}
	
	/**
	 * Create Atom entry from journal-, article-, and suppfile-level metadata, then
	 * write Atom entry to disk for later deposit. 
	 */
	function createAtomEntry($article) {
		// Article metadata
		$this->addMetadata('title', $article->getLocalizedTitle());
		$this->addMetadata('description', $article->getLocalizedAbstract());
		foreach ($article->getAuthors() as $author) {
			$this->addMetadata('creator', $author->getFullName(true));
		}
		// subject: academic disciplines
		$split = '/\s*'. DATAVERSE_PLUGIN_SUBJECT_SEPARATOR .'\s*/';
		foreach(preg_split($split, $article->getLocalizedDiscipline(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
			$this->addMetadata('subject', $subject);
		}
		// subject: subject classifications
		foreach(preg_split($split, $article->getLocalizedSubjectClass(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
			$this->addMetadata('subject', $subject);
		}
		// subject:	 keywords		 
		foreach(preg_split($split, $article->getLocalizedSubject(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
			$this->addMetadata('subject', $subject);
		}
		// geographic coverage
		foreach(preg_split($split, $article->getLocalizedCoverageGeo(), NULL, PREG_SPLIT_NO_EMPTY) as $coverage) {
			$this->addMetadata('coverage', $coverage);
		}
		
		// Journal metadata
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journal =& $journalDao->getById($article->getJournalId());
		assert(!is_null($journal));
		$this->addMetadata('publisher', $journal->getSetting('publisherInstitution'));
		$this->addMetadata('rights', $journal->getLocalizedSetting('copyrightNotice'));
		$this->addMetadata('isReferencedBy', $this->getCitation($article));
		
		// Suppfile metadata
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO');				
		$dvFileDao =& DAORegistry::getDAO('DataverseFileDAO');
		$dvFiles =& $dvFileDao->getDataverseFilesBySubmissionId($article->getId());
		foreach ($dvFiles as $dvFile) {
			$suppFile =& $suppFileDao->getSuppFile($dvFile->getSuppFileId(), $article->getId());
			assert(!is_null($suppFile));
			foreach(preg_split($split, $suppFile->getSuppFileSubject(), NULL, PREG_SPLIT_NO_EMPTY) as $subject) {
				$this->addMetadata('subject', $subject);
			}
			if ($suppFile->getType()) $this->addMetadata('type', $suppFile->getType());
			if ($suppFile->getSuppFileTypeOther()) $this->addMetadata('type', $suppFile->getSuppFileTypeOther());
		}
		// Write Atom entry file to /tmp		
		$this->create();
	}
	
	/**
	 * Create deposit package of files.
	 */
	function createPackage() {
		$package = new ZipArchive();
		$package->open($this->getPackageFilePath(), ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
		foreach ($this->_files as $suppFile) {
			$suppFile->setFileStage(ARTICLE_FILE_SUPP); // workaround for #8444
			$package->addFile($suppFile->getFilePath(), $suppFile->getOriginalFileName());
		}
		$package->close();
	}

	/**
	 * Get path to Atom entry file.
	 * @return string
	 */
	function getAtomEntryFilePath() {
		return $this->_outPath .'/'. $this->_fileDir .'/'. $this->_atomEntryFileName;
	}	 

	/**
	 * Get path to deposit package.
	 * @return string
	 */
	function getPackageFilePath() {
		return $this->_outPath .'/'. $this->_fileDir .'/'. $this->_packageFileName;		 
	}	 
	
	/**
	 * Get packaging format of deposit.
	 * @return string
	 */
	function getPackaging() {
		return $this->_packaging;
	}
	
	/**
	 * Get content type of deposit.
	 * @return string
	 */
	function getContentType() {
		return $this->_contentType;
	}
		
}

?>
