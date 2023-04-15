<?php

/**
 * @file tools/install.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSInstallTool
 *
 * @ingroup tools
 *
 * @brief CLI tool for installing OJS.
 */

require(dirname(__FILE__) . '/bootstrap.php');

class OJSInstallTool extends \PKP\cliTool\InstallTool
{
    /**
     * Read installation parameters from stdin.
     * FIXME: May want to implement an abstract "CLIForm" class handling input/validation.
     * FIXME: Use readline if available?
     */
    public function readParams()
    {
        printf("%s\n", __('installer.appInstallation'));

        parent::readParams();

        $this->readParamBoolean('install', 'installer.installApplication');

        return $this->params['install'];
    }
}

$tool = new OJSInstallTool($argv ?? []);
$tool->execute();
