<?php

/**
 * @defgroup plugins_generic_referral
 */
 
/**
 * @file plugins/generic/referral/index.php
 *
 * Copyright (c) 2013-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_referral
 * @brief Wrapper for referral plugin.
 *
 */

require_once('ReferralPlugin.inc.php');

return new ReferralPlugin();

?>
