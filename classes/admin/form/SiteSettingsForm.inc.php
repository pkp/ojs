<?php

/**
 * @file classes/admin/form/SiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteSettingsForm
 * @ingroup admin_form
 * @see PKPSiteSettingsForm
 *
 * @brief Form to edit site settings.
 */

import('lib.pkp.classes.admin.form.PKPSiteSettingsForm');

class SiteSettingsForm extends PKPSiteSettingsForm {
	/**
	 * Constructor.
	 */
	function SiteSettingsForm() {
		parent::PKPSiteSettingsForm();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');
		$journals =& $journalDao->getJournalTitles();
		$templateMgr =& TemplateManager::getManager();

		$allThemes =& PluginRegistry::loadCategory('themes');
		$themes = array();
		foreach ($allThemes as $key => $junk) {
			$plugin =& $allThemes[$key]; // by ref
			$themes[basename($plugin->getPluginPath())] =& $plugin;
			unset($plugin);
		}
		$templateMgr->assign('themes', $themes);

		$templateMgr->assign('redirectOptions', $journals);

		return parent::display();
	}

	/**
	 * Initialize the form from the current settings.
	 */
	function initData() {
		parent::initData();

		$siteDao =& DAORegistry::getDAO('SiteDAO');
		$site =& $siteDao->getSite();

		$this->_data['useAlphalist'] = $site->getSetting('useAlphalist');
		$this->_data['usePaging'] = $site->getSetting('usePaging');
	}

	/**
	 * Assign user-submitted data to form.
	 */
	function readInputData() {
		$this->readUserVars(array('useAlphalist', 'usePaging'));
		return parent::readInputData();
	}

	/**
	 * Save the from parameters.
	 */
	function execute() {
		parent::execute();

		$siteSettingsDao =& $this->siteSettingsDao;
		$siteSettingsDao->updateSetting('useAlphalist', (boolean) $this->getData('useAlphalist'), 'bool');
		$siteSettingsDao->updateSetting('usePaging', (boolean) $this->getData('usePaging'), 'bool');
	}
}

?>
