<?php

/**
 * @file XMLParserDOMHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package xml
 * @class XMLParserDOMHandler
 *
 * Default handler for XMLParser returning a simple DOM-style object.
 * This handler parses an XML document into a tree structure of XMLNode objects.
 * 
 * $Id$
 */

import('xml.XMLNode');

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

?>
