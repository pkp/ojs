<?php
/**
 * @defgroup api_v1_contexts Context API requests
 */

/**
 * @file api/v1/contexts/index.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup api_v1_contexts
 * @brief Handle API requests for contexts (journals/presses).
 */
import('api.v1.contexts.ContextHandler');
return new ContextHandler();
