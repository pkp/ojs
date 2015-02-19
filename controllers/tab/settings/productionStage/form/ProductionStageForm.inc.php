<?php

/**
 * @file controllers/tab/settings/productionStage/form/ProductionStageForm.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ProductionStageForm
 * @ingroup controllers_tab_settings_productionStage_form
 *
 * @brief Form to edit production stage settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class ProductionStageForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function ProductionStageForm($wizardMode = false) {
		$settings = array(
			'publisherNote' => 'string',
			'publisherInstitution' => 'string',
			'publisherUrl' => 'string'
		);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/productionStage/form/productionStageForm.tpl', $wizardMode);

		$this->addCheck(new FormValidatorUrl($this, 'publisherUrl', 'optional', 'user.profile.form.urlInvalid'));
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('publisherNote');
	}
}

?>
