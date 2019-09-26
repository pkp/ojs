<?php

/**
 * @defgroup pages_archive Archive Pages
 */

/**
 * @file pages/archive/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_archive
 * @brief Handle requests for archive functions.
 *
 */
switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'ArchiveHandler');
		import('pages.archive.ArchiveHandler');
		break;
}


