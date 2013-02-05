<?php

/**
 * @defgroup pages_header
 */

/**
 * @file pages/header/index.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_header
 * @brief Handle site header requests.
 *
 */


switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'HeaderHandler');
		import('pages.header.HeaderHandler');
		break;
}

?>
