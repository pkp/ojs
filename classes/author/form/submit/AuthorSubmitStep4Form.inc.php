<?php

/**
 * AuthorSubmitStep4Form.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package author.form.submit
 *
 * Form for Step 4 of author submit.
 *
 * $Id$
 */

import("author.form.submit.AuthorSubmitForm");

class AuthorSubmitStep4Form extends AuthorSubmitForm {
	
	function AuthorSubmitStep4Form() {
		parent::AuthorSubmitForm(
			4,
			array(
				'publicationFormat' => 'int',
				'initialVolume' => 'int',
				'initialNumber' => 'int',
				'initialYear' => 'int',
				'pubFreqPolicy' => 'string',
				'editorialProcessType' => 'int',
				'useCopyeditors' => 'bool',
				'copyeditInstructions' => 'string',
				'useLayoutEditors' => 'bool',
				'useProofreaders' => 'bool',
				'proofInstructions' => 'string'
			)
		);
	}
	
}

?>
