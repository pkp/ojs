<?php

/**
 * @file controllers/tab/settings/submissionStage/form/SubmissionStageForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubmissionStageForm
 * @ingroup controllers_tab_settings_submissionStage_form
 *
 * @brief Form to edit submission stage information.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

// We delegate some settings on this form to the MetadataGridHandler
import('lib.pkp.controllers.grid.settings.metadata.MetadataGridHandler');

class SubmissionStageForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 * @param $wizardMode boolean True iff in wizard mode.
	 */
	function __construct($wizardMode = false) {
		$this->addCheck(new FormValidatorEmail($this, 'copySubmissionAckAddress'));

		// Add the list of metadata field-related settings per the MetadataGridHandler
		// e.g.: typeEnabledSubmission; typeEnabledWorkflow; typeRequired
		$metadataFieldNames = array_keys(MetadataGridHandler::getNames());
		$metadataSettings = array_merge(
			array_map(function($n) {return $n.'EnabledSubmission';}, $metadataFieldNames),
			array_map(function($n) {return $n.'EnabledWorkflow';}, $metadataFieldNames),
			array_map(function($n) {return $n.'Required';}, $metadataFieldNames)
		);

		parent::__construct(
			array_merge(
				array(
					'copySubmissionAckPrimaryContact' => 'bool',
					'copySubmissionAckAddress' => 'string',
					'authorGuidelines' => 'string',
				),
				array_combine($metadataSettings, array_fill(0, count($metadataSettings), 'bool'))
			),
			'controllers/tab/settings/submissionStage/form/submissionStageForm.tpl',
			$wizardMode
		);
	}

	/**
	 * @copydoc Form::getLocaleFieldNames()
	 */
	function getLocaleFieldNames() {
		return array('authorGuidelines');
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $params = null) {
		$templateMgr = TemplateManager::getManager($request);

		import('lib.pkp.classes.mail.MailTemplate');
		$mail = new MailTemplate('SUBMISSION_ACK');
		$templateMgr->assign('submissionAckDisabled', !$mail->isEnabled());

		return parent::fetch($request, $params);
	}
}

?>
