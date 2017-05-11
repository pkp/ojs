<?php

/**
 * @file lib/pkp/tests/functional/plugins/importexport/FunctionalImportExportBaseTestCase.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class FunctionalImportExportBaseTestCase
 * @ingroup tests_functional_plugins_importexport
 *
 * @brief Base class to test document export.
 */


import('lib.pkp.tests.WebTestCase');

class FunctionalImportExportBaseTestCase extends WebTestCase {

	//
	// Protected helper methods
	//
	/**
	 * Retrieve the export as an XML string.
	 * @param $pluginUrl string the url to be requested for export.
	 * @param $postParams array additional post parameters
	 * @return string
	 */
	protected function getXmlOnExport($pluginUrl, $postParams = array()) {
		// Prepare HTTP session.
		$curlCh = curl_init ();
		curl_setopt($curlCh, CURLOPT_POST, true);

		// Create a cookie file (required for log-in).
		$cookies = tempnam (sys_get_temp_dir(), 'curlcookies');

		// Log in.
		$loginUrl = $this->baseUrl.'/index.php/test/login/signIn';

		// Bug #8518 safety work-around
		if ($this->password[0] == '@') die('CURL parameters may not begin with @.');

		$loginParams = array(
			'username' => 'admin',
			'password' => $this->password
		);
		curl_setopt($curlCh, CURLOPT_URL, $loginUrl);
		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $loginParams);
		curl_setopt($curlCh, CURLOPT_COOKIEJAR, $cookies);
		self::assertTrue(curl_exec($curlCh));

		// Request export document.
		$exportUrl = $this->baseUrl.'/index.php/test/manager/importexport/plugin/'
			.$pluginUrl;
		curl_setopt($curlCh, CURLOPT_URL, $exportUrl);

		// Bug #8518 safety work-around
		foreach ($postParams as $paramValue) {
			if ($paramValue[0] == '@') die('CURL parameters may not begin with @.');
		}

		curl_setopt($curlCh, CURLOPT_POSTFIELDS, $postParams);
		curl_setopt($curlCh, CURLOPT_HTTPHEADER, array('Accept: application/xml, application/x-gtar, */*'));
		curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlCh, CURLOPT_HEADER, true);
		$response = curl_exec($curlCh);

		do {
			list($header, $response) = explode("\r\n\r\n", $response, 2);
		} while (PKPString::regexp_match('#HTTP/.*100#', $header));

		// Check whether we got a tar file.
		if (PKPString::regexp_match('#Content-Type: application/x-gtar#', $header)) {
			// Save the data to a temporary file.
			$tempfile = tempnam(sys_get_temp_dir(), 'tst');
			file_put_contents($tempfile, $response);

			// Recursively extract tar file.
			$result = $this->extractTarFile($tempfile);
			unlink($tempfile);
		} else {
			$matches = null;
			PKPString::regexp_match_get('#filename="([^"]+)"#', $header, $matches);
			self::assertTrue(isset($matches[1]));
			$result = array($matches[1] => $response);
		}

		// Destroy HTTP session.
		curl_close($curlCh);
		unlink($cookies);
		return $result;
	}

	/**
	 * Retrieve the export as a DOM.
	 * @param $pluginUrl string the url to be requested for export.
	 * @return DOMDocument
	 */
	protected function getDomOnExport($pluginUrl) {
		$xml = $this->getXmlOnExport($pluginUrl);
		self::assertEquals(1, count($xml));
		$dom = new DOMDocument();
		$dom->loadXml(array_pop($xml));
		return $dom;
	}

	/**
	 * Export into XML and return an XPath object on this XML.
	 * @param $pluginUrl string the url to be requested for export.
	 * @return DOMXPath
	 */
	protected function getXpathOnExport($pluginUrl) {
		$dom = $this->getDomOnExport($pluginUrl);

		// Prepare XPath object for testing.
		$xPath = new DOMXPath($dom);
		return $xPath;
	}

	/**
	 * Execute the plug-in via its CLI interface.
	 * @param $pluginName string
	 * @param $args array
	 * @return string CLI output
	 */
	protected function executeCLI($pluginName, $args) {
		ob_start();
		$plugin = $this->instantiatePlugin($pluginName);
		PKPTestHelper::xdebugScream(false);
		$plugin->executeCLI(get_class($this), $args, true);
		PKPTestHelper::xdebugScream(true);
		return ob_get_clean();
	}

	/**
	 * Instantiate an import-export plugin.
	 * @param $pluginName string
	 * @return ImportExportPlugin
	 */
	protected function instantiatePlugin($pluginName) {
		// Load all import-export plug-ins.
		if (!defined('PWD')) define('PWD', getcwd());
		PluginRegistry::loadCategory('importexport');
		$plugin = PluginRegistry::getPlugin('importexport', $pluginName);
		// self::assertType() has been removed from PHPUnit 3.6
		// but self::assertInstanceOf() is not present in PHPUnit 3.4
		// which is our current test server version.
		// FIXME: change this to assertInstanceOf() after upgrading the
		// test server.
		self::assertTrue(is_a($plugin, 'ImportExportPlugin'));
		return $plugin;
	}

	/**
	 * Recursively unpack the given tar file
	 * and return its contents as an array.
	 * @param $tarFile string
	 * @return array
	 */
	protected function extractTarFile($tarFile) {
		$tarBinary = Config::getVar('cli', 'tar');

		// Cygwin compat.
		$cygwin = Config::getVar('cli', 'cygwin');

		// Make sure we got the tar binary installed.
		self::assertTrue((!empty($tarBinary) && is_executable($tarBinary)) || is_executable($cygwin), 'tar must be installed');

		// Create a temporary directory.
		do {
			$tempdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5(time().mt_rand());
		} while(file_exists($tempdir));
		$fileManager = new FileManager();
		$fileManager->mkdir($tempdir);

		// Extract the tar to the temporary directory.
		if ($cygwin) {
			$tarCommand = $cygwin . " --login -c '" . $tarBinary . ' -C ' . escapeshellarg(cygwinConversion($tempdir)) . ' -xzf ' . escapeshellarg(cygwinConversion($tarFile)) . "'";
		} else {
			$tarCommand = $tarBinary . ' -C ' . escapeshellarg($tempdir) . ' -xzf ' . escapeshellarg($tarFile);
		}
		exec($tarCommand);

		// Read the results into an array.
		$result = array();
		foreach(glob($tempdir . DIRECTORY_SEPARATOR . '*.{tar.gz,xml}', GLOB_BRACE) as $extractedFile) {
			if (substr($extractedFile, -4) == '.xml') {
				// Read the XML file into the result array.
				$result[basename($extractedFile)] = file_get_contents($extractedFile);
			} else {
				// Recursively extract tar files.
				$result[basename($extractedFile)] = $this->extractTarFile($extractedFile);
			}
			unlink($extractedFile);
		}
		rmdir($tempdir);
		ksort($result);
		return $result;
	}
}
?>
