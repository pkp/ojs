<?php

/**
 * @file controllers/tab/settings/siteAccessOptions/form/SiteAccessOptionsForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
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
	function SiteAccessOptionsForm($wizardMode = false) {
		$settings = array(
			'disableUserReg' => 'bool',
			'restrictSiteAccess' => 'bool',
			'restrictArticleAccess' => 'bool',
			'showGalleyLinks' => 'bool'
		);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/siteAccessOptions/form/siteAccessOptionsForm.tpl', $wizardMode);
	}

}

?>
