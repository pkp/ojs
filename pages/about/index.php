<?php

/**
 * @defgroup pages_about
 */
 
/**
 * @file pages/about/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_about
 * @brief Handle requests for about the journal functions. 
 *
 */

switch($op) {
	case 'index':
	case 'contact':
	case 'editorialTeam':
	case 'displayMembership':
	case 'editorialTeamBio':
	case 'editorialPolicies':
	case 'subscriptions':
	case 'memberships':
	case 'submissions':
	case 'journalSponsorship':
	case 'siteMap':
	case 'history':
	case 'aboutThisPublishingSystem':
	case 'statistics':
		define('HANDLER_CLASS', 'AboutHandler');
		import('pages.about.AboutHandler');
		break;
}

?>
