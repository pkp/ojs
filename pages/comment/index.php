<?php

/**
 * @defgroup pages_comment
 */
 
/**
 * @file pages/comment/index.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_comment
 * @brief Handle requests for comment functions. 
 *
 */

switch ($op) {
	case 'view':
	case 'add':
	case 'delete':
		define('HANDLER_CLASS', 'CommentHandler');
		import('pages.comment.CommentHandler');
		break;
}

?>
