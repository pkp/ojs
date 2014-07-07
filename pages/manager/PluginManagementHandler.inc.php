<?php

/**
 * @file pages/manager/PluginManagementHandler.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginManagementHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for installing/upgrading/deleting plugins.
 */

define('VERSION_FILE', '/version.xml');
define('INSTALL_FILE', '/install.xml');
define('UPGRADE_FILE', '/upgrade.xml');

import('lib.pkp.classes.site.Version');
import('lib.pkp.classes.site.VersionCheck');
import('lib.pkp.classes.file.FileManager');
import('classes.install.Install');
import('classes.install.Upgrade');
import('pages.manager.ManagerHandler');

class PluginManagementHandler extends ManagerHandler {
	/**
	 * Constructor
	 */
	function PluginManagementHandler() {
		parent::ManagerHandler();
	}

	/**
	 * Display a list of plugins along with management options.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function managePlugins($args, &$request) {
		$this->validate($request);
		$path = isset($args[0])?$args[0]:null;
		$category = isset($args[1])?$args[1]:null;
		$plugin = isset($args[2])?$args[2]:null;

		switch($path) {
			case 'install':
				$this->_showInstallForm($request);
				break;
			case 'installPlugin':
				$this->_uploadPlugin($request, 'install');
				break;
			case 'upgrade':
				$this->_showUpgradeForm($request, $category, $plugin);
				break;
			case 'upgradePlugin':
				$this->_uploadPlugin($request, 'upgrade', $category, $plugin);
				break;
			case 'delete':
				$this->_showDeleteForm($request, $category, $plugin);
				break;
			case 'deletePlugin':
				$this->_deletePlugin($request, $category, $plugin);
				break;
			default:
				$request->redirect(null, 'manager', 'plugins');
		}

		$this->setupTemplate(true);
	}

	/**
	 * The site setting option 'preventManagerPluginManagement' must not be set for
	 * journal managers to be able to manage plugins.
	 * @param $request PKPRequest
	 */
	function validate($request) {
		parent::validate();
		if (!Validation::isSiteAdmin()) {
			$site =& $request->getSite();
			$preventManagerPluginManagement = $site->getSetting('preventManagerPluginManagement');
			if ($preventManagerPluginManagement) $request->redirect(null, 'manager', 'plugins');
		}
	}

	/**
	 * Show plugin installation form.
	 * @param $request PKPRequest
	 */
	function _showInstallForm($request) {
		$this->validate($request);
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);

		$templateMgr->assign('path', 'install');
		$templateMgr->assign('uploaded', false);
		$templateMgr->assign('error', false);

