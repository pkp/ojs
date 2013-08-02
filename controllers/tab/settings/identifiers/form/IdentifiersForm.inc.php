<?php

/**
 * @file controllers/tab/settings/identifiers/form/IdentifiersForm.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IdentifiersForm
 * @ingroup controllers_tab_settings_siteIdentifiersOptions_form
 *
 * @brief Form to edit site access options.
 */

import('lib.pkp.classes.controllers.tab.settings.form.ContextSettingsForm');

class IdentifiersForm extends ContextSettingsForm {

	/**
	 * Constructor.
	 */
	function IdentifiersForm($wizardMode = false) {
		parent::ContextSettingsForm(
			array(
				'enablePublicArticleId' => 'bool',
				'enablePublicIssueId' => 'bool',
				'enablePublicGalleyId' => 'bool',
				'enablePageNumber' => 'bool',
			),
			'controllers/tab/settings/identifiers/form/identifiersForm.tpl',
			$wizardMode
		);
	}
}

?>
