<?php

/**
 * @file classes/xml/XMLComment.inc.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMLComment
 * @ingroup xml
 *
 * @brief Extension of XMLNode for a simple DOM-style comment.
 */

import ('lib.pkp.classes.xml.XMLNode');

class XMLComment extends XMLNode {

	/**
	 * Constructor.
	 * @param $name element/tag name
	 */
	function __construct() {
		parent::__construct();
		$this->name = '!--';
		$this->parent = null;
		$this->attributes = array();
		$this->value = null;
		$this->children = array();
	}

	/**
	 * @param $includeNamespace boolean
	 * @return string
	 */
	function getName($includeNamespace = true) {
		return false;
	}

	/**
	 * @param $name string
	 */
	function setName($name) {
		assert(false);
	}

	/**
	 * @return array all attributes
	 */
	function getAttributes() {
		return array();
	}

	/**
	 * @param $name string attribute name
	 * @return string attribute value
	 */
	function getAttribute($name) {
		return null;
	}

	/**
	 * @param $name string attribute name
	 * @param value string attribute value
	 */
	function setAttribute($name, $value) {
		assert(false);
	}

	/**
	 * @param $attributes array
	 */
	function setAttributes($attributes) {
		assert(false);
	}

	/**
	 * @return array this node's children (XMLNode objects)
	 */
	function &getChildren() {
		return array();
	}

	/**
	 * @param $name
	 * @param $index
	 * @return XMLNode the ($index+1)th child matching the specified name
	 */
	function &getChildByName($name, $index = 0) {
		$child = null;
		return $child;
	}

	/**
	 * Get the value of a child node.
	 * @param $name String name of node
	 * @param $index Optional integer index of child node to find
	 * @return string
	 */
	function &getChildValue($name, $index = 0) {
		$returner = null;
		return $returner;
	}

	/**
	 * @param $node XMLNode the child node to add
	 */
	function addChild(&$node) {
		assert(false);
	}
}
?>
