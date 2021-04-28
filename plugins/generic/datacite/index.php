<?php

/**
 * @defgroup plugins_generic_datacite DataCite Plugin
 */

/**
 * @file plugins/generic/datacite/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_datacite
 *
 * @brief Wrapper for the DataCite plugin.
 */


require_once('DatacitePlugin.inc.php');

return new DatacitePlugin();
