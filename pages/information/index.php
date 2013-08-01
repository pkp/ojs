<?php

/**
 * @defgroup pages_information Information Pages
 */
 
/**
 * @file pages/information/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
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
