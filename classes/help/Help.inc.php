<?php

/**
 * Help.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 * 
 * Provides methods for translating help topic keys to their respected topic help id
 *
 * $Id$
 */

class Help {

	/**
	 * Constructor.
	 */
	function Help() {
	}
	
	/**
	 * Translate a help topic key to its numerical id.
	 * @param $key string
	 * @return string
	 */
	function translate($key) {
		static $mappings;
		
		// load help mappings
		if (!isset($mappings)) {
			$mappings = Help::loadHelpMappings();
		}

		$key = trim($key);
		if (empty($key)) {
			return '';
		}

		if (isset($mappings[$key])) {
			$helpId = $mappings[$key];
			return $helpId;

		} else {
			// Add some octothorpes to missing keys to make them more obvious
			return '##' . $key . '##';
		}
	}
	
	/**
	 * Load mappings of help page keys and their ids from an XML file (or cache, if available).
	 * @return array associative array of page keys and ids
	 */
	function &loadHelpMappings() {
		$mappings = array();

		$helpFile = "help/help.xml";
		$cacheFile = "help/cache/help.inc.php";

		if (file_exists($cacheFile) && filemtime($helpFile) < filemtime($cacheFile)) {
			// Load cached help file
			require($cacheFile);
			
		} else {

			// Reload help XML file
			$xmlDao = &new XMLDAO();
			$data = $xmlDao->parseStruct($helpFile, array('topic'));

			// Build associative array of page keys and ids
			if (isset($data['topic'])) {
				foreach ($data['topic'] as $helpData) {
					$mappings[$helpData['attributes']['key']] = $helpData['attributes']['id'];
				}
			}

			// Cache array
			if ((file_exists($cacheFile) && is_writable($cacheFile)) || (!file_exists($cacheFile) && is_writable(dirname($cacheFile)))) {
				$fp = fopen($cacheFile, 'w');
				if (function_exists('var_export')) {
					fwrite($fp, '<?php $mappings = ' . var_export($mappings, true) . '; ?>');				
				} else {
					fwrite($fp, '<?php $mappings = ' . $xmlDao->custom_var_export($mappings, true) . '; ?>');
				}				
				fclose($fp);
			}
		}

		return $mappings;	
	}
	
	/**
	 * Load table of contents from xml help topics and their tocs
	 * (return cache, if available)
	 * @return array associative array of topics and subtopics
	 */
	function getTableOfContents() {
		$helpToc = array();
		
		$helpDir = 'help/'.Locale::getLocale().'/.';
		$cacheFile = 'help/cache/helpToc.inc.php';
		
		if (file_exists($cacheFile) && Help::dirmtime($helpDir,true) < filemtime($cacheFile)) {
			require($cacheFile);
		} else {
			$topicId = 'index/topic/000000';

			$helpToc = Help::buildTopicSection($topicId);

			$xmlDao = &new XMLDAO();
			if ((file_exists($cacheFile) && is_writable($cacheFile)) || (!file_exists($cacheFile) && is_writable(dirname($cacheFile)))) {
				$fp = fopen($cacheFile, 'w');
				if (function_exists('var_export')) {
					fwrite($fp, '<?php $helpToc = ' . var_export($helpToc, true) . '; ?>');				
				} else {
					fwrite($fp, '<?php $helpToc = ' . $xmlDao->custom_var_export($helpToc, true) . '; ?>');
				}				
				fclose($fp);
			}
		}
		
		return Help::buildToc($helpToc);
	}

	/**
	 * Modifies retrieved array of topics and arranges them into toc
	 * @param $helpToc array
	 * @return array
	 */
	function buildToc($helpToc) {
	
		$toc = array();
		$num = 1;
		foreach($helpToc as $topicId => $section) {
			$toc[$topicId] = array('title' => $section['title'], 'num' => "$num. ");
			Help::buildTocHelper($toc, $section['section'], $num);
			$num++;
		}
		return $toc;
	}
	
	/**
	 * Helper method for buildToc
	 * @param $helpToc array
	 * @param $section array
	 * @param $num numbering of topic
	 */	
	function buildTocHelper(&$toc, $section, $num) {
		if (isset($section)) {
			$index = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$num";
			$counter = 1;
			foreach($section as $topicId => $sect) {
				$toc[$topicId] = array('title' => $sect['title'], 'num' => "$index.$counter ");
				Help::buildTocHelper($toc, $sect['section'], "$index.$counter");
				$counter++;
			}
		}
	}
		
	/**
	 * Helper method for getTableOfContents
	 * @param $topicId int
	 * @param $prevTocId int
	 * @return array
	 */
	function buildTopicSection($topicId, $prevTocId = null) {
		$topicDao = &DAORegistry::getDAO('HelpTopicDAO');
		$topic = $topicDao->getTopic($topicId);
		$tocId = $topic->getTocId();
		
		$section = array();
		if ($tocId != $prevTocId) {
			$tocDao = &DAORegistry::getDAO('HelpTocDAO');
			$toc = $tocDao->getToc($tocId);
			$topics = $toc->getTopics();
			foreach($topics as $currTopic) {
				$currId = $currTopic->getId();
				$currTitle = $currTopic->getTitle();
				if ($currId != $topicId) {
					$section[$currId] = array('title' => $currTitle, 'section' => Help::buildTopicSection($currId, $tocId)); 
				}
			}
		}
		if (empty($section)) {
			$section = null;
		}

		return $section;
	}
	
	/**
	 * Returns the most recent modified file in the specified directory
	 * Taken from the php.net site under filemtime
	 * @param $dirName string
	 * @param $doRecursive bool
	 * @return int
	 */
	function dirmtime($dirName,$doRecursive) {
	   $d = dir($dirName);
	   $lastModified = 0;
	   while($entry = $d->read()) {
	       if ($entry != "." && $entry != "..") {
	           if (!is_dir($dirName."/".$entry)) {
	               $currentModified = filemtime($dirName."/".$entry);
	           } else if ($doRecursive && is_dir($dirName."/".$entry)) {
	               $currentModified = Help::dirmtime($dirName."/".$entry,true);
	           }
	           if ($currentModified > $lastModified){
	               $lastModified = $currentModified;
	           }
	       }
	   }
	   $d->close();
	   return $lastModified;
	}	
}

?>
