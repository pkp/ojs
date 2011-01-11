<?php

/**
 * @defgroup pages_oai
 */
 
/**
 * @file pages/oai/index.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_oai
 * @brief Handle Open Archives Initiative protocol interaction requests. 
 *
 */

// $Id$


switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'OAIHandler');
		import('pages.oai.OAIHandler');
		break;
}

?>
