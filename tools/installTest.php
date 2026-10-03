<?php

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
 *
 * The config keeps installed = On for the web fleet; this tool runs the
 * installer against a temp copy with installed = Off.
 */

// Resolve the config BEFORE the app bootstrap reads it.
$configFile = getenv('PKP_CONFIG_FILE') ?: dirname(__DIR__) . '/config.test.inc.php';
if (!is_readable($configFile)) {
    fwrite(STDERR, "installTest: config file not readable: {$configFile}\n");
    exit(1);
}

$ini = parse_ini_file($configFile, true, INI_SCANNER_RAW);
$iniGet = function (string $section, string $key, ?string $default = null) use ($ini): ?string {
    $value = $ini[$section][$key] ?? $default;
    if (is_string($value)) {
        $value = trim($value);
        if (preg_match('/^(["\']).*\1$/', $value)) {
            $value = substr($value, 1, -1);
        }
    }
    return $value === null ? null : (string) $value;
};

$dbName = $iniGet('database', 'name');
if (!$dbName || stripos($dbName, 'test') === false) {
    fwrite(STDERR, "installTest: database \"{$dbName}\" does not look like a test DB (no \"test\" in the name) — refusing.\n");
    exit(1);
}

// The installer path requires installed = Off; run against a temp copy.
$tmpConfig = tempnam(sys_get_temp_dir(), 'pkp-test-install-');
file_put_contents(
    $tmpConfig,
    preg_replace('/^(\s*installed\s*=\s*)On\b/mi', '${1}Off', file_get_contents($configFile))
);
putenv("PKP_CONFIG_FILE={$tmpConfig}");

require(dirname(__FILE__) . '/bootstrap.php');

use APP\install\Install;

class TestInstallTool extends \PKP\cliTool\InstallTool
{
    public function __construct(
        private array $configParams,
        array $argv = []
    ) {
        parent::__construct($argv);
    }

    public function execute()
    {
        $this->params = $this->configParams;

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

$locale = $iniGet('i18n', 'locale', 'en');
$installedLocales = array_values(array_filter(array_map(
    'trim',
    explode(',', $iniGet('i18n', 'installed_locales', $locale))
)));
$additionalLocales = array_values(array_diff($installedLocales, [$locale]));

$tool = new TestInstallTool([
    'locale' => $locale,
    'additionalLocales' => $additionalLocales,
    'timeZone' => $iniGet('general', 'time_zone', 'UTC'),
    'filesDir' => $iniGet('files', 'files_dir'),
    'adminUsername' => 'admin',
    'adminPassword' => 'admin',
    'adminPassword2' => 'admin',
    'adminEmail' => 'admin@mail.test',
    'databaseDriver' => $iniGet('database', 'driver'),
    'databaseHost' => $iniGet('database', 'host'),
    'databaseUsername' => $iniGet('database', 'username'),
    'databasePassword' => $iniGet('database', 'password', ''),
    'databaseName' => $dbName,
    'oaiRepositoryId' => 'test',
    'enableBeacon' => false,
    'install' => true,
], $argv ?? []);
$tool->execute();
