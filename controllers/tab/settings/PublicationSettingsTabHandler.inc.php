<?php

/**
 * @file controllers/tab/settings/PublicationSettingsTabHandler.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicationSettingsTabHandler
 * @ingroup controllers_tab_settings
 *
 * @brief Handle AJAX operations for tabs on Publication Process page.
 */

// Import the base Handler.
import('lib.pkp.controllers.tab.settings.ManagerSettingsTabHandler');

class PublicationSettingsTabHandler extends ManagerSettingsTabHandler {
	/**
	 * Constructor
	 */
	function PublicationSettingsTabHandler() {
		parent::ManagerSettingsTabHandler();
		$this->setPageTabs(array(
			'submissionStage' => 'lib.pkp.controllers.tab.settings.submissionStage.form.SubmissionStageForm',
			'emailTemplates' => 'lib.pkp.controllers.tab.settings.emailTemplates.form.EmailTemplatesForm'
		));
	}
}

?>
