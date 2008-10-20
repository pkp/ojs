<?php

/**
 * @file PluginManagementHandler.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginManagementHandler
 * @ingroup pages_manager
 *
 * @brief Handle requests for installing/upgrading/deleting plugins.
 */

// $Id$

define('VERSION_FILE', '/version.xml');
define('INSTALL_FILE', '/install.xml');
define('UPGRADE_FILE', '/upgrade.xml');

import('site.Version');
import('site.VersionCheck');
import('file.FileManager');
import('install.Install');


class PluginManagementHandler extends ManagerHandler {
	
	/**
	 * Display a list of plugins along with management options.
	 */
	function managePlugins($args) {
		$path = isset($args[0])?$args[0]:null;
		$plugin = isset($args[1])?$args[1]:null;
		
		switch($path) {
			case 'install':
				PluginManagementHandler::showInstallForm();
				break;
			case 'installPlugin':
				PluginManagementHandler::uploadPlugin('install');
				break;
			case 'upgrade':
				PluginManagementHandler::showUpgradeForm($plugin);
				break;
			case 'upgradePlugin':
				PluginManagementHandler::uploadPlugin('upgrade');
				break;
			case 'delete':
				PluginManagementHandler::showDeleteForm($plugin);
				break;
			case 'deletePlugin':
				PluginManagementHandler::deletePlugin($plugin);
				break;
		}
	
		parent::setupTemplate(true);
	}

	/**
	 * Show plugin installation form.
	 */
	function showInstallForm() {
		$templateMgr =& TemplateManager::getManager();
		parent::setupTemplate(true);
		
		$templateMgr->assign('path', 'install');
		$templateMgr->assign('uploaded', false);
		$templateMgr->assign('error', false);
		$templateMgr->assign('pageHierarchy', PluginManagementHandler::setBreadcrumbs(true));

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}
	
	/**
	 * Show form to select plugin for upgrade.
	 * @param plugin string
	 */
	function showUpgradeForm($plugin) {
		$templateMgr =& TemplateManager::getManager();
		parent::setupTemplate(true);

		$templateMgr->assign('path', 'upgrade');
		$templateMgr->assign('plugin', $plugin);
		$templateMgr->assign('uploaded', false);
		$templateMgr->assign('pageHierarchy', PluginManagementHandler::setBreadcrumbs(true));

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}
	
	/**
	 * Confirm deletion of plugin.
	 * @param plugin string
	 */
	function showDeleteForm($plugin) {
		$templateMgr =& TemplateManager::getManager();
		parent::setupTemplate(true);
		
		$templateMgr->assign('path', 'delete');
		$templateMgr->assign('plugin', $plugin);
		$templateMgr->assign('deleted', false);
		$templateMgr->assign('error', false);
		$templateMgr->assign('pageHierarchy', PluginManagementHandler::setBreadcrumbs(true));

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}

	
	/**
	 * Decompress uploaded plugin and install in the correct plugin directory.
	 * $param function string type of operation to perform after upload ('upgrade' or 'install')
	 */
	function uploadPlugin($function) {
		$templateMgr = &TemplateManager::getManager();
		parent::setupTemplate(true);
		
		$templateMgr->assign('error', false);
		$templateMgr->assign('uploaded', false);
		$templateMgr->assign('path', $function);
		$templateMgr->assign('pageHierarchy', PluginManagementHandler::setBreadcrumbs(true));
		
		if (Request::getUserVar('uploadPlugin')) {
			import('file.TemporaryFileManager');
			$temporaryFileManager = &new TemporaryFileManager();
			$user = &Request::getUser();
		
			if ($temporaryFile = $temporaryFileManager->handleUpload('newPlugin', $user->getUserId())) {
				// tar archive basename must equal plugin directory name, and plugin files must be in root directory
				$pluginName = basename($temporaryFile->getOriginalFileName(), '.tar.gz');
				$pluginDir = dirname($temporaryFile->getFilePath());
				exec('tar -xzf ' . escapeshellarg($temporaryFile->getFilePath()) . ' -C ' . escapeshellarg($pluginDir));
				
				if ($function == 'install') {
					PluginManagementHandler::installPlugin($pluginDir . DIRECTORY_SEPARATOR . $pluginName, $templateMgr);
				} else if ($function == 'upgrade') { 
					PluginManagementHandler::upgradePlugin($pluginDir . DIRECTORY_SEPARATOR . $pluginName, $templateMgr);
				}
			} else {
				$templateMgr->assign('error', true);
				$templateMgr->assign('message', 'manager.plugins.uploadError');
			}
		} else if (Request::getUserVar('installPlugin')) {
			if(Request::getUserVar('pluginUploadLocation') == '') {
				$templateMgr->assign('error', true);
				$templateMgr->assign('message', 'manager.plugins.fileSelectError');
			}
		}

		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}
	
