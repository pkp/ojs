<?php

/**
 * @defgroup plugins_generic_sehl SEHL (Search Engine HighLighting) Plugin
 */
 
/**
 * @file plugins/generic/sehl/index.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_sehl
 * @brief Wrapper for SEHL (Search Engine HighLighting) plugin.
 *
 */

require_once('SehlPlugin.inc.php');

return new SehlPlugin();

?> 
