<?php
/**
 * @defgroup api_v1_dois DOI API requests
 */

/**
 * @file api/v1/dois/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_dois
 * @brief Handle API requests for DOI operations.
 */
import('api.v1.dois.DoiHandler');
return new DoiHandler();
