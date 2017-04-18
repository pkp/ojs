<?php

/**
 * @defgroup pages_publicfiles
 */

/**
 * @file pages/publicfiles/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_publicfiles
 * @brief Handle requests for public files functions.
 *
 */ 
switch ($op) {
	case 'delete':
	case 'download':
		define('HANDLER_CLASS', 'PublicFilesHandler');
		import('pages.publicfiles.PublicFilesHandler');
}

?>
