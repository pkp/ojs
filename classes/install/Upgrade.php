<?php

/**
 * @file classes/install/Upgrade.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class Upgrade
 *
 * @ingroup install
 *
 * @brief Perform system upgrade.
 */

namespace APP\install;

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\journal\JournalDAO;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\DB;
use PKP\config\Config;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\file\FileManager;
use PKP\identity\Identity;
use PKP\install\Installer;
use PKP\navigationMenu\NavigationMenuItemDAO;
use PKP\plugins\PluginSettingsDAO;
use PKP\security\Role;
use PKP\site\SiteDAO;
use PKP\stageAssignment\StageAssignmentDAO;
use PKP\submissionFile\SubmissionFile;

class Upgrade extends Installer
{
    protected $appEmailTemplateVariableNames = [
        'contextName' => 'journalName',
        'contextUrl' => 'journalUrl',
        'contextSignature' => 'journalSignature',
    ];

    /**
     * Constructor.
     *
     * @param array $params upgrade parameters
     * @param string $installFile Name of XML descriptor to install
     * @param bool $isPlugin True iff the installer is for a plugin.
     */
    public function __construct($params, $installFile = 'upgrade.xml', $isPlugin = false)
    {
        parent::__construct($installFile, $params, $isPlugin);
    }


    /**
     * Returns true iff this is an upgrade process.
     *
     * @return bool
     */
    public function isUpgrade()
    {
        return true;
    }

    //
    // Upgrade actions
    //

    /**
     * Rebuild the search index.
     *
     * @return bool
     */
    public function rebuildSearchIndex()
    {
        $submissionSearchIndex = Application::getSubmissionSearchIndex();
        $submissionSearchIndex->rebuildIndex();
        return true;
    }

