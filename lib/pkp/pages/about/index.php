<?php

/**
 * @defgroup pages_about About page
 */

/**
 * @file pages/about/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 * @brief Handle requests for about the press functions.
 *
 */

switch ($op) {
	case 'index':
	case 'editorialTeam':
	case 'submissions':
	case 'contact':
		define('HANDLER_CLASS', 'AboutContextHandler');
		import('lib.pkp.pages.about.AboutContextHandler');
		break;
	case 'aboutThisPublishingSystem':
		define('HANDLER_CLASS', 'AboutSiteHandler');
		import('lib.pkp.pages.about.AboutSiteHandler');
		break;
}

?>
