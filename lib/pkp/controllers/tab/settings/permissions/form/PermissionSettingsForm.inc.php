<?php

/**
 * @file controllers/tab/settings/permissions/form/PermissionSettingsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PermissionSettingsForm
 * @ingroup controllers_tab_settings_indexing_form
 *
 * @brief Form to edit content permission settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class PermissionSettingsForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($settings = array(), $wizardMode = false) {
		parent::__construct(
			array_merge(
				$settings,
				array(
					'copyrightHolderType' => 'string',
					'copyrightHolderOther' => 'string',
					'copyrightYearBasis' => 'string',
					'copyrightNotice' => 'string',
					'copyrightNoticeAgree' => 'bool',
					'licenseURL' => 'string',
				)
			),
			'controllers/tab/settings/permissions/form/permissionSettingsForm.tpl',
			$wizardMode
		);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * Get all locale field names
	 */
	function getLocaleFieldNames() {
		return array('copyrightNotice', 'copyrightHolderOther');
	}

	/**
	 * @copydoc ContextSettingsForm::fetch
	 */
	function fetch($request, $params = null) {
		$templateMgr = TemplateManager::getManager($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION);
		$templateMgr->assign('ccLicenseOptions', array_merge(
			array('' => 'common.other'),
			Application::getCCLicenseOptions()
		));
		return parent::fetch($request, $params);
	}
}

?>