		$templateMgr->assign('pageHierarchy', $this->_setBreadcrumbs($request, true));

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}

	/**
	 * Show form to select plugin for upgrade.
	 * @param $request PKPRequest
	 * @param $category string
	 * @param $plugin string
	 */
	function _showUpgradeForm($request, $category, $plugin) {
		$this->validate($request);
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);

		$templateMgr->assign('path', 'upgrade');
		$templateMgr->assign('category', $category);
		$templateMgr->assign('plugin', $plugin);
		$templateMgr->assign('uploaded', false);
		$templateMgr->assign('pageHierarchy', $this->_setBreadcrumbs($request, true, $category));

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}

	/**
	 * Confirm deletion of plugin.
	 * @param $request PKPRequest
	 * @param $category string
	 * @param $plugin string
	 */
	function _showDeleteForm($request, $category, $plugin) {
		$this->validate($request);
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);

		$templateMgr->assign('path', 'delete');
		$templateMgr->assign('category', $category);
		$templateMgr->assign('plugin', $plugin);
		$templateMgr->assign('deleted', false);
		$templateMgr->assign('error', false);
		$templateMgr->assign('pageHierarchy', $this->_setBreadcrumbs($request, true, $category));

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}


	/**
	 * Decompress uploaded plugin and install in the correct plugin directory.
	 * @param $request PKPRequest
	 * @param $function string type of operation to perform after upload ('upgrade' or 'install')
	 * @param $category string the category of the uploaded plugin (upgrade only)
	 * @param $plugin string the name of the uploaded plugin (upgrade only)
	 */
	function _uploadPlugin($request, $function, $category = null, $plugin = null) {
		$this->validate($request);
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);

		$templateMgr->assign('error', false);
		$templateMgr->assign('uploaded', false);
		$templateMgr->assign('path', $function);

		$errorMsg = '';
		if ($request->getUserVar('uploadPlugin')) {
			import('classes.file.TemporaryFileManager');
			$temporaryFileManager = new TemporaryFileManager();
			$user =& $request->getUser();
		} else {
			$errorMsg = 'manager.plugins.fileSelectError';
		}

		if (empty($errorMsg)) {
			if ($temporaryFile = $temporaryFileManager->handleUpload('newPlugin', $user->getId())) {
				// tar archive basename (less potential version number) must equal plugin directory name
				// and plugin files must be in a directory named after the plug-in.
				$matches = array();
				String::regexp_match_get('/^[a-zA-Z0-9]+/', basename($temporaryFile->getOriginalFileName(), '.tar.gz'), $matches);
				$pluginName = array_pop($matches);
				// Create random dirname to avoid symlink attacks.
				$pluginDir = dirname($temporaryFile->getFilePath()) . DIRECTORY_SEPARATOR . $pluginName . substr(md5(mt_rand()), 0, 10);
				mkdir($pluginDir);
			} else {
				$errorMsg = 'manager.plugins.uploadError';
			}
		}

		if (empty($errorMsg)) {
			// Test whether the tar binary is available for the export to work
			$tarBinary = Config::getVar('cli', 'tar');
			if (!empty($tarBinary) && file_exists($tarBinary)) {
				exec($tarBinary.' -xzf ' . escapeshellarg($temporaryFile->getFilePath()) . ' -C ' . escapeshellarg($pluginDir));
			} else {
				$errorMsg = 'manager.plugins.tarCommandNotFound';
			}
		}

		if (empty($errorMsg)) {
			// We should now find a directory named after the
			// plug-in within the extracted archive.
			$pluginDir .= DIRECTORY_SEPARATOR . $pluginName;
			if (is_dir($pluginDir)) {
				if ($function == 'install') {
					$this->_installPlugin($request, $pluginDir, $templateMgr);
				} else if ($function == 'upgrade') {
					$this->_upgradePlugin($request, $pluginDir, $templateMgr, $category, $plugin);
				}
			} else {
				$errorMsg = 'manager.plugins.invalidPluginArchive';
			}
		}

		if (!empty($errorMsg)) {
			$templateMgr->assign('error', true);
			$templateMgr->assign('message', $errorMsg);
		}

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}

	/**
	 * Installs the uploaded plugin
	 * @param $request PKPRequest
	 * @param $path string path to plugin Directory
	 * @param $templateMgr reference to template manager
	 * @return boolean
	 */
	function _installPlugin($request, $path, &$templateMgr) {
		$this->validate($request);
		$versionFile = $path . VERSION_FILE;
		$templateMgr->assign('error', true);
		$templateMgr->assign('pageHierarchy', $this->_setBreadcrumbs($request, true));

		$pluginVersion =& VersionCheck::getValidPluginVersionInfo($versionFile);
		if (is_null($pluginVersion)) return false;
		assert(is_a($pluginVersion, 'Version'));

		$versionDao =& DAORegistry::getDAO('VersionDAO'); /* @var $versionDao VersionDAO */
		$installedPlugin = $versionDao->getCurrentVersion($pluginVersion->getProductType(), $pluginVersion->getProduct(), true);

		if(!$installedPlugin) {
			$pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . strtr($pluginVersion->getProductType(), '.', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pluginVersion->getProduct();
			$pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . strtr($pluginVersion->getProductType(), '.', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $pluginVersion->getProduct();

			// Copy the plug-in from the temporary folder to the
			// target folder.
			// Start with the library part (if any).
			$libPath = $path . DIRECTORY_SEPARATOR . 'lib';
			$fileManager = new FileManager();
			if (is_dir($libPath)) {
				if(!$fileManager->copyDir($libPath, $pluginLibDest)) {
					$templateMgr->assign('message', 'manager.plugins.copyError');
					return false;
				}
				// Remove the library part of the temporary folder.
				$fileManager->rmtree($libPath);
			}

			// Continue with the application-specific part (mandatory).
			if(!$fileManager->copyDir($path, $pluginDest)) {
				$templateMgr->assign('message', 'manager.plugins.copyError');
				return false;
			}

			// Remove the temporary folder.
			$fileManager->rmtree(dirname($path));

			// Upgrade the database with the new plug-in.
			$installFile = $pluginDest . INSTALL_FILE;
			if(!is_file($installFile)) $installFile = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'xml' . DIRECTORY_SEPARATOR . 'defaultPluginInstall.xml';
			assert(is_file($installFile));
			$params = $this->_setConnectionParams();
			$installer = new Install($params, $installFile, true);
			$installer->setCurrentVersion($pluginVersion);
			if (!$installer->execute()) {
				// Roll back the copy
				if (is_dir($pluginLibDest)) $fileManager->rmtree($pluginLibDest);
				if (is_dir($pluginDest)) $fileManager->rmtree($pluginDest);
				$templateMgr->assign('message', array('manager.plugins.installFailed', $installer->getErrorString()));
				return false;
			}

			$message = array('manager.plugins.installSuccessful', $pluginVersion->getVersionString());
			$templateMgr->assign('message', $message);
			$templateMgr->assign('uploaded', true);
			$templateMgr->assign('error', false);

			$versionDao->insertVersion($pluginVersion, true);
			return true;
		} else {
			if ($this->_checkIfNewer($pluginVersion->getProductType(), $pluginVersion->getProduct(), $pluginVersion)) {
				$templateMgr->assign('message', 'manager.plugins.pleaseUpgrade');
				return false;
			} else {
				$templateMgr->assign('message', 'manager.plugins.installedVersionOlder');
				return false;
			}
		}
	}

	/**
	 * Upgrade a plugin to a newer version from the user's filesystem
	 * @param $request PKPRequest
	 * @param $path string path to plugin Directory
	 * @param $templateMgr reference to template manager
	 * @param $category string
	 * @param $plugin string
	 * @return boolean
	 */
	function _upgradePlugin($request, $path, &$templateMgr, $category, $plugin) {
		$this->validate($request);
		$versionFile = $path . VERSION_FILE;
		$templateMgr->assign('error', true);
		$templateMgr->assign('pageHierarchy', $this->_setBreadcrumbs($request, true, $category));

		$pluginVersion =& VersionCheck::getValidPluginVersionInfo($versionFile);
		if (is_null($pluginVersion)) return false;
		assert(is_a($pluginVersion, 'Version'));

		// Check whether the uploaded plug-in fits the original plug-in.
		if ('plugins.'.$category != $pluginVersion->getProductType()) {
			$templateMgr->assign('message', 'manager.plugins.wrongCategory');
			return false;
		}

		if ($plugin != $pluginVersion->getProduct()) {
			$templateMgr->assign('message', 'manager.plugins.wrongName');
			return false;
		}

		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$installedPlugin = $versionDao->getCurrentVersion($pluginVersion->getProductType(), $pluginVersion->getProduct(), true);
		if(!$installedPlugin) {
			$templateMgr->assign('message', 'manager.plugins.pleaseInstall');
			return false;
		}

		if ($this->_checkIfNewer($pluginVersion->getProductType(), $pluginVersion->getProduct(), $pluginVersion)) {
			$templateMgr->assign('message', 'manager.plugins.installedVersionNewer');
			return false;
		} else {
			$pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $plugin;
			$pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $plugin;

			// Delete existing files.
			$fileManager = new FileManager();
			if (is_dir($pluginDest)) $fileManager->rmtree($pluginDest);
			if (is_dir($pluginLibDest)) $fileManager->rmtree($pluginLibDest);

			// Check whether deleting has worked.
			if(is_dir($pluginDest) || is_dir($pluginLibDest)) {
				$templateMgr->assign('message', 'manager.plugins.deleteError');
				return false;
			}

			// Copy the plug-in from the temporary folder to the
			// target folder.
			// Start with the library part (if any).
			$libPath = $path . DIRECTORY_SEPARATOR . 'lib';
			if (is_dir($libPath)) {
				if(!$fileManager->copyDir($libPath, $pluginLibDest)) {
					$templateMgr->assign('message', 'manager.plugins.copyError');
					return false;
				}
				// Remove the library part of the temporary folder.
				$fileManager->rmtree($libPath);
			}

			// Continue with the application-specific part (mandatory).
			if(!$fileManager->copyDir($path, $pluginDest)) {
				$templateMgr->assign('message', 'manager.plugins.copyError');
				return false;
			}

			// Remove the temporary folder.
			$fileManager->rmtree(dirname($path));

			$upgradeFile = $pluginDest . UPGRADE_FILE;
			if($fileManager->fileExists($upgradeFile)) {
				$params = $this->_setConnectionParams();
				$installer = new Upgrade($params, $upgradeFile, true);

				if (!$installer->execute()) {
					$templateMgr->assign('message', array('manager.plugins.upgradeFailed', $installer->getErrorString()));
					return false;
				}
			}

			$installedPlugin->setCurrent(0);
			$pluginVersion->setCurrent(1);
			$versionDao->insertVersion($pluginVersion, true);

			$templateMgr->assign('category', $category);
			$templateMgr->assign('plugin', $plugin);
			$templateMgr->assign('message', array('manager.plugins.upgradeSuccessful', $pluginVersion->getVersionString()));
			$templateMgr->assign('uploaded', true);
			$templateMgr->assign('error', false);

			return true;
		}
	}

	/**
	 * Delete a plugin from the system
	 * @param $request PKPRequest
	 * @param $category string
	 * @param $plugin string
	 */
	function _deletePlugin($request, $category, $plugin) {
		$this->validate($request);
		$templateMgr =& TemplateManager::getManager();
		$this->setupTemplate(true);

		$templateMgr->assign('path', 'delete');
		$templateMgr->assign('deleted', false);
		$templateMgr->assign('error', false);

		$versionDao =& DAORegistry::getDAO('VersionDAO'); /* @var $versionDao VersionDAO */
		$installedPlugin = $versionDao->getCurrentVersion('plugins.'.$category, $plugin, true);

		if ($installedPlugin) {
			$pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $plugin;
			$pluginLibDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'pkp' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $category . DIRECTORY_SEPARATOR . $plugin;

			//make sure plugin type is valid and then delete the files
			if (in_array($category, PluginRegistry::getCategories())) {
				// Delete the plugin from the file system.
				$fileManager = new FileManager();
				$fileManager->rmtree($pluginDest);
				$fileManager->rmtree($pluginLibDest);
			}

			if(is_dir($pluginDest) || is_dir($pluginLibDest)) {
				$templateMgr->assign('error', true);
				$templateMgr->assign('message', 'manager.plugins.deleteError');
			} else {
				$versionDao->disableVersion('plugins.'.$category, $plugin);
				$templateMgr->assign('deleted', true);
			}

		} else {
			$templateMgr->assign('error', true);
			$templateMgr->assign('message', 'manager.plugins.doesNotExist');
		}

		$templateMgr->assign('pageHierarchy', $this->_setBreadcrumbs($request, true, $category));
		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}

	/**
	 * Checks to see if local version of plugin is newer than installed version
	 * @param $productType string Product type of plugin
	 * @param $productName string Product name of plugin
	 * @param $newVersion Version Version object of plugin to check against database
	 * @return boolean
	 */
	function _checkIfNewer($productType, $productName, $newVersion) {
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$installedPlugin = $versionDao->getCurrentVersion($productType, $productName, true);

		if (!$installedPlugin) return false;
		if ($installedPlugin->compare($newVersion) > 0) return true;
		else return false;
	}

	/**
	 * Set the page's breadcrumbs
	 * @param $request PKPRequest
	 * @param $subclass boolean
	 * @param $category string
	 */
	function _setBreadcrumbs($request, $subclass = false, $category = null) {
		$templateMgr =& TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				$request->url(null, 'user'),
				'navigation.user',
				false
			),
			array(
				$request->url(null, 'manager'),
				'manager.journalManagement',
				false
			)
		);

		if ($subclass) {
			$pageCrumbs[] = array(
				$request->url(null, 'manager', 'plugins'),
				'manager.plugins.pluginManagement',
				false
			);
		}

		if ($category) {
			$pageCrumbs[] = array(
				$request->url(null, 'manager', 'plugins', $category),
				"plugins.categories.$category",
				false
			);
		}

		return $pageCrumbs;
	}

	/**
	 * Load database connection parameters into an array (needed for upgrade).
	 * @return array
	 */
	function _setConnectionParams() {
		return array(
			'clientCharset' => Config::getVar('i18n', 'client_charset'),
			'connectionCharset' => Config::getVar('i18n', 'connection_charset'),
			'databaseCharset' => Config::getVar('i18n', 'database_charset'),
			'databaseDriver' => Config::getVar('database', 'driver'),
			'databaseHost' => Config::getVar('database', 'host'),
			'databaseUsername' => Config::getVar('database', 'username'),
			'databasePassword' => Config::getVar('database', 'password'),
			'databaseName' => Config::getVar('database', 'name')
		);
	}
}

?>
