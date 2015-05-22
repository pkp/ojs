<?php

/**
 * @file plugins/generic/dataverse/classes/DataversePackager.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	
	/** @var array of files to be deposited: file paths indexed by filename */
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
	 * @param $filePath String file path
	 * @param $fileName String file name
	 */
	function addFile($filePath, $fileName) {
		$this->_files[$fileName] = $filePath;
	}
	
	/**
	 * Create Atom entry. Wrapper renames parent::create() to distinguish between
	 * Atom entry creation and deposit package creation.
	 */
	function createAtomEntry() {
		$this->create();
	}
	
	/**
	 * Create deposit package of files.
	 */
	function createPackage() {
		$package = new ZipArchive();
		$package->open($this->getPackageFilePath(), ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
		foreach ($this->_files as $fileName => $filePath) {
			$package->addFile($filePath, $fileName);
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
