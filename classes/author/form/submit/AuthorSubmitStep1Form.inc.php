<?php

/**
 * AuthorSubmitStep1Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 1 of journal author submit.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep1Form extends AuthorSubmitForm {
	
	function AuthorSubmitStep1Form() {
		parent::AuthorSubmitForm(
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
				'contributorNote' => 'string',
				'contributors' => 'object',
				'searchDescription' => 'string',
				'searchKeywords' => 'string',
				'customHeaders' => 'string'
			)
		);
	}
	
}

?>
