<?php

/**
 * @defgroup plugins_importexport_datacite DataCite Plugin
 */

/**
 * @file plugins/importexport/datacite/index.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_datacite
 *
 * @brief Wrapper for the DataCite export plugin.
 */


require_once('DataciteExportPlugin.inc.php');

return new DataciteExportPlugin();


