<?php

/**
 * XMLWriter.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package xml
 *
 * Wrapper class for writing XML documents using PHP 4.x or 5.x
 *
 * $Id$
 */

import ('xml.XMLNode');

class XMLWriter {
	/**
	 * Create a new XML document.
	 * If $url is set, the DOCTYPE definition is treated as a PUBLIC
	 * definition; $url should contain the ID, and $url should contain the
	 * URL. Otherwise, $dtd should be the DTD name.
	 */
	function &createDocument($type, $dtd, $url = null) {
		$version = '1.0';
		if (class_exists('DOMImplementation')) {
			// Use the new (PHP 5.x) DOM
			$impl = new DOMImplementation();
			$domdtd = $impl->createDocumentType($type, isset($url)?$dtd:'', isset($url)?$url:$dtd);
			$doc = $impl->createDocument($version, '', $domdtd);
		} else {
			// Use the XMLNode class
			$doc = new XMLNode();
			$doc->setAttribute('version', $version);
			$doc->setAttribute('type', $type);
			$doc->setAttribute('dtd', $dtd);
			$doc->setAttribute('url', $url);
		}
		return $doc;
	}

	function &createElement(&$doc, $name) {
		if (is_callable(array($doc, 'createElement'))) $element = &$doc->createElement($name);
		else $element = new XMLNode($name);

		return $element;
	}

	function &createTextNode(&$doc, $value) {
		if (is_callable(array($doc, 'createTextNode'))) $element = &$doc->createTextNode($value);
		else {
			$element = new XMLNode();
			$element->setValue($value);
		}

		return $element;
	}

	function &appendChild(&$parentNode, &$child) {
		if (is_callable(array($parentNode, 'appendChild'))) $node = &$parentNode->appendChild($child);
		else {
			$parentNode->addChild($child);
			$child->setParent($parentNode);
			$node = &$child;
		}

		return $node;
	}

	function &getAttribute(&$node, $name) {
		return $node->getAttribute($name);
	}

	function &hasAttribute(&$node, $name) {
		if (is_callable(array($node, 'hasAttribute'))) $value = &$node->hasAttribute($name);
		else {
			$attribute = &XMLWriter::getAttribute($node, $name);
			$value = ($attribute !== null);
		}
		return $value;
	}

	function setAttribute(&$node, $name, $value, $appendIfEmpty = true) {
		if (!$appendIfEmpty && $value == '') return;
		return $node->setAttribute($name, $value);
	}

	function &getXML(&$doc) {
		if (is_callable(array($doc, 'saveXML'))) $xml = &$doc->saveXML();
		else {
			$xml = $doc->toXml();
		}
		return $xml;
	}

	function printXML(&$doc) {
		if (is_callable(array($doc, 'saveXML'))) echo $doc->saveXML();
		else $doc->toXml(true);
	}

	function &createChildWithText(&$doc, &$node, $name, $value, $appendIfEmpty = true) {
		$childNode = null;
		if ($appendIfEmpty || $value != '') {
			$childNode = &XMLWriter::createElement($doc, $name);
			$textNode = &XMLWriter::createTextNode($doc, $value);
			XMLWriter::appendChild($childNode, $textNode);
			XMLWriter::appendChild($node, $childNode);
		}
		return $childNode;
	}

	function &createChildFromFile(&$doc, &$node, $name, $filename) {
		$contents = &FileManager::readFile($filename);
		if ($contents === false) return null;
	}
}

?>
