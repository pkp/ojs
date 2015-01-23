<?php

/**
 * @file controllers/tab/settings/permissions/form/OJSPermissionSettingsForm.inc.php
 *
 * Copyright (c) 2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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
	function OJSPermissionSettingsForm($wizardMode = false) {
		parent::PermissionSettingsForm(
			array(
				'copyrightYearBasis' => 'string',
			),
			$wizardMode
		);
	}
}

?>
