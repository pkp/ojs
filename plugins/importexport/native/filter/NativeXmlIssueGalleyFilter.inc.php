<?php

/**
 * @file plugins/importexport/native/filter/NativeXmlIssueGalleyFilter.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2000-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeXmlIssueGalleyFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Base class that converts a Native XML document to a set of issue galleys
 */

import('lib.pkp.plugins.importexport.native.filter.NativeImportFilter');

class NativeXmlIssueGalleyFilter extends NativeImportFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function NativeXmlIssueGalleyFilter($filterGroup) {
		$this->setDisplayName('Native XML issue galley import');
		parent::NativeImportFilter($filterGroup);
	}

	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.NativeXmlIssueGalleyFilter';
	}


	/**
	 * Handle a submission element
	 * @param $node DOMElement
	 * @return array Array of Representation objects
	 */
	function handleElement($node) {
		$deployment = $this->getDeployment();
		$context = $deployment->getContext();
		$issue = $deployment->getIssue();
		assert(is_a($issue, 'Issue'));

		// Create the data object
		$issueGalleyDao  = DAORegistry::getDAO('IssueGalleyDAO');
		$issueGalley = $issueGalleyDao->newDataObject();
		$issueGalley->seIssueId($issue->getId());
		$issueGalley->setLocale($node->getAttribute('locale'));
		$issueGalley->setSequence($issueGalleyDao->getNextGalleySequence($issue->getId()));

		// Handle metadata in subelements.
		for ($n = $node->firstChild; $n !== null; $n=$n->nextSibling) if (is_a($n, 'DOMElement')) switch($n->tagName) {
			case 'label': $issueGalley->setLabel($n->textContent); break;
			case 'issue_file':
				$issueFileDao = DAORegistry::getDAO('IssueFileDAO');
				$issueFile = $issueFileDao->newDataObject();
				$issueFile->setIssueId($issue->getId());

				for ($o = $n->firstChild; $o !== null; $o=$o->nextSibling) if (is_a($o, 'DOMElement')) switch($o->tagName) {
					case 'file_name': $issueFile->setServerFileName($o->textContent); break;
					case 'file_type': $issueFile->setFileType($o->textContent); break;
					case 'file_size': $issueFile->setFileSize($o->textContent); break;
					case 'content_type': $issueFile->setContentType($o->textContent); break;
					case 'original_file_name': $issueFile->setOriginalFileName($o->textContent); break;
					case 'date_uploaded': $issueFile->setDateUploaded($o->textContent); break;
					case 'date_modified': $issueFile->setDateModified($o->textContent); break;
					case 'embed':
						import('classes.file.IssueFileManager');
						$issueFileManager = new IssueFileManager($issue->getId());
						$filePath = $issueFileManager->getFilesDir() . '/' . $issueFileManager->contentTypeToPath($issueFile->getContentType()) . '/' . $issueFile->getServerFileName();
						file_put_contents($filePath, base64_decode($o->textContent));
						break;
				}
			break;
			$issueFileId = $issueFileDao->insertObject($issueFile);
			$issueGalley->setFileId($issueFileId);
		}

		return $issueGalley;
	}
}

?>
