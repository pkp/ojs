<?php

/**
 * ArticleSearch.inc.php
 *
 * Copyright (c) 2003-2004 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package search
 *
 * Class for retrieving article search results.
 *
 * $Id$
 */
 
// Search types
define('ARTICLE_SEARCH_AUTHOR',			0x00000001);
define('ARTICLE_SEARCH_TITLE',			0x00000002);
define('ARTICLE_SEARCH_ABSTRACT',		0x00000003);
define('ARTICLE_SEARCH_DISCIPLINE',		0x00000004);
define('ARTICLE_SEARCH_SUBJECT',		0x00000005);
define('ARTICLE_SEARCH_TYPE',			0x00000006);
define('ARTICLE_SEARCH_COVERAGE',		0x00000007);
define('ARTICLE_SEARCH_GALLEY_FILE',		0x00000010);
define('ARTICLE_SEARCH_SUPPLEMENTARY_FILE',	0x00000020);

class ArticleSearch {
	// FIXME Implement search results retrieval
}

?>
