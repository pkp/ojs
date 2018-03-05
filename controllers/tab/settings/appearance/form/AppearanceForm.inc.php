<?php

/**
 * @file controllers/tab/settings/appearance/form/AppearanceForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
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
	function __construct($wizardMode = false) {
		parent::__construct($wizardMode);
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
