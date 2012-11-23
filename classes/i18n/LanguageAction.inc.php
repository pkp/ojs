<?php
/**
 * @defgroup admin
 */

/**
 * @file classes/i18n/LanguageAction.inc.php
 * @defgroup admin
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class LanguageAction
 * @ingroup admin
 *
 * @brief LanguageAction class.
 */

define('LANGUAGE_PACK_DESCRIPTOR_URL', 'http://pkp.sfu.ca/ojs/xml/%s/locales.xml');
define('LANGUAGE_PACK_TAR_URL', 'http://pkp.sfu.ca/ojs/xml/%s/%s.tar.gz');

import('lib.pkp.classes.i18n.PKPLanguageAction');

class LanguageAction extends PKPLanguageAction {
	/**
	 * Constructor.
	 */
	function LanguageAction() {
		parent::PKPLanguageAction();
	}
}

?>
