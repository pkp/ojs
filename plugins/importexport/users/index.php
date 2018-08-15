<?php

/**
 * @defgroup plugins_importexport_users User import/export plugin
 */

/**
 * @file plugins/importexport/users/index.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_importexport_users
 * @brief Wrapper for XML user import/export plugin.
 *
 */


require_once('UserImportExportPlugin.inc.php');

return new UserImportExportPlugin();


