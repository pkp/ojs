<?php

/**
 * @defgroup pages_help Help Pages
 */

/**
 * @file pages/help/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_help
 * @brief Handle requests for help functions.
 *
 */

switch($op) {
	case 'index':
		define('HANDLER_CLASS', 'HelpHandler');
		import('lib.pkp.pages.help.HelpHandler');
		break;
}

?>
