<?php

/**
 * @file controllers/tab/settings/appearance/form/AppearanceForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AppearanceForm
 * @ingroup controllers_tab_settings_appearance_form
 *
 * @brief Form to edit appearance settings.
 */

import('lib.pkp.controllers.tab.settings.appearance.form.PKPAppearanceForm');

class AppearanceForm extends PKPAppearanceForm {
	/**
	 * Constructor.
	 */
	function AppearanceForm($wizardMode = false) {
		parent::PKPAppearanceForm($wizardMode);
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
