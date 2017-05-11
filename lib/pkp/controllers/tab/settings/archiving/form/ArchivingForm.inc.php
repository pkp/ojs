<?php

/**
 * @file controllers/tab/settings/archiving/form/ArchivingForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArchivingForm
 * @ingroup controllers_tab_settings_archiving_form
 *
 * @brief Form to edit archiving information.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class ArchivingForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false) {
		$settings = array(
			'enableLockss' => 'bool',
			'enableClockss' => 'bool',
		);

		parent::__construct($settings, 'controllers/tab/settings/archiving/form/archivingForm.tpl', $wizardMode);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('lockssLicense', 'clockssLicense');
	}
}

?>
