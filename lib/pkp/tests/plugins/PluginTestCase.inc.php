<?php

/**
 * @defgroup tests_plugins Plugin test suite
 */

/**
 * @file tests/plugins/PluginTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PluginTestCase
 * @ingroup tests_plugins
 * @see Plugin
 *
 * @brief Abstract base class for Plugin tests.
 */

require_mock_env('env2');

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.plugins.Plugin');

class PluginTestCase extends DatabaseTestCase {
	/**
	 * @copydoc DatabaseTestCase::getAffectedTables()
	 */
	protected function getAffectedTables() {
		return array(
			'filters', 'filter_settings', 'filter_groups',
			'versions', 'plugin_settings'
		);
	}

	/**
	 * @copydoc PKPTestCase::getMockedRegistryKeys()
	 */
	protected function getMockedRegistryKeys() {
		return array('request', 'hooks');
	}

	/**
	 * Executes the plug-in test.
	 * @param $pluginCategory string
	 * @param $pluginDir string
	 * @param $pluginName string
	 * @param $filterGroups array
	 */
	protected function executePluginTest($pluginCategory, $pluginDir, $pluginName, $filterGroups) {
		// Make sure that the xml configuration is valid.
		$filterConfigFile = 'plugins/'.$pluginCategory.'/'.$pluginDir.'/filter/'.PLUGIN_FILTER_DATAFILE;
		$this->validateXmlConfig(array('./'.$filterConfigFile, './lib/pkp/'.$filterConfigFile));

		// Mock request and router.
		import('lib.pkp.classes.core.PKPRouter');
		import('classes.core.Request');
		$mockRequest = $this->getMock('Request', array('getRouter', 'getUser'));
		$router = new PKPRouter();
		$mockRequest->expects($this->any())
		            ->method('getRouter')
		            ->will($this->returnValue($router));
		$mockRequest->expects($this->any())
		            ->method('getUser')
		            ->will($this->returnValue(null));
		Registry::set('request', $mockRequest);

		// Instantiate the installer.
		import('classes.install.Install');
		$installFile = './lib/pkp/tests/plugins/testPluginInstall.xml';
		$params = $this->getConnectionParams();
		$installer = new Install($params, $installFile, true);

		// Parse the plug-ins version.xml.
		import('lib.pkp.classes.site.VersionCheck');
		self::assertFileExists($versionFile = './plugins/'.$pluginCategory.'/'.$pluginDir.'/version.xml');
		self::assertArrayHasKey('version', $versionInfo = VersionCheck::parseVersionXML($versionFile));
		self::assertInstanceOf('Version', $pluginVersion = $versionInfo['version']);
		$installer->setCurrentVersion($pluginVersion);

		// Install the plug-in.
		self::assertTrue($installer->execute());

		// Reset the hook registry.
		Registry::set('hooks', $nullVar = null);

		// Test whether the installation is idempotent.
		$this->markTestIncomplete('Idempotence test disabled temporarily.');
		// self::assertTrue($installer->execute());

		// Test whether the filter groups have been installed.
		$filterGroupDao = DAORegistry::getDAO('FilterGroupDAO');
		foreach($filterGroups as $filterGroupSymbolic) {
			// Check the group.
			self::assertInstanceOf('FilterGroup', $filterGroupDao->getObjectBySymbolic($filterGroupSymbolic), $filterGroupSymbolic);
		}
	}


	//
	// Protected helper function
	//
	protected function validateXmlConfig($configFiles) {
		foreach($configFiles as $configFile) {
			if(file_exists($configFile)) {
				$xmlDom = new DOMDocument();
				$xmlDom->load($configFile);
				self::assertTrue($xmlDom->validate());
				unset($xmlDom);
			}
		}
	}


	//
	// Private helper function
	//
	/**
	 * Load database connection parameters into an array (needed for upgrade).
	 * @return array
	 */
	private function getConnectionParams() {
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
