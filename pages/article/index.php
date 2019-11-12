<?php

/**
 * @defgroup pages_preprint Preprint Pages
 */

/**
 * @file pages/preprint/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_preprint
 * @brief Handle requests for preprint functions.
 *
 */

switch ($op) {
	case 'viewFile': // Old URLs; see https://github.com/pkp/pkp-lib/issues/1541
	case 'downloadSuppFile': // Old URLs; see https://github.com/pkp/pkp-lib/issues/1541
	case 'view':
	case 'download':
		define('HANDLER_CLASS', 'PreprintHandler');
		import('pages.preprint.PreprintHandler');
		break;
}


