<?php

/**
 * @file classes/core/PageRouter.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PageRouter
 * @ingroup core
 *
 * @brief Class providing OJS-specific page routing.
 */

import('lib.pkp.classes.core.PKPPageRouter');

class PageRouter extends PKPPageRouter {
	/**
	 * Constructor
	 */
	function PageRouter() {
		parent::PKPPageRouter();
	}

	/**
	 * get the cacheable pages
	 * @return array
	 */
	function getCacheablePages() {
		return array('about', 'announcement', 'help', 'index', 'information', 'rt', 'issue', '');
	}
}

?>
