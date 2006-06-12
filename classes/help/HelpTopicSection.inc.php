<?php

/**
 * HelpTopicSection.inc.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 *
 * Help section class, designated a subsection of a topic.
 * A HelpTopicSection is associated with a single HelpTopic.
 *
 * $Id$
 */

class HelpTopicSection extends DataObject {

	/**
	 * Constructor.
	 */
	function HelpTopicSection() {
		parent::DataObject();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get section title.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set section title.
	 * @param $title string
	 */
	function setTitle($title) {
		$this->setData('title', $title);
	}
	
	/**
	 * Get section content (assumed to be in HTML format).
	 * @return string
	 */
	function getContent() {
		return $this->getData('content');
	}
	
	/**
	 * Set section content.
	 * @param $content string
	 */
	function setContent($content) {
		$this->setData('content', $content);
	}
	
}

?>
