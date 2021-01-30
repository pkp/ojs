<?php

/**
 * @defgroup pages_oai OAI Pages
 */
 
/**
 * @file pages/oai/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup pages_oai
 * @brief Handle Open Archives Initiative protocol interaction requests. 
 *
 */

switch ($op) {
	case 'index':
		define('HANDLER_CLASS', 'OAIHandler');
		import('pages.oai.OAIHandler');
		break;
}


