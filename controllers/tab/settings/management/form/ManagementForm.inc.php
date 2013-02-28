<?php

/**
 * @file controllers/tab/settings/management/form/ManagementForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep4Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 4 of journal setup.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class ManagementForm extends ContextSettingsForm {
	/**
	 * Constructor.
	 */
	function ManagementForm($wizardMode = false) {
		$settings = array(
			'pubFreqPolicy' => 'string',
			'useCopyeditors' => 'bool',
			'copyeditInstructions' => 'string',
			'useLayoutEditors' => 'bool',
			'layoutInstructions' => 'string',
			'provideRefLinkInstructions' => 'bool',
			'refLinkInstructions' => 'string',
			'useProofreaders' => 'bool',
			'proofInstructions' => 'string',
			'publishingMode' => 'int',
			'enablePublicIssueId' => 'bool',
			'enablePublicArticleId' => 'bool',
			'enablePublicGalleyId' => 'bool',
			'enablePublicSuppFileId' => 'bool',
			'enablePageNumber' => 'bool'
		);
		parent::ContextSettingsForm($settings, 'controllers/tab/settings/management/form/managementForm.tpl', $wizardMode);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('pubFreqPolicy', 'copyeditInstructions', 'layoutInstructions', 'refLinkInstructions', 'proofInstructions');
	}
}

?>
