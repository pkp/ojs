<?php

/**
 * SiteDAO.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package site
 *
 * Class for Site DAO.
 * Operations for retrieving and modifying the Site object.
 *
 * $Id$
 */

class SiteDAO extends DAO {

	/**
	 * Constructor.
	 */
	function SiteDAO() {
		parent::DAO();
	}
	
	/**
	 * Retrieve site information.
	 * @return Site
	 */
	function &getSite() {
		$result = &$this->retrieve(
			'SELECT * FROM site'
		);
		
		if ($result->RecordCount() == 0) {
			return null;
			
		} else {
			return $this->_returnSiteFromRow($result->GetRowAssoc(false));
		}
	}
	
	/**
	 * Internal function to return a Site object from a row.
	 * @param $row array
	 * @return Site
	 */
	function &_returnSiteFromRow(&$row) {
		$site = &new Site();
		$site->setTitle($row['title']);
		$site->setIntro($row['intro']);
		
		return $site;
	}
	
	/**
	 * Update existing site information.
	 * @param $site Site
	 */
	function updateSite(&$site) {
		return $this->update(
			'UPDATE site
				SET
					title = ?,
					intro = ?',
			array(
				$site->getTitle(),
				$site->getIntro()
			)
		);
	}
	
}

?>
