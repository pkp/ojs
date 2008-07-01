<?php

/**
 * @defgroup plugins_blocks_user
 */
 
/**
 * @file plugins/blocks/donation/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_blocks_user
 * @brief Wrapper for donation block plugin.
 *
 */

// $Id$


require_once('DonationBlockPlugin.inc.php');

return new DonationBlockPlugin();

?> 
