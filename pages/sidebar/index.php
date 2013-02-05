<?php

/**
 * @defgroup pages_sidebar
 */

/**
 * @file pages/sidebar/index.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_sidebar
 * @brief Handle site sidebar requests.
 *
 */


switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'SidebarHandler');
		import('lib.pkp.pages.sidebar.SidebarHandler');
		break;
}

?>
