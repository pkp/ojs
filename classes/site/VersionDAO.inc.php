<?php

/**
 * @file VersionDAO.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 * @class VersionDAO
 *
 * Class for Version DAO.
 * Operations for retrieving and modifying Version objects.
 *
 * $Id$
 */

import('site.Version');

class VersionDAO extends DAO {
	/**
	 * Retrieve the current version.
	 * @return Version
	 */
	function &getCurrentVersion() {
		$result = &$this->retrieve(
			'SELECT * FROM versions WHERE current = 1'
		);

		$returner = null;
		if ($result->RecordCount() != 0) {
			$returner = &$this->_returnVersionFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $returner;
	}
	
	/**
	 * Retrieve the complete version history, ordered by date (most recent first).
	 * @return array Versions
	 */
	function &getVersionHistory() {
		$versions = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM versions ORDER BY date_installed DESC'
		);
		
		while (!$result->EOF) {
			$versions[] = $this->_returnVersionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}

		$result->Close();
		unset($result);
		
		return $versions;
	}
	
	/**
	 * Internal function to return a Version object from a row.
	 * @param $row array
	 * @return Version
	 */
	function &_returnVersionFromRow(&$row) {
		$version = &new Version();
		$version->setMajor($row['major']);
		$version->setMinor($row['minor']);
		$version->setRevision($row['revision']);
		$version->setBuild($row['build']);
		$version->setDateInstalled($this->datetimeFromDB($row['date_installed']));
		$version->setCurrent($row['current']);

		HookRegistry::call('VersionDAO::_returnVersionFromRow', array(&$version, &$row));

		return $version;
	}
	
	/**
	 * Insert a new version.
	 * @param $version Version
	 */
	function insertVersion(&$version) {
		if ($version->getCurrent()) {
			// Version to insert is the new current, reset old current
			$this->update('UPDATE versions SET current = 0 WHERE current = 1');
		}
		if ($version->getDateInstalled() == null) {
			$version->setDateInstalled(Core::getCurrentDate());
		}
		
		return $this->update(
			sprintf('INSERT INTO versions
				(major, minor, revision, build, date_installed, current)
				VALUES
				(?, ?, ?, ?, %s, ?)',
				$this->datetimeToDB($version->getDateInstalled())),
			array(
				$version->getMajor(),
				$version->getMinor(),
				$version->getRevision(),
				$version->getBuild(),
				$version->getCurrent()
			)
		);
	}
}

?>
