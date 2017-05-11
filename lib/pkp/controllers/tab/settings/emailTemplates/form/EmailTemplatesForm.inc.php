<?php

/**
 * @file controllers/tab/settings/emailTemplates/form/EmailTemplatesForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class EmailTemplatesForm
 * @ingroup controllers_tab_settings_emailTemplates_form
 *
 * @brief Form to edit email identification settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class EmailTemplatesForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function __construct($wizardMode = false) {
		$settings = array(
			'emailSignature' => 'string',
			'envelopeSender' => 'string'
		);

		$this->addCheck(new FormValidatorEmail($this, 'envelopeSender', 'optional', 'user.profile.form.emailRequired'));

		parent::__construct($settings, 'controllers/tab/settings/emailTemplates/form/emailTemplatesForm.tpl', $wizardMode);
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * @copydoc ContextSettingsForm::fetch()
	 */
	function fetch($request) {
		$context = $request->getContext();
		$dispatcher = $request->getDispatcher();
		return parent::fetch($request, array(
			'envelopeSenderDisabled' => !Config::getVar('email', 'allow_envelope_sender') || Config::getVar('email', 'force_default_envelope_sender') && Config::getVar('email', 'default_envelope_sender'),
			'emailVariables' => array(
				'contextName' => $context->getLocalizedName(),
				'senderName' => __('email.senderName'),
				'senderEmail' => __('email.senderEmail'),
			),
		));
	}
}

?>
