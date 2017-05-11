<?php

/**
 * @file classes/xml/XMLCustomWriter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMLCustomWriter
 * @ingroup xml
 *
 * @brief Wrapper class for writing XML documents using PHP 4.x or 5.x
 */


import ('lib.pkp.classes.xml.XMLNode');
import ('lib.pkp.classes.xml.XMLComment');

class XMLCustomWriter {
	/**
	 * Create a new XML document.
	 * If $url is set, the DOCTYPE definition is treated as a PUBLIC
	 * definition; $dtd should contain the ID, and $url should contain the
	 * URL. Otherwise, $dtd should be the DTD name.
	 * @param $type string
	 * @param $dtd string
	 * @param $url string
	 * @return DOMDocument|XMLNode
	 */
	static function &createDocument($type = null, $dtd = null, $url = null) {
		$version = '1.0';
		if (class_exists('DOMImplementation')) {
			// Use the new (PHP 5.x) DOM
			$impl = new DOMImplementation();
			// only generate a DOCTYPE if type is non-empty
			if ($type != '') {
				$domdtd = $impl->createDocumentType($type, isset($url)?$dtd:'', isset($url)?$url:$dtd);
				$doc = $impl->createDocument($version, '', $domdtd);
			} else {
				$doc = $impl->createDocument($version, '');
			}
			// ensure we are outputting UTF-8
			$doc->encoding = 'UTF-8';
		} else {
			// Use the XMLNode class
			$doc = new XMLNode();
			$doc->setAttribute('version', $version);
			if ($type !== null) $doc->setAttribute('type', $type);
			if ($dtd !== null) $doc->setAttribute('dtd', $dtd);
			if ($url !== null) $doc->setAttribute('url', $url);
		}
		return $doc;
	}

	/**
	 * Create a new element.
	 * @param $doc XMLNode|DOMDocument
	 * @param $name string
	 * @return XMLNode
	 */
	static function &createElement(&$doc, $name) {
		if (is_callable(array($doc, 'createElement'))) $element = $doc->createElement($name);
		else $element = new XMLNode($name);

		return $element;
	}

	/**
	 * Create a new comment.
	 * @param $doc XMLNode|DOMDocument
	 * @param $content string
	 * @return XMLNode
	 */
	static function &createComment(&$doc, $content) {
		if (is_callable(array($doc, 'createComment'))) {
			$element =& $doc->createComment($content);
		} else {
			$element = new XMLComment();
			$element->setValue($content);
		}

		return $element;
	}

	/**
	 * Create a new text node.
	 * @param $doc XMLNode|DOMDocument
	 * @param $value string
	 * @return XMLNode
	 */
	static function &createTextNode(&$doc, $value) {

		$value = Core::cleanVar($value);

		if (is_callable(array($doc, 'createTextNode'))) $element = $doc->createTextNode($value);
		else {
			$element = new XMLNode();
			$element->setValue($value);
		}

		return $element;
	}

	/**
	 * Add a child to the DOM tree.
	 * @param $parentNode XMLNode
	 * @param $child XMLNode $doc XMLNode
	 * @return XMLNode
	 */
	static function &appendChild(&$parentNode, &$child) {
		if (is_callable(array($parentNode, 'appendChild'))) $node = $parentNode->appendChild($child);
		else {
			$parentNode->addChild($child);
			$child->setParent($parentNode);
			$node =& $child;
		}

		return $node;
	}

	/**
	 * Get the value of an attribute from a node.
	 * @param $node XMLNode
	 * @param $name string
	 * @return string
	 */
	static function &getAttribute(&$node, $name) {
		return $node->getAttribute($name);
	}

	/**
	 * Determine whether a node has a named attribute.
	 * @param $node XMLNode
	 * @param $name string
	 * @return boolean
	 */
	static function &hasAttribute(&$node, $name) {
		if (is_callable(array($node, 'hasAttribute'))) $value =& $node->hasAttribute($name);
		else {
			$attribute =& XMLCustomWriter::getAttribute($node, $name);
			$value = ($attribute !== null);
		}
		return $value;
	}

	/**
	 * Set an attribute on a node.
	 * @param $node XMLNode
	 * @param $name string
	 * @param $value string
	 * @param $appendIfEmpty boolean True iff empty attributes should be added anyway.
	 * @return string
	 */
	static function setAttribute(&$node, $name, $value, $appendIfEmpty = true) {
		if (!$appendIfEmpty && $value == '') return;
		return $node->setAttribute($name, $value);
	}

	/**
	 * Get the serialized XML for a document.
	 * @param $doc DOMDocument|XMLNode
	 * @return string
	 */
	static function &getXML(&$doc) {
		if (is_callable(array($doc, 'saveXML'))) $xml = $doc->saveXML();
		else {
			$xml = $doc->toXml();
		}
		return $xml;
	}

	/**
	 * Print the serialized XML for a document.
	 * @param $doc DOMDocument|XMLNode
	 * @return string
	 */
	static function printXML(&$doc) {
		if (is_callable(array($doc, 'saveXML'))) echo $doc->saveXML();
		else $doc->toXml(true);
	}

	/**
	 * Add a child node with the specified text contents.
	 * @param $doc DOMDocument|XMLNode
	 * @param $node XMLNode
	 * @param $name string
	 * @param $value string
	 * @param $appendIfEmpty boolean True iff empty attributes should be added anyway.
	 * @return XMLNode
	 */
	static function &createChildWithText(&$doc, &$node, $name, $value, $appendIfEmpty = true) {
		$childNode = null;
		if ($appendIfEmpty || $value != '') {
			$childNode =& XMLCustomWriter::createElement($doc, $name);
			$textNode =& XMLCustomWriter::createTextNode($doc, $value);
			XMLCustomWriter::appendChild($childNode, $textNode);
			XMLCustomWriter::appendChild($node, $childNode);
		}
		return $childNode;
	}
}

?>
