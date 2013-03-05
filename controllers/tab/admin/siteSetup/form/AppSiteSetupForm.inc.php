<?php

/**
 * @file controllers/tab/admin/siteSetup/form/AppSiteSetupForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AppSiteSetupForm
 * @ingroup controllers_tab_admin_siteSetup_form
 *
 * @brief Form to edit site settings.
 */

import('lib.pkp.controllers.tab.settings.siteSetup.form.SiteSetupForm');

class AppSiteSetupForm extends SiteSetupForm {
	/**
	 * Constructor.
	 */
	function SiteSettingsForm() {
		parent::SiteSetupForm();
	}

	/**
	 * @see SiteSetupForm::fetch()
	 */
	function fetch($request, $params = null) {
		$application = PKPApplication::getApplication();
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('availableMetricTypes', $application->getMetricTypes(true));

		return parent::fetch($request, $params = null);
	}

	/**
	 * @see SiteSetupForm::initData()
	 */
	function initData($request) {
		parent::initData($request);

		$site = $request->getSite();
		$siteDao = DAORegistry::getDAO('SiteDAO');
		$this->setData('defaultMetricType', $site->getSetting('defaultMetricType'));
	}

	/**
	 * @see SiteSetupForm::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('defaultMetricType'));
		return parent::readInputData();
	}

	/**
	 * @see SiteSetupForm::execute()
	 */
	function execute() {
		parent::execute();

		$siteSettingsDao =& $this->siteSettingsDao; /* @var $siteSettingsDao SiteSettingsDAO */
		$siteSettingsDao->updateSetting('defaultMetricType', $this->getData('defaultMetricType'), 'string');
	}
}

?>
