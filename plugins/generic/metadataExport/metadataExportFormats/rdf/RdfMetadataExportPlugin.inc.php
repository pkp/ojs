<?php

/**
 * @file plugins/generic/metadataExport/metadataExportFormats/rdf/RdfMetadataExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RdfMetadataExportPlugin
 * @ingroup plugins_generic_metadataExport_metadataExportFormats_rdf
 *
 * @brief RDF metadata export format plugin
 */

import('plugins.generic.metadataExport.MetadataExportPlugin');
import('lib.pkp.classes.xml.XMLCustomWriter');

class RdfMetadataExportPlugin extends MetadataExportPlugin {

	/**
	 * @copydoc MetadataExportPlugin::getName()
	 */
	function getName() {
		return 'RdfMetadataExportPlugin';
	}

	/**
	 * @copydoc MetadataExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.metadataExportFormats.rdf.displayName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getMetadataExportFormatName()
	 */
	function getMetadataExportFormatName() {
		return __('plugins.metadataExportFormats.rdf.metadataExportFormatName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.metadataExportFormats.rdf.description');
	}
	
	/**
	 * @copydoc MetadataExportPlugin::getFileExtension()
	 */
	function getFileExtension() {
		return 'xml';
	}
	
	/**
	 * @copydoc MetadataExportPlugin::getRootElement()
	 */
	function getRootElement() {
		return 'rdf:RDF';
	}
	
	/**
	 * Namespace for a RDF document
	 * @return String namespace
	 */
	function getRdfNamespace() {
		return 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
	}
	
	/**
	 * Namespace for a Dublin Core document
	 * @return String namespace
	 */
	function getDcNamespace() {
		return 'http://purl.org/dc/elements/1.1/';
	}
	
	/**
	 * "About" identifier for RDF document
	 * @return String about identifier
	 */
	function getRdfAbout() {
		return 'http://www.w3schools.com';
	}
	
	/**
	 * @copydoc MetadataExportPlugin::getFileContent()
	 */
	function getFileContent($journal, $articles) {
		$rdf = XMLCustomWriter::createElement($this->doc, $this->getRootElement());
		$rdf->setAttribute('xmlns:rdf', $this->getRdfNamespace());
		$rdf->setAttribute('xmlns:dc', $this->getDcNamespace());
		
		foreach($articles as $article) {
			$this->_initData($journal, $article);
			
			$descriptionNode = XMLCustomWriter::createElement($this->doc, 'rdf:Description');
			XMLCustomWriter::setAttribute($descriptionNode, 'rdf:about', $this->getRdfAbout());
			
			$this->_createNode($descriptionNode, 'dc:title', $this->title); //title node
			$this->_createNode($descriptionNode, 'dc:creator', $this->creators); //creator nodes
			$this->_createNode($descriptionNode, 'dc:subject', $this->subjects); //subject nodes
			$this->_createNode($descriptionNode, 'dc:description', $this->abstract); //description node (=abstract)
			$this->_createNode($descriptionNode, 'dc:publisher', $this->publisher); //publisher node
			$this->_createNode($descriptionNode, 'dc:contributor', $this->contributors); //contributor nodes
			$this->_createNode($descriptionNode, 'dc:date', $this->datePublished); //date node
			$this->_createNode($descriptionNode, 'dc:type', $this->types); //type nodes
			$this->_createNode($descriptionNode, 'dc:format', $this->formats); //format nodes
			$this->_createNode($descriptionNode, 'dc:identifier', $this->identifier); //identifier node
			$this->_createNode($descriptionNode, 'dc:source', $this->sources); //source nodes
			$this->_createNode($descriptionNode, 'dc:language', $this->language); //language node
			$this->_createNode($descriptionNode, 'dc:relation', $this->relations); //relation nodes
			$this->_createNode($descriptionNode, 'dc:rights', $this->copyright); //rights node
			
			XMLCustomWriter::appendChild($rdf, $descriptionNode);
			$this->_unsetData();
		}
		
		XMLCustomWriter::appendChild($this->doc, $rdf);
	}
	
	
	/**
	 * Create and append the single RDF nodes
	 * @param $descriptionNode XMLCustomWriter object
	 * @param $fieldName String
	 * @param $value Mixed
	 */
	function _createNode($descriptionNode, $fieldName, $value) {
		if (!is_array($value)) {
			$value = array($value);
		}
		
		foreach($value as $v) {
			if ($v) {
				$node = XMLCustomWriter::createChildWithText($this->doc, $descriptionNode, $fieldName, $v, false);
				XMLCustomWriter::appendChild($descriptionNode, $node);
			}
		}
	}
}
?>