<?php 

/**
 * @defgroup plugins
 */
 
/**
 * @file plugins/paymethod/manual/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins
 * @brief Wrapper for manual payment plugin.
 *
 *
 */
 
require_once('ManualPaymentPlugin.inc.php');

return new ManualPaymentPlugin();
 
?> 
