<?php

/**
 * @defgroup plugins_generic_translator Translator plugin
 */

/**
 * @file plugins/generic/translator/index.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_translator
 * @brief Wrapper for translation maintenance plugin.
 *
 */



require_once('TranslatorPlugin.inc.php');

return new TranslatorPlugin();

?>
