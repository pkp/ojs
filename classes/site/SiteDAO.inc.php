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

import('site.Site');

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
		$site = null;
		$result = $this->retrieve(
			'SELECT * FROM site'
		);
		
		if ($result->RecordCount() != 0) {
			$site = $this->_returnSiteFromRow($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $site;
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
		$site->setAbout($row['about']);
		$site->setJournalRedirect($row['journal_redirect']);
		$site->setContactName($row['contact_name']);
		$site->setContactEmail($row['contact_email']);
		$site->setMinPasswordLength($row['min_password_length']);
		$site->setLocale($row['locale']);
		$site->setInstalledLocales(isset($row['installed_locales']) && !empty($row['installed_locales']) ? explode(':', $row['installed_locales']) : array());
		$site->setSupportedLocales(isset($row['supported_locales']) && !empty($row['supported_locales']) ? explode(':', $row['supported_locales']) : array());
		$site->setProfileLocalesEnabled($row['profile_locales']);

		return $site;
	}
	
	/**
	 * Insert site information.
	 * @param $site Site
	 */
	function insertSite(&$site) {
		return $this->update(
			'INSERT INTO site
				(title, intro, about, journal_redirect, contact_name, contact_email, min_password_length, locale, installed_locales, supported_locales, profile_locales)
				VALUES
				(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$site->getTitle(),
				$site->getIntro(),
				$site->getAbout(),
				$site->getJournalRedirect(),
				$site->getContactName(),
				$site->getContactEmail(),
				$site->getMinPasswordLength(),
				$site->getLocale(),
				join(':', $site->getInstalledLocales()),
				join(':', $site->getSupportedLocales()),
				$site->getProfileLocalesEnabled() == null ? 0 : $site->getProfileLocalesEnabled()
			)
		);
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
					intro = ?,
					about = ?,
					journal_redirect = ?,
					contact_name = ?,
					contact_email = ?,
					min_password_length = ?,
					locale = ?,
					installed_locales = ?,
					supported_locales = ?,
					profile_locales = ?',
			array(
				$site->getTitle(),
				$site->getIntro(),
				$site->getAbout(),
				$site->getJournalRedirect(),
				$site->getContactName(),
				$site->getContactEmail(),
				$site->getMinPasswordLength(),
				$site->getLocale(),
				join(':', $site->getInstalledLocales()),
				join(':', $site->getSupportedLocales()),
				$site->getProfileLocalesEnabled() == null ? 0 : $site->getProfileLocalesEnabled()
			)
		);
	}
	
}

?>
