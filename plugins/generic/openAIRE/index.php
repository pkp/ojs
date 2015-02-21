<?php

/**
 * @defgroup plugins_generic_openAIRE
 */
 
/**
 * @file plugins/generic/openAIRE/index.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_openAIRE
 * @brief Wrapper for openAIRE plugin.
 *
 */
require_once('OpenAIREPlugin.inc.php');

return new OpenAIREPlugin();

?>
