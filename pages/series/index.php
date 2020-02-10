<?php

/**
 * @defgroup pages_series Series Pages
 */

/**
 * @file pages/series/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_preprint
 * @brief Handle requests for series functions.
 *
 */

switch ($op) {
	case 'view':
		define('HANDLER_CLASS', 'SeriesHandler');
		import('pages.series.SeriesHandler');
		break;
}


