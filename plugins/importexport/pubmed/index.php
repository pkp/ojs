<?php

/**
 * @defgroup plugins_importexport_pubmed PubMed Export Plugin
 */
 
/**
 * @file plugins/importexport/pubmed/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_pubmed
 * @brief Wrapper for PubMed export plugin.
 *
 */

require_once('PubMedExportPlugin.inc.php');

return new PubMedExportPlugin();


