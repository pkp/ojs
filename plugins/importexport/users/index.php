<?php

/**
 * @defgroup plugins_importexport_users Users Import/Export Plugin
 */
 
/**
 * @file plugins/importexport/users/index.php
 *
 * Copyright (c) 2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_users
 * @brief Wrapper for XML user import/export plugin.
 *
 */

require_once('UserImportExportPlugin.inc.php');

return new UserImportExportPlugin();

?>
