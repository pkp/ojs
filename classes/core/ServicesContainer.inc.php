<?php

/**
 * @file classes/core/ServicesContainer.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ServicesContainer
 * @ingroup core
 * @see Core
 *
 * @brief Pimple Dependency Injection Container.
 */

import('lib.pkp.classes.core.PKPServicesContainer');

class ServicesContainer extends PKPServicesContainer  {

	/**
	 * container initialization
	 */
	protected function init() {
		$this->container->register(new OJS\Services\OJSServiceProvider());
	}

}
