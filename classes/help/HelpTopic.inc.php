<?php

/**
 * HelpTopic.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 *
 * Help topic class.
 * A HelpTopic object is associated with a single HelpToc object and zero or more HelpTopicSection objects.
 *
 * $Id$
 */

class HelpTopic extends DataObject {

	/** The set of sections comprising this topic */
	var $sections;

	/** The set of related topics */
	var $relatedTopics;
	
	/**
	 * Constructor.
	 */
	function HelpTopic() {
		parent::DataObject();
		$this->sections = array();
		$this->relatedTopics = array();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get topic ID (a unique six-digit string).
	 * @return string
	 */
	function getId() {
		return $this->getData('id');
	}
	
	/**
	 * Set topic ID (a unique six-digit string).
	 * @param $id string
	 */
	function setId($id) {
		$this->setData('id', $id);
	}
	
	/**
	 * Get topic title.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set topic title.
	 * @param $title string
	 */
	function setTitle($title) {
		$this->setData('title', $title);
	}
	
	/**
	 * Get the ID of this topic's toc.
	 * @return string
	 */
	function getTocId() {
		return $this->getData('tocId');
	}
	
	/**
	 * Set the ID of this topic's toc.
	 * @param $tocId string
	 */
	function setTocId($tocId) {
		$this->setData('tocId', $tocId);
	}
	
	/**
	 * Get the ID of this topic's subtoc.
	 * @return string
	 */
	function getSubTocId() {
		return $this->getData('subTocId');
	}
	
	/**
	 * Set the ID of this topic's subtoc.
	 * @param $subTocId string
	 */
	function setSubTocId($subTocId) {
		$this->setData('subTocId', $subTocId);
	}
	
	/**
	 * Get the set of sections comprising this topic's contents.
	 * @return array the sections in order of appearance
	 */
	function &getSections() {
		return $this->sections;
	}
	
	/**
	 * Associate a section with this topic.
	 * Sections are added in the order they appear in the topic (i.e., FIFO).
	 * @param $section HelpTopicSection
	 */
	function addSection(&$section) {
		$this->sections[] = $section;
	}

	/**
	 * Get the set of related topics.
	 * @return array the related topics
	 */
	function &getRelatedTopics() {
		return $this->relatedTopics;
	}
	
	/**
	 * Add a related topic
	 * @param $section HelpTopicSection
	 */
	function addRelatedTopic(&$relatedTopic) {
		$this->relatedTopics[] = $relatedTopic;
	}

}

?>
