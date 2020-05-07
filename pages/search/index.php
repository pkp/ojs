<?php

/**
 * @defgroup pages_search Search Pages
 */

/**
 * @file pages/search/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
		define('HANDLER_CLASS', 'SearchHandler');
		import('pages.search.SearchHandler');
		break;
}


