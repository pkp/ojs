<?php

/**
 * @defgroup plugins_paymethod_manual Manual Payment Processing Plugin
 */
 
/**
 * @file plugins/paymethod/manual/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_paymethod_manual
 * @brief Wrapper for manual payment plugin.
 */

require_once('ManualPaymentPlugin.inc.php');

return new ManualPaymentPlugin();

?> 
