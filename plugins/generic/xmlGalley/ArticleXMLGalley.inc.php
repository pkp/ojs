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
	 * Check if galley is an HTML galley.
	 * @return boolean
	 */
	function isHTMLGalley() {
		switch ($this->getFileType()) {
			case 'application/xhtml':
			case 'application/xhtml+xml':
			case 'text/html':
			case 'application/xml':
			case 'text/xml':
				return true;
			default: return false;
		}
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
	function _xsltCacheMiss(&$cache) {
		static $contents;
		if (!isset($contents)) {
			$journal = &Request::getJournal();
			$xmlGalleyPlugin = &PluginRegistry::getPlugin('generic', 'XMLGalleyPlugin');

			$xsltRenderer = $xmlGalleyPlugin->getSetting($journal->getJournalId(), 'XSLTrenderer');

			// get command for external XSLT tool
			if ($xsltRenderer == "external") $xsltRenderer = $xmlGalleyPlugin->getSetting($journal->getJournalId(), 'externalXSLT');

			// choose the configured stylesheet: built-in, or custom
			$xslStylesheet = $xmlGalleyPlugin->getSetting($journal->getJournalId(), 'XSLstylesheet');
			switch ($xslStylesheet) {
				case 'NLM':
					// if the XML galley is a PDF galley then render the XSL-FO stylesheet
					if ($this->isPdfGalley()) {
						$xslSheet = $xmlGalleyPlugin->getPluginPath() . '/transform/nlm/nlm-fo.xsl';
					} else {
						$xslSheet = $xmlGalleyPlugin->getPluginPath() . '/transform/nlm/nlm-xhtml.xsl';

						// send XHTML (and inline MathML and SVG) if it's explicitly accepted, otherwise send XHTML
						// TODO: ask alf about this; if he has an example with firefox
						//if (stristr($_SERVER['HTTP_ACCEPT'], 'application/mathml+xml')) $arguments['mathml'] = true;
						//if (stristr($_SERVER['HTTP_ACCEPT'], 'image/svg+xml')) $arguments['svg'] = true;
					}
					break;
				case 'custom';
					// get file path for custom XSL sheet
					$journalFileManager =& new JournalFileManager($journal);
					$xslSheet = $journalFileManager->filesDir . $xmlGalleyPlugin->getSetting($journal->getJournalId(), 'customXSL');
					break;
			}

			// transform the XML using whatever XSLT processor we have available
			$contents = $this->transformXSLT($this->getFilePath(), $xslSheet, $xsltRenderer, $arguments);

			// if all goes well, cache the results of the XSLT transformation
			if ($contents) $cache->setEntireCache($contents);
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
		$xmlGalleyPlugin = &PluginRegistry::getPlugin('generic', 'XMLGalleyPlugin');

		// if the XML Galley plugin is not installed or enabled,
		// then pass through to ArticleHTMLGalley
		if ( !$xmlGalleyPlugin ) return parent::getHTMLContents();
		if ( !$xmlGalleyPlugin->getEnabled() ) return parent::getHTMLContents();

		$cache =& $this->_getXSLTCache($this->getFileName() . '-' . $this->getGalleyId());
		$contents = $cache->getContents();

		// if contents is false/empty, then we have an XSLT error
		// return the straight XML contents instead
		if ($contents == "") return parent::getHTMLContents();

		// Replace image references
		$images = &$this->getImageFiles();

		if ($images !== null) {
			foreach ($images as $image) {
				$imageUrl = Request::url(null, 'article', 'viewFile', array($this->getArticleId(), $this->getGalleyId(), $image->getFileId()));
				$contents = preg_replace(
					'/(src|href)\s*=\s*"([^"]*' . preg_quote($image->getOriginalFileName()) . ')"/i',
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

		// Replace supplementary file references
		// $suppFiles = &$this->getSuppFiles($this->getArticleId());
		$this->suppFileDao = &DAORegistry::getDAO('SuppFileDAO');
		$suppFiles = $this->suppFileDao->getSuppFilesByArticle($this->getArticleId());

		if ($suppFiles) {
			foreach ($suppFiles as $supp) {
				$journal = &Request::getJournal();
				$suppUrl = Request::url(null, 'article', 'downloadSuppFile', array($this->getArticleId(), $supp->getBestSuppFileId($journal)));

				$contents = preg_replace(
					'/href="' . preg_quote($supp->getOriginalFileName()) . '"/',
					'href="' . $suppUrl . '"',
					$contents
				);
			}
		}

		// if client encoding is set to iso-8859-1, transcode string to HTML entities
		// since we transform all XML in utf8 and can't rely on built-in PHP functions
		if (LOCALE_ENCODING == "iso-8859-1") $contents = &String::utf2html($contents);

		return $contents;
	}

	/**
	 * Output PDF generated from the XML/XSL/FO source to browser
	 * This function performs any necessary filtering, like image URL replacement.
	 * @return string
	 */
	function viewFileContents() {
		$pdfFileName = str_replace(FileManager::parseFileExtension($this->getFilePath()), 'pdf', $this->getFilePath());

		// if file does not exist or is outdated, regenerate it from FO
		if ( !FileManager::fileExists($pdfFileName) || filemtime($pdfFileName) < filemtime($this->getFilePath()) ) {

			// render XML into XSL-FO
			$cache =& $this->_getXSLTCache($this->getFileName() . '-' . $this->getGalleyId());

			$contents = $cache->getContents();
			if (!$contents) return false;		// if for some reason the XSLT failed, show original file

			// create temporary FO file and write the contents
			import('file.TemporaryFileManager');
			$temporaryFileManager = &new TemporaryFileManager();
			$tempFoName = $temporaryFileManager->filesDir . $this->getFileName() . '-' . $this->getGalleyId() . '.fo';

			$temporaryFileManager->writeFile($tempFoName, $contents);

			// perform %fo and %pdf replacements for fully-qualified shell command
			$journal = &Request::getJournal();
			$xmlGalleyPlugin = &PluginRegistry::getPlugin('generic', 'XMLGalleyPlugin');

			$fopCommand = str_replace(array('%fo', '%pdf'), 
					array($tempFoName, $pdfFileName), 
					$xmlGalleyPlugin->getSetting($journal->getJournalId(), 'externalFOP'));

			// check for safe mode and escape the shell command
			if( !ini_get('safe_mode') ) $fopCommand = escapeshellcmd($fopCommand);

			// run the shell command and get the results
			exec($fopCommand . ' 2>&1', $contents, $status);
			
			// if there is an error, spit out the shell results to aid debugging
			if ($status != false) {
				if ($contents != '') {
					foreach ($contents as $line) echo $line . "\n";
					$cache->flush();			// clear the XSL cache in case it's a FO error
					return true;
				} else return false;
			}

			// clear the temporary FO file
			FileManager::deleteFile($tempFoName);
		}

		// use FileManager to send file to browser
		FileManager::viewFile($pdfFileName, $this->getFileType());

		return true;
	}

	/**
	 * Return string containing the transformed XML output.
	 * This function applies an XSLT transform to a given XML source.
	 * @param $xmlFile pathname to the XML source file (absolute)
	 * @param $xslFile pathname to the XSL stylesheet (absolute)
	 * @param (optional) $xsltType type of XSLT renderer to use (PHP4, PHP5, or XSLT shell command)
	 * @param (optional) $arguments array of param-value pairs to pass to the XSLT  
	 * @return string
	 */
	function transformXSLT($xmlFile, $xslFile, $xsltType = "", $arguments = null) {
		// if either XML or XSL file don't exist, then fail without trying to process XSLT
		if (!FileManager::fileExists($xmlFile) || !FileManager::fileExists($xslFile)) return false;

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
				foreach ($arguments as $param => $value) {
					$proc->setParameter(null, $param, $value);
				}

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
