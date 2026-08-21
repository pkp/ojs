<?php

/**
 * @file tools/installTest.php
 *
 * Copyright (c) 2014-2026 Simon Fraser University
 * Copyright (c) 2003-2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InstallTestTool
 *
 * @ingroup tools
 *
 * @brief Non-interactive installer for the Playwright e2e test install.
 *
 * Installs the application schema and initial data into the database named by
 * the ACTIVE configuration file, which is selected with the PKP_CONFIG_FILE
 * environment variable:
 *
 *   PKP_CONFIG_FILE=/abs/path/config.test.inc.php php tools/installTest.php [--recreate-db]
 *
 * Every installation parameter is read from that configuration file, so the
 * tool is database-driver agnostic: it drives the application's own installer
 * machinery (dbscripts/xml/install.xml, Laravel migrations) exactly as the
 * interactive `tools/install.php` does.
 *
 * `--recreate-db` additionally drops and recreates the database itself. Creating
 * a database is the one operation the application installer does not perform, so
 * `TestInstallDatabase` below is the ONE place in the harness where driver names
 * appear; the dispatch reads the driver from the configuration file.
 *
 * Refuses to run unless PKP_CONFIG_FILE is set, so it can never install over a
 * development or production database by accident.
 *
 * EMPTY-DATABASE SELF-HEALING. Bootstrapping the application reads the `versions`
 * table whenever the configuration says `installed = On`, so the tool cannot boot
 * against a database that has no tables at all — the state a new contributor or a
 * CI job starts from. The pre-boot block below detects exactly that case and
 * writes `installed = Off` before the bootstrap runs; the installer writes the
 * flag back to `On` when it finishes. See TestInstallConfigFile.
 */

/**
 * Configuration-file access that works BEFORE the application bootstraps.
 *
 * Deliberately dependency-free (parse_ini_file plus a line-oriented rewrite):
 * the whole point of this class is to run at a moment when no PKP class can be
 * loaded, because loading them is what fails. Post-boot configuration writes
 * still go through the application's own ConfigParser.
 */
final class TestInstallConfigFile
{
    /**
     * @return array<string, array<string, string>>|null null when unreadable/unparseable
     */
    public static function parse(string $file): ?array
    {
        if (!is_readable($file)) {
            return null;
        }

        $data = @parse_ini_file($file, true, INI_SCANNER_RAW);

        return is_array($data) ? $data : null;
    }

    /**
     * Interpret a raw ini value the way the application's config reader does.
     */
    public static function isOn(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'on', 'true', 'yes'], true);
    }

    /**
     * Set a key inside a section, preserving comments, ordering and formatting.
     *
     * Returns false when the key is not present in that section (this never
     * ADDS a key: an absent `installed` already means "not installed", so there
     * would be nothing to fix), or when the rewritten file would not parse.
     */
    public static function setExisting(string $file, string $section, string $key, string $value): bool
    {
        $content = @file_get_contents($file);

        if ($content === false) {
            return false;
        }

        $lines = preg_split('/(\r\n|\n|\r)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $current = null;
        $changed = false;

        foreach ($lines as $index => $line) {
            if (preg_match('/^\s*\[(.+)\]\s*$/', $line, $matches)) {
                $current = trim($matches[1]);

                continue;
            }

            if ($current !== $section || $changed) {
                continue;
            }

            if (preg_match('/^(\s*)' . preg_quote($key, '/') . '(\s*)=/', $line, $matches)) {
                $lines[$index] = $matches[1] . $key . ($matches[2] ?: ' ') . '= ' . $value;
                $changed = true;
            }
        }

        if (!$changed) {
            return false;
        }

        $rewritten = implode('', $lines);

        // Never leave an unparseable configuration file behind.
        if (@parse_ini_string($rewritten, true, INI_SCANNER_RAW) === false) {
            return false;
        }

        return file_put_contents($file, $rewritten, LOCK_EX) !== false;
    }
}

/**
 * The single driver dispatch in the harness.
 *
 * Two things the application's own installer cannot do for us: create the
 * database, and answer "does this database have a schema yet?" before the
 * application is allowed to boot. Both need a driver-specific DSN, and both
 * live here so that exactly one table of driver names exists.
 */
