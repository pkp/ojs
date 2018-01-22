<?php

/**
 * @file controllers/tab/settings/siteAccessOptions/form/SiteAccessOptionsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SiteAccessOptionsForm
 * @ingroup controllers_tab_settings_siteAccessOptions_form
 *
 * @brief Form to edit site access options.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class SiteAccessOptionsForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false) {
		$settings = array(
			'disableUserReg' => 'bool',
			'restrictSiteAccess' => 'bool',
			'restrictArticleAccess' => 'bool',
		);

		parent::__construct($settings, 'controllers/tab/settings/siteAccessOptions/form/siteAccessOptionsForm.tpl', $wizardMode);
	}

}

?>
