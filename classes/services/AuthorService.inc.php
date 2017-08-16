<?php

/**
 * @file classes/services/AuthorService.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorService
 * @ingroup services
 *
 * @brief Extends the base author helper service class with app-specific
 *  requirements.
 */

namespace OJS\Services;

use \PKP\Services\PKPAuthorService;

class AuthorService extends PKPAuthorService {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
	}
}