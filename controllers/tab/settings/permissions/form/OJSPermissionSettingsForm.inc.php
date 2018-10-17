<?php

/**
 * @file controllers/tab/settings/permissions/form/OJSPermissionSettingsForm.inc.php
 *
 * Copyright (c) 2016-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OJSPermissionSettingsForm
 * @ingroup controllers_tab_settings_indexing_form
 *
 * @brief Form to edit content permission settings. (Extends the pkp-lib form.)
 */

import('lib.pkp.controllers.tab.settings.permissions.form.PermissionSettingsForm');

class OJSPermissionSettingsForm extends PermissionSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false) {
		parent::__construct(
			array(
				'enableAuthorSelfArchive' => 'bool',
				'authorSelfArchivePolicy' => 'string',				
				'copyrightYearBasis' => 'string',
			),
			$wizardMode
		);
	}

	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames
	 */
	function getLocaleFieldNames() {
		return array_merge(parent::getLocaleFieldNames(), array('authorSelfArchivePolicy'));
	}

	/**
	 * @copydoc ContextSettingsForm::fetch
	 */
	function fetch($request, $params = null) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('scheduledTasksEnabled', (boolean) Config::getVar('general', 'scheduled_tasks'));
		return parent::fetch($request, $params);
	}

}


