<?php

/**
 * @file controllers/tab/settings/details/form/DetailsForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Details
 * @ingroup controllers_tab_settings_details_form
 *
 * @brief Form for Step 1 of journal setup.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class DetailsForm extends ContextSettingsForm {
	/**
	 * Constructor.
	 */
	function DetailsForm($wizardMode = false) {
		$settings = array(
			'history' => 'string'
		);
		parent::ContextSettingsForm($settings, 'controllers/tab/settings/details/form/detailsForm.tpl', $wizardMode);

		// Validation checks for this form
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('history');
	}
}

?>
