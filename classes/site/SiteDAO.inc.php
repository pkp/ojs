<?php

/**
 * @file classes/site/SiteDAO.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteDAO
 * @ingroup site
 * @see Site
 *
 * @brief Operations for retrieving and modifying the Site object.
 */

// $Id$


import('site.Site');

class SiteDAO extends DAO {
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
			$site = $this->_returnSiteFromRowWithData($result->GetRowAssoc(false));
		}

		$result->Close();
		unset($result);

		return $site;
	}

	function &_returnSiteFromRowWithData(&$row) {
		$site =& $this->_returnSiteFromRow($row, false);
		$this->getDataObjectSettings('site_settings', null, null, $site);

		HookRegistry::call('UserDAO::_returnSiteFromRowWithData', array(&$site, &$row));

		return $site;
	}

	/**
	 * Internal function to return a Site object from a row.
	 * @param $row array
	 * @param $callHook boolean
	 * @return Site
	 */
	function &_returnSiteFromRow(&$row, $callHook = true) {
		$site = &new Site();
		$site->setJournalRedirect($row['journal_redirect']);
		$site->setMinPasswordLength($row['min_password_length']);
		$site->setPrimaryLocale($row['primary_locale']);
		$site->setOriginalStyleFilename($row['original_style_file_name']);
		$site->setInstalledLocales(isset($row['installed_locales']) && !empty($row['installed_locales']) ? explode(':', $row['installed_locales']) : array());
		$site->setSupportedLocales(isset($row['supported_locales']) && !empty($row['supported_locales']) ? explode(':', $row['supported_locales']) : array());

		if ($callHook) HookRegistry::call('SiteDAO::_returnSiteFromRow', array(&$site, &$row));

		return $site;
	}

	/**
	 * Insert site information.
	 * @param $site Site
	 */
	function insertSite(&$site) {
		$returner = $this->update(
			'INSERT INTO site
				(journal_redirect, min_password_length, primary_locale, installed_locales, supported_locales, original_style_file_name)
				VALUES
				(?, ?, ?, ?, ?, ?)',
			array(
				$site->getJournalRedirect(),
				$site->getMinPasswordLength(),
				$site->getPrimaryLocale(),
				join(':', $site->getInstalledLocales()),
				join(':', $site->getSupportedLocales()),
				$site->getOriginalStyleFilename()
			)
		);
		$this->updateLocaleFields($site);
		return $returner;
	}

	function getLocaleFieldNames() {
		return array('pageHeaderTitleType', 'title', 'intro', 'about', 'contactName', 'contactEmail', 'pageHeaderTitleImage');
	}

	function updateLocaleFields(&$site) {
		$this->updateDataObjectSettings('site_settings', $site, array());
	}

	/**
	 * Update existing site information.
	 * @param $site Site
	 */
	function updateSite(&$site) {
		$this->updateLocaleFields($site);
		return $this->update(
			'UPDATE site
				SET
					journal_redirect = ?,
					min_password_length = ?,
					primary_locale = ?,
					installed_locales = ?,
					supported_locales = ?,
					original_style_file_name = ?',
			array(
				$site->getJournalRedirect(),
				$site->getMinPasswordLength(),
				$site->getPrimaryLocale(),
				join(':', $site->getInstalledLocales()),
				join(':', $site->getSupportedLocales()),
				$site->getOriginalStyleFilename()
			)
		);
	}
}

?>
