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

import('classes.plugins.DOIPubIdExportPlugin');

// DataCite API
define('DATACITE_API_RESPONSE_OK', 201);
define('DATACITE_API_URL', 'https://mds.datacite.org/');

// Test DOI prefix
define('DATACITE_API_TESTPREFIX', '10.5072');

// Export file types.
define('DATACITE_EXPORT_FILE_XML', 0x01);
define('DATACITE_EXPORT_FILE_TAR', 0x02);


class DataciteExportPlugin extends DOIPubIdExportPlugin {
	/**
	 * Constructor
	 */
	function DataciteExportPlugin() {
		parent::DOIPubIdExportPlugin();
	}

	/**
	 * @see Plugin::getName()
	 */
	function getName() {
		return 'DataciteExportPlugin';
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.importexport.datacite.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.importexport.datacite.description');
	}

	/**
	 * @copydoc DOIExportPlugin::getSubmissionFilter()
	 */
	function getSubmissionFilter() {
		return 'article=>datacite-xml';
	}

	/**
	 * @copydoc DOIExportPlugin::getIssueFilter()
	 */
	function getIssueFilter() {
		return 'issue=>datacite-xml';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getPluginSettingsPrefix()
	 */
	function getPluginSettingsPrefix() {
		return 'datacite';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getSettingsFormClassName()
	 */
	function getSettingsFormClassName() {
		return 'DataciteSettingsForm';
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::getExportDeploymentClassName()
	 */
	function getExportDeploymentClassName() {
		return 'DataciteExportDeployment';
	}

	/**
	 * @copydoc ImportExportPlugin::display()
	 */
	function display($args, $request) {
		$context = $request->getContext();
		switch (current($args)) {
			case 'exportRepresentations':
				$selectedRepresentations = (array) $request->getUserVar('selectedRepresentations');
				if (!empty($selectedRepresentations)) {
					$objects = $this->_getArticleGalleys($selectedRepresentations, $context);
					$filter = 'galley=>datacite-xml';
					$tab = (string) $request->getUserVar('tab');
					$objectsFileNamePart = 'galleys';
				}
				// Execute export action
				$this->executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart);
			default:
				parent::display($args, $request);
		}
	}

	/**
	 * @copydoc ImportExportPlugin::executeExportAction()
	 */
	function executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart) {
		$context = $request->getContext();
		$path = array('plugin', $this->getName());

		// Export
		if ($request->getUserVar(DOI_EXPORT_ACTION_EXPORT)) {
			$result = $this->_checkForTar();
			if ($result === true) {
				$exportedFiles = array();
				foreach ($objects as $object) {
					// Get the XML
					$exportXml = $this->exportXML($object, $filter, $context);
					// Write the XML to a file.
					// export file name example: datacite/20160723-160036-articles-1-1.xml
					$objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
					$exportFileName = $this->getExportFileName($objectFileNamePart, $context);
					file_put_contents($exportFileName, $exportXml);
					$exportedFiles[] = $exportFileName;
				}
				// If we have more than one export file we package the files
				// up as a single tar before going on.
				assert(count($exportedFiles) >= 1);
				if (count($exportedFiles) > 1) {
					// tar file name: e.g. datacite/20160723-160036-articles-1.tar.gz
					$finalExportFileName = $this->getExportPath() . date('Ymd-His') .'-' . $objectsFileNamePart .'-' . $context->getId() . '.tar.gz';
					$finalExportFileType = DATACITE_EXPORT_FILE_TAR;
					$this->_tarFiles($this->getExportPath(), $finalExportFileName, $exportedFiles);
					// remove files
					foreach ($exportedFiles as $exportedFile) {
						$this->cleanTmpfile($exportedFile);
					}
				} else {
					$finalExportFileName = array_shift($exportedFiles);
					$finalExportFileType = DATACITE_EXPORT_FILE_XML;
				}
				header('Content-Type: application/' . ($finalExportFileType == DATACITE_EXPORT_FILE_TAR ? 'x-gtar' : 'xml'));
				header('Cache-Control: private');
				header('Content-Disposition: attachment; filename="' . basename($finalExportFileName) . '"');
				readfile($finalExportFileName);
			} else {
				if (is_array($result)) {
					foreach($result as $error) {
						assert(is_array($error) && count($error) >= 1);
						$this->_sendNotification(
							$request->getUser(),
							$error[0],
							NOTIFICATION_TYPE_ERROR,
							(isset($error[1]) ? $error[1] : null)
						);
					}
				}
				// redirect back to the right tab
				$request->redirect(null, null, null, $path, null, $tab);
			}
		} elseif ($request->getUserVar(DOI_EXPORT_ACTION_DEPOSIT)) {
			$resultErrors = array();
			foreach ($objects as $object) {
				// Get the XML
				$exportXml = $this->exportXML($object, $filter, $context);
				// Write the XML to a file.
				// export file name example: datacite/20160723-160036-articles-1-1.xml
				$objectFileNamePart = $objectsFileNamePart . '-' . $object->getId();
				$exportFileName = $this->getExportFileName($objectFileNamePart, $context);
				file_put_contents($exportFileName, $exportXml);
				// Deposit the XML file.
				$result = $this->depositXML($object, $context, $exportFileName);
				if (is_array($result)) {
					$resultErrors[] = $result;
				}
				// Remove all temporary files.
				$this->cleanTmpfile($exportFileName);
			}
			// send notifications
			if (empty($resultErrors)) {
				$this->_sendNotification(
					$request->getUser(),
					$this->getDepositSuccessNotificationMessageKey(),
					NOTIFICATION_TYPE_SUCCESS
				);
			} else {
				foreach($resultErrors as $error) {
					assert(is_array($error) && count($error) >= 1);
					$this->_sendNotification(
						$request->getUser(),
						$error[0],
						NOTIFICATION_TYPE_ERROR,
						(isset($error[1]) ? $error[1] : null)
					);
				}
			}
			// redirect back to the right tab
			$request->redirect(null, null, null, $path, null, $tab);
		} else {
			return parent::executeExportAction($request, $objects, $filter, $tab, $objectsFileNamePart);
		}
	}

	/**
	 * @copydoc DOIPubIdExportPlugin::depositXML()
	 */
	function depositXML($object, $context, $filename) {
		$request = $this->getRequest();
		// Get the DOI and the URL for the object.
		$doi = $object->getStoredPubId('doi');
		assert(!empty($doi));
		if ($this->isTestMode($context)) {
			$doi = PKPString::regexp_replace('#^[^/]+/#', DATACITE_API_TESTPREFIX . '/', $doi);
		}
		$url = $this->_getObjectUrl($request, $context, $object);
		assert(!empty($url));
		// Prepare HTTP session.
		$curlCh = curl_init();
		if ($httpProxyHost = Config::getVar('proxy', 'http_host')) {
			curl_setopt($curlCh, CURLOPT_PROXY, $httpProxyHost);
			curl_setopt($curlCh, CURLOPT_PROXYPORT, Config::getVar('proxy', 'http_port', '80'));
			if ($username = Config::getVar('proxy', 'username')) {
				curl_setopt($curlCh, CURLOPT_PROXYUSERPWD, $username . ':' . Config::getVar('proxy', 'password'));
			}
		}
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_POST, true);
		// Set up basic authentication.
		$username = $this->getSetting($context->getId(), 'username');
		$password = $this->getSetting($context->getId(), 'password');
		curl_setopt($curlCh, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curlCh, CURLOPT_USERPWD, "$username:$password");
		// Set up SSL.
		curl_setopt($curlCh, CURLOPT_SSL_VERIFYPEER, false);
		// Transmit meta-data.
		assert(is_readable($filename));
		$payload = file_get_contents($filename);
		assert($payload !== false && !empty($payload));
		curl_setopt($curlCh, CURLOPT_URL, DATACITE_API_URL . 'metadata');
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Content-Type: application/xml;charset=UTF-8'));
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $payload);
		$result = true;
		$response = curl_exec($curlCh);
		if ($response === false) {
			$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: No response from server."));
		} else {
			$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
			if ($status != DATACITE_API_RESPONSE_OK) {
				$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: $status - $response"));
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
				$result = array(array('plugins.importexport.common.register.error.mdsError', 'Registering DOI $doi: No response from server.'));
			} else {
				$status = curl_getinfo($curlCh, CURLINFO_HTTP_CODE);
				if ($status != DATACITE_API_RESPONSE_OK) {
					$result = array(array('plugins.importexport.common.register.error.mdsError', "Registering DOI $doi: $status - $response"));
				}
			}
		}
		curl_close($curlCh);
		if ($result === true) {
			$object->setData($this->getDepositStatusSettingName(), DOI_EXPORT_STATUS_REGISTERED);
			$this->saveRegisteredDoi($context, $object, DATACITE_API_TESTPREFIX);
		}
		return $result;
	}

	/**
	 * Retrieve all unregistered articles.
	 * @param $context Context
	 * @return array
	 */
	function getUnregisteredGalleys($context) {
		// Retrieve all galleys that have not yet been registered.
		$galleyDao = DAORegistry::getDAO('ArticleGalleyDAO'); /* @var $galleyDao ArticleGalleyDAO */
		$galleys = $galleyDao->getByPubIdType(
			$this->getPubIdType(),
			$context?$context->getId():null,
			null,
			null,
			null,
			$this->getPluginSettingsPrefix(). '::' . DOI_EXPORT_REGISTERED_DOI,
			null,
			null
		);
		return $galleys->toArray();
	}


	/**
	 * Get article galleys from gallley IDs.
	 * @param $galleyIds array
	 * @param $context Context
	 * @return array
	 */
	function _getArticleGalleys($galleyIds, $context) {
		$galleys = array();
		$articleGalleyDao = DAORegistry::getDAO('ArticleGalleyDAO');
		foreach ($galleyIds as $galleyId) {
			$articleGalley = $articleGalleyDao->getById($galleyId, null, $context->getId());
			if ($articleGalley) $galleys[] = $articleGalley;
		}
		return $galleys;
	}

	/**
	 * Test whether the tar binary is available.
	 * @return boolean|array Boolean true if available otherwise
	 *  an array with an error message.
	 */
	function _checkForTar() {
		$tarBinary = Config::getVar('cli', 'tar');
		if (empty($tarBinary) || !is_executable($tarBinary)) {
			$result = array(
				array('manager.plugins.tarCommandNotFound')
			);
		} else {
			$result = true;
		}
		return $result;
	}

	/**
	 * Create a tar archive.
	 * @param $targetPath string
	 * @param $targetFile string
	 * @param $sourceFiles array
	 */
	function _tarFiles($targetPath, $targetFile, $sourceFiles) {
		assert($this->_checkForTar());
		// GZip compressed result file.
		$tarCommand = Config::getVar('cli', 'tar') . ' -czf ' . escapeshellarg($targetFile);
		// Do not reveal our internal export path by exporting only relative filenames.
		$tarCommand .= ' -C ' . escapeshellarg($targetPath);
		// Do not reveal our webserver user by forcing root as owner.
		$tarCommand .= ' --owner 0 --group 0 --';
		// Add each file individually so that other files in the directory
		// will not be included.
		foreach($sourceFiles as $sourceFile) {
			assert(dirname($sourceFile) . '/' === $targetPath);
			if (dirname($sourceFile) . '/' !== $targetPath) continue;
			$tarCommand .= ' ' . escapeshellarg(basename($sourceFile));
		}
		// Execute the command.
		exec($tarCommand);
	}

	/**
	 * Get the canonical URL of an object.
	 * @param $request Request
	 * @param $context Context
	 * @param $object Issue|PublishedArticle|ArticleGalley
	 */
	function _getObjectUrl($request, $context, $object) {
		$router = $request->getRouter();
		// Retrieve the article of article files.
		if (is_a($object, 'ArticleGalley')) {
			$articleId = $object->getSubmissionId();
			$cache = $this->getCache();
			if ($cache->isCached('articles', $articleId)) {
				$article = $cache->get('articles', $articleId);
			} else {
				$articleDao = DAORegistry::getDAO('PublishedArticleDAO'); /* @var $articleDao PublishedArticleDAO */
				$article = $articleDao->getPublishedArticleByArticleId($articleId, $context->getId(), true);
			}
			assert(is_a($article, 'PublishedArticle'));
		}
		$url = null;
		switch (true) {
			case is_a($object, 'Issue'):
				$url = $router->url($request, $context->getPath(), 'issue', 'view', $object->getBestIssueId());
				break;
			case is_a($object, 'PublishedArticle'):
				$url = $router->url($request, $context->getPath(), 'article', 'view', $object->getBestArticleId());
				break;
			case is_a($object, 'ArticleGalley'):
				$url = $router->url($request, $context->getPath(), 'article', 'view', array($article->getBestArticleId(), $object->getBestGalleyId()));
				break;
		}
		if ($this->isTestMode($context)) {
			// Change server domain for testing.
			$url = PKPString::regexp_replace('#://[^\s]+/index.php#', '://example.com/index.php', $url);
		}
		return $url;
	}

}

?>
