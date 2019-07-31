<?php

/**
 * @file plugins/generic/metadataExport/MetadataExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MetadataExportPlugin
 * @ingroup plugins_generic_metadataExportPlugin
 *
 * @brief Metadata Export plugin class
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class MetadataExportPlugin extends GenericPlugin {
	
	/* Constructor */
	function MetadataExportPlugin() {
		parent::GenericPlugin();
		$this->issueDao = DAORegistry::getDAO('IssueDAO');
	}
	
	/* @var XMLCustomWriter object */
	var $doc;
	
	/* @var issueDAO object */
	var $issueDao;
	
	
	/********************/
	/* Metadata fields: */
	/********************/
	
	/* @var string Title of the article */
	var $title;
	
	/* @var array Authors of the article */
	var $creators;
	
	/* @var array Subjects of the article */
	var $subjects;
	
	/* @var string Abstracts of the article */
	var $abstract;
	
	/* @var string Publisher of the article */
	var $publisher;
	
	/* @var array Contributors of the article */
	var $contributors;
	
	/* @var string Publishing date of the article */
	var $datePublished;
	
	/* @var array Types of the files belonging to the article */
	var $types;
	
	/* @var array Formats of the files belonging to the article */
	var $formats;
	
	/* @var string Identifier of the article */
	var $identifier;
	
	/* @var array Sources of the article */
	var $sources;
	
	/* @var string Language of the article */
	var $language;
	
	/* @var array Related files of the article */
	var $relations;
	
	/* @var array Galley files belonging to the article */
	var $galleyURLs;
	
	/* @var array Supplementary files belonging to the article */
	var $suppFileURLs;
	
	/* @var string Copyright of the article */
	var $copyright;
	
	
	/**
	 * @copydoc PKPPlugin::register()
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) { 
			if ($this->getEnabled()) {
				HookRegistry::register('PluginRegistry::loadCategory', array($this, 'callbackLoadCategory')); 
				HookRegistry::register('LoadHandler', array($this, 'callbackHandleContent'));
			}
			return true;
		}
		return false;
	}
	
	/**
	 * Register as a block plugin, even though this is a generic plugin.
	 * This will allow the plugin to behave as a block plugin
	 * @param $hookName String
	 * @param $args Array
	 * @return Boolean
	 */
	function callbackLoadCategory($hookName, $args) {
		$category = $args[0];
		$plugins =& $args[1]; // & operator necessary in this case

		switch ($category) {
			case 'blocks':
				$this->import('MetadataExportBlockPlugin');
				$blockPlugin = new MetadataExportBlockPlugin($this->getName());
				$plugins[$blockPlugin->getSeq()][$blockPlugin->getPluginPath()] = $blockPlugin;
				break;
		}
		return false;
	}
	
	/**
	 * Handle the request and generate the export file
	 * @param $hookName String
	 * @param $args Array
	 * @return Boolean
	 */
	function callbackHandleContent($hookName, $args) {
		$templateMgr = TemplateManager::getManager();

		$page = $args[0];
		$op = $args[1];

		if ($page == 'metadata') {
			define('METADATA_EXPORT_PLUGIN_NAME', $this->getName());
			define('HANDLER_CLASS', 'MetadataExportHandler'); 
			$this->import('MetadataExportHandler'); 
			return true;
		}
		return false;
	}
	
	/**
	 * @copydoc PKPPlugin::getTemplatePath()
	 */
	function getTemplatePath() {
		return parent::getTemplatePath() . 'templates/';
	}
	
	/**
	 * @copydoc PKPPlugin::getName()
	 */
	function getName() {
		return 'MetadataExportPlugin';
	}
	
	/**
	 * @copydoc PKPPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.generic.metadataExport.displayName');
	}
	
	/**
	 * @copydoc PKPPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.generic.metadataExport.description');
	}
	
	/**
	 * Get the name of the metadata format.
	 * Should be overridden by subclasses.
	 * @return String
	 */
	function getMetadataExportFormatName() {
		assert(false);
	}
	
	/**
	 * Get the file extension of the export file.
	 * Should be overridden by subclasses.
	 * @return String
	 */
	function getFileExtension() {
		assert(false);
	}
	
	/**
	 * Get the type of the XML's root node
	 * Should be overridden by subclasses.
	 * @return String
	 */
	function getRootElement() {
		assert(false);
	}
	
	/**
	 * Generate the content of the text or XML file
	 * Should be overridden by subclasses.
	 * @param $journal Journal Object
	 * @param $articles Array of Article Objects
	 * @return Mixed (String or XMLCustomWriter Object)
	 */
	function getFileContent($journal, $articles) {
		assert(false);
	}
	
	/**
	 * Identification as a reviewed article
	 * @return String
	 */
	function getType() {
		return __('rt.metadata.pkp.peerReviewed');
	}
	
	/**
	 * Abbreviation for the term "issue"
	 * @return String
	 */
	function getVolumeAbbreviation() {
		return __('issue.vol');
	}
	
	/**
	 * Identifier for publication type
	 * @return String
	 */
	function getTypeIdentifier() {
		return 'info:eu-repo/semantics/article';
	}
	
	/**
	 * Identifier for publication version
	 * @return String
	 */
	function getVersionIdentifier() {
		return 'info:eu-repo/semantics/publishedVersion';
	}
	
	/**
	 * Get the Copyright statement
	 * @param $copyrightHolder String
	 * @param $copyrightYear Int
	 * @return String
	 */
	function getCopyrightStatement($copyrightHolder, $copyrightYear) {
		return __('submission.copyrightStatement', array('copyrightHolder' => $copyrightHolder, 'copyrightYear' => $copyrightYear));
	}
	
	/**
	 * Checks if the current download file is an XML file
	 * @return boolean
	 */
	function isXML() {
		return $this->getFileExtension() == 'xml';
	}
	
	/**
	 * Initialize all metadata for the export file
	 * @param $journal Journal object
	 * @param $article Article object
	 */
	function _initData($journal, $article) {
		$locale = $article->getLocale();
		$issue = $this->issueDao->getIssueById($article->getIssueId(), $journal->getId());
		
		// title
		$this->title = $article->getLocalizedTitle();

		// creators
		$authors = $article->getAuthors();
		for ($i = 0, $num = count($authors); $i < $num; $i++) {
			$authorName = $authors[$i]->getFullName(true);
			$affiliation = $authors[$i]->getLocalizedAffiliation();
			if (!empty($affiliation)) {
				$authorName .= '; ' . $affiliation;
			}
			$this->creators[] = $authorName;
		}
		
		// subject
		$subjects = $article->getSubject();
		$subjectsLocalized = explode(';', $subjects[$locale]);
		foreach($subjectsLocalized as $subject) {
			$this->subjects[] = trim($subject);
		}
		
		//description (=abstract)
		$abstract = $article->getAbstract();
		$this->abstract = $abstract[$locale];
		
		// publisher
		$this->publisher = $journal->getLocalizedTitle();
		$publisherInstitution = $journal->getSetting('publisherInstitution');
		if (!empty($publisherInstitution)) {
			$this->publisher = $publisherInstitution;
		}
		
		// contributors
		$contributors = $article->getSponsor();
		$contributorsLocalized = explode(';', $contributors[$locale]);
		foreach($contributorsLocalized as $contributors) {
			$this->contributors[] = trim($contributors);
		}
		
		// date 
		$this->datePublished = date('Y-m-d', strtotime($article->getDatePublished()));
		
		// type
		$this->types = array($this->getType(), $this->getTypeIdentifier(), $this->getVersionIdentifier());
		
		// formats and relations
		$galleyFormats = array();
		$suppFileFormats = array();
		
		foreach ($article->getData('galleys') as $galley) {
			$galleyFormats[] = $galley->getFileType();
			$this->galleyURLs[] = Request::url($journal->getPath(), 'article', 'view', array($article->getId(), $galley->getId()));
		}
		
		foreach ($article->getSuppFiles() as $suppFile) {
			$suppFileFormats[] = $suppFile->getFileType();
			$this->suppFileURLs[] = Request::url($journal->getPath(), 'article', 'downloadSuppFile', array($article->getId(), $suppFile->getId()));
		}
		$this->formats = array_merge($galleyFormats, $suppFileFormats);
		$this->relations = array_merge($this->galleyURLs, $this->suppFileURLs);
		
		//identifier
		$this->identifier = Request::url($journal->getPath(), 'article', 'view', array($article->getId()));
		
		// source
		foreach($journal->getTitle() as $journalTitle) {
			$this->sources[] = $journalTitle . '; ' . $this->getVolumeAbbreviation() . ' ' . $issue->getData('volume');
		}
		if ($issue->getData('pub-id::doi')) {
			$this->sources[] = $issue->getData('pub-id::doi');
		}
		
		//language
		$this->language = $article->getLanguage();
		
		// copyright
		$copyrightHolder = $article->getLocalizedCopyrightHolder();
		$copyrightYear = $article->getCopyrightYear();
		$licenseUrl = $article->getDefaultLicenseUrl();
		
		$this->copyright = $this->getCopyrightStatement($copyrightHolder, $copyrightYear);
		if ($licenseUrl) {
			$this->copyright .= ', ' . $licenseUrl;
		}
	}
	
	/**
	 * Unset all metadata class variables before they are initialized in _initData()
	 */
	function _unsetData() {
		unset($this->title);
		unset($this->creators);
		unset($this->subjects);
		unset($this->abstract);
		unset($this->publisher);
		unset($this->contributors);
		unset($this->datePublished);
		unset($this->types);
		unset($this->formats);
		unset($this->identifier);
		unset($this->sources);
		unset($this->language);
		unset($this->relations);
		unset($this->galleyURLs);
		unset($this->suppFileURLs);
		unset($this->copyright);
	}
	
	/**
	 * Generate a text file (.bib, .ris etc.)
	 * @param $journal Journal object
	 * @param $article Array of Article objects
	 * @param $name String
	 */
	function createTextFile($journal, $articles) {
		$contents = $this->getFileContent($journal, $articles);

		header('content-type: text/plain');
		header('content-disposition: attachment; filename=metadata.' . $this->getFileExtension());
		
		echo $contents;
	}
	
	/**
	 * Generate an XML file
	 * @param $journal Journal 0bject
	 * @param $articles Array of Article objects
	 */
	function createXmlFile($journal, $articles) {
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APPLICATION_COMMON));
		$this->doc = XMLCustomWriter::createDocument();
		$contents = $this->getFileContent($journal, $articles);

		header('Content-Type: application/xml');
		header('Cache-Control: private');
		header('Content-Disposition: attachment; filename=metadata.' . $this->getFileExtension());
		
		XMLCustomWriter::printXML($this->doc);
	}
}
?>