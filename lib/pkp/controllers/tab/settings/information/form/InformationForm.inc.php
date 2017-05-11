<?php

/**
 * @file controllers/tab/settings/information/form/InformationForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class InformationForm
 * @ingroup controllers_tab_settings_information_form
 *
 * @brief Form to edit context information.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class InformationForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false) {
		$settings = array(
			'readerInformation' => 'string',
			'authorInformation' => 'string',
			'librarianInformation' => 'string'
		);

		parent::__construct($settings, 'controllers/tab/settings/information/form/informationForm.tpl', $wizardMode);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('readerInformation', 'authorInformation', 'librarianInformation');
	}
}

?>
