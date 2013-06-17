<?php

/**
 * @file controllers/tab/settings/appearance/form/OJSAppearanceForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AppearanceForm
 * @ingroup controllers_tab_settings_appearance_form
 *
 * @brief Form to edit appearance settings.
 */

import('lib.pkp.controllers.tab.settings.appearance.form.AppearanceForm');

class OJSAppearanceForm extends AppearanceForm {
	/**
	 * Constructor.
	 */
	function OJSAppearanceForm($wizardMode = false) {
		parent::AppearanceForm($wizardMode);
	}

	/**
	 * Get the images settings name.
	 * @return array
	 */
	function getImagesSettingsName() {
		return array_merge(
			parent::getImagesSettingsName(),
			array('journalThumbnail' => 'manager.setup.journalThumbnail.altText')
		);
	}
}

?>
