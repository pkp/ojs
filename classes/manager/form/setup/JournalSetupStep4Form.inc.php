<?php

/**
 * @file classes/manager/form/setup/JournalSetupStep4Form.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSetupStep4Form
 * @ingroup manager_form_setup
 *
 * @brief Form for Step 4 of journal setup.
 */

import('classes.manager.form.setup.JournalSetupForm');

class JournalSetupStep4Form extends JournalSetupForm {
	/**
	 * Constructor.
	 */
	function JournalSetupStep4Form() {
		parent::JournalSetupForm(
			4,
			array(
				'disableUserReg' => 'bool',
				'allowRegReader' => 'bool',
				'allowRegAuthor' => 'bool',
				'allowRegReviewer' => 'bool',
				'restrictSiteAccess' => 'bool',
				'restrictArticleAccess' => 'bool',
				'publicationFormatVolume' => 'bool',
				'publicationFormatNumber' => 'bool',
				'publicationFormatYear' => 'bool',
				'publicationFormatTitle' => 'bool',
				'initialVolume' => 'int',
				'initialNumber' => 'int',
				'initialYear' => 'int',
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
				'showGalleyLinks' => 'bool',
				'openAccessPolicy' => 'string',
				'enableAnnouncements' => 'bool',
				'enableAnnouncementsHomepage' => 'bool',
				'numAnnouncementsHomepage' => 'int',
				'announcementsIntroduction' => 'string',
				'volumePerYear' => 'int',
				'issuePerVolume' => 'int',
				'enablePublicIssueId' => 'bool',
				'enablePublicArticleId' => 'bool',
				'enablePublicGalleyId' => 'bool',
				'enablePublicSuppFileId' => 'bool',
				'enablePageNumber' => 'bool'
			)
		);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('pubFreqPolicy', 'copyeditInstructions', 'layoutInstructions', 'refLinkInstructions', 'proofInstructions', 'openAccessPolicy', 'announcementsIntroduction');
	}
}

?>
