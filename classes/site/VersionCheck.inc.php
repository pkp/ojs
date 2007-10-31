<?php

/**
 * @file VersionCheck.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 * @class VersionCheck
 *
 * VersionCheck class.
 * Provides methods to check for the latest version of OJS.
 *
 * $Id$
 */

define('VERSION_CHECK_URL', 'http://pkp.sfu.ca/ojs/xml/ojs-version.xml');
define('VERSION_CODE_PATH', 'dbscripts/xml/version.xml');

import('db.XMLDAO');
import('site.Version');

class VersionCheck {

	/**
	 * Return information about the latest available version.
	 * @return array
	 */
	function &getLatestVersion() {
		$returner = &VersionCheck::parseVersionXML(VERSION_CHECK_URL);
		return $returner;
	}

	/**
	 * Return the currently installed database version.
	 * @return Version
	 */
	function &getCurrentDBVersion() {
		$versionDao = &DAORegistry::getDAO('VersionDAO');
		$dbVersion = &$versionDao->getCurrentVersion();
		return $dbVersion;
	}

	/**
	 * Return the current code version.
	 * @return Version
	 */
	function &getCurrentCodeVersion() {
		$versionInfo = VersionCheck::parseVersionXML(VERSION_CODE_PATH);
		if ($versionInfo) {
			$version = $versionInfo['version'];
		} else {
			$version = false;
		}
		return $version;
	}

	/**
	 * Parse information from a version XML file.
	 * @return array
	 */
	function &parseVersionXML($url) {
		$xmlDao = &new XMLDAO();
		$data = $xmlDao->parseStruct($url, array());
		if (!$data) {
			$result = false;
			return $result;
		}

		// FIXME validate parsed data?
		$versionInfo = array(
			'application' => $data['application'][0]['value'],
			'release' => $data['release'][0]['value'],
			'tag' => $data['tag'][0]['value'],
			'date' => $data['date'][0]['value'],
			'info' => $data['info'][0]['value'],
			'package' => $data['package'][0]['value'],
			'patch' => array(),
			'version' => Version::fromString($data['release'][0]['value'])
		);

		foreach ($data['patch'] as $patch) {
			$versionInfo['patch'][$patch['attributes']['from']] = $patch['value'];
		}

		return $versionInfo;
	}

	/**
	 * Find the applicable patch for the current code version (if available).
	 * @param $versionInfo array as returned by parseVersionXML()
	 * @param $codeVersion as returned by getCurrentCodeVersion()
	 * @return string
	 */
	function getPatch(&$versionInfo, $codeVersion = null) {
		if (!isset($codeVersion)) {
			$codeVersion = &VersionCheck::getCurrentCodeVersion();
		}
		if (isset($versionInfo['patch'][$codeVersion->getVersionString()])) {
			return $versionInfo['patch'][$codeVersion->getVersionString()];
		}
		return null;
	}

	/**
	 * Return URL to the remote version check script.
	 * @return array
	 */
	function getVersionCheckUrl() {
		return VERSION_CHECK_URL;
	}

}

?>
