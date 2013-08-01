<?php

/**
 * @defgroup pages_search Search Pages
 */

/**
 * @file pages/search/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_search
 * @brief Handle search requests.
 *
 */

switch ($op) {
	case 'index':
	case 'search':
	case 'similarDocuments':
	case 'authors':
	case 'titles':
	case 'categories':
	case 'category':
		define('HANDLER_CLASS', 'SearchHandler');
		import('pages.search.SearchHandler');
		break;
}

?>
