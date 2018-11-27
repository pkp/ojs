<?php

/**
 * @file classes/core/Services.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class Services
 * @ingroup core
 * @see Core
 *
 * @brief Pimple Dependency Injection Container.
 */

import('lib.pkp.classes.core.PKPServices');

class Services extends PKPServices  {

	/**
	 * container initialization
	 */
	protected function init() {
		$this->container->register(new APP\Services\OJSServiceProvider());
	}

}
