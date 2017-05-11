<?php

/**
 * @file plugins/importexport/native/filter/RepresentationNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RepresentationNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a representation to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class RepresentationNativeXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML representation export');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.native.filter.RepresentationNativeXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $representation Representation
	 * @return DOMDocument
	 */
	function &process(&$representation) {
		// Create the XML document
		$doc = new DOMDocument('1.0');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();
		$rootNode = $this->createRepresentationNode($doc, $representation);
		$doc->appendChild($rootNode);
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());

		return $doc;
	}

	//
	// Representation conversion functions
	//
	/**
	 * Create and return a representation node.
	 * @param $doc DOMDocument
	 * @param $representation Representation
	 * @return DOMElement
	 */
	function createRepresentationNode($doc, $representation) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();

		// Create the representation node
		$representationNode = $doc->createElementNS($deployment->getNamespace(), $deployment->getRepresentationNodeName());

		$this->addIdentifiers($doc, $representationNode, $representation);
		// Add metadata
		$this->createLocalizedNodes($doc, $representationNode, 'name', $representation->getName(null));
		$sequenceNode = $doc->createElementNS($deployment->getNamespace(), 'seq');
		$sequenceNode->appendChild($doc->createTextNode($representation->getSequence()));
		$representationNode->appendChild($sequenceNode);

		$remoteURL = $representation->getRemoteURL();
		if ($remoteURL) {
			$remoteNode = $doc->createElementNS($deployment->getNamespace(), 'remote');
			$remoteNode->setAttribute('src', $remoteURL);
			$representationNode->appendChild($remoteNode);
		} else {
			// Add files
			foreach ($this->getFiles($representation) as $submissionFile) {
				$fileRefNode = $doc->createElementNS($deployment->getNamespace(), 'submission_file_ref');
				$fileRefNode->setAttribute('id', $submissionFile->getFileId());
				$fileRefNode->setAttribute('revision', $submissionFile->getRevision());
				$representationNode->appendChild($fileRefNode);
			}
		}

		return $representationNode;
	}

	/**
	 * Create and add identifier nodes to a representation node.
	 * @param $doc DOMDocument
	 * @param $representationNode DOMElement
	 * @param $representation Representation
	 */
	function addIdentifiers($doc, $representationNode, $representation) {
		$deployment = $this->getDeployment();

		// Add internal ID
		$representationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $representation->getId()));
		$node->setAttribute('type', 'internal');
		$node->setAttribute('advice', 'ignore');

		// Add public ID
		if ($pubId = $representation->getStoredPubId('publisher-id')) {
			$representationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', htmlspecialchars($pubId, ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('type', 'public');
			$node->setAttribute('advice', 'update');
		}

		// Add pub IDs by plugin
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true, $deployment->getContext()->getId());
		foreach ((array) $pubIdPlugins as $pubIdPlugin) {
			$this->addPubIdentifier($doc, $representationNode, $representation, $pubIdPlugin);
		}
	}

	/**
	 * Add a single pub ID element for a given plugin to the representation.
	 * @param $doc DOMDocument
	 * @param $representationNode DOMElement
	 * @param $representation Representation
	 * @param $pubIdPlugin PubIdPlugin
	 * @return DOMElement|null
	 */
	function addPubIdentifier($doc, $representationNode, $representation, $pubIdPlugin) {
		$pubId = $representation->getStoredPubId($pubIdPlugin->getPubIdType());
		if ($pubId) {
			$deployment = $this->getDeployment();
			$representationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', htmlspecialchars($pubId, ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('type', $pubIdPlugin->getPubIdType());
			$node->setAttribute('advice', 'update');
			return $node;
		}
		return null;
	}

	//
	// Abstract methods to be implemented by subclasses
	//
	/**
	 * Get the submission files associated with this representation
	 * @param $representation Representation
	 * @return array
	 */
	function getFiles($representation) {
		assert(false); // To be overridden by subclasses
	}
}

?>
