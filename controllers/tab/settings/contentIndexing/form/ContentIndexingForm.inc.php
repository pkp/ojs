<?php

/**
 * @file controllers/tab/settings/contentIndexing/form/ContentIndexingForm.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ContentIndexingForm
 * @ingroup controllers_tab_settings_indexing_form
 *
 * @brief Form to edit indexing settings.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class ContentIndexingForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function ContentIndexingForm($wizardMode = false) {
		$settings = array(
			'metaDiscipline' => 'bool',
			'metaSubjectClass' => 'bool',
			'metaSubjectClassTitle' => 'string',
			'metaSubjectClassUrl' => 'string',
			'metaCoverage' => 'bool',
			'metaType' => 'bool',
		);

		parent::ContextSettingsForm($settings, 'controllers/tab/settings/contentIndexing/form/contentIndexingForm.tpl', $wizardMode);
		$this->addCheck(new FormValidatorLocaleURL($this, 'metaSubjectClassUrl', 'optional', 'manager.setup.subjectClassificationURLValid'));
	}


	//
	// Implement template methods from Form.
	//
	/**
	 * Get all locale field names
	 */
	function getLocaleFieldNames() {
		return array('metaSubjectClassTitle', 'metaSubjectClassUrl');
	}
}

?>
