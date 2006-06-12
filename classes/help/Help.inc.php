<?php

/**
 * Help.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
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
	 * Get the locale to display help files in.
	 * If help isn't available for the current locale,
	 * defaults to en_US.
	 */
	function getLocale() {
		$locale = Locale::getLocale();
		if (!file_exists("help/$locale/.")) {
			return 'en_US';
		}
		return $locale;
	}

	/**
	 * Translate a help topic key to its numerical id.
	 * @param $key string
	 * @return string
	 */
	function translate($key) {
		$key = trim($key);
		if (empty($key)) {
			return '';
		}

		$cache =& Help::_getMappingCache();
		$value = $cache->get($key);
		if (!isset($value)) {
			return '##' . $key . '##';
		}
		return $value;
	}

	function &_getMappingCache() {
		static $cache;

		if (!isset($cache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$cache =& $cacheManager->getFileCache(
				'help', 'mapping',
				array('Help', '_mappingCacheMiss')
			);

			// Check to see if the cache info is outdated.
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime(Help::getMappingFilename())) {
				// The cached data is out of date.
				$cache->flush();
			}
		}
		return $cache;
	}

	function &_getTocCache() {
		static $cache;

		if (!isset($cache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$cache =& $cacheManager->getFileCache(
				'help', 'toc',
				array('Help', '_tocCacheMiss')
			);

			// Check to see if the cache info is outdated.
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < Help::dirmtime('help/'.Help::getLocale().'/.', true)) {
				// The cached data is out of date.
				$cache->flush();
			}
		}
		return $cache;
	}

	function _mappingCacheMiss(&$cache, $id) {
		// Keep a secondary cache of the mappings so that a few
		// cache misses won't destroy the server
		static $mappings;
		if (!isset($mappings)) {
			$mappings =& Help::loadHelpMappings();
			$cache->setEntireCache($mappings);
		}
		return isset($mappings[$id])?$mappings[$id]:null;
	}

	function _tocCacheMiss(&$cache, $id) {
		// Keep a secondary cache of the TOC so that a few
		// cache misses won't destroy the server
		static $toc;
		if (!isset($toc)) {
			$helpToc = array();
			$topicId = 'index/topic/000000';
			$helpToc = Help::buildTopicSection($topicId);
			$toc =& Help::buildToc($helpToc);

			$cache->setEntireCache($toc);
		}
		return null;
	}

	function getMappingFilename() {
		return 'help/help.xml'; break;
	}

	/**
	 * Load mappings of help page keys and their ids from an XML file
	 * @return array associative array of page keys and ids
	 */
	function &loadHelpMappings() {
		$mappings = array();

		// Reload help XML file
		$xmlDao = &new XMLDAO();
		$data = $xmlDao->parseStruct(Help::getMappingFilename(), array('topic'));

		// Build associative array of page keys and ids
		if (isset($data['topic'])) {
			foreach ($data['topic'] as $helpData) {
				$mappings[$helpData['attributes']['key']] = $helpData['attributes']['id'];
			}
		}

		return $mappings;	
	}
	
	/**
	 * Load table of contents from xml help topics and their tocs
	 * (return cache, if available)
	 * @return array associative array of topics and subtopics
	 */
	function &getTableOfContents() {
		$cache =& Help::_getTocCache();
		return $cache->getContents();
	}

	/**
	 * Modifies retrieved array of topics and arranges them into toc
	 * @param $helpToc array
	 * @return array
	 */
	function &buildToc($helpToc) {
	
		$toc = array();
		foreach($helpToc as $topicId => $section) {
			$toc[$topicId] = array('title' => $section['title'], 'prefix' => '');
			Help::buildTocHelper($toc, $section['section'], '');
		}
		return $toc;
	}
	
	/**
	 * Helper method for buildToc
	 * @param $helpToc array
	 * @param $section array
	 * @param $num numbering of topic
	 */	
	function buildTocHelper(&$toc, $section, $prefix) {
		if (isset($section)) {
			$prefix = "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$prefix";
			foreach($section as $topicId => $sect) {
				$toc[$topicId] = array('title' => $sect['title'], 'prefix' => $prefix);
				Help::buildTocHelper($toc, $sect['section'], $prefix);
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
		if ($topicId == 'index/topic/000000') {
			$tocId = $topic->getTocId();
		} else {
			$tocId = $topic->getSubTocId();
		}
		
		$section = array();
		if ($tocId && $tocId != $prevTocId) {
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
