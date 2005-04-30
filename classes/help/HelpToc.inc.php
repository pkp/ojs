<?php

/**
 * HelpToc.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package help
 *
 * Help table of contents class.
 * A HelpToc object is associated with zero or more HelpTopic objects.
 *
 * $Id$
 */

class HelpToc extends DataObject {

	/** The list of topics belonging to this toc */
	var $topics;

	/** The list of breadcrumbs belonging to this toc */
	var $breadcrumbs;
	
	/**
	 * Constructor.
	 */
	function HelpToc() {
		parent::DataObject();
		$this->topics = array();
		$this->breadcrumbs = array();
	}
	
	//
	// Get/set methods
	//
	
	/**
	 * Get toc ID (a unique six-digit string).
	 * @return string
	 */
	function getId() {
		return $this->getData('id');
	}
	
	/**
	 * Set toc ID (a unique six-digit string).
	 * @param $id int
	 */
	function setId($id) {
		$this->setData('id', $id);
	}
	
	/**
	 * Get toc title.
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}
	
	/**
	 * Set toc title.
	 * @param $title string
	 */
	function setTitle($title) {
		$this->setData('title', $title);
	}
	
	/**
	 * Get the ID of the topic one-level up from this one.
	 * @return string
	 */
	function getParentTopicId() {
		return $this->getData('parentTopicId');
	}
	
	/**
	 * Set the ID of the topic one-level up from this one.
	 * @param $parentTopicId string
	 */
	function setParentTopicId($parentTopicId) {
		$this->setData('parentTopicId', $parentTopicId);
	}
	
	/**
	 * Get the set of topics in this table of contents.
	 * @return array the topics in order of appearance
	 */
	function &getTopics() {
		return $this->topics;
	}
	
	/**
	 * Associate a topic with this toc.
	 * Topics are added in the order they appear in the toc (i.e., FIFO).
	 * @param $topic HelpTopic
	 */
	function addTopic(&$topic) {
		$this->topics[] = $topic;
	}

	/**
	 * Get breadcrumbs.
	 * @return array
	 */
	function &getBreadcrumbs() {
		return $this->breadcrumbs;
	}
	
	/**
	 * Set breadcrumbs.
	 * @param $name string
	 * @param $url string
	 */
	function addBreadcrumb($name,$url) {
		$this->breadcrumbs[$name] = $url;
	}	
	
}

?>
