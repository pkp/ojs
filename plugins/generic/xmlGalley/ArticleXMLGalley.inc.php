<?php

/**
 * ArticleXMLGalley.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Article XML galley model object
 *
 * $Id$
 */
 
import('article.ArticleHTMLGalley');
import('article.SuppFileDAO');

class ArticleXMLGalley extends ArticleHTMLGalley {
    
	/**
    * Constructor.
    */
	function ArticleXMLGalley() {
		parent::ArticleHTMLGalley();
	}
	
	/**
    * Check if galley is an XML galley.
    * @return boolean
    */
	function isXMLGalley() {
		return true;
	}

	/**
    * Get results of XSLT transform from file cache
    * @return cache object
    */
	function &_getXSLTCache($key) {
		static $caches;
		if (!isset($caches)) {
			$caches = array();
		}

		if (!isset($caches[$key])) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$caches[$key] =& $cacheManager->getFileCache(
				'xsltGalley', $key,
				array(&$this, '_xsltCacheMiss')
			);

			// Check to see if the data is outdated
			$cacheTime = $caches[$key]->getCacheTime();

			if ($cacheTime !== null && $cacheTime < filemtime($this->getFilePath())) {
				$caches[$key]->flush();
			}

		}
		return $caches[$key];
	}

	/**
    * Re-run the XSLT transformation on a stale (or missing) cache
    * @return boolean
    */
	function _xsltCacheMiss(&$cache, $id) {

		static $contents;

		if (!isset($contents)) {

			$journal = &Request::getJournal();
			$xsltRenderer = $this->xmlGalleyPlugin->getSetting($journal->getJournalId(), 'XSLTrenderer');

			// get command for external XSLT tool
			if ($xsltRenderer == "external") $xsltRenderer = $this->xmlGalleyPlugin->getSetting($journal->getJournalId(), 'externalXSLT');
	
			// choose the configured stylesheet: built-in, or custom
			$xslStylesheet = $this->xmlGalleyPlugin->getSetting($journal->getJournalId(), 'XSLstylesheet');
			switch ($xslStylesheet) {
				case 'NLM':
					$xslSheet = $this->xmlGalleyPlugin->getPluginPath() . '/transform/nlm-xhtml.xsl';
					break;
				case 'custom';
					// get file path for custom XSL sheet
					// FIXME:  bug here: Fatal error*: Class 'JournalFileManager' not found in */home/asmecher/cvs/ojs2/plugins/generic/xmlGalley/ArticleXMLGalley.inc.php* on line *89
					$journalFileManager =& new JournalFileManager($journal);
					$xslSheet = $journalFileManager->filesDir . $this->xmlGalleyPlugin->getSetting($journal->getJournalId(), 'customXSL');
					break;
			}

			// transform the XML using whatever XSLT processor we have available
			$contents = $this->transformXSLT($this->getFilePath(), $xslSheet, $xsltRenderer);

			$cache->setEntireCache($contents);
		}
		return null;

	}

	/**
    * Return string containing an XHTML fragment generated from the XML/XSL source
    * This function performs any necessary filtering, like image URL replacement.
    * @param $baseImageUrl string base URL for image references
    * @return string
    */
    function getHTMLContents() {

		$this->xmlGalleyPlugin = &PluginRegistry::getPlugin('generic', 'XMLGalleyPlugin');

		// if the XML Galley plugin is not installed or enabled,
		// then pass through to ArticleHTMLGalley
		if ( !$this->xmlGalleyPlugin ) return parent::getHTMLContents();
		if ( !$this->xmlGalleyPlugin->getEnabled() ) return parent::getHTMLContents();

		$cache =& $this->_getXSLTCache($this->getFileName());
		$contents = $cache->getContents();

		// if contents is false/empty, then we have an XSLT error
		// return the straight XML contents instead
		if ($contents == "") return parent::getHTMLContents();

		//Replace image references
		$images = &$this->getImageFiles();

		if ($images !== null) {
			foreach ($images as $image) {
				$imageUrl = Request::url(null, 'article', 'viewFile', array($this->getArticleId(), $this->getGalleyId(), $image->getFileId()));
				$contents = preg_replace(
	            '/(src|href)\s*=\s*"([^"]*' . preg_quote($image->getOriginalFileName()) .    ')"/i', 
	            '$1="' . $imageUrl . '"',
	            $contents
				);
			}
		}

		// Perform replacement for ojs://... URLs
		$contents = String::regexp_replace_callback(
			'/(<[^<>]*")[Oo][Jj][Ss]:\/\/([^"]+)("[^<>]*>)/',
			array(&$this, '_handleOjsUrl'),
			$contents
		);

        //Replace supplementary file references    
        //$suppFiles = &$this->getSuppFiles($this->getArticleId());
        $this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
        $suppFiles = $this->suppFileDao->getSuppFilesByArticle($this->getArticleId());
        
        if ($suppFiles){
            foreach ($suppFiles as $supp) {
				$journal = &Request::getJournal();
                $suppUrl = Request::url(null, 'article', 'downloadSuppFile', array($this->getArticleId(), $supp->getBestSuppFileId($journal)));

                $contents = preg_replace(
                    '/href="' . preg_quote($supp->getOriginalFileName()) .    '"/', 
                    'href="' . $suppUrl . '"',
                    $contents
                    );
            }
        }

		// if client encoding is set to iso-8859-1, transcode string from utf8 since we transform all XML in utf8
		// FIXME: this doesn't seem to work as expected - perhaps two XSL sheets?
		if (LOCALE_ENCODING == "iso-8859-1") $contents = utf8_decode($contents);
//		if (LOCALE_ENCODING == "iso-8859-1") $contents =  mb_convert_encoding($contents, "UTF-8", "ISO-8859-1");

		return $contents;
	}

	/**
    * Return string containing the transformed XML output.
    * This function applies an XSLT transform to a given XML source.
    * @param $xmlFile pathnae to the XML source file (absolute)
    * @param $xslFile pathname to the XSL stylesheet (absolute)
    * @param (optional) $xsltType type of XSLT renderer to use (PHP4, PHP5, or XSLT shell command)
    * @return string
    */
	function transformXSLT($xmlFile, $xslFile, $xsltType = "") {

		// Determine the appropriate XSLT processor for the system
		if ( version_compare(PHP_VERSION,'5','>=') && extension_loaded('xsl') && extension_loaded('dom') ) {
			// PHP5.x with XSL/DOM modules present

			if ( $xsltType == "PHP5"  || $xsltType == "" ) {			
	
		        // load the XML file as a domdocument
		        $xmlDom = new DOMDocument("1.0", "UTF-8");

				// these are required for external entity resolution (eg. &nbsp;)
				// it slows loading substantially (20-100x), often up to 60s
				// this can be solved by use of local catalogs to speed resolution
				//
				// http://www.whump.com/moreLikeThis/link/03815
				// http://www.suite75.net/blog/mt/tim/archives/2004_07.php
				//putenv("XML_CATALOG_FILES=/Users/mj/Sites/ojs2/plugins/generic/xmlGalley/transform/Publishing-2_2-dtd-June/catalog.ent");
				$xmlDom->substituteEntities = true;
				$xmlDom->resolveExternals = true;

		        $xmlDom->load($xmlFile);

		        // create the processor and import the stylesheet
		        $xslDom = new DOMDocument("1.0", "UTF-8");
		        $xslDom->load($xslFile);

		        $proc = new XsltProcessor();
		        $proc->importStylesheet($xslDom);
	
				// set XSL parameters
		        // send XHTML (and inline MathML and SVG) if it's explicitly accepted, otherwise send XHTML
//	            if (stristr($_SERVER['HTTP_ACCEPT'], 'application/mathml+xml')) $proc->setParameter(null, 'mathml', true);
//	            if (stristr($_SERVER['HTTP_ACCEPT'], 'image/svg+xml')) $proc->setParameter(null, 'svg', true);
	
		        // transform the XML document to an XHTML fragment
				$contents = $proc->transformToXML($xmlDom);

		        return $contents;
			}
		} 

		if ( version_compare(PHP_VERSION,'5','<') && extension_loaded('xslt') ) {
			// PHP4.x with XSLT module present

			if ( $xsltType == "PHP4"  || $xsltType == "" ) {			
	
				// create the processor
				$proc = xslt_create();
				
				// set XSL parameters
		        // send XHTML (and inline MathML and SVG) if it's explicitly accepted, otherwise send HTML
//	            if (stristr($_SERVER['HTTP_ACCEPT'], 'application/mathml+xml')) $arguments['mathml'] = true;
//	            if (stristr($_SERVER['HTTP_ACCEPT'], 'image/svg+xml')) $arguments['svg'] = true;

		        // transform the XML document to an XHTML fragment
		        $contents = xslt_process($proc, $xmlFile, $xslFile, null, null, $arguments);

				return $contents;
			}

		}

		if ( $xsltType != "" ) {
			// external command-line renderer

			// parse the external command to check for %xsl and %xml parameter substitution
			if ( strpos($xsltType, '%xsl') === false ) return false;

			// perform %xsl and %xml replacements for fully-qualified shell command
			$xsltCommand = str_replace(array('%xsl', '%xml'), array($xslFile, $xmlFile), $xsltType);

			// check for safe mode and escape the shell command
			if( !ini_get('safe_mode') ) $xsltCommand = escapeshellcmd($xsltCommand);

			// run the shell command and get the results
			exec($xsltCommand, $contents, $status);
			if ($status != false) return false;

			return implode("\n", $contents);

		} else {
			// No XSLT processor available
			return false;
		}

	}
	
}

?>