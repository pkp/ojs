<?php

/**
 * @defgroup plugins_generic_orcidProfile
 */
 
/**
 * @file plugins/generic/orcidProfile/index.php
 *
 * Copyright (c) 2015-2016 University of Pittsburgh
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_orcidProfile
 * @brief Wrapper for ORCID Profile plugin.
 *
 */

require_once('OrcidProfilePlugin.inc.php');

return new OrcidProfilePlugin();

?>
