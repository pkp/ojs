<?php

/**
 * @file controllers/tab/settings/access/form/AccessForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AccessForm
 * @ingroup controllers_tab_settings_access_form
 *
 * @brief Form to edit site access options.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class AccessForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 * @param $wizardMode boolean True IFF wizard mode is to be used.
	 */
	function __construct($wizardMode = false) {
		parent::__construct(
			array(
				'publishingMode' => 'int',
			),
			'controllers/tab/settings/access/form/accessForm.tpl',
			$wizardMode
		);
	}
}

?>
