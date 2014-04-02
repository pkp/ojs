<?php

/**
 * @defgroup plugins_generic_booksForReview
 */
 
/**
 * @file plugins/generic/booksForReview/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_booksForReview
 * @brief Wrapper for books for review plugin.
 *
 */
require_once('BooksForReviewPlugin.inc.php');

return new BooksForReviewPlugin();

?>
