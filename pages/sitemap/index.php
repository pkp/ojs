<?php

/**
 * @defgroup pages_sitemap Site Map Pages
 */
 
/**
 * @file pages/sitemap/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_sitemap
 * @brief Produce a sitemap in XML format for submitting to search engines. 
 *
 */

switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'SitemapHandler');
		import('pages.sitemap.SitemapHandler');
		break;
}


