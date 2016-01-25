<?php

/**
 * @file plugins/importexport/native/filter/IssueGalleyNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2000-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a set of issue galleys to a Native XML document
 */

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class IssueGalleyNativeXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function IssueGalleyNativeXmlFilter($filterGroup) {
		$this->setDisplayName('Native XML issue galley export');
		parent::NativeExportFilter($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'lib.pkp.plugins.importexport.native.filter.IssueGalleyNativeXmlFilter';
	}


	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::process()
	 * @param $issueGalleys array Array of issue galleys
	 * @return DOMDocument
	 */
	function &process(&$issueGalleys) {
		// Create the XML document
		$doc = new DOMDocument('1.0');
		$deployment = $this->getDeployment();

		$rootNode = $doc->createElementNS($deployment->getNamespace(), 'issue_galleys');
		foreach ($issueGalleys as $issueGalley) {
			$rootNode->appendChild($this->createIssueGalleyNode($doc, $issueGalley));
		}

		$doc->appendChild($rootNode);
		$rootNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$rootNode->setAttribute('xsi:schemaLocation', $deployment->getNamespace() . ' ' . $deployment->getSchemaFilename());

		return $doc;
	}

	//
	// Submission conversion functions
	//
	/**
	 * Create and return an issueGalley node.
	 * @param $doc DOMDocument
	 * @param $issueGalley IssueGalley
	 * @return DOMElement
	 */
	function createIssueGalleyNode($doc, $issueGalley) {
		// Create the root node and attributes
		$deployment = $this->getDeployment();
		$issueGalleyNode = $doc->createElementNS($deployment->getNamespace(), 'issue_galley');
		$issueGalleyNode->setAttribute('locale', $issueGalley->getLocale());
		$issueGalleyNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'label', $issueGalley->getLabel()));

		$this->addFile($doc, $issueGalleyNode, $issueGalley);

		return $issueGalleyNode;
	}

	/**
	 * Add the issue file to its DOM element.
	 * @param $doc DOMDocument
	 * @param $issueGalleyNode DOMElement
	 * @param $issueGalley IssueGalley
	 */
	function addFile($doc, $issueGalleyNode, $issueGalley) {
		$issueFileDao = DAORegistry::getDAO('IssueFileDAO');
		$issueFile = $issueFileDao->getById($issueGalley->getFileId());

		if ($issueFile) {
			$deployment = $this->getDeployment();
			$issueFileNode = $doc->createElementNS($deployment->getNamespace(), 'issue_file');
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'file_name', $issueFile->getServerFileName()));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'file_type', $issueFile->getContentType()));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'file_size', $issueFile->getFileSize()));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'content_type', $issueFile->getFileType()));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'original_file_name', $issueFile->getOriginalFileName()));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_uploaded', strftime('%F', strtotime($issueFile->getDateUploaded()))));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_modified', strftime('%F', strtotime($issueFile->getDateModified()))));

			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issueGalley->getIssueId());

			$filePath = $issueFileManager->getFilesDir() . '/' . $issueFileManager->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getServerFileName();
			$embedNode = $doc->createElementNS($deployment->getNamespace(), 'embed', base64_encode(file_get_contents($filePath)));
			$embedNode->setAttribute('encoding', 'base64');
			$issueFileNode->appendChild($embedNode);

			$issueGalleyNode->appendChild($issueFileNode);
		}
	}
}

?>
