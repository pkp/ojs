<?php

/**
 * @file classes/install/Install.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Install
 *
 * @ingroup install
 *
 * @see Installer, InstallForm
 *
 * @brief Perform system installation.
 *
 * This script will:
 *  - Create the database (optionally), and install the database tables and initial data.
 *  - Update the config file with installation parameters.
 */

namespace APP\install;

use PKP\install\PKPInstall;

class Install extends PKPInstall
{
    /**
     * Constructor.
     *
     * @see install.form.InstallForm for the expected parameters
     *
     * @param array $params installer parameters
     * @param string $descriptor descriptor path
     * @param bool $isPlugin true iff a plugin is being installed
     */
    public function __construct($params, $descriptor = 'install.xml', $isPlugin = false)
    {
        parent::__construct($descriptor, $params, $isPlugin);
    }

    //
    // Installer actions
    //

    /**
     * Get the names of the directories to create.
     *
     * @return array
     */
    public function getCreateDirectories()
    {
        $directories = parent::getCreateDirectories();
        $directories[] = 'journals';
        return $directories;
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\install\Install', '\Install');
}
