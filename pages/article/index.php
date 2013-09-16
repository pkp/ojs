<?php

/**
 * @defgroup pages_article Article Pages
 */
 
/**
 * @file pages/article/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_article
 * @brief Handle requests for article functions. 
 *
 */

switch ($op) {
	case 'view':
	case 'viewArticle':
	case 'viewRST':
	case 'viewFile':
	case 'download':
		define('HANDLER_CLASS', 'ArticleHandler');
		import('pages.article.ArticleHandler');
		break;
}

?>
