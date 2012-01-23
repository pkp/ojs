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

// DataCite API
define('DATACITE_API_RESPONSE_OK', 201);
define('DATACITE_API_URL', 'https://mds.datacite.org/');

// Test DOI prefix
define('DATACITE_API_TESTPREFIX', '10.5072');

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
	 * @see DoiExportPlugin::getAllObjectTypes()
	 */
	function getAllObjectTypes() {
		$objectTypes = parent::getAllObjectTypes();
		$objectTypes['suppFile'] = DOI_EXPORT_SUPPFILES;
		return $objectTypes;
	}

	/**
	 * @see DoiExportPlugin::displayAllUnregisteredObjects()
	 */
	function displayAllUnregisteredObjects(&$templateMgr, &$journal) {
		// Prepare information specific to this plug-in.
		$templateMgr->assign_by_ref('suppFiles', $this->_getUnregisteredSuppFiles($journal));
		parent::displayAllUnregisteredObjects($templateMgr, $journal);
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
	 * @see DoiExportPlugin::displaySuppFileList()
	 */
	function displaySuppFileList(&$templateMgr, &$journal) {
		$this->setBreadcrumbs(array(), true);

		// Retrieve all published articles.
		$articles = $this->getAllPublishedArticles($journal);

		// Retrieve supp file data.
		$this->registerDaoHook('SuppFileDAO');
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
		$templateMgr->assign_by_ref('suppFiles', $iterator);
		$templateMgr->display($this->getTemplatePath() . 'suppFiles.tpl');
	}

	/**
	 * @see DoiExportPlugin::generateExportFiles()
	 */
	function generateExportFiles(&$request, $exportType, &$objects, $targetPath, &$journal, &$errors) {
		// Additional locale file.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_OJS_EDITOR));

		// Export objects one by one (DataCite does not allow
		// multiple objects per file).
		$this->import('classes.DataciteExportDom');
		$exportFiles = array();
		foreach($objects as $object) {
			// Generate the export XML.
			$dom = new DataciteExportDom($request, $this, $journal, $this->getCache());
			$doc =& $dom->generate($object);
			if ($doc === false) {
				$this->cleanTmpfiles($targetPath, array_keys($exportFiles));
				$errors =& $dom->getErrors();
				return false;
			}

			// Write the result.
			$exportFile = $this->getTargetFileName($targetPath, $exportType, $object->getId());
			file_put_contents($exportFile, XMLCustomWriter::getXML($doc));
			$fileManager = new FileManager();
			$fileManager->setMode($exportFile, FILE_MODE_MASK);
			$exportFiles[$exportFile] = array(&$object);
			unset($object);
		}

		return $exportFiles;
	}

	/**
	 * @see DoiExportPlugin::registerDoi()
	 */
	function registerDoi(&$request, &$journal, &$objects, $file) {
		// DataCite should always export exactly
		// one object per meta-data file.
		assert(count($objects) == 1);
		$object =& $objects[0];

		// Get the DOI and the URL for the object.
		$doi = $object->getPubId('doi');
		assert(!empty($doi));
		if ($this->isTestMode($request)) {
			$doi = String::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $doi);
		}
		$url = $this->_getObjectUrl($request, $journal, $object);
		assert(!empty($url));
		$payload = "doi=$doi\nurl=$url";

		// Prepare HTTP session.
		$curlCh = curl_init ();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);

		// Set up basic authentication.
		$username = $this->getSetting($journal->getId(), 'username');
		$password = base64_decode($this->getSetting($journal->getId(), 'password'));
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");

		// Set up SSL.
		curl_setopt($curlCh, CURLOPT_SSLVERSION, 3);
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);

		// Mint a DOI.
		curl_setopt($curlCh, CURLOPT_URL, DATACITE_API_URL . 'doi');
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: text/plain;charset=UTF-8'));
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);

		$result = array();
		$response = curl_exec($curlCh);
		if ($response !== false) {
			$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
			if ($status == DATACITE_API_RESPONSE_OK) {
				$result = true;
			} else {
				$result[] = array('plugins.importexport.common.register.error.mdsError', "$status - $response");
			}
		}

		// Transmit meta-data.
		if ($result === true) {
			assert(is_readable($file));
			$payload = file_get_contents($file);
			assert($payload !== false && !empty($payload));
			curl_setopt($curlCh, CURLOPT_URL, DATACITE_API_URL . 'metadata');
			curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: application/xml;charset=UTF-8'));
			curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);
			$response = curl_exec($curlCh);
			if ($response !== false) {
				$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
				if ($status != DATACITE_API_RESPONSE_OK) {
					$result = array(
						array('plugins.importexport.common.register.error.mdsError', "$status - $response")
					);
				}
			}
		}

		curl_close($curlCh);

		if ($result === true) {
			// Mark the object as registered.
			$this->markRegistered($request, $object, DATACITE_API_TESTPREFIX);
		}

		return $result;
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
	 * Retrieve all unregistered supplementary files and their corresponding issues and articles.
	 * @param $journal Journal
	 * @return array
	 */
	function &_getUnregisteredSuppFiles(&$journal) {
		// Retrieve all supp files that have not yet been registered.
		$suppFileDao =& DAORegistry::getDAO('SuppFileDAO'); /* @var $suppFileDao SuppFileDAO */
		$suppFiles = $suppFileDao->getSuppFilesBySetting($this->getPluginId(). '::' . DOI_EXPORT_REGDOI, null, null, $journal->getId());

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

	/**
	 * Get the canonical URL of an object.
	 * @param $request Request
	 * @param $journal Journal
	 * @param $object Issue|PublishedArticle|ArticleGalley|SuppFile
	 */
	function _getObjectUrl(&$request, &$journal, &$object) {
		$router =& $request->getRouter();

		// Retrieve the article of article files.
		if (is_a($object, 'ArticleFile')) {
			$articleId = $object->getArticleId();
			$cache = $this->getCache();
			if ($cache->isCached('articles', $articleId)) {
				$article =& $cache->get('articles', $articleId);
			} else {
				$articleDao =& DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
				$article =& $articleDao->getPublishedArticleByArticleId($articleId, $journal->getId(), true);
			}
			assert(is_a($article, 'PublishedArticle'));
		}

		$url = null;
		switch (true) {
			case is_a($object, 'Issue'):
				$url = $router->url($request, null, 'issue', 'view', $object->getBestIssueId($journal));
				break;

			case is_a($object, 'PublishedArticle'):
				$url = $router->url($request, null, 'article', 'view', $object->getBestArticleId($journal));
				break;

			case is_a($object, 'ArticleGalley'):
				$url = $router->url($request, null, 'article', 'view', array($article->getBestArticleId($journal), $object->getBestGalleyId($journal)));
				break;

			case is_a($object, 'SuppFile'):
				$url = $router->url($request, null, 'article', 'downloadSuppFile', array($article->getBestArticleId($journal), $object->getBestSuppFileId($journal)));
				break;
		}

		if ($this->isTestMode($request)) {
			// Change server domain for testing.
			$url = String::regexp_replace('#://[^\s]+/index.php#', '://example.com/index.php', $url);
		}
		return $url;
	}
}

?>
