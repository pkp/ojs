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
		$data = &$this->parse(sprintf('help/%s/toc/%s.xml', Locale::getLocale(), Core::cleanFileVar($tocId)));

		if ($data === false) {
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
		
		return $toc;
	}
	
}

?>
