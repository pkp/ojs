<?php

/**
 * Help.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 * 
 * Provides methods for translating help topic keys to their respected topic
 * help ids.
 *
 * $Id$
 */

class Help {
	/** @var $mappingFiles array of HelpMappingFile objects */
	var $mappingFiles;

	/**
	 * Get an instance of the Help object.
	 */
	function &getHelp() {
		$instance =& Registry::get('help');
		if ($instance == null) {
			unset($instance);
			$instance = new Help();
			Registry::set('help', $instance);
		}
		return $instance;
	}

	/**
	 * Constructor.
	 */
	function Help() {
		import('help.OJSHelpMappingFile');
		$mainMappingFile =& new OJSHelpMappingFile();
		$this->mappingFiles = array();
		$this->addMappingFile($mainMappingFile);
	}

	function &getMappingFiles() {
		return $this->mappingFiles;
	}

	function addMappingFile(&$mappingFile) {
		$this->mappingFiles[] =& $mappingFile;
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

		$mappingFiles =& $this->getMappingFiles();
		for ($i=0; $i < count($mappingFiles); $i++) {
			// Not using foreach because it runs by value
			$mappingFile =& $mappingFiles[$i];
			$value = $mappingFile->map($key);
			if ($value !== null) return $value;
			unset($mappingFile);
		}

		if (!isset($value)) {
			return '##' . $key . '##';
		}
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
			if ($cacheTime !== null && $cacheTime < $this->dirmtime('help/'. $this->getLocale() . '/.', true)) {
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

		$result = null;
		if (HookRegistry::call('Help::_mappingCacheMiss', array(&$cache, &$id, &$mappings, &$result))) return $result;

		if (!isset($mappings)) {
			$mappings =& $this->loadHelpMappings();
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
			$helpToc = $this->buildTopicSection($topicId);
			$toc =& $this->buildToc($helpToc);

			$cache->setEntireCache($toc);
		}
		return null;
	}

	/**
	 * Load table of contents from xml help topics and their tocs
	 * (return cache, if available)
	 * @return array associative array of topics and subtopics
	 */
	function &getTableOfContents() {
		$cache =& $this->_getTocCache();
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
			$this->buildTocHelper($toc, $section['section'], '');
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
				$this->buildTocHelper($toc, $sect['section'], $prefix);
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
					$section[$currId] = array('title' => $currTitle, 'section' => $this->buildTopicSection($currId, $tocId)); 
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
					$currentModified = $this->dirmtime($dirName."/".$entry,true);
				}
				if ($currentModified > $lastModified) {
					$lastModified = $currentModified;
				}
			}
		}
		$d->close();
		return $lastModified;
	}

	function getSearchPaths() {
		$mappingFiles =& $this->getMappingFiles();
		$searchPaths = array();
		for ($i = 0; $i < count($mappingFiles); $i++) {
			$searchPaths[$mappingFiles[$i]->getSearchPath()] =& $mappingFiles[$i];
		}
		return $searchPaths;
	}
}

?>
