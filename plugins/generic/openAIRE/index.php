<?php

/**
 * @defgroup plugins_generic_openAIRE
 */
 
/**
 * @file plugins/generic/openAIRE/index.php
 *
 * Copyright (c) 2013-2017 Simon Fraser University Library
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 * 
 * Contributed by 4Science (http://www.4science.it).
 *
 * @ingroup plugins_generic_openAIRE
 * @brief Wrapper for openAIRE plugin.
 *
 */
require_once('OpenAIREPlugin.inc.php');

return new OpenAIREPlugin();
