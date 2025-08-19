<?php

/**
 * @file plugins/importexport/native/filter/IssueGalleyNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueGalleyNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a set of issue galleys to a Native XML document
 */

use function PHP81_BC\strftime;

import('lib.pkp.plugins.importexport.native.filter.NativeExportFilter');

class IssueGalleyNativeXmlFilter extends NativeExportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		$this->setDisplayName('Native XML issue galley export');
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.IssueGalleyNativeXmlFilter';
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
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->preserveWhiteSpace = false;
		$doc->formatOutput = true;
		$deployment = $this->getDeployment();

		$rootNode = $doc->createElementNS($deployment->getNamespace(), 'issue_galleys');
		foreach ($issueGalleys as $issueGalley) {
			if ($issueGalleyNode = $this->createIssueGalleyNode($doc, $issueGalley)) {
				$rootNode->appendChild($issueGalleyNode);
			}
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
	 * @return ?DOMElement
	 */
	function createIssueGalleyNode($doc, $issueGalley) {
		// Create the root node and attributes
		$deployment = $this->getDeployment();
		$issueGalleyNode = $doc->createElementNS($deployment->getNamespace(), 'issue_galley');
		$issueGalleyNode->setAttribute('locale', $issueGalley->getLocale());
		$issueGalleyNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'label', htmlspecialchars($issueGalley->getLabel(), ENT_COMPAT, 'UTF-8')));

		$this->addIdentifiers($doc, $issueGalleyNode, $issueGalley);

		if (!$this->addFile($doc, $issueGalleyNode, $issueGalley)) {
			return null;
		}

		return $issueGalleyNode;
	}

	/**
	 * Add the issue file to its DOM element.
	 * @param $doc DOMDocument
	 * @param $issueGalleyNode DOMElement
	 * @param $issueGalley IssueGalley
	 */
	function addFile($doc, $issueGalleyNode, $issueGalley) {
		$issueFileDao = DAORegistry::getDAO('IssueFileDAO'); /** @var IssueFileDAO $issueFileDao  */
		$issueFile = $issueFileDao->getById($issueGalley->getFileId());

		if ($issueFile) {
			import('classes.file.IssueFileManager');
			$issueFileManager = new IssueFileManager($issueGalley->getIssueId());

			$filePath = $issueFileManager->getFilesDir() . '/' . $issueFileManager->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getServerFileName();
			$deployment = $this->getDeployment();
			if (!file_exists($filePath)) {
				$deployment->addWarning(ASSOC_TYPE_ISSUE_GALLEY, $issueGalley->getId(), __('plugins.importexport.common.error.issueGalleyFileMissing', ['id' => $issueGalley->getId(), 'path' => $filePath]));
				return false;
			}

			$issueFileNode = $doc->createElementNS($deployment->getNamespace(), 'issue_file');
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'file_name', htmlspecialchars($issueFile->getServerFileName(), ENT_COMPAT, 'UTF-8')));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'file_type', htmlspecialchars($issueFile->getFileType(), ENT_COMPAT, 'UTF-8')));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'file_size', $issueFile->getFileSize()));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'content_type', htmlspecialchars($issueFile->getContentType(), ENT_COMPAT, 'UTF-8')));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'original_file_name', htmlspecialchars($issueFile->getOriginalFileName(), ENT_COMPAT, 'UTF-8')));


			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_uploaded', strftime('%Y-%m-%d', strtotime($issueFile->getDateUploaded()))));
			$issueFileNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'date_modified', strftime('%Y-%m-%d', strtotime($issueFile->getDateModified()))));

			if ($this->opts['serializationMode'] !== self::SERIALIZATION_MODE_EMBED) {
				$request = Application::get()->getRequest();

				$fileUrl = ($this->opts['serializationMode'] === self::SERIALIZATION_MODE_URL)
					? $request->url(null, 'issue', 'view', array($issueGalley->getIssueId(), $issueGalley->getId()))
					: "{$request->getBasePath()}/{$filePath}";

				$hrefNode = $doc->createElementNS($deployment->getNamespace(), 'href');
				$hrefNode->setAttribute('src', htmlspecialchars($fileUrl, ENT_COMPAT, 'UTF-8'));
				$hrefNode->setAttribute('mime_type', $issueFile->getFileType());
				$issueFileNode->appendChild($hrefNode);
			} else {
				$embedNode = $doc->createElementNS($deployment->getNamespace(), 'embed', base64_encode(file_get_contents($filePath)));
				$embedNode->setAttribute('encoding', 'base64');
				$issueFileNode->appendChild($embedNode);
			}

			$issueGalleyNode->appendChild($issueFileNode);
			return true;
		}
		return false;
	}

	/**
	 * Create and add identifier nodes to an issue galley node.
	 * @param $doc DOMDocument
	 * @param $issueGalleyNode DOMElement
	 * @param $issueGalley IssueGalley
	 */
	function addIdentifiers($doc, $issueGalleyNode, $issueGalley) {
		$deployment = $this->getDeployment();

		// Add internal ID
		$issueGalleyNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', $issueGalley->getId()));
		$node->setAttribute('type', 'internal');
		$node->setAttribute('advice', 'ignore');

		// Add public ID
		if ($pubId = $issueGalley->getStoredPubId('publisher-id')) {
			$issueGalleyNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'id', htmlspecialchars($pubId, ENT_COMPAT, 'UTF-8')));
			$node->setAttribute('type', 'public');
			$node->setAttribute('advice', 'update');
		}
	}
}


