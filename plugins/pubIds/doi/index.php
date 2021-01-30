<?php

/**
 * @defgroup plugins_pubIds_doi DOI Pub ID Plugin
 */

/**
 * @file plugins/pubIds/doi/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_pubIds_doi
 * @brief Wrapper for DOI plugin.
 *
 */
require_once('DOIPubIdPlugin.inc.php');

return new DOIPubIdPlugin();


