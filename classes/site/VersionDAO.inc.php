<?php

/**
 * VersionDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 *
 * Class for Version DAO.
 * Operations for retrieving and modifying Version objects.
 *
 * $Id$
 */

import('site.Version');

class VersionDAO extends DAO {

	/**
	 * Constructor.
	 */
	function VersionDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve the current version.
	 * @return Version
	 */
	function &getCurrentVersion() {
		$result = &$this->retrieve(
			'SELECT * FROM versions WHERE current = 1'
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnVersionFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Retrieve the complete version history, ordered by date (most recent first).
	 * @return array Versions
	 */
	function &getVersionHistory() {
		$versions = array();
		
		$result = &$this->retrieve(
			'SELECT * FROM versions order by date_installed DESC'
		);
		
		while (!$result->EOF) {
			$versions[] = $this->_returnVersionFromRow($result->GetRowAssoc(false));
			$result->MoveNext();
		}
		$result->Close();
		
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
		$version->setDateInstalled($row['date_installed']);
		$version->setCurrent($row['current']);
		
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
		
		return $this->update(
			'INSERT INTO versions
				(major, minor, revision, build, date_installed, current)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$version->getMajor(),
				$version->getMinor(),
				$version->getRevision(),
				$version->getBuild(),
				$version->getDateInstalled() == null ? Core::getCurrentDate() : $version->getDateInstalled(),
				$version->getCurrent()
			)
		);
	}
	
}

?>
