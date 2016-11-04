<?php

/**
 * @file controllers/tab/settings/masthead/form/MastheadForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class MastheadForm
 * @ingroup controllers_tab_settings_masthead_form
 *
 * @brief Form to edit masthead settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class VersioningForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function VersioningForm($wizardMode = false) {
		$settings = array(
			'versioningEnabled' => 'bool',
		);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/versioning/form/versioningForm.tpl', $wizardMode);
	
	}

}

?>
