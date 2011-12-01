<?php

/**
 * @file plugins/importexport/datacite/DataciteExportPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataciteExportPlugin
 * @ingroup plugins_importexport_datacite
 *
 * @brief DataCite export/registration plugin.
 */


import('plugins.importexport.datacite.classes.DoiExportPlugin');

// Additional export type.
define('DOI_EXPORT_SUPPFILES', 0x04);

class DataciteExportPlugin extends DoiExportPlugin {

	//
	// Constructor
	//
	function DataciteExportPlugin() {
		parent::DoiExportPlugin();
	}


	//
	// Implement template methods from ImportExportPlugin
	//
	/**
	 * @see ImportExportPlugin::getName()
	 */
	function getName() {
		return 'DataciteExportPlugin';
	}

	/**
	 * @see ImportExportPlugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.datacite.displayName');
	}

	/**
	 * @see ImportExportPlugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.datacite.description');
	}


	//
	// Implement template methods from DoiExportPlugin
	//
	/**
	 * @see DoiExportPlugin::getPluginId()
	 */
	function getPluginId() {
		return 'datacite';
	}

	/**
	 * @see DoiExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'DataciteSettingsForm';
	}

	/**
	 * @see DoiExportPlugin::getAllObjectIds()
	 */
	function getAllObjectIds() {
		$objectIdSpec = parent::getAllObjectIds();
		$objectIdSpec[DOI_EXPORT_SUPPFILES] = 'suppFileId';
		return $objectIdSpec;
	}

	/**
	 * @see DoiExportPlugin::handlePluginSpecificOps()
	 */
	function handlePluginSpecificOps($op, &$args, &$request, &$journal, $previousResult) {
		switch ($op) {
			case 'suppFiles':
				$this->_displaySuppFileList($journal);
				return $previousResult;

			case 'exportSuppFiles':
				return $this->exportObjects($request, array(DOI_EXPORT_SUPPFILES => $request->getUserVar('suppFileId')), $journal);

			case 'exportSuppFile':
				return $this->exportObjects($request, array(DOI_EXPORT_SUPPFILES => array_shift($args)), $journal);

			default:
				return parent::handlePluginSpecificOps($op, $request, $args, $journal, $previousResult);
		}
	}

	/**
	 * @see DoiExportPlugin::handlePluginSpecificCliOps()
	 */
	function handlePluginSpecificCliOps($request, $objectType, $objectIds, $journal, $xmlFile, $previousResult) {
		if($objectType == 'suppFiles') {
			return $this->exportObjects($request, array(DOI_EXPORT_SUPPFILES => $objectIds), $journal, $xmlFile);
		} else {
			return parent::handlePluginSpecificCliOps($request, $objectType, $objectIds, $journal, $xmlFile, $previousResult);
		}
	}

	/**
	 * @see DoiExportPlugin::displayAllUnregisteredObjects()
	 */
	function displayAllUnregisteredObjects(&$journal) {
		// Prepare information specific to this plug-in.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('suppFiles', $this->_getUnregisteredSuppFiles($journal));
		parent::displayAllUnregisteredObjects($journal);
	}

	/**
	 * @see DoiExportPlugin::getObjectName()
	 */
	function getObjectName($exportType) {
		if ($exportType == DOI_EXPORT_SUPPFILES) {
			return 'supp-file';
		} else {
			return parent::getObjectName($exportType);
		}
	}

	/**
	 * @see DoiExportPlugin::multipleObjectsPerExportFile()
	 */
	function multipleObjectsPerExportFile() {
		return false;
	}

