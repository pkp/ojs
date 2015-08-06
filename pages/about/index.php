<?php

/**
 * @defgroup pages_about About Pages
 */

/**
 * @file pages/about/index.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 * @brief Handle requests for about the journal functions.
 *
 */

switch($op) {
	case 'about':
	case 'subscriptions':
	case 'editorialTeam':
	case 'submissions':
	case 'memberships':
	case 'history':
		define('HANDLER_CLASS', 'AboutContextHandler');
		import('pages.about.AboutContextHandler');
		break;
	case 'aboutThisPublishingSystem':
		define('HANDLER_CLASS', 'AboutSiteHandler');
		import('lib.pkp.pages.about.AboutSiteHandler');
		break;
}

?>
