<?php

/**
 * @defgroup plugins_paymethod_manual Manual Payment Processing Plugin
 */
 
/**
 * @file plugins/paymethod/manual/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_paymethod_manual
 * @brief Wrapper for manual payment plugin.
 */

require_once('ManualPaymentPlugin.inc.php');

return new ManualPaymentPlugin();


