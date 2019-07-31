<?php

/**
 * @file plugins/generic/metadataExport/metadataExportFormats/marcxml/MarcXMLMetadataExportPlugin.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MarcXMLMetadataExportPlugin
 * @ingroup plugins_generic_metadataExport_metadataExportFormats_marcxml
 *
 * @brief MarcXML metadata export format plugin
 */

import('plugins.generic.metadataExport.MetadataExportPlugin');
import('lib.pkp.classes.xml.XMLCustomWriter');

class MarcXMLMetadataExportPlugin extends MetadataExportPlugin {

	/**
	 * @copydoc MetadataExportPlugin::getName()
	 */
	function getName() {
		return 'MarcXMLMetadataExportPlugin';
	}

	/**
	 * @copydoc MetadataExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.metadataExportFormats.marcxml.displayName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getMetadataExportFormatName()
	 */
	function getMetadataExportFormatName() {
		return __('plugins.metadataExportFormats.marcxml.metadataExportFormatName');
	}

	/**
	 * @copydoc MetadataExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.metadataExportFormats.marcxml.description');
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
		return 'collection';
	}
	
	/**
	 * Namespace for a MarcXML document
	 * @return String
	 */
	function getMarcXmlNamespace() {
		return 'http://www.loc.gov/MARC21/slim';
	}
	
	/**
	 * Schema location for a record node
	 * @return String
	 */
	function getMarcXmlSchemaLocation() {
		return 'http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd';
	}
	
	/**
	 * Schema instance for a record node
	 * @return String
	 */
	function getMarcXmlSchemaInstance() {
		return 'http://www.w3.org/2001/XMLSchema-instance';
	}

	/**
	 * @copydoc MetadataExportPlugin::getFileContent()
	 */
	function getFileContent($journal, $articles) {
		$marcXML = XMLCustomWriter::createElement($this->doc, $this->getRootElement());
		$marcXML->setAttribute('xmlns', $this->getMarcXmlNamespace());
		
		foreach($articles as $article) {
			$this->_initData($journal, $article);
			
			$recordNode = XMLCustomWriter::createElement($this->doc, 'record'); 
			$recordNode->setAttribute('xsi:schemaLocation', $this->getMarcXmlSchemaLocation());
			$recordNode->setAttribute('xmlns:xsi', $this->getMarcXmlSchemaInstance());
			XMLCustomWriter::appendChild($marcXML, $recordNode); 
		
			$leaderField = XMLCustomWriter::createChildWithText($this->doc, $recordNode, 'leader', '     cam         3u     ', false);
			XMLCustomWriter::appendChild($recordNode, $leaderField);
			
			$this->_createNode($recordNode, $this->title, 245, 0, 0, 'a'); //title node
			$this->_createNode($recordNode, $this->creators, 720, null, null, 'a'); //creator nodes
			$this->_createNode($recordNode, $this->subjects, 653, null, null, 'a'); //subject nodes
			$this->_createNode($recordNode, $this->abstract, 520, null, null, 'a'); //abstract node
			$this->_createNode($recordNode, $this->publisher, 260, null, null, 'b'); //publisher node
			$this->_createNode($recordNode, $this->datePublished, 260, null, null, 'c'); //date node
			$this->_createNode($recordNode, $this->formats, 856, null, null, 'q'); //format nodes
			$this->_createNode($recordNode, $this->identifier, 856, 4, 0, 'u'); //identifier node
			$this->_createNode($recordNode, $this->sources, 786, 0, null, 'n'); //source nodes
			$this->_createNode($recordNode, $this->language, 546, null, null, 'a'); //language node
			$this->_createNode($recordNode, $this->relations, 787, 0, null, 'n'); //relation nodes
			$this->_createNode($recordNode, $this->copyright, 540, null, null, 'a'); //rights node

			XMLCustomWriter::appendChild($marcXML, $recordNode);
			$this->_unsetData();
		}

		XMLCustomWriter::appendChild($this->doc, $marcXML);
	}
	
	
	/**
	 * Create and append the MARC nodes
	 * @param $recordNode XMLCustomWriter object
	 * @param $value Mixed
	 * @param $tag Int
	 * @param $ind1 Int
	 * @param $ind2 Int
	 * @param $code String
	 */
	function _createNode($recordNode, $value, $tag, $ind1, $ind2, $code) {
		if (!is_array($value)) {
			$value = array($value);
		}
		
		foreach($value as $v) {
			if ($v) {
				$dataField = XMLCustomWriter::createElement($this->doc, 'datafield');
				$dataField->setAttribute('ind2', $ind2);
				$dataField->setAttribute('ind1', $ind1);
				$dataField->setAttribute('tag', $tag);
				
				$subField = XMLCustomWriter::createChildWithText($this->doc, $recordNode, 'subfield', $v, false);
				$subField->setAttribute('code', $code);
				
				XMLCustomWriter::appendChild($dataField, $subField);
				XMLCustomWriter::appendChild($recordNode, $dataField);
			}
		}
	}
}
?>