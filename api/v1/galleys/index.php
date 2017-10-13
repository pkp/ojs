<?php

/**
 * @defgroup api_v1_galleys Galleys API requests
 */

/**
 * @file api/v1/galleys/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_galleys
 * @brief Handle requests for galleys API functions.
 *
 */

import('api.v1.galleys.GalleysHandler');
return new GalleysHandler();
