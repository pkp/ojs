<?php

/**
 * JournalSetupStep1Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 1 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupStep1Form extends JournalSetupForm {
	
	function JournalSetupStep1Form() {
		parent::JournalSetupForm(
			1,
			array(
				'journalTitle' => 'string',
				'journalInitials' => 'string',
				'issn' => 'string',
				'mailingAddress' => 'string',
				'useEditorialBoard' => 'bool',
				'contactName' => 'string',
				'contactTitle' => 'string',
				'contactAffiliation' => 'string',
				'contactEmail' => 'string',
				'contactPhone' => 'string',
				'contactFax' => 'string',
				'contactMailingAddress' => 'string',
				'supportName' => 'string',
				'supportEmail' => 'string',
				'supportPhone' => 'string',
				'sponsorNote' => 'string',
				'sponsors' => 'object',
				'publisher' => 'object',
				'contributorNote' => 'string',
				'contributors' => 'object',
				'searchDescription' => 'string',
				'searchKeywords' => 'string',
				'customHeaders' => 'string'
			)
		);
		
		// Validation checks for this form
		$this->addCheck(new FormValidator(&$this, 'journalTitle', 'required', 'manager.setup.form.journalTitleRequired'));
		$this->addCheck(new FormValidator(&$this, 'journalInitials', 'required', 'manager.setup.form.journalInitialsRequired'));
	}
	
}

?>
