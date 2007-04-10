<?php

/**
 * FormValidatorEmail.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package form.validation
 *
 * Form validation check for email addresses.
 *
 * $Id$
 */

import('form.validation.FormValidatorRegExp');

class FormValidatorEmail extends FormValidatorRegExp {
	function getRegexp() {

		$atom = '[-a-z0-9!#\$%&\'\*\+\/=\?\^_\`\{\|\}~]';    	// allowed characters for part before "at" character
		$domain = '([a-z]([-a-z0-9]*[a-z0-9]+)?)';							// allowed characters for part after "at" character
		
		$regex = '^' . $atom . '+' . 			// One or more atom characters.
		'(\.' . $atom . '+)*'.              			// Followed by zero or more dot separated sets of one or more atom characters.
		'@'.                                					// Followed by an "at" character.
		'(' . $domain . '{1,63}\.)+'.        		// Followed by one or max 63 domain characters (dot separated).
		$domain . '{2,63}'.                 			// Must be followed by one set consisting a period of two
		'$';                                					// or max 63 domain characters.

		return '/' .$regex. '$/i';
		}

	/**
	 * Constructor.
	 * @see FormValidatorRegExp::FormValidatorRegExp()
	 */
	function FormValidatorEmail(&$form, $field, $type, $message) {
		parent::FormValidatorRegExp($form, $field, $type, $message, FormValidatorEmail::getRegexp());
	}
	
}

?>