	/**
	 * Installs the uploaded plugin
	 * @param $path string path to plugin Directory
	 * @param $templateMgr reference to template manager
	 * @return boolean
	 */
	function installPlugin($path, &$templateMgr) {
		$versionFile = $path . VERSION_FILE;
		$templateMgr->assign('error', true);
		$templateMgr->assign('path', 'install');

		if (FileManager::fileExists($versionFile)) {
			$versionInfo =& VersionCheck::parseVersionXML($versionFile);
		} else {
			$templateMgr->assign('message', 'manager.plugins.versionFileNotFound');
			return false;
		}
		
		$pluginVersion = $versionInfo['version'];
		$pluginName = $pluginVersion->getProduct();
		$pluginType = explode(".", $pluginVersion->getProductType());
 
		$versionDao =& DAORegistry::getDAO('VersionDAO'); 
		$installedPlugin = $versionDao->getCurrentVersion($pluginName);
		
		if(!$installedPlugin) {
			$pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $pluginType[1] . DIRECTORY_SEPARATOR . $pluginName;

			if(!FileManager::copyDir($path, $pluginDest)) {
				$templateMgr->assign('message', 'manager.plugins.copyError');
				return false;
			}
			
			// If plugin has an install.xml file, update database with it
			$installFile = $pluginDest . DIRECTORY_SEPARATOR . INSTALL_FILE;
			if(FileManager::fileExists($installFile)) {
				$params = PluginManagementHandler::setConnectionParams();
				$installer = &new Install($params, $installFile, true);

				if ($installer->execute()) {
					$newVersion = &$installer->getNewVersion();
				} else {
					// Roll back the copy
					FileManager::rmtree($pluginDest);
					$templateMgr->assign('message', array('manager.plugins.installFailed', $installer->getErrorString()));
					return false;
				}
			} else {
				$newVersion = $pluginVersion;
			}
			
			$message = array('manager.plugins.installSuccessful', $newVersion->getVersionString());
			$templateMgr->assign('message', $message);
			$templateMgr->assign('uploaded', true);
			$templateMgr->assign('error', false);
			
			$newVersion->setCurrent(1);
			$versionDao->insertVersion($newVersion);
			return true;
		} else {
			if (PluginManagementHandler::checkIfNewer($pluginName, $pluginVersion)) {
				$templateMgr->assign('message', 'manager.plugins.pleaseUpgrade');
				return false;
			}
			if (!PluginManagementHandler::checkIfNewer($pluginName, $pluginVersion)) {
				$templateMgr->assign('message', 'manager.plugins.installedVersionOlder');
				return false;
			}
		}
	}
	
