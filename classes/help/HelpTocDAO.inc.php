<?php

/**
 * HelpTocDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 *
 * Class for HelpToc DAO.
 * Operations for retrieving HelpToc objects.
 *
 * $Id$
 */

class HelpTocDAO extends XMLDAO {

	/**
	 * Constructor.
	 */
	function HelpTocDAO() {
		parent::XMLDAO();
	}
	
	/**
	 * Retrieves a toc by its ID.
	 * @param $tocId string
	 * @return HelpToc
	 */
	function &getToc($tocId) {

		$helpFile = sprintf('help/%s/%s.xml', Locale::getLocale(), $tocId);
		$cacheFile = sprintf('help/%s/cache/%s.inc.php', Locale::getLocale(), str_replace('/','.',$tocId));

		// if available, load up cache of this table of contents otherwise load xml file
		if (file_exists($cacheFile) && filemtime($helpFile) < filemtime($cacheFile)) {
			require($cacheFile);

		} else {
			$data = &$this->parseStruct($helpFile);

			// check if data exists before saving it to cache
			if ($data === false) {
				return false;
			}			

			// Cache array
			if ((file_exists($cacheFile) && is_writable($cacheFile)) || (!file_exists($cacheFile) && is_writable(dirname($cacheFile)))) {
				$fp = fopen($cacheFile, 'w');
				if (function_exists('var_export')) {
					fwrite($fp, '<?php $data = ' . var_export($data, true) . '; ?>');
				} else {
					fwrite($fp, '<?php $data = ' . $this->custom_var_export($data, true) . '; ?>');				
				}
				fclose($fp);
			}			
		}	
		
		// check if data exists after loading
		if (!is_array($data)) {
			return false;
		}
		
		$toc = &new HelpToc();
		
		$toc->setId($data['toc'][0]['attributes']['id']);
		$toc->setTitle($data['toc'][0]['attributes']['title']);
		if (isset($data['toc'][0]['attributes']['prev_topic'])) {
			$toc->setPrevTopicId($data['toc'][0]['attributes']['prev_topic']);
		}
		
		if (isset($data['topic'])) {
			foreach ($data['topic'] as $topicData) {
				$topic = &new HelpTopic();
				$topic->setId($topicData['attributes']['id']);
				$topic->setTitle($topicData['attributes']['title']);
				$toc->addTopic($topic);
			}
		}

		if (isset($data['breadcrumb'])) {
			foreach ($data['breadcrumb'] as $breadcrumbData) {
				$toc->addBreadcrumb($breadcrumbData['value'], $breadcrumbData['attributes']['url']);
			}
		}
		
		return $toc;
	}	
}

?>
