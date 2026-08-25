<?php

// Nothing below may run over the web: refuse any non-CLI SAPI before the
// first side effect. (Stronger than the CommandLineTool constructor guard,
// which fires only after the whole app has bootstrapped.)
if (PHP_SAPI !== 'cli') {
    exit('This script can only be executed from the command-line');
}

/**
 * @file tools/installTest.php
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Non-interactive schema install for the Playwright test fleet.
 *
 * Reads every parameter from the config file named by PKP_CONFIG_FILE
 * (default: config.test.inc.php next to the app root) and installs the schema
 * plus the admin account. Self-healing:
 * - empty DB → full install;
 * - partial debris (tables but no current version row) → drop all tables,
 *   install fresh;
 * - installed DB (current version row present) → no-op, exit 0.
 * Refuses any database whose name does not contain "test"
 * (`npm run test:e2e:reset` is the tool that empties it).
 */

// The installer requires installed = Off, and bootstrapping with
// installed = On against an empty database fatals on the versions query —
// so the bootstrap must be pointed at a temp copy with the flag off BEFORE
// it runs. This is the one step that cannot live inside execute().
$configFile = getenv('PKP_CONFIG_FILE') ?: dirname(__DIR__) . '/config.test.inc.php';
if (!is_readable($configFile)) {
    fwrite(STDERR, "installTest: config file not readable: {$configFile}\n");
    exit(1);
}
$tmpConfig = tempnam(sys_get_temp_dir(), 'pkp-test-install-');
file_put_contents(
    $tmpConfig,
    preg_replace('/^(\s*installed\s*=\s*)On\b/mi', '${1}Off', file_get_contents($configFile))
);
register_shutdown_function(fn () => @unlink($tmpConfig));
putenv("PKP_CONFIG_FILE={$tmpConfig}");

require(dirname(__FILE__) . '/bootstrap.php');

use APP\install\Install;
use PKP\config\Config;

class TestInstallTool extends \PKP\cliTool\InstallTool
{
    public function execute()
    {
        // All parameters come from the config the bootstrap just loaded
        // (the temp copy — identical to the fleet config except installed).
        $dbName = (string) Config::getVar('database', 'name');
        if (!$dbName || stripos($dbName, 'test') === false) {
            fwrite(STDERR, "installTest: database \"{$dbName}\" does not look like a test DB (no \"test\" in the name) — refusing.\n");
            exit(1);
        }

        $locale = Config::getVar('i18n', 'locale', 'en');
        $installedLocales = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) Config::getVar('i18n', 'installed_locales', $locale))
        )));

        $this->params = [
            'locale' => $locale,
            'additionalLocales' => array_values(array_diff($installedLocales, [$locale])),
            'timeZone' => Config::getVar('general', 'time_zone', 'UTC'),
            'filesDir' => Config::getVar('files', 'files_dir'),
            'adminUsername' => 'admin',
            'adminPassword' => 'admin',
            'adminPassword2' => 'admin',
            'adminEmail' => 'admin@mail.test',
            'databaseDriver' => Config::getVar('database', 'driver'),
            'databaseHost' => Config::getVar('database', 'host'),
            'databaseUsername' => Config::getVar('database', 'username'),
            'databasePassword' => Config::getVar('database', 'password', ''),
            'databaseName' => $dbName,
            'oaiRepositoryId' => 'test',
            'enableBeacon' => false,
            'install' => true,
        ];

        // Probe the database state through the installer's own connection
        // setup (driver-agnostic — no raw DSNs here).
        $probeInstaller = new Install($this->params);
        $probeInstaller->preInstall();
        $schemaBuilder = \Illuminate\Support\Facades\DB::connection()->getSchemaBuilder();

        if ($schemaBuilder->hasTable('versions')) {
            $currentVersions = \Illuminate\Support\Facades\DB::table('versions')->where('current', 1)->count();
            if ($currentVersions > 0) {
                printf("installTest: schema already installed — nothing to do.\n");
                return;
            }
        }

        if (count($schemaBuilder->getTables()) > 0) {
            printf("installTest: partial install debris found — dropping all tables.\n");
            $schemaBuilder->dropAllTables();
        }

        printf("installTest: installing schema into a fresh database…\n");
        $this->install();

        if (!$schemaBuilder->hasTable('versions') || \Illuminate\Support\Facades\DB::table('versions')->where('current', 1)->count() === 0) {
            fwrite(STDERR, "installTest: install did not complete (no current version row).\n");
            exit(1);
        }
    }
}

$tool = new TestInstallTool($argv ?? []);
$tool->execute();
