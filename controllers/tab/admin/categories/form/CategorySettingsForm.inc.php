<?php

/**
 * @file controllers/tab/settings/announcements/form/CategorySettingsForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class CategorySettingsForm
 * @ingroup controllers_tab_settings_announcements_form
 *
 * @brief Form to edit announcement settings.
 */

import('lib.pkp.classes.form.Form');

class CategorySettingsForm extends Form {
	/**
	 * Constructor.
	 */
	function CategorySettingsForm() {
		parent::Form('controllers/tab/admin/categories/form/categorySettingsForm.tpl');
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $params = array()) {
		return parent::fetch($request);
	}

	/**
	 * @copydoc Form::initData()
	 */
	function initData($request) {
		$site = $request->getSite();
		$this->_data = array(
			'categoriesEnabled' => $site->getSetting('categoriesEnabled')
		);
	}

	/**
	 * @copydoc Form::readUserVars()
	 */
	function readInputData() {
		$this->readUserVars(array('categoriesEnabled'));
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute($request) {
		$site = $request->getSite();
		$site->updateSetting('categoriesEnabled', (int) $this->getData('categoriesEnabled'));
	}
}

?>
