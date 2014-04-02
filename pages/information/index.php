<?php

/**
 * @defgroup pages_information
 */
 
/**
 * @file pages/information/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_information
 * @brief Handle information requests. 
 *
 */

switch ($op) {
	case 'index':
	case 'readers':
	case 'authors':
	case 'librarians':
	case 'competingInterestGuidelines':
	case 'sampleCopyrightWording':
		define('HANDLER_CLASS', 'InformationHandler');
		import('pages.information.InformationHandler');
		break;
}

?>
