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
  var $_files  = array();
  
  /** @var string Atom entry filename */
  var $_atomEntryFileName = 'atom';
  
  /** @var string deposit package filename */
  var $_packageFileName = 'deposit.zip';
  
  /** @var string packaging */
  var $_packaging = 'http://purl.org/net/sword/package/SimpleZip';
  
  /** @var string package content type */
  var $_contentType = 'application/zip';
          
  function DataversePackager() {
    // Create temporary directory for Atom entry & deposit files
    /** @fixme cumbersome but need separate args for parent constructor */
    $this->_outPath = tempnam('/tmp', 'dataverse');
    unlink($this->_outPath);
    mkdir($this->_outPath);
    mkdir($this->_outPath .'/'. $this->_fileDir);    
    
    /** @fixme no attribute to name Atom XML file written to outPath/files */
    /** @fixme last argument in constructor is not actually used */
    parent::__construct($this->_outPath, $this->_fileDir, $this->_outPath, '');
  }

  function addFile($suppFile) {
    $this->_files[] = $suppFile;
  }
  
  /**
   * Function name disambiguator to distinguish parent::create() from creation
   * methods in this class.
   */
  function createAtomEntry() {
    $this->create();
  }
  
  /**
   * Create deposit package of files added to packager
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
   * Get path to Atom entry file
   * @return string
   */
  function getAtomEntryFilePath() {
    /** @fixme Atom entry file name hard-coded in packager_atom_twostep.php */
    return $this->_outPath .'/'. $this->_fileDir .'/'. $this->_atomEntryFileName;
  }  

  /**
   * Get path to deposit package
   * @return string
   */
  function getPackageFilePath() {
    return $this->_outPath .'/'. $this->_fileDir .'/'. $this->_packageFileName;    
  }  
  
  /**
   * Get packaging format of deposit
   * @return string
   */
  function getPackaging() {
    return $this->_packaging;
  }
  
  /**
   * Get content type of deposit
   * @return string
   */
  function getContentType() {
    return $this->_contentType;
  }
    
}

?>
