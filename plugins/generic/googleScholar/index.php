<?php

/**
 * @defgroup plugins_generic_googleScholar GoogleScholar plugin
 */

/**
 * @file plugins/generic/googleScholar/index.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_googleScholar
 * @brief Wrapper for GoogleScholar plugin.
 *
 */

require_once('GoogleScholarPlugin.inc.php');

return new GoogleScholarPlugin();

?>
