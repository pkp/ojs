<?php

/**
 * @defgroup plugins_generic_crossrefDeposit
 */

/**
 * @file plugins/generic/crossrefDeposit/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_crossrefDeposit
 * @brief Wrapper for the Crossref Deposit Plugin.
 *
 */
require_once('CrossrefDepositPlugin.inc.php');

return new CrossrefDepositPlugin();

?>
