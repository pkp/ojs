<?php

/**
 * RTXMLParser.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt
 *
 * Class to parse Reading Tools data from an XML format.
 *
 * $Id$
 */

import('xml.XMLParser');
import('rt.RTStruct');

class RTXMLParser {

	/** @var XMLParser the parser to use */
	var $parser;


	/**
	 * Constructor.
	 */
	function RTXMLParser() {
		$this->parser = &new XMLParser();
	}
	
	/**
	 * Parse an RT version XML file.
	 * @param $file string path to the XML file
	 * @return RTVersion
	 */
	function &parse($file) {	
		$tree = $this->parser->parse($file);
		$version = false;
		
		if ($tree !== false) {
			$version = &$this->parseVersion($tree);
		}
		
		return $version;
	}
	
	
	/**
	 * Parse all RT version XML files in a directory.
	 * @param $dir string path to the directory
	 * @return array RTVersion
	 */
	function &parseAll($dir) {
		$versions = array();
		
		if(($fd = opendir($dir)) !== false) {
			while (($file = readdir($fd)) !== false) {
				if (preg_match('/\.xml$/', $file)) {
					if (($version = $this->parse($dir . '/' . $file))) {
						array_push($versions, $version);
					}
				}
			}
			closedir($fd);
		}
		
		return $versions;
	}
	
	
	//
	// PRIVATE
	//
	
	
	/**
	 * Parse version entity.
	 * @param $version XMLNode
	 * @return RTVersion
	 */
	function &parseVersion(&$version) {
		$newVersion = &new RTVersion();
		$numContexts = 0;
		
		$newVersion->key = $version->getAttribute('id');
		$newVersion->locale = $version->getAttribute('locale');
		
		foreach ($version->getChildren() as $attrib) {
			switch ($attrib->getName()) {
				case 'version_title':
					$newVersion->title = $attrib->getValue();
					break;
				case 'version_description':
					$newVersion->description = $attrib->getValue();
					break;
				case 'context':
					$newContext = &$this->parseContext($attrib);
					$newContext->order = $numContexts++;
					$newVersion->addContext($newContext);
					break;
			}
		}
		
		return $newVersion;
	}
	
	/**
	 * Parse context entity.
	 * @param $context XMLNode
	 * @return RTContext
	 */
	function &parseContext(&$context) {
		$newContext = &new RTContext();
		$numSearches = 0;
		
		foreach ($context->getChildren() as $attrib) {
			switch ($attrib->getName()) {
				case 'context_title':
					$newContext->title = $attrib->getValue();
					break;
				case 'context_abbrev':
					$newContext->abbrev = $attrib->getValue();
					break;
				case 'context_description':
					$newContext->description = $attrib->getValue();
					break;
				case 'author_terms':
					$newContext->authorTerms = true;
					break;
				case 'define_terms':
					$newContext->defineTerms = true;
					break;
				case 'search':
					$newSearch = &$this->parseSearch($attrib);
					$newSearch->order = $numSearches++;
					$newContext->addSearch($newSearch);
					break;
			}
		}
		
		return $newContext;
	}
	
	/**
	 * Parse search entity.
	 * @param $context XMLNode
	 * @return RTSearch
	 */
	function &parseSearch(&$search) {
		$newSearch = &new RTSearch();
		
		foreach ($search->getChildren() as $attrib) {
			switch ($attrib->getName()) {
				case 'search_title':
					$newSearch->title = $attrib->getValue();
					break;
				case 'search_description':
					$newSearch->description = $attrib->getValue();
					break;
				case 'url':
					$newSearch->url = $attrib->getValue();
					break;
				case 'search_url':
					$newSearch->searchUrl = $attrib->getValue();
					break;
				case 'search_post':
					$newSearch->searchPost = $attrib->getValue();
					break;
			}
		}
		
		return $newSearch;
	}
	
}

?>
