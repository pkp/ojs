<?php

/**
 * @file plugins/importexport/datacite/DataciteExportPlugin.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataciteExportPlugin
 * @ingroup plugins_importexport_datacite
 *
 * @brief DataCite export/registration plugin.
 */

if (!class_exists('DOIExportPlugin')) { // Bug #7848
	import('plugins.importexport.datacite.classes.DOIExportPlugin');
}

// DataCite API
define('DATACITE_API_RESPONSE_OK', 201);
define('DATACITE_API_URL', 'https://mds.datacite.org/');

// Test DOI prefix
define('DATACITE_API_TESTPREFIX', '10.5072');

class DataciteExportPlugin extends DOIExportPlugin {

	//
	// Constructor
	//
	function DataciteExportPlugin() {
		parent::DOIExportPlugin();
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
	// Implement template methods from DOIExportPlugin
	//
	/**
	 * @see DOIExportPlugin::getPluginId()
	 */
	function getPluginId() {
		return 'datacite';
	}

	/**
	 * @see DOIExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'DataciteSettingsForm';
	}

	/**
	 * @see DOIExportPlugin::generateExportFiles()
	 */
	function generateExportFiles($request, $exportType, &$objects, $targetPath, $journal, &$errors) {
		// Additional locale file.
		AppLocale::requireComponents(array(LOCALE_COMPONENT_APP_EDITOR));

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
	 * @see DOIExportPlugin::registerDoi()
	 */
	function registerDoi($request, $journal, &$objects, $file) {
		// DataCite should always export exactly
		// one object per meta-data file.
		assert(count($objects) == 1);
		$object =& $objects[0];

		// Get the DOI and the URL for the object.
		$doi = $object->getPubId('doi');
		assert(!empty($doi));
		if ($this->isTestMode($request)) {
			$doi = PKPString::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $doi);
		}
		$url = $this->_getObjectUrl($request, $journal, $object);
		assert(!empty($url));

		// Prepare HTTP session.
		$curlCh = curl_init ();
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);

		// Set up basic authentication.
		$username = $this->getSetting($journal->getId(), 'username');
		$password = $this->getSetting($journal->getId(), 'password');
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");

		// Set up SSL.
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);

		// Transmit meta-data.
		assert(is_readable($file));
		$payload = file_get_contents($file);
		assert($payload !== false && !empty($payload));
		curl_setopt($curlCh, CURLOPT_URL, DATACITE_API_URL . 'metadata');
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: application/xml;charset=UTF-8'));
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);

		$result = true;
		$response = curl_exec($curlCh);
		if ($response === false) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
		} else {
			$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
			if ($status != DATACITE_API_RESPONSE_OK) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', "$status - $response"));
			}
		}

		// Mint a DOI.
		if ($result === true) {
			$payload = "doi=$doi\nurl=$url";

			curl_setopt($curlCh, CURLOPT_URL, DATACITE_API_URL . 'doi');
			curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: text/plain;charset=UTF-8'));
			curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);

			$response = curl_exec($curlCh);
			if ($response === false) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', 'No response from server.'));
			} else {
				$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
				if ($status != DATACITE_API_RESPONSE_OK) {
					$result = array(array('plugins.importexport.common.register.error.mdsError', "$status - $response"));
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


	//
	// Private helper methods
	//
	/**
	 * Get the canonical URL of an object.
	 * @param $request Request
	 * @param $journal Journal
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 */
	function _getObjectUrl($request, $journal, $object) {
		$router = $request->getRouter();

		// Retrieve the article of article files.
		if (is_a($object, 'SubmissionFile')) {
			$articleId = $object->getArticleId();
			$cache = $this->getCache();
			if ($cache->isCached('articles', $articleId)) {
				$article = $cache->get('articles', $articleId);
			} else {
				$articleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
				$article = $articleDao->getPublishedArticleByArticleId($articleId, $journal->getId(), true);
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
		}

		if ($this->isTestMode($request)) {
			// Change server domain for testing.
			$url = PKPString::regexp_replace('#://[^\s]+/index.php#', '://example.com/index.php', $url);
		}
		return $url;
	}

	/**
	 * @copydoc PKPImportExportPlugin::usage
	 */
	function usage($scriptName) {
		fatalError('Not implemented');
	}
}

?>
