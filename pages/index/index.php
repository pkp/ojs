<?php

/**
 * @defgroup pages_index Index Pages
 */
 
/**
 * @file pages/index/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_index
 * @brief Handle site index requests. 
 *
 */

switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'IndexHandler');
		import('pages.index.IndexHandler');
		break;
}

?>