    /**
     * Clear the CSS cache files (needed when changing LESS files)
     *
     * @return bool
     */
    public function clearCssCache()
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->clearCssCache();
        return true;
    }

    /**
     * For 3.1.0 upgrade (#2467): In multi-journal upgrades from OJS 2.x, the
     * user_group_id column in the authors table may be updated to point to
     * user groups in other journals.
     *
     * @return bool
     */
    public function fixAuthorGroup()
    {
        $rows = DB::table('authors as a')
            ->leftJoin('submissions as s', 's.submission_id', '=', 'a.submission_id')
            ->leftJoin('user_groups as g', 'a.user_group_id', '=', 'g.user_group_id')
            ->whereColumn('g.context_id', '<>', 's.context_id')
            ->get(['a.author_id', 's.context_id']);

        foreach ($rows as $row) {
            $authorGroup = Repo::userGroup()->getByRoleIds([Role::ROLE_ID_AUTHOR], $row->context_id, true);
            if ($authorGroup) {
                DB::table('authors')
                    ->where('author_id', '=', $row->author_id)
                    ->update(['user_group_id' => $authorGroup->getId()]);
            }
        }

        return true;
    }

    /**
     * For 3.0.x - 3.1.1 upgrade: repair the migration of the supp files.
     *
     * @return bool True indicates success.
     */
    public function repairSuppFilesFilestage()
    {
        $fileManager = new FileManager();

        $rows = DB::table('submission_supplementary_files as ssf')
            ->leftJoin('submission_files as sf', 'sf.file_id', '=', 'ssf.file_id')
            ->leftJoin('submissions as s', 's.submission_id', '=', 'sf.submission_id')
            ->where('sf.file_stage', '=', SubmissionFile::SUBMISSION_FILE_SUBMISSION)
            ->where('sf.assoc_type', '=', Application::ASSOC_TYPE_REPRESENTATION)
            ->whereColumn('sf.revision', '=', 'ssf.revision')
            ->get();

        foreach ($rows as $row) {
            $submissionDir = Repo::submissionFile()
                ->getSubmissionDir($row->context_id, $row->submission_id);
            $generatedOldFilename = sprintf(
                '%d-%s-%d-%d-%d-%s.%s',
                $row->submission_id,
                $row->genre_id,
                $row->file_id,
                $row->revision,
                $row->file_stage,
                date('Ymd', strtotime($row->date_uploaded)),
                strtolower_codesafe($fileManager->parseFileExtension($row->original_file_name))
            );
            $generatedNewFilename = sprintf(
                '%d-%s-%d-%d-%d-%s.%s',
                $row->submission_id,
                $row->genre_id,
                $row->file_id,
                $row->revision,
                SubmissionFile::SUBMISSION_FILE_PROOF,
                date('Ymd', strtotime($row->date_uploaded)),
                strtolower_codesafe($fileManager->parseFileExtension($row->original_file_name))
            );
            $oldFileName = $submissionDir . '/' . $this->_fileStageToPath($row->file_stage) . '/' . $generatedOldFilename;
            $newFileName = $submissionDir . '/' . $this->_fileStageToPath($row->file_stage) . '/' . $generatedNewFilename;
            if (!Services::get('file')->fs->rename($oldFileName, $newFileName)) {
                error_log("Unable to move \"{$oldFileName}\" to \"{$newFileName}\".");
            }
            DB::table('submission_files')
                ->where('file_id', '=', $row->file_id)
                ->where('revision', '=', $row->revision)
                ->update(['file_stage' => SubmissionFile::SUBMISSION_FILE_PROOF]);
        }
        return true;
    }

    /**
     * If StaticPages table exists we should port the data as NMIs
     *
     * @return bool
     */
    public function migrateStaticPagesToNavigationMenuItems()
    {
        if ($this->tableExists('static_pages')) {
            $contextDao = Application::getContextDAO();
            $navigationMenuItemDao = DAORegistry::getDAO('NavigationMenuItemDAO'); /** @var NavigationMenuItemDAO $navigationMenuItemDao */

            $staticPagesDao = new \APP\plugins\generic\staticPages\classes\StaticPagesDAO();

            $contexts = $contextDao->getAll();
            while ($context = $contexts->next()) {
                $contextStaticPages = $staticPagesDao->getByContextId($context->getId())->toAssociativeArray();
                foreach ($contextStaticPages as $staticPage) {
                    $retNMIId = $navigationMenuItemDao->portStaticPage($staticPage);
                    if ($retNMIId) {
                        $staticPagesDao->deleteById($staticPage->getId());
                    } else {
                        error_log('WARNING: The StaticPage "' . $staticPage->getLocalizedTitle() . '" uses a path (' . $staticPage->getPath() . ') that conflicts with an existing Custom Navigation Menu Item path. Skipping this StaticPage.');
                    }
                }
            }
        }

        return true;
    }

    /**
     * Migrate sr_SR locale to the new sr_RS@latin.
     *
     * @return bool
     */
    public function migrateSRLocale()
    {
        $oldLocale = 'sr_SR';
        $newLocale = 'sr_RS@latin';

        $oldLocaleStringLength = 's:5';

        $journalSettingsDao = new class () extends \PKP\db\DAO {
            /**
             * Method for update journal setting
             *
             * @param int $journalId
             * @param string $name
             * @param string $type data type of the setting. If omitted, type will be guessed
             * @param bool $isLocalized
             */
            public function updateSetting($journalId, $name, $value, $type = null, $isLocalized = false)
            {
                if (!$isLocalized) {
                    $value = $this->convertToDB($value, $type);
                    DB::table('journal_settings')->updateOrInsert(
                        ['journal_id' => (int) $journalId, 'setting_name' => $name, 'locale' => ''],
                        ['setting_value' => $value, 'setting_type' => $type]
                    );
                } else {
                    if (is_array($value)) {
                        foreach ($value as $locale => $localeValue) {
                            $this->update('DELETE FROM journal_settings WHERE journal_id = ? AND setting_name = ? AND locale = ?', [(int) $journalId, $name, $locale]);
                            if (empty($localeValue)) {
                                continue;
                            }
                            $type = null;
                            $this->update(
                                'INSERT INTO journal_settings (journal_id, setting_name, setting_value, setting_type, locale) VALUES (?, ?, ?, ?, ?)',
                                [$journalId, $name, $this->convertToDB($localeValue, $type), $type, $locale]
                            );
                        }
                    }
                }
            }

            /**
             * Retrieve a context setting value.
             *
             * @param string $name
             * @param string $locale optional
             */
            public function getSetting($journalId, $name, $locale = null)
            {
                $params = [(int) $journalId, $name];
                if ($locale) {
                    $params[] = $locale;
                }
                $result = $this->retrieve(
                    'SELECT	setting_name, setting_value, setting_type, locale
                    FROM journal_settings
                    WHERE journal_id = ? AND
                        setting_name = ?' .
                        ($locale ? ' AND locale = ?' : ''),
                    $params
                );

                $returner = [];
                foreach ($result as $row) {
                    $returner[$row->locale] = $this->convertFromDB($row->setting_value, $row->setting_type);
                }
                if (count($returner) == 1) {
                    return array_shift($returner);
                }
                if (count($returner) == 0) {
                    return false;
                }
                return $returner;
            }
        };

        // Check if the sr_SR is used, and if not do not run further
        $srExistResult = $journalSettingsDao->retrieve('SELECT COUNT(*) AS row_count FROM site WHERE installed_locales LIKE ?', ['%' . $oldLocale . '%']);
        $row = $srExistResult->current();
        $srExist = $row && $row->row_count;
        if (!$srExist) {
            return true;
        }

        // Consider all DB tables that have locale column:
        $dbTables = [
            'announcement_settings', 'announcement_type_settings', 'author_settings', 'books_for_review_settings', 'citation_settings', 'controlled_vocab_entry_settings',
            'data_object_tombstone_settings', 'email_templates_data', 'email_templates_default_data', 'external_feed_settings', 'filter_settings', 'genre_settings', 'group_settings',
            'issue_galleys', 'issue_galley_settings', 'issue_settings', 'journal_settings', 'library_file_settings',
            'navigation_menu_item_assignment_settings', 'navigation_menu_item_settings', 'notification_settings', 'referral_settings',
            'review_form_element_settings', 'review_form_settings', 'review_object_metadata_settings', 'review_object_type_settings', 'section_settings', 'site_settings',
            'static_page_settings', 'submissions', 'submission_file_settings', 'submission_galleys', 'submission_galley_settings', 'submission_settings', 'subscription_type_settings',
            'user_group_settings', 'user_settings',
        ];
        foreach ($dbTables as $dbTable) {
            if ($this->tableExists($dbTable)) {
                $journalSettingsDao->update('UPDATE ' . $dbTable . ' SET locale = ? WHERE locale = ?', [$newLocale, $oldLocale]);
            }
        }
        // Consider other locale columns
        $journalSettingsDao->update('UPDATE journals SET primary_locale = ? WHERE primary_locale = ?', [$newLocale, $oldLocale]);
        $journalSettingsDao->update('UPDATE site SET primary_locale = ? WHERE primary_locale = ?', [$newLocale, $oldLocale]);
        $journalSettingsDao->update('UPDATE site SET installed_locales = REPLACE(installed_locales, ?, ?)', [$oldLocale, $newLocale]);
        $journalSettingsDao->update('UPDATE site SET supported_locales = REPLACE(supported_locales, ?, ?)', [$oldLocale, $newLocale]);
        $journalSettingsDao->update('UPDATE users SET locales = REPLACE(locales, ?, ?)', [$oldLocale, $newLocale]);

        // journal_settings
        // Consider array setting values from the setting names:
        // supportedFormLocales, supportedLocales, supportedSubmissionLocales
        $settingNames = "('supportedFormLocales', 'supportedLocales', 'supportedSubmissionLocales')";
        // As a precaution use $oldLocaleStringLength, to exclude that the text contain the old locale string
        $settingValueResult = $journalSettingsDao->retrieve('SELECT * FROM journal_settings WHERE setting_name IN ' . $settingNames . ' AND setting_value LIKE ? AND setting_type = \'object\'', ['%' . $oldLocaleStringLength . ':"' . $oldLocale . '%']);
        foreach ($settingValueResult as $row) {
            $arraySettingValue = $journalSettingsDao->getSetting($row->journal_id, $row->setting_name);
            for ($i = 0; $i < count($arraySettingValue); $i++) {
                if ($arraySettingValue[$i] == $oldLocale) {
                    $arraySettingValue[$i] = $newLocale;
                }
            }
            $journalSettingsDao->updateSetting($row->journal_id, $row->setting_name, $arraySettingValue);
        }

        // Consider journal images
        // Note that the locale column values are already changed above
        $publicFileManager = new PublicFileManager();
        $settingNames = "('homeHeaderLogoImage', 'homeHeaderTitleImage', 'homepageImage', 'journalFavicon', 'journalThumbnail', 'pageHeaderLogoImage', 'pageHeaderTitleImage')";
        $settingValueResult = $journalSettingsDao->retrieve('SELECT * FROM journal_settings WHERE setting_name IN ' . $settingNames . ' AND locale = ? AND setting_value LIKE ? AND setting_type = \'object\'', [$newLocale, '%' . $oldLocale . '%']);
        foreach ($settingValueResult as $row) {
            $arraySettingValue = $journalSettingsDao->getSetting($row->journal_id, $row->setting_name, $newLocale);
            $oldUploadName = $arraySettingValue['uploadName'];
            $newUploadName = str_replace('_' . $oldLocale . '.', '_' . $newLocale . '.', $oldUploadName);
            if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldUploadName)) {
                $publicFileManager->copyContextFile($row->journal_id, $publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldUploadName, $newUploadName);
                $publicFileManager->removeContextFile($row->journal_id, $oldUploadName);
            }
            $arraySettingValue['uploadName'] = $newUploadName;
            $newArraySettingValue[$newLocale] = $arraySettingValue;
            $journalSettingsDao->updateSetting($row->journal_id, $row->setting_name, $newArraySettingValue, 'object', true);
        }

        // Consider issue cover images
        // Note that the locale column values are already changed above
        $settingValueResult = $journalSettingsDao->retrieve('SELECT a.*, b.journal_id FROM issue_settings a, issues b WHERE a.setting_name = \'fileName\' AND a.locale = ? AND a.setting_value LIKE ? AND a.setting_type = \'string\' AND b.issue_id = a.issue_id', [$newLocale, '%' . $oldLocale . '%']);
        foreach ($settingValueResult as $row) {
            $oldCoverImage = $row->setting_value;
            $newCoverImage = str_replace('_' . $oldLocale . '.', '_' . $newLocale . '.', $oldCoverImage);
            if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldCoverImage)) {
                $publicFileManager->copyContextFile($row->journal_id, $publicFileManager->getContextFilesPath($row->journal_id) . '/' . $oldCoverImage, $newCoverImage);
                $publicFileManager->removeContextFile($row->journal_id, $oldCoverImage);
            }
            $journalSettingsDao->update('UPDATE issue_settings SET setting_value = ? WHERE issue_id = ? AND setting_name = \'fileName\' AND locale = ?', [$newCoverImage, (int) $row->issue_id, $newLocale]);
        }

        // Consider article cover images
        // Note that the locale column values are already changed above
        $settingValueResult = $journalSettingsDao->retrieve('SELECT a.*, b.context_id FROM submission_settings a, submissions b WHERE a.setting_name = \'fileName\' AND a.locale = ? AND a.setting_value LIKE ? AND b.submission_id = a.submission_id', [$newLocale, '%' . $oldLocale . '%']);
        foreach ($settingValueResult as $row) {
            $oldCoverImage = $row->setting_value;
            $newCoverImage = str_replace('_' . $oldLocale . '.', '_' . $newLocale . '.', $oldCoverImage);
            if ($publicFileManager->fileExists($publicFileManager->getContextFilesPath($row->context_id) . '/' . $oldCoverImage)) {
                $publicFileManager->copyContextFile($row->context_id, $publicFileManager->getContextFilesPath($row->context_id) . '/' . $oldCoverImage, $newCoverImage);
                $publicFileManager->removeContextFile($row->context_id, $oldCoverImage);
            }
            $journalSettingsDao->update('UPDATE submission_settings SET setting_value = ? WHERE submission_id = ? AND setting_name = \'fileName\' AND locale = ?', [$newCoverImage, (int) $row->submission_id, $newLocale]);
        }

        // plugin_settings
        // Consider array setting values from the setting names:
        // blockContent (from a custom block plugin), additionalInformation (from objects for review plugin)
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO'); /** @var PluginSettingsDAO $pluginSettingsDao */
        $settingNames = "('blockContent', 'additionalInformation')";
        $settingValueResult = $pluginSettingsDao->retrieve('SELECT * FROM plugin_settings WHERE setting_name IN ' . $settingNames . ' AND setting_value LIKE ?', ['%' . $oldLocaleStringLength . ':"' . $oldLocale . '%']);
        foreach ($settingValueResult as $row) {
            $arraySettingValue = $pluginSettingsDao->getSetting($row->context_id, $row->plugin_name, $row->setting_name);
            $arraySettingValue[$newLocale] = $arraySettingValue[$oldLocale];
            unset($arraySettingValue[$oldLocale]);
            $pluginSettingsDao->updateSetting($row->context_id, $row->plugin_name, $row->setting_name, $arraySettingValue);
        }

        return true;
    }

    /**
     * Migrate first and last user names as multilingual into the DB table user_settings.
     *
     * @return bool
     */
    public function migrateUserAndAuthorNames()
    {
        // the user names will be saved in the site's primary locale
        DB::insert("INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT u.user_id, s.primary_locale, ?, u.first_name, 'string' FROM users_tmp u, site s", [Identity::IDENTITY_SETTING_GIVENNAME]);
        DB::insert("INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT u.user_id, s.primary_locale, ?, u.last_name, 'string' FROM users_tmp u, site s", [Identity::IDENTITY_SETTING_FAMILYNAME]);
        // the author names will be saved in the submission's primary locale
        DB::insert("INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT a.author_id, s.locale, ?, a.first_name, 'string' FROM authors_tmp a, submissions s WHERE s.submission_id = a.submission_id", [Identity::IDENTITY_SETTING_GIVENNAME]);
        DB::insert("INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) SELECT DISTINCT a.author_id, s.locale, ?, a.last_name, 'string' FROM authors_tmp a, submissions s WHERE s.submission_id = a.submission_id", [Identity::IDENTITY_SETTING_FAMILYNAME]);

        // middle name will be migrated to the given name
        // note that given names are already migrated to the settings table
        switch (Config::getVar('database', 'driver')) {
            case 'mysql':
            case 'mysqli':
                // the alias for _settings table cannot be used for some reason -- syntax error
                DB::update("UPDATE user_settings, users_tmp u SET user_settings.setting_value = CONCAT(user_settings.setting_value, ' ', u.middle_name) WHERE user_settings.setting_name = ? AND u.user_id = user_settings.user_id AND u.middle_name IS NOT NULL AND u.middle_name <> ''", [Identity::IDENTITY_SETTING_GIVENNAME]);
                DB::update("UPDATE author_settings, authors_tmp a SET author_settings.setting_value = CONCAT(author_settings.setting_value, ' ', a.middle_name) WHERE author_settings.setting_name = ? AND a.author_id = author_settings.author_id AND a.middle_name IS NOT NULL AND a.middle_name <> ''", [Identity::IDENTITY_SETTING_GIVENNAME]);
                break;
            case 'postgres':
            case 'postgres64':
            case 'postgres7':
            case 'postgres8':
            case 'postgres9':
                DB::update("UPDATE user_settings SET setting_value = CONCAT(setting_value, ' ', u.middle_name) FROM users_tmp u WHERE user_settings.setting_name = ? AND u.user_id = user_settings.user_id AND u.middle_name IS NOT NULL AND u.middle_name <> ''", [Identity::IDENTITY_SETTING_GIVENNAME]);
                DB::update("UPDATE author_settings SET setting_value = CONCAT(setting_value, ' ', a.middle_name) FROM authors_tmp a WHERE author_settings.setting_name = ? AND a.author_id = author_settings.author_id AND a.middle_name IS NOT NULL AND a.middle_name <> ''", [Identity::IDENTITY_SETTING_GIVENNAME]);
                break;
            default: fatalError('Unknown database type!');
        }

        // salutation and suffix will be migrated to the preferred public name
        // user preferred public names will be inserted for each supported site locales
        $siteDao = DAORegistry::getDAO('SiteDAO'); /** @var SiteDAO $siteDao */
        $site = $siteDao->getSite();
        $supportedLocales = $site->getSupportedLocales();
        $userResult = DB::select(
            "SELECT user_id, first_name, last_name, middle_name, salutation, suffix FROM users_tmp
			WHERE (salutation IS NOT NULL AND salutation <> '') OR
			(suffix IS NOT NULL AND suffix <> '')"
        );
        foreach ($userResult as $row) {
            $userId = $row->user_id;
            $firstName = $row->first_name;
            $lastName = $row->last_name;
            $middleName = $row->middle_name;
            $salutation = $row->salutation;
            $suffix = $row->suffix;
            foreach ($supportedLocales as $siteLocale) {
                $preferredPublicName = ($salutation != '' ? "{$salutation} " : '') . "{$firstName} " . ($middleName != '' ? "{$middleName} " : '') . $lastName . ($suffix != '' ? ", {$suffix}" : '');
                DB::insert(
                    "INSERT INTO user_settings (user_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, 'preferredPublicName', ?, 'string')",
                    [(int) $userId, $siteLocale, $preferredPublicName]
                );
            }
        }

        // author suffix will be migrated to the author preferred public name
        // author preferred public names will be inserted for each journal supported locale
        // get supported locales for all journals
        $journalDao = DAORegistry::getDAO('JournalDAO'); /** @var JournalDAO $journalDao */
        $journals = $journalDao->getAll();
        $journalsSupportedLocales = [];
        while ($journal = $journals->next()) {
            $journalsSupportedLocales[$journal->getId()] = $journal->getSupportedLocales();
        }
        // get all authors with a suffix
        $authorResult = DB::select(
            "SELECT a.author_id, a.first_name, a.last_name, a.middle_name, a.suffix, j.journal_id FROM authors_tmp a
			LEFT JOIN submissions s ON (s.submission_id = a.submission_id)
			LEFT JOIN journals j ON (j.journal_id = s.context_id)
			WHERE suffix IS NOT NULL AND suffix <> ''"
        );
        foreach ($authorResult as $row) {
            $authorId = $row->author_id;
            $firstName = $row->first_name;
            $lastName = $row->last_name;
            $middleName = $row->middle_name;
            $suffix = $row->suffix;
            $journalId = $row->journal_id;
            $supportedLocales = $journalsSupportedLocales[$journalId];
            foreach ($supportedLocales as $locale) {
                $preferredPublicName = "{$firstName} " . ($middleName != '' ? "{$middleName} " : '') . $lastName . ($suffix != '' ? ", {$suffix}" : '');
                DB::insert(
                    "INSERT INTO author_settings (author_id, locale, setting_name, setting_value, setting_type) VALUES (?, ?, 'preferredPublicName', ?, 'string')",
                    [(int) $authorId, $locale, $preferredPublicName]
                );
            }
        }

        // remove temporary table
        $siteDao->update('DROP TABLE users_tmp');
        $siteDao->update('DROP TABLE authors_tmp');
        return true;
    }

    /**
    * Update assoc_id for assoc_type Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER = 531
    *
    * @return bool True indicates success.
    */
    public function updateSuppFileMetrics()
    {
        // Copy 531 assoc_type data to temp table
        DB::statement('CREATE TABLE metrics_supp AS (SELECT * FROM metrics WHERE assoc_type = 531)');
        // Fetch submission_file data with old-supp-id
        $result = DB::select(
            'SELECT * FROM submission_file_settings WHERE setting_name =  ?',
            ['old-supp-id']
        );
        // Loop through the data and save to temp table
        foreach ($result as $row) {
            // Use assoc_type 2531 to prevent collisions between old assoc_id and new assoc_id
            DB::update('UPDATE metrics_supp SET assoc_id = ?, assoc_type = ? WHERE assoc_type = ? AND assoc_id = ?', [(int) $row->file_id, 2531, 531, (int) $row->setting_value]);
        }
        // update temprorary 2531 values to 531 values
        DB::update('UPDATE metrics_supp SET assoc_type = ? WHERE assoc_type = ?', [531, 2531]);
        // delete all existing 531 values from the actual metrics table
        DB::statement('DELETE FROM metrics WHERE assoc_type = 531');
        // copy updated 531 values from metrics_supp to metrics table
        DB::insert('INSERT INTO metrics SELECT * FROM metrics_supp');
        // Drop metrics_supp table
        DB::statement('DROP TABLE metrics_supp');
        return true;
    }

    /**
     * Add an entry for the site stylesheet to the site_settings database when it
     * exists
     */
    public function migrateSiteStylesheet()
    {
        $siteDao = DAORegistry::getDAO('SiteDAO'); /** @var SiteDAO $siteDao */

        $publicFileManager = new PublicFileManager();

        if (!file_exists($publicFileManager->getSiteFilesPath() . '/sitestyle.css')) {
            return true;
        }

        $site = $siteDao->getSite();
        $site->setData('styleSheet', 'sitestyle.css');
        $siteDao->updateObject($site);

        return true;
    }

    /**
     * Copy a context's copyrightNotice to a new licenseTerms setting, leaving
     * the copyrightNotice in place.
     */
    public function createLicenseTerms()
    {
        $contextDao = Application::getContextDao();

        $result = $contextDao->retrieve('SELECT * from ' . $contextDao->settingsTableName . " WHERE setting_name='copyrightNotice'");
        foreach ($result as $row) {
            $row = (array) $row;
            $contextDao->update(
                '
				INSERT INTO ' . $contextDao->settingsTableName . ' (
					' . $contextDao->primaryKeyColumn . ',
					locale,
					setting_name,
					setting_value
				) VALUES (?, ?, ?, ?)',
                [
                    $row[$contextDao->primaryKeyColumn],
                    $row['locale'],
                    'licenseTerms',
                    $row['setting_value'],
                ]
            );
        }
        return true;
    }

    /**
     * Update permit_metadata_edit and can_change_metadata for user_groups and stage_assignments tables.
     *
     * @return bool True indicates success.
     */
    public function changeUserRolesAndStageAssignmentsForStagePermitSubmissionEdit()
    {
        $stageAssignmentDao = DAORegistry::getDAO('StageAssignmentDAO'); /** @var StageAssignmentDAO $stageAssignmentDao */

        $roles = Repo::userGroup()::NOT_CHANGE_METADATA_EDIT_PERMISSION_ROLES;
        $roleString = '(' . implode(',', $roles) . ')';

        DB::table('user_groups')
            ->whereIn('role_id', $roles)
            ->update(['permit_metadata_edit' => 1]);

        switch (Config::getVar('database', 'driver')) {
            case 'mysql':
            case 'mysqli':
                $stageAssignmentDao->update('UPDATE stage_assignments sa JOIN user_groups ug on sa.user_group_id = ug.user_group_id SET sa.can_change_metadata = 1 WHERE ug.role_id IN ' . $roleString);
                break;
            case 'postgres':
            case 'postgres64':
            case 'postgres7':
            case 'postgres8':
            case 'postgres9':
                $stageAssignmentDao->update('UPDATE stage_assignments sa SET can_change_metadata=1 FROM user_groups ug WHERE sa.user_group_id = ug.user_group_id AND ug.role_id IN ' . $roleString);
                break;
            default: fatalError('Unknown database type!');
        }

        return true;
    }

    /**
     * Update how submission cover images are stored
     *
     * Combines the coverImage and coverImageAltText settings in the
     * submissions table into an assoc array stored under the coverImage
     * setting.
     *
     * This will be migrated to the publication_settings table in
     * 3.2.0_versioning.xml.
     */
    public function migrateSubmissionCoverImages()
    {
        $coverImagesBySubmission = [];

        $deprecatedDao = Repo::submission()->dao->deprecatedDao;
        $result = $deprecatedDao->retrieve(
            'SELECT * from submission_settings WHERE setting_name=\'coverImage\' OR setting_name=\'coverImageAltText\''
        );
        foreach ($result as $row) {
            $submissionId = $row->submission_id;
            if (empty($coverImagesBySubmission[$submissionId])) {
                $coverImagesBySubmission[$submissionId] = [];
            }
            if ($row->setting_name === 'coverImage') {
                $coverImagesBySubmission[$submissionId]['uploadName'] = $row->setting_value;
                $coverImagesBySubmission[$submissionId]['dateUploaded'] = Core::getCurrentDate();
            } elseif ($row->setting_name === 'coverImageAltText') {
                $coverImagesBySubmission[$submissionId]['altText'] = $row->setting_value;
            }
        }

        foreach ($coverImagesBySubmission as $submissionId => $coverImagesBySubmission) {
            $deprecatedDao->update(
                'UPDATE submission_settings
					SET setting_value = ?
					WHERE submission_id = ? AND setting_name = ?',
                [
                    serialize($coverImagesBySubmission),
                    $submissionId,
                    'coverImage',
                ]
            );
        }

        return true;
    }

    /**
     * Get the directory of a file based on its file stage
     *
     * @param int $fileStage One of SubmissionFile::SUBMISSION_FILE_ constants
     *
     * @return string
     */
    public function _fileStageToPath($fileStage)
    {
        static $fileStagePathMap = [
            SubmissionFile::SUBMISSION_FILE_SUBMISSION => 'submission',
            SubmissionFile::SUBMISSION_FILE_NOTE => 'note',
            SubmissionFile::SUBMISSION_FILE_REVIEW_FILE => 'submission/review',
            SubmissionFile::SUBMISSION_FILE_REVIEW_ATTACHMENT => 'submission/review/attachment',
            SubmissionFile::SUBMISSION_FILE_REVIEW_REVISION => 'submission/review/revision',
            SubmissionFile::SUBMISSION_FILE_FINAL => 'submission/final',
            SubmissionFile::SUBMISSION_FILE_COPYEDIT => 'submission/copyedit',
            SubmissionFile::SUBMISSION_FILE_DEPENDENT => 'submission/proof',
            SubmissionFile::SUBMISSION_FILE_PROOF => 'submission/proof',
            SubmissionFile::SUBMISSION_FILE_PRODUCTION_READY => 'submission/productionReady',
            SubmissionFile::SUBMISSION_FILE_ATTACHMENT => 'attachment',
            SubmissionFile::SUBMISSION_FILE_QUERY => 'submission/query',
        ];

        if (!isset($fileStagePathMap[$fileStage])) {
            throw new \Exception('A file assigned to the file stage ' . $fileStage . ' could not be migrated.');
        }

        return $fileStagePathMap[$fileStage];
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\install\Upgrade', '\Upgrade');
}
