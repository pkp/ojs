<?php

/**
 * @file controllers/tab/settings/affiliation/form/AffiliationForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AffiliationForm
 * @ingroup controllers_tab_settings_affiliation_form
 *
 * @brief Form to edit affiliation and support information.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class AffiliationForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 * @param $wizardMode boolean
	 */
	function __construct($wizardMode = false) {
		$settings = array(
			'sponsorNote' => 'string',
			'contributorNote' => 'string'
		);

		parent::__construct($settings, 'controllers/tab/settings/affiliation/form/affiliationForm.tpl', $wizardMode);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('sponsorNote', 'contributorNote');
	}
}

?>
