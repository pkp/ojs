<?php

/**
 * @file tools/installTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OJSTestInstallTool
 *
 * @ingroup tools
 *
 * @brief Non-interactive OJS install for the Playwright test harness.
 *
 * Thin subclass of `InstallTool`. Populates `$this->params` from env
 * vars and delegates to the unmodified install machinery. Refuses to
 * run unless APPLICATION_ENV=test — nothing here should ever touch a
 * production install.
 *
 * Expected env vars (none of which have sensible defaults for real use):
 *   OJS_DB_DRIVER          mysql | pgsql  (default: mysql)
 *   OJS_DB_HOST            required
 *   OJS_DB_USER            required
 *   OJS_DB_PASSWORD        required
 *   OJS_DB_NAME            required, DB must already exist
 *   OJS_FILES_DIR          required, must be writable
 *   OJS_ADMIN_PASSWORD     default: 'admin'
 *   OJS_ADMIN_EMAIL        default: 'admin@test.local'
 */

// Refuse early — before the bootstrap chain fires the
// APPLICATION_ENV=test config-redirect hook. The caller is responsible
// for ensuring config.test.inc.php exists (the Playwright webServer
// command seeds it from config.TEMPLATE.inc.php on first start). If you
// run this tool outside Playwright, copy the template yourself first:
//
//   cp config.TEMPLATE.inc.php config.test.inc.php
if (getenv('APPLICATION_ENV') !== 'test') {
    fwrite(STDERR, "installTest.php refuses to run unless APPLICATION_ENV=test\n");
    exit(1);
}

require(dirname(__FILE__) . '/bootstrap.php');

class OJSTestInstallTool extends \PKP\cliTool\InstallTool
{
    public function readParams()
    {
        $required = ['OJS_DB_HOST', 'OJS_DB_USER', 'OJS_DB_PASSWORD', 'OJS_DB_NAME', 'OJS_FILES_DIR'];
        foreach ($required as $name) {
            if (getenv($name) === false || getenv($name) === '') {
                fwrite(STDERR, "Missing required env var: {$name}\n");
                exit(1);
            }
        }

        $adminPassword = getenv('OJS_ADMIN_PASSWORD') ?: 'admin';

        $this->params = [
            'locale' => 'en',
            'additionalLocales' => ['fr_CA'],
            'filesDir' => getenv('OJS_FILES_DIR'),
            'adminUsername' => 'admin',
            'adminPassword' => $adminPassword,
            'adminPassword2' => $adminPassword,
            'adminEmail' => getenv('OJS_ADMIN_EMAIL') ?: 'admin@test.local',
            'databaseDriver' => getenv('OJS_DB_DRIVER') ?: 'mysql',
            'databaseHost' => getenv('OJS_DB_HOST'),
            'databaseUsername' => getenv('OJS_DB_USER'),
            'databasePassword' => getenv('OJS_DB_PASSWORD'),
            'databaseName' => getenv('OJS_DB_NAME'),
            'oaiRepositoryId' => 'ojs-test',
            'enableBeacon' => 0,
            'install' => true,
        ];

        return true;
    }
}

$tool = new OJSTestInstallTool($argv ?? []);
$tool->execute();
