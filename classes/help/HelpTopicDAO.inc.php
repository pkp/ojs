<?php

/**
 * HelpTopicDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 *
 * Class for HelpTopic DAO.
 * Operations for retrieving HelpTopic objects.
 *
 * $Id$
 */

import('help.HelpTopic');

class HelpTopicDAO extends XMLDAO {

	/**
	 * Constructor.
	 */
	function HelpTopicDAO() {
		parent::XMLDAO();
	}

	function &_getCache($topicId) {
		static $cache;
		if (!isset($cache)) {
			import('cache.CacheManager');
			$cacheManager =& CacheManager::getManager();
			$cache =& $cacheManager->getFileCache('help-topic-' . Locale::getLocale(), $topicId, array($this, '_cacheMiss'));

			// Check to see if the cache info is outdated.
			$cacheTime = $cache->getCacheTime();
			if ($cacheTime !== null && $cacheTime < filemtime($this->getFilename($topicId))) {
				// The cached data is out of date.
				$cache->flush();
			}
		}
		return $cache;
	}

	function _cacheMiss(&$cache, $id) {
		static $data;
		if (!isset($data)) {
			$helpFile = $this->getFilename($cache->getCacheId());
			$data = &$this->parseStruct($helpFile);

			// check if data exists before saving it to cache
			if ($data === false) {
				$returner = false;
				return $returner;
			}
			$cache->setEntireCache($data);
		}
		return null;
	}
	
	function getFilename($topicId) {
		return sprintf('help/%s/%s.xml', Locale::getLocale(), $topicId);
	}

	/**
	 * Retrieve a topic by its ID.
	 * @param $topicId string
	 * @return HelpTopic
	 */
	function &getTopic($topicId) {
		$cache =& $this->_getCache($topicId);
		$data = $cache->getContents();

		// check if data exists after loading
		if (!is_array($data)) {
			$returner = false;
			return $returner;
		}

		$topic = &new HelpTopic();

		$topic->setId($data['topic'][0]['attributes']['id']);
		$topic->setTitle($data['topic'][0]['attributes']['title']);
		$topic->setTocId($data['topic'][0]['attributes']['toc']);
		if (isset($data['topic'][0]['attributes']['subtoc'])) {
			$topic->setSubTocId($data['topic'][0]['attributes']['subtoc']);
		}

		if (isset($data['section'])) {
			foreach ($data['section'] as $sectionData) {
				$section = &new HelpTopicSection();
				$section->setTitle(isset($sectionData['attributes']['title']) ? $sectionData['attributes']['title'] : null);
				$section->setContent($sectionData['value']);
				$topic->addSection($section);
			}
		}

		if (isset($data['related_topic'])) {
			foreach ($data['related_topic'] as $relatedTopic) {
				$relatedTopicArray = array('id' => $relatedTopic['attributes']['id'], 'title' => $relatedTopic['attributes']['title']);
				$topic->addRelatedTopic($relatedTopicArray);
			}
		}

		return $topic;
	}

	/**
	 * Returns a set of topics matching a specified keyword.
	 * @param $keyword string
	 * @return array matching HelpTopics
	 */
	function &getTopicsByKeyword($keyword) {
		$keyword = String::strtolower($keyword);
		$matchingTopics = array();
		$topicsDir = sprintf('help/%s', Locale::getLocale());
		$dir = opendir($topicsDir);
		while (($file = readdir($dir)) !== false) {
			$currFile = sprintf('%s/%s',$topicsDir,$file);
			if (is_dir($currFile) && $file != 'toc' && $file != '.' && $file != '..') {
				HelpTopicDAO::searchDirectory($matchingTopics,$keyword,$currFile);
			}
		}
		closedir($dir);

		krsort($matchingTopics);
		$topics = array_values($matchingTopics);

		return $topics;
	}

	/**
	 * Parses deeper into folders if subdirectories exists otherwise scans the topic xml files
	 * @param $matchingTopics array stores topics that match the keyword
	 * @param $keyword string
	 * @param $dir string
	 * @modifies $matchingTopics array by reference by making appropriate calls to functions
	 */
	function searchDirectory(&$matchingTopics,$keyword,$dir) {
		$currDir = opendir($dir);
		while (($file = readdir($currDir)) !== false) {
			$currFile = sprintf('%s/%s',$dir,$file);
			if (is_dir($currFile) && $file != '.' && $file != '..' && $file != 'toc') {
				HelpTopicDAO::searchDirectory($matchingTopics,$keyword,$currFile);
			} else {
				HelpTopicDAO::scanTopic($matchingTopics,$keyword,$dir,$file);
			}
		}
		closedir($currDir);
	}

	/**
	 * Scans topic xml files for keywords
	 * @param $matchingTopics array stores topics that match the keyword
	 * @param $keyword string
	 * @param $dir string
	 * @param $file string
	 * @modifies $matchingTopics array by reference
	 */
	function scanTopic(&$matchingTopics,$keyword,$dir,$file) {
		if (preg_match('/^\d{6,6}\.xml$/', $file)) {
			// remove the help/<locale> from directory path and use the latter half or url
			$url = split('/', str_replace('\\', '/', $dir), 3);
			$topicId = $url[2] . '/' . str_replace('.xml', '', $file);
			$topic = &$this->getTopic($topicId);
			
			if ($topic) {
				$numMatches = String::substr_count(String::strtolower($topic->getTitle()), $keyword);
				
				foreach ($topic->getSections() as $section) {
					$numMatches += String::substr_count(String::strtolower($section->getTitle()), $keyword);
					$numMatches += String::substr_count(String::strtolower($section->getContent()), $keyword);
				}
				
				if ($numMatches > 0) {
					$matchingTopics[($numMatches << 16) + count($matchingTopics)] = $topic;
				}
			}
		}
	}
}

?>