	/**
	 * Upgrade a plugin to a newer version from the user's filesystem
	 * @param $path string path to plugin Directory
	 * @param $templateMgr reference to template manager
	 * @return boolean
	 */
	function upgradePlugin($path, &$templateMgr) {
		$versionFile = $path . VERSION_FILE;
		$templateMgr->assign('error', true);
		$templateMgr->assign('path', 'upgrade');
		
		if (FileManager::fileExists($versionFile)) {
			$versionInfo =& VersionCheck::parseVersionXML($versionFile);
		} else {
			$templateMgr->assign('message', 'manager.plugins.versionFileNotFound');
			return false;
		}
		
		$pluginVersion = $versionInfo['version'];
		$pluginName = $versionInfo['application'];
		$pluginType = explode(".", $pluginVersion->getProductType());
		
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$installedPlugin = $versionDao->getCurrentVersion($pluginName);
		if(!$installedPlugin) {
			$templateMgr->assign('message', 'manager.plugins.pleaseInstall');
			return false;
		}
		
		if (PluginManagementHandler::checkIfNewer($pluginName, $pluginVersion)) {
			$templateMgr->assign('message', 'manager.plugins.installedVersionNewer');
			return false;
		} else {
			$pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $pluginType[1] . DIRECTORY_SEPARATOR . $pluginName;
			
			FileManager::rmtree($pluginDest);
			if(FileManager::fileExists($pluginDest, 'dir')) {
				$templateMgr->assign('message', 'manager.plugins.deleteError');
				return false;
			}
			if(!FileManager::copyDir($path, $pluginDest)) {
				$templateMgr->assign('message', 'manager.plugins.copyError');
				return false;
			}
			
			$upgradeFile = $pluginDest . DIRECTORY_SEPARATOR . UPGRADE_FILE;
			if(FileManager::fileExists($upgradeFile)) {
				$params = PluginManagementHandler::setConnectionParams();
				$installer = &new Upgrade($params, $upgradeFile, true);

				if (!$installer->execute()) {
					$templateMgr->assign('message', array('manager.plugins.upgradeFailed', $installer->getErrorString()));
					return false;
				}
			}
			
			$installedPlugin->setCurrent(0);
			$pluginVersion->setCurrent(1);
			$versionDao->insertVersion($pluginVersion);
			
			$templateMgr->assign('message', array('manager.plugins.upgradeSuccessful', $pluginVersion->getVersionString()));
			$templateMgr->assign('uploaded', true);
			$templateMgr->assign('error', false);
			
			return true;
		}
	}
	
	/**
	 * Delete a plugin from the system
	 * @param plugin string
	 */
	function deletePlugin($plugin) {
		$templateMgr =& TemplateManager::getManager();
		parent::setupTemplate(true);
		
		$templateMgr->assign('path', 'delete');
		$templateMgr->assign('deleted', false);
		$templateMgr->assign('error', false);
		$templateMgr->assign('pageHierarchy', PluginManagementHandler::setBreadcrumbs(true));

		$versionDao =& DAORegistry::getDAO('VersionDAO'); 
		$installedPlugin = $versionDao->getCurrentVersion($plugin);
		
		if ($installedPlugin) {
			
			$pluginType = explode(".", $installedPlugin->getProductType());
			$pluginDest = Core::getBaseDir() . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $pluginType[1] . DIRECTORY_SEPARATOR . $plugin;
			
			FileManager::rmtree($pluginDest);
			if(FileManager::fileExists($pluginDest, 'dir')) {
				$templateMgr->assign('error', true);
				$templateMgr->assign('message', 'manager.plugins.deleteError');
			} else {
				$versionDao->disableVersion($plugin);
				$templateMgr->assign('deleted', true);	
			}
			
		} else {
			$templateMgr->assign('error', true);
			$templateMgr->assign('message', 'manager.plugins.doesNotExist');
		}
		
		$templateMgr->display('manager/plugins/managePlugins.tpl');
	}
	
	/**
	 * Checks to see if local version of plugin is newer than installed version
	 * @param $pluginName string Product name of plugin
	 * @param $newVersion Version Version object of plugin to check against database
	 * @return boolean	 
	 */
	function checkIfNewer($pluginName, $newVersion) {
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$installedPlugin = $versionDao->getCurrentVersion($pluginName);

		if (!$installedPlugin) return false;
		if ($installedPlugin->compare($newVersion) > 0) return true;
		else return false;
	}
	
	/**
	 * Set the page's breadcrumbs
	 * @param $subclass boolean
	 */
	function setBreadcrumbs($subclass = false) {
		$templateMgr = &TemplateManager::getManager();
		$pageCrumbs = array(
			array(
				Request::url(null, 'user'),
				'navigation.user',
				false
			),
			array(
				Request::url(null, 'manager'),
				'manager.journalManagement',
				false
			)
		);
		
		if ($subclass) {
			$pageCrumbs[] = array(
				Request::url(null, 'manager', 'plugins'),
				'manager.plugins.pluginManagement',
				false
			);
		}

		return $pageCrumbs;
	}
	
	/**
	 * Load database connection parameters into an array (needed for upgrade).
	 * @return array
	 */
	function setConnectionParams() {
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
