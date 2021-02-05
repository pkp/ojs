<?php

/**
 * @defgroup plugins_pubIds_urn URN Pub ID Plugin
 */

/**
 * @file plugins/pubIds/urn/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_pubIds_urn
 * @brief Wrapper for urn plugin.
 *
 */
require_once('URNPubIdPlugin.inc.php');

return new URNPubIdPlugin();


