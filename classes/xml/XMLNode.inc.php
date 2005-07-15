<?php

/**
 * XMLNode.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package xml
 *
 * Default handler for XMLParser returning a simple DOM-style object.
 * This handler parses an XML document into a tree structure of XMLNode objects.
 * 
 * $Id$
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
	 * @param $name string attribute name
	 * @param value string attribute value
	 */
	function setAttribute($name, &$value) {
		$this->attributes[$name] = &$value;
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
	function &getValue() {
		return $this->value;
	}
	
	/**
	 * @param $value string
	 */
	function setValue(&$value) {
		$this->value = &$value;
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
		$this->children[] = &$node;
	}

	/**
	 * @param $output file handle to write to, or true for stdout, or null if XML to be returned as string
	 * @return string
	 */
	function &toXml($output = null) {
		$out = '';

		if ($this->parent === null) {
			// This is the root node. Output information about the document.
			$out .= "<?xml version=\"" . $this->getAttribute('version') . "\" encoding=\"UTF-8\"?>\n";
			$out .= "<!DOCTYPE " . $this->getAttribute('type') . " SYSTEM \"" . $this->getAttribute('dtd') . "\">";
		}

		if ($this->name !== null) {
			$out .= '<' . $this->name;
			foreach ($this->attributes as $name => $value) {
				$value = XMLNode::xmlentities($value);
				$out .= " $name=\"$value\"";
			}
			$out .= '>';
		}
		$out .= XMLNode::xmlentities($this->value, ENT_NOQUOTES);
		foreach ($this->children as $child) {
			if ($output !== null) {
				if ($output === true) echo $out;
				else fwrite ($output, $out);
				$out = '';
			}
			$out .= $child->toXml($output);
		}
		if ($this->name !== null) $out .= '</' . $this->name . '>';
		if ($output !== null) {
			if ($output === true) echo $out;
			else fwrite ($output, $out);
			return null;
		}
		return $out;
	}

	function xmlentities($string, $quote_style=ENT_QUOTES) {
		// from tmp1000 at fastmail dot deleteme dot fm and duke at redump dot de;
		// see http://ca3.php.net/htmlentities

		static $trans;
		if (!isset($trans)) {
		 	$trans = get_html_translation_table(HTML_ENTITIES, $quote_style);
		 	foreach ($trans as $key => $value)
			  	$trans[$key] = '&#'.ord($key).';';
		 	// dont translate the '&' in case it is part of &xxx;
		 	$trans[chr(38)] = '&';
		}
		// after the initial translation, _do_ map standalone '&' into '&#38;'
		return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,5};)/","&#38;" , strtr($string, $trans));
	}

}

?>
