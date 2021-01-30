<?php

/**
 * @defgroup pages_catalog Catalog page
 */

/**
 * @file pages/catalog/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_catalog
 * @brief Handle requests for the public catalog view.
 *
 */

switch ($op) {
	case 'category':
	case 'fullSize':
	case 'thumbnail':
		define('HANDLER_CLASS', 'PKPCatalogHandler');
		import('lib.pkp.pages.catalog.PKPCatalogHandler');
		break;
}


