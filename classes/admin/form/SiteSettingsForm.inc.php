<?php

/**
 * SiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package admin.form
 *
 * Form to edit site settings.
 *
 * $Id$
 */

class SiteSettingsForm extends Form {
	
	/**
	 * Constructor.
	 */
	function SiteSettingsForm() {
		parent::Form('admin/settings.tpl');
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'title', 'required', 'admin.settings.form.titleRequired'));
	}
	
	/**
	 * Display the form.
	 */
	function display() {
		$journalDao = &DAORegistry::getDAO('JournalDAO');
		$journals = &$journalDao->getJournalTitles();
		$templateMgr = &TemplateManager::getManager();
		$templateMgr->assign('redirectOptions', array('' => Locale::Translate('admin.settings.noJournalRedirect')) + $journals);

		parent::display();
	}
	
	/**
	 * Initialize form data from current settings.
	 */
	function initData() {
		$siteDao = &DAORegistry::getDAO('SiteDAO');
		$site = &$siteDao->getSite();
		
		$this->_data = array(
			'title' => $site->getTitle(),
			'intro' => $site->getIntro(),
			'redirect' => $site->getJournalRedirect()
		);
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array('title', 'intro', 'redirect')
		);
	}
	
	/**
	 * Save site settings.
	 */
	function execute() {
		$siteDao = &DAORegistry::getDAO('SiteDAO');
		$site = &$siteDao->getSite();
		
		$site->setTitle($this->getData('title'));
		$site->setIntro($this->getData('intro'));
		$site->setJournalRedirect($this->getData('redirect'));
		
		$siteDao->updateSite($site);
	}
	
}

?>
