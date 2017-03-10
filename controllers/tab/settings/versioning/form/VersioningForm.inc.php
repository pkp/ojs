<?php

/**
 * @file controllers/tab/settings/versioning/form/VersioningForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class VersioningForm
 * @ingroup controllers_tab_settings_versioning_form
 *
 * @brief Form to enable or disable versioning for this journal.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class VersioningForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false) {
		$settings = array(
			'versioningEnabled' => 'bool',
			'versioningPolicy' => 'string',
		);

		parent::__construct($settings, 'controllers/tab/settings/versioning/form/versioningForm.tpl', $wizardMode);
	
	}

}

?>
