<?php

/**
 * @file pages/header/HeaderHandler.inc.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class HeaderHandler
 * @ingroup pages_header
 *
 * @brief Handle site header requests.
 */

import('lib.pkp.pages.header.PKPHeaderHandler');

class HeaderHandler extends PKPHeaderHandler {
	/**
	 * Constructor
	 */
	function HeaderHandler() {
		parent::PKPHeaderHandler();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_MANAGER); // editor.navigation.issues; manager.website etc.
	}
}

?>
