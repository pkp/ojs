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

class XMLWriter {
	function &createDocument($type, $dtd) {
		$version = '1.0';
		if (class_exists('DOMImplementation')) {
			// Use the new (PHP 5.x) DOM
			$impl = new DOMImplementation();
			$domdtd = $impl->createDocumentType($type, '', $dtd);
			$doc = $impl->createDocument($version, '', $domdtd);
		} else {
			// Use the old (PHP 4.2) DOM
			// FIXME: This does not attach doctype information
			$doc = &domxml_new_doc($version);
		}
		return $doc;
	}

	function &createElement(&$doc, $name) {
		if (is_callable(array($doc, 'createElement'))) $element = &$doc->createElement($name);
		else $element = &$doc->create_element($name);
		return $element;
	}

	function &createTextNode(&$doc, $value) {
		if (is_callable(array($doc, 'createTextNode'))) $element = &$doc->createTextNode($value);
		else $element = &$doc->create_text_node(&$value);
		return $element;
	}

	function &appendChild(&$doc, &$child) {
		if (is_callable(array($doc, 'appendChild'))) $node = &$doc->appendChild($child);
		else $node = &$doc->append_child($child);
		return $node;
	}

	function &getAttribute(&$doc, $name) {
		if (is_callable(array($doc, 'getAttribute'))) $value = &$doc->getAttribute($name);
		else $value = &$doc->get_attribute($name);
		return $value;
	}

	function &hasAttribute(&$doc, $name) {
		if (is_callable(array($doc, 'hasAttribute'))) $value = &$doc->hasAttribute($name);
		else $value = &$doc->has_attribute($name);
		return $value;
	}

	function &setAttribute(&$doc, $name, $value, $appendIfEmpty = true) {
		if (!$appendIfEmpty && $value == '') return;
		if (is_callable(array($doc, 'setAttribute'))) $value = &$doc->setAttribute($name, $value);
		else $value = &$doc->set_attribute($name, $value);
		return $value;
	}

	function &getXML(&$doc) {
		if (is_callable(array($doc, 'saveXML'))) $xml = &$doc->saveXML();
		else $xml = &$doc->dump_mem();
		return $xml;
	}

	function &createChildWithText(&$doc, &$node, $name, $value, $appendIfEmpty = true) {
		if (!$appendIfEmpty && $value == '') return;
		$childNode = &XMLWriter::createElement(&$doc, $name);
		$textNode = &XMLWriter::createTextNode(&$doc, $value);
		XMLWriter::appendChild(&$childNode, &$textNode);
		XMLWriter::appendChild(&$node, &$childNode);
		return $childNode;
	}

	function &createChildFromFile(&$doc, &$node, $name, $filename) {
		$contents = &FileManager::readFile($filename);
		if ($contents === false) return null;
	}
}

?>
