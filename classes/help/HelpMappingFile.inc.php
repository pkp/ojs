<?php

/**
 * @file HelpMappingFile.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 * @class HelpMappingFile
 * 
 * Abstracts a Help mapping XML file.
 *
 * $Id$
 */

class HelpMappingFile {
	/** @var $filename string */
	var $filename;
	var $cache;

	/**
	 * Constructor.
	 */
	function HelpMappingFile($filename) {
		$this->filename = $filename;
	}

	function &_getCache() {
		if (!isset($this->cache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$this->cache = $cacheManager->getFileCache(
				'helpmap', md5($this->filename),
				array(&$this, '_cacheMiss')
			);

			// Check to see if the cache info is outdated.
			$cacheTime = $this->cache->getCacheTime();
			if ($cacheTime !== null && file_exists($this->filename) && $cacheTime < filemtime($this->filename)) {
				// The cached data is out of date.
				$this->cache->flush();
			}
		}
		return $this->cache;
	}

	function _cacheMiss(&$cache, $id) {
		$mappings = array();

		// Add a debug note indicating an XML load.
		$notes =& Registry::get('system.debug.notes');
		$notes[] = array('debug.notes.helpMappingLoad', array('id' => $id, 'filename' => $this->filename));

		// Reload help XML file
		$xmlDao =& new XMLDAO();
		$data = $xmlDao->parseStruct($this->filename, array('topic'));
		// Build associative array of page keys and ids
		if (isset($data['topic'])) {
			foreach ($data['topic'] as $helpData) {
				$mappings[$helpData['attributes']['key']] = $helpData['attributes']['id'];
			}
		}

		$cache->setEntireCache($mappings);
		return isset($mappings[$id])?$mappings[$id]:null;
	}

	function map($key) {
		$cache =& $this->_getCache();
		return $cache->get($key);
	}

	function containsToc($tocId) {
		return file_exists($this->getTocFilename($tocId));
	}

	function containsTopic($topicId) {
		return file_exists($this->getTopicFilename($topicId));
	}

	/**
	 * This is an abstract function that should be implemented by
	 * subclasses.
	 */
	function getTocFilename($tocId) {
		fatalError('HelpMappingFile::getTocFilename should be overridden');
	}

	/**
	 * This is an abstract function that should be implemented by
	 * subclasses.
	 */
	function getTopicFilename($topicId) {
		fatalError('HelpMappingFile::getTopicFilename should be overridden');
	}

	/**
	 * This is an abstract function that should be implemented by
	 * subclasses.
	 */
	function getSearchPath($locale = null) {
		fatalError('HelpMappingFile::getSearchPath should be overridden');
	}

	/**
	 * This is an abstract function that should be implemented by
	 * subclasses.
	 */
	function getTopicIdForFilename($filename) {
		fatalError('HelpMappingFile::getSearchPath should be overridden');
	}
}

?>
