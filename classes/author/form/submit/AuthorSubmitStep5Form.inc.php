<?php

/**
 * AuthorSubmitStep5Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 5 of author submit.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep5Form extends AuthorSubmitForm {
	
	function AuthorSubmitStep5Form() {
		parent::AuthorSubmitForm(
			5,
			array(
				'headerTitleType' => 'int',
				'journalHeaderTitle' => 'string',
				'journalHeaderTitleImage' => 'string'
			)
		);
	}
	
}

?>
