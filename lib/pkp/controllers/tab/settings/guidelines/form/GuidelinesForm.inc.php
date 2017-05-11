<?php

/**
 * @file controllers/tab/settings/guidelines/form/GuidelinesForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class GuidelinesForm
 * @ingroup controllers_tab_settings_guidelines_form
 *
 * @brief Form to edit guidelines information.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class GuidelinesForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false) {
		$settings = array(
			'authorGuidelines' => 'string'
		);

		parent::__construct($settings, 'controllers/tab/settings/guidelines/form/guidelinesForm.tpl', $wizardMode);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('authorGuidelines');
	}
}

?>
