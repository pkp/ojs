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

class HelpTopicDAO extends XMLDAO {

	/**
	 * Constructor.
	 */
	function HelpTopicDAO() {
		parent::XMLDAO();
	}
	
	/**
	 * Retrieve a topic by its ID.
	 * @param $topicId string
	 * @return HelpTopic
	 */
	function &getTopic($topicId) {
		$data = &$this->parseStruct(sprintf('help/%s/topic/%s.xml', Locale::getLocale(), Core::cleanFileVar($topicId)));

		if ($data === false) {
			return false;
		}
		
		$topic = &new HelpTopic();
		
		$topic->setId($data['topic'][0]['attributes']['id']);
		$topic->setTitle($data['topic'][0]['attributes']['title']);
		$topic->setTocId($data['topic'][0]['attributes']['toc']);
		
		if (isset($data['section'])) {
			foreach ($data['section'] as $sectionData) {
				$section = &new HelpTopicSection();
				$section->setTitle($sectionData['attributes']['title']);
				$section->setContent($sectionData['value']);
				$topic->addSection($section);
			}
		}
		
		return $topic;
	}
	
	/**
	 * Returns a set of topics matching a specified keyword.
	 * TODO: Should be replaced with something more efficient that was not written while inebriated.
	 * @param $keyword string
	 * @return array matching HelpTopics
	 */
	function &getTopicsByKeyword($keyword) {
		$keyword = strtolower($keyword);
		$matchingTopics = array();
		$topicsDir = sprintf('help/%s/topic', Locale::getLocale());
		
		$dir = opendir($topicsDir);
		while (($file = readdir($dir)) !== false) {
			if (preg_match('/^\d{6,6}\.xml$/', $file)) {
				// Only match actual text content
				$fileContents = preg_replace('/(<!\[CDATA\[)|(\]\]>)|(<[^>]*>)/', '', join('', file("$topicsDir/$file")));
				if (($numMatches = substr_count(strtolower($fileContents), $keyword)) > 0) {
					$matchingTopics[str_replace('.xml', '', $file)] = $numMatches;
				}
			}
		}
		closedir($dir);
		
		arsort($matchingTopics);
		
		$topics = array();
		
		foreach ($matchingTopics as $topicId => $numMatches) {
			$topics[] = &$this->getTopic($topicId);
		}
		
		return $topics;
	}
	
}

?>
