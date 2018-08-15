<?php

/**
 * @file controllers/tab/admin/siteSetup/form/AppSiteSetupForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	 * @copydoc SiteSetupForm::fetch()
	 */
	function fetch($request, $params = null) {
		$application = Application::getApplication();
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('availableMetricTypes', $application->getMetricTypes(true));

		return parent::fetch($request, $params = null);
	}

	/**
	 * @copydoc SiteSetupForm::initData()
	 */
	function initData() {
		parent::initData();

		$request = Application::getRequest();
		$site = $request->getSite();
		$this->setData('defaultMetricType', $site->getSetting('defaultMetricType'));

		// Journal list display options
		$this->setData('showTitle', $site->getSetting('showTitle'));
		$this->setData('showThumbnail', $site->getSetting('showThumbnail'));
		$this->setData('showDescription', $site->getSetting('showDescription'));
	}

	/**
	 * @copydoc SiteSetupForm::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('defaultMetricType', 'showTitle', 'showThumbnail', 'showDescription'));
		return parent::readInputData();
	}

	/**
	 * @copydoc SiteSetupForm::execute()
	 */
	function execute() {
		parent::execute();

		$siteSettingsDao = $this->siteSettingsDao; /* @var $siteSettingsDao SiteSettingsDAO */
		$siteSettingsDao->updateSetting('defaultMetricType', $this->getData('defaultMetricType'), 'string');

		// Journal list display options
		$siteSettingsDao->updateSetting('showTitle', $this->getData('showTitle'), 'bool');
		$siteSettingsDao->updateSetting('showThumbnail', $this->getData('showThumbnail'), 'bool');
		$siteSettingsDao->updateSetting('showDescription', $this->getData('showDescription'), 'bool');
	}
}


