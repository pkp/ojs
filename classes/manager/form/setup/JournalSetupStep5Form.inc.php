<?php

/**
 * JournalSetupStep5Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package manager.form.setup
 *
 * Form for Step 5 of journal setup.
 *
 * $Id$
 */

import("manager.form.setup.JournalSetupForm");

class JournalSetupStep5Form extends JournalSetupForm {
	
	function JournalSetupStep5Form() {
		parent::JournalSetupForm(
			5,
			array(
				'headerTitleType' => 'int',
				'journalHeaderTitle' => 'string',
				'journalHeaderTitleImage' => 'string',
				'navItems' => 'object'
				
			)
		);
	}
}

?>