	/**
	 * @see DoiExportPlugin::generateExportFile()
	 */
	function generateExportFile(&$request, $exportType, &$objects, $targetFilename, &$journal) {
		// Initialize local variables.
		$this->import('classes.DataciteExportDom');
		$targetPath = dirname($targetFilename) . '/';

		// Additional locale file.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));

		// Export objects one by one (DataCite does not allow
		// multiple objects per file).
		$exportFiles = array();
		foreach($objects as $object) {
			// Generate the export XML.
			$dom = new DataciteExportDom($request, $journal, $this->getCache());
			$doc =& $dom->generate($object);
			if ($doc === false) {
				$this->cleanTmpfiles($targetPath, $exportFiles);
				return $dom->getErrors();
			}

			// Identify the export filename.
			if (count($objects) > 1) {
				list($exportFile, $dummy) = $this->getTargetFileSpec($targetPath, $exportType, $object->getId());
				assert($dummy == DOI_EXPORT_FILE_XML);
			} else {
				$exportFile = $targetFilename;
			}

			// Write the result.
			file_put_contents($exportFile, XMLCustomWriter::getXML($doc));
			$exportFiles[] = $exportFile;
		}

		// If we have several files then package them up as a tar archive.
		if (count($exportFiles) > 1) {
			$this->tarFiles($targetPath, $targetFilename, $exportFiles);
			$this->cleanTmpfiles($targetPath, $exportFiles);
		}

		return true;
	}

	/**
	 * @see DoiExportPlugin::getDaoName()
	 */
	function getDaoName($exportType) {
		if ($exportType == DOI_EXPORT_SUPPFILES) {
			return array('SuppFileDAO', 'getSuppFile');
		} else {
			return parent::getDaoName($exportType);
		}
	}

	/**
	 * @see DoiExportPlugin::getObjectNotFoundErrorKey()
	 */
	function getObjectNotFoundErrorKey($exportType) {
		if ($exportType == DOI_EXPORT_SUPPFILES) {
			return 'plugins.importexport.datacite.export.error.suppFileNotFound';
		} else {
			return parent::getObjectNotFoundErrorKey($exportType);
		}
	}


	//
	// Private helper methods
	//
	/**
	 * Display a list of supplementary files for export.
	 *
	 * @param $journal Journal
	 */
	function _displaySuppFileList(&$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published articles.
		$articles = $this->getAllPublishedArticles($journal);

		// Retrieve supp file data.
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
		$suppFileData = array();
		while ($article =& $articles->next()) {
			// Retrieve supp files for the article.
			$suppFiles =& $suppFileDao->getSuppFilesByArticle($article->getId());
			foreach ($suppFiles as $suppFile) {
				$suppFileData[] =& $this->_prepareSuppFileData($suppFile, $journal);
				unset($suppFile);
			}
			unset($article);
		}

		// Paginate supp files.
		$totalSuppFiles = count($suppFileData);
		$rangeInfo = Handler::getRangeInfo('suppFiles');
		if ($rangeInfo->isValid()) {
			$suppFileData = array_slice($suppFileData, $rangeInfo->getCount() * ($rangeInfo->getPage()-1), $rangeInfo->getCount());
		}

		// Instantiate supp file iterator.
		import('lib.pkp.classes.core.VirtualArrayIterator');
		$iterator = new VirtualArrayIterator($suppFileData, $totalSuppFiles, $rangeInfo->getPage(), $rangeInfo->getCount());

		// Prepare and display the supp file template.
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign_by_ref('suppFiles', $iterator);
		$templateMgr->display($this->getTemplatePath() . 'suppFiles.tpl');
	}

	/**
	 * Retrieve all unregistered supplementary files and their corresponding issues and articles.
	 * @param $journal Journal
	 * @return array
	 */
	function &_getUnregisteredSuppFiles(&$journal) {
		// Retrieve all supp files that have not yet been registered.
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
		$suppFiles = $suppFileDao->getSuppFilesBySetting($this->getPluginId(). '::status', null, null, $journal->getId());

		// Retrieve issues and articles for supp files.
		$suppFileData = array();
		foreach ($suppFiles as $suppFile) {
			$suppFileData[] =& $this->_prepareSuppFileData($suppFile, $journal);
			unset($suppFile);
		}
		return $suppFileData;
	}

	/**
	 * Identify published article and issue of the given supp file.
	 * @param $suppFile SuppFile
	 * @param $journal Journal
	 * @return array
	 */
	function &_prepareSuppFileData(&$suppFile, &$journal) {
		// Retrieve article and issue for the supp file.
		$suppFileData =& $this->prepareArticleFileData($suppFile, $journal);

		// Add the supp file itself.
		$suppFileData['suppFile'] =& $suppFile;

		return $suppFileData;
	}
}

?>