final class TestInstallDatabase
{
    /**
     * @param array<string, string> $database the config file's [database] section
     */
    public static function dsn(array $database, bool $maintenance = false): string
    {
        $driver = strtolower((string) ($database['driver'] ?? ''));
        $host = (string) ($database['host'] ?? '');
        $port = $database['port'] ?? null;
        $name = (string) ($database['name'] ?? '');
        $socket = $database['unix_socket'] ?? null;

        $tail = $host !== '' ? "host={$host}" : '';
        $tail .= $port ? ";port={$port}" : '';
        $tail .= $socket ? ";unix_socket={$socket}" : '';

        return match (true) {
            str_starts_with($driver, 'postgres') => 'pgsql:' . $tail . ';dbname=' . ($maintenance ? 'postgres' : $name),
            str_starts_with($driver, 'mysql'), str_starts_with($driver, 'mariadb') => 'mysql:' . $tail . ($maintenance ? '' : ';dbname=' . $name),
            default => throw new Exception("Unsupported database driver '{$driver}'."),
        };
    }

    /**
     * @param array<string, string> $database
     */
    public static function connect(array $database, bool $maintenance = false): PDO
    {
        return new PDO(
            static::dsn($database, $maintenance),
            $database['username'] ?? null,
            $database['password'] ?? null,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    /** No application tables at all — a brand-new, empty database. */
    public const SCHEMA_NONE = 'none';

    /** Tables exist, but the application's own version row does not. */
    public const SCHEMA_PARTIAL = 'partial';

    /** A complete, bootable installation. */
    public const SCHEMA_INSTALLED = 'installed';

    /**
     * What state is the target database in?
     *
     * Two plain queries, no information-schema dialects: every supported driver
     * spells both the same way, and the second one is exactly the question
     * `PKPApplication::__construct()` asks (`VersionDAO::getCurrentVersion()`
     * looks for the current `core` row and dereferences the result). A database
     * that has tables but no core version row is the state a killed install
     * leaves behind: booting against it fatals, so it must be treated as
     * not-installed for the purpose of the `installed` flag, and as
     * already-populated for the purpose of refusing to install over it.
     *
     * Any connection failure answers SCHEMA_NONE; the caller only ever uses that
     * to be MORE cautious, and a genuinely unreachable database fails loudly a
     * moment later in the installer itself.
     *
     * @param array<string, string> $database
     *
     * @return self::SCHEMA_*
     */
    public static function schemaState(array $database): string
    {
        try {
            $pdo = static::connect($database);
            $pdo->query('SELECT COUNT(*) FROM versions')->fetchColumn();
        } catch (Throwable) {
            return static::SCHEMA_NONE;
        }

        try {
            $current = (int) $pdo
                ->query("SELECT COUNT(*) FROM versions WHERE current = 1 AND product_type = 'core'")
                ->fetchColumn();
        } catch (Throwable) {
            return static::SCHEMA_PARTIAL;
        }

        return $current > 0 ? static::SCHEMA_INSTALLED : static::SCHEMA_PARTIAL;
    }

    /**
     * @param array<string, string> $database
     */
    public static function recreate(array $database): void
    {
        $driver = strtolower((string) ($database['driver'] ?? ''));
        $name = (string) ($database['name'] ?? '');

        $quoted = str_starts_with($driver, 'postgres')
            ? '"' . str_replace('"', '', $name) . '"'
            : '`' . str_replace('`', '', $name) . '`';

        $pdo = static::connect($database, true);

        if (str_starts_with($driver, 'postgres')) {
            // Terminate other sessions so DROP DATABASE can proceed.
            $pdo->prepare('SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ? AND pid <> pg_backend_pid()')
                ->execute([$name]);
        }

        $pdo->exec("DROP DATABASE IF EXISTS {$quoted}");
        $pdo->exec("CREATE DATABASE {$quoted}");
    }
}

/**
 * Pre-boot self-heal. Runs before `bootstrap.php` because bootstrapping is what
 * an empty database breaks.
 *
 * Only ever flips `installed` from On to Off, and only when the target database
 * demonstrably has no schema. On success the installer writes it back to On; if
 * the install fails midway the flag stays Off, which is precisely the state that
 * lets the NEXT run boot and recreate.
 */
(static function (): void {
    $configFile = getenv('PKP_CONFIG_FILE');

    if (!$configFile) {
        // The tool reports the missing variable properly once it can run.
        return;
    }

    $config = TestInstallConfigFile::parse($configFile);

    if ($config === null || !TestInstallConfigFile::isOn($config['general']['installed'] ?? null)) {
        return;
    }

    $state = TestInstallDatabase::schemaState($config['database'] ?? []);

    if ($state === TestInstallDatabase::SCHEMA_INSTALLED) {
        return;
    }

    if (TestInstallConfigFile::setExisting($configFile, 'general', 'installed', 'Off')) {
        printf(
            "The database named in %s is %s; set installed = Off so the application\n"
                . "can boot. The installer restores it when it finishes.\n",
            $configFile,
            $state === TestInstallDatabase::SCHEMA_NONE
                ? 'empty'
                : 'partially installed (no core version row)'
        );
    }
})();

require(dirname(__FILE__) . '/bootstrap.php');

use PKP\config\Config;
use PKP\config\ConfigParser;

class InstallTestTool extends \PKP\cliTool\InstallTool
{
    protected bool $recreateDatabase = false;

    /**
     * Remember the configured base URL before the installer overwrites it.
     */
    protected ?string $baseUrl = null;

    public function usage()
    {
        echo "Install the test database schema for the Playwright e2e suite.\n"
            . "Usage: PKP_CONFIG_FILE=/abs/path/config.test.inc.php {$this->scriptName} [--recreate-db]\n\n"
            . "  --recreate-db   drop and recreate the database itself before installing\n\n"
            . "A database with no tables at all is handled automatically: the tool sets\n"
            . "installed = Off in the configuration file so the application can boot, and\n"
            . "the installer sets it back to On.\n";
    }

    public function execute()
    {
        if (in_array('--help', $this->argv) || in_array('-h', $this->argv)) {
            $this->usage();

            return;
        }

        $this->recreateDatabase = in_array('--recreate-db', $this->argv);

        if (!getenv('PKP_CONFIG_FILE')) {
            printf("ERROR: PKP_CONFIG_FILE is not set; refusing to install.\n");
            $this->usage();
            exit(1);
        }

        printf("Using configuration file: %s\n", Config::getConfigFileName());

        if ($this->recreateDatabase) {
            TestInstallDatabase::recreate($this->databaseSection());
            printf("Recreated database '%s'.\n", Config::getVar('database', 'name'));
        } elseif (($state = TestInstallDatabase::schemaState($this->databaseSection())) !== TestInstallDatabase::SCHEMA_NONE) {
            printf(
                "ERROR: database '%s' is %s. Re-run with --recreate-db to drop and\n"
                    . "reinstall it (this is what `npm run test:e2e:reset` does).\n",
                Config::getVar('database', 'name'),
                $state === TestInstallDatabase::SCHEMA_INSTALLED
                    ? 'already installed'
                    : 'partially installed — a previous run did not finish'
            );
            exit(1);
        }

        $this->readParams();

        if (!$this->install()) {
            printf(
                "\nThe configuration file still says installed = Off, which is what lets the\n"
                    . "next run boot. Re-run with --recreate-db to start from a clean database.\n"
            );
            exit(1);
        }

        $this->fixUpConfig();
    }

    /**
     * The [database] section as raw strings, for the pre-boot helpers.
     *
     * @return array<string, string>
     */
    protected function databaseSection(): array
    {
        return array_filter([
            'driver' => Config::getVar('database', 'driver'),
            'host' => Config::getVar('database', 'host'),
            'port' => Config::getVar('database', 'port'),
            'unix_socket' => Config::getVar('database', 'unix_socket'),
            'name' => Config::getVar('database', 'name'),
            'username' => Config::getVar('database', 'username'),
            'password' => Config::getVar('database', 'password'),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Build the installation parameters from the active configuration file.
     * Nothing is read from stdin.
     */
    public function readParams()
    {
        $locale = Config::getVar('i18n', 'locale', 'en');
        $configuredLocales = Config::getVar('i18n', 'installed_locales');

        $this->params = [
            'locale' => $locale,
            'additionalLocales' => array_values(array_unique(array_filter(
                array_map('trim', explode(',', (string) ($configuredLocales ?: $locale))),
            ))),
            'filesDir' => Config::getVar('files', 'files_dir'),
            'adminUsername' => getenv('TEST_ADMIN_USERNAME') ?: 'admin',
            'adminPassword' => getenv('TEST_ADMIN_PASSWORD') ?: 'admin',
            'adminEmail' => getenv('TEST_ADMIN_EMAIL') ?: 'admin@example.org',
            'encryption' => Config::getVar('security', 'encryption', 'sha1'),
            'databaseDriver' => Config::getVar('database', 'driver'),
            'databaseHost' => Config::getVar('database', 'host'),
            'databasePort' => Config::getVar('database', 'port'),
            'unixSocket' => Config::getVar('database', 'unix_socket'),
            'databaseUsername' => Config::getVar('database', 'username'),
            'databasePassword' => Config::getVar('database', 'password'),
            'databaseName' => Config::getVar('database', 'name'),
            'oaiRepositoryId' => 'ojs-test.localhost',
            'enableBeacon' => false,
            'timeZone' => Config::getVar('general', 'time_zone', 'UTC'),
            'install' => true,
        ];

        printf(
            "Installing %s schema into database '%s' (driver %s) with files dir '%s'.\n",
            \APP\core\Application::get()->getName(),
            $this->params['databaseName'],
            $this->params['databaseDriver'],
            $this->params['filesDir']
        );

        printf("Installing locales: %s\n", implode(', ', $this->params['additionalLocales']));

        if (!$configuredLocales) {
            printf(
                "NOTICE: [i18n] installed_locales is not set in %s, so only '%s' will be\n"
                    . "installed. A seed that declares any other locale will be refused with\n"
                    . "\"Locale 'xx' is not installed on this site\". Add every locale the suite\n"
                    . "needs (the base seed uses en,fr_CA) and re-run with --recreate-db.\n",
                Config::getConfigFileName(),
                $locale
            );
        }

        if (!is_dir($this->params['filesDir'])) {
            mkdir($this->params['filesDir'], 0o755, true);
        }

        return true;
    }

    /**
     * The installer derives base_url/allowed_hosts from the (absent) HTTP request
     * when it is run from the command line. Restore the values the test fleet
     * actually serves under, taken from the configuration file itself.
     *
     * `installed` is written here too, belt-and-braces: the installer's own
     * createConfig() already sets it, and this makes the tool's post-condition
     * — a successful run always leaves a usable configuration — explicit.
     */
    protected function fixUpConfig(): void
    {
        $configFile = Config::getConfigFileName();
        $baseUrl = $this->baseUrl;
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $port = parse_url($baseUrl, PHP_URL_PORT);

        $parser = new ConfigParser();
        $parser->updateConfig($configFile, [
            'general' => [
                'installed' => 'On',
                'base_url' => $baseUrl,
                'allowed_hosts' => json_encode(array_values(array_unique(array_filter([
                    $host,
                    $port ? "{$host}:{$port}" : null,
                ])))),
                'enable_beacon' => 'Off',
            ],
        ]);
        $parser->writeConfig($configFile);

        printf("Restored installed=On, base_url=%s and allowed_hosts in %s\n", $baseUrl, $configFile);
    }

    /**
     * Run the installer, reporting whether it succeeded.
     *
     * The parent prints the failure and returns void, which would let a broken
     * install exit 0 — and the Playwright setup project shells out to this tool,
     * so a silent success is a silent wrong-state suite.
     */
    public function install(): bool
    {
        $this->baseUrl = Config::getVar('general', 'base_url');

        $installer = new \APP\install\Install($this->params);
        $installer->setLogger($this);

        if (!$installer->execute()) {
            printf("ERROR: Installation failed: %s\n", $installer->getErrorString());

            return false;
        }

        foreach ($installer->getNotes() as $note) {
            printf("%s\n", $note);
        }

        printf("Successfully installed version %s\n", $installer->getNewVersion()->getVersionString(false));

        return true;
    }
}

$tool = new InstallTestTool($argv ?? []);
$tool->execute();
