<?php

/**
 * XMLParserDOMHandler.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package xml
 *
 * Default handler for XMLParser returning a simple DOM-style object.
 * This handler parses an XML document into a tree structure of XMLNode objects.
 * 
 * $Id$
 */

class XMLParserDOMHandler extends XMLParserHandler {

	/** @var XMLNode reference to the root node */
	var $rootNode;
	
	/** @var XMLNode reference to the node currently being parsed */
	var $currentNode;
	
	/** @var reference to the current data */
	var $currentData;
	
	/**
	 * Constructor.
	 */
	function XMLParserHandler() {
		$this->rootNodes = array();
		$this->currentNode = null;
	}
	
	/**
	 * Callback function to act as the start element handler.
	 */
	function startElement(&$parser, $tag, $attributes) {
		$this->currentData = null;
		$node = &new XMLNode($tag);
		$node->setAttributes($attributes);
		
		if ($this->currentNode != null) {
			$this->currentNode->addChild($node);
			$node->setParent($this->currentNode);
			
		} else {
			$this->rootNode = &$node;
		}
		
		$this->currentNode = &$node;
	}
	
	/**
	 * Callback function to act as the end element handler.
	 */
	function endElement(&$parser, $tag) {
		$this->currentNode->setValue($this->currentData);
		$this->currentNode = &$this->currentNode->getParent();
		$this->currentData = null;
	}
	
	/**
	 * Callback function to act as the character data handler.
	 */
	function characterData(&$parser, $data) {
		$this->currentData .= $data;
	}
	
	/**
	 * Returns a reference to the root node of the tree representing the document.
	 * @return XMLNode
	 */
	function &getResult() {
		return $this->rootNode;
	}
	
}

/**
 * Structure representing a single node in an XML tree structure.
 */
class XMLNode {

	/** @var string the element (tag) name */
	var $name;
	
	/** @var XMLNode reference to the parent node (null if this is the root node) */
	var $parent;
	
	/** @var array the element's attributes */
	var $attributes;
	
	/** @var string the element's value */
	var $value;
	
	/** @var array references to the XMLNode children of this node */
	var $children;
	
	/**
	 * Constructor.
	 * @param $name element/tag name
	 */
	function XMLNode($name = null) {
		$this->name = $name;
		$this->parent = null;
		$this->attributes = array();
		$this->value = null;
		$this->children = array();
	}
	
	/**
	 * @return string
	 */
	function getName() {
		return $this->name;
	}
	
	/**
	 * @param $name string
	 */
	function setName($name) {
		$this->name = $name;
	}
	
	/**
	 * @return XMLNode
	 */
	function &getParent() {
		return $this->parent;
	}
	
	/**
	 * @param $parent XMLNode
	 */
	function setParent(&$parent) {
		$this->parent = &$parent;
	}
	
	/**
	 * @return array all attributes
	 */
	function getAttributes() {
		return $this->attributes;
	}
	
	/**
	 * @param $name string attribute name
	 * @return string attribute value
	 */
	function getAttribute($name) {
		return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
	}
	
	/**
	 * @param $attributes array
	 */
	function setAttributes($attributes) {
		$this->attributes = $attributes;
	}
	
	/**
	 * @return string
	 */
	function getValue() {
		return $this->value;
	}
	
	/**
	 * @param $value string
	 */
	function setValue($value) {
		$this->value = $value;
	}
	
	/**
	 * @return array this node's children (XMLNode objects)
	 */
	function &getChildren() {
		return $this->children;
	}

	/**
	 * @param $name
	 * @param $index
	 * @return XMLNode the ($index+1)th child matching the specified name
	 */
	function &getChildByName($name, $index = 0) {
		foreach ($this->children as $child) {
			if ($child->getName() == $name) {
				if ($index == 0) {
					return $child;
				} else {
					$index--;
				}
			}
		}
		return null;
	}
	
	/**
	 * @param $node XMLNode the child node to add
	 */
	function addChild(&$node) {
		array_push($this->children, &$node);
	}
	
}

?>
