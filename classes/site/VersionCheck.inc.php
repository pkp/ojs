<?php

/**
 * VersionCheck.inc.php
 *
 * Copyright (c) 2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 *
 * VersionCheck class.
 * Provides methods to check for the latest version of OJS.
 *
 * $Id$
 */

define('VERSION_CHECK_URL', 'http://pkp.sfu.ca/ojs/ojs-version.xml');
define('VERSION_CODE_PATH', 'dbscripts/xml/version.xml');

import('db.XMLDAO');
import('site.Version');

class VersionCheck {

	/**
	 * Return information about the latest available version.
	 * @return array
	 */
	function &getLatestVersion() {
		return VersionCheck::parseVersionXML(VERSION_CHECK_URL);
	}
	
	/**
	 * Return the currently installed database version.
	 * @return Version
	 */
	function &getCurrentDBVersion() {
		$versionDao = &DAORegistry::getDAO('VersionDAO');
		return $versionDao->getCurrentVersion();
	}
	
	/**
	 * Return the current code version.
	 * @return Version
	 */
	function &getCurrentCodeVersion() {
		$versionInfo = VersionCheck::parseVersionXML(VERSION_CODE_PATH);
		if ($versionInfo) {
			return $versionInfo['version'];
		} else {
			return false;
		}
	}
	
	/**
	 * Parse information from a version XML file.
	 * @return array
	 */
	function &parseVersionXML($url) {
		$xmlDao = &new XMLDAO();
		$data = $xmlDao->parseStruct($url, array());
		if (!$data) {
			return false;
		}
		// FIXME validate parsed data?
		return array(
			'application' => $data['application'][0]['value'],
			'release' => $data['release'][0]['value'],
			'tag' => $data['tag'][0]['value'],
			'date' => $data['date'][0]['value'],
			'info' => $data['info'][0]['value'],
			'package' => $data['package'][0]['value'],
			'patch' => $data['patch'][0]['value'],
			'version' => Version::fromString($data['release'][0]['value'])
		);
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
