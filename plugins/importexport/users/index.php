<?php

/**
 * @defgroup plugins_importexport_users
 */
 
/**
 * @file plugins/importexport/users/index.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_users
 * @brief Wrapper for XML user import/export plugin.
 *
 */

// $Id$


require_once('UserImportExportPlugin.inc.php');

return new UserImportExportPlugin();

?>
