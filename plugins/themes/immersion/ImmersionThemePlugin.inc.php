<?php

/**
 * @file plugins/themes/immersion/ImmersionThemePlugin.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ImmersionThemePlugin
 *
 * @ingroup plugins_themes_immersion
 *
 * @brief Immersion theme
 */

use APP\core\Request;
use APP\facades\Repo;
use PKP\facades\Locale;
use PKP\plugins\PluginSettingsDAO;
use PKP\plugins\ThemePlugin;

class ImmersionThemePlugin extends ThemePlugin
{
    public function init()
    {

        // Adding styles (JQuery UI, Bootstrap, Tag-it)
        $this->addStyle('app-css', 'resources/dist/app.min.css');
        $this->addStyle('less', 'resources/less/import.less');

        // Styles for HTML galleys
        $this->addStyle('htmlGalley', 'templates/plugins/generic/htmlArticleGalley/css/default.less', ['contexts' => 'htmlGalley']);

        // Adding scripts (JQuery, Popper, Bootstrap, JQuery UI, Tag-it, Theme's JS)
        $this->addScript('app-js', 'resources/dist/app.min.js');

        // Add navigation menu areas for this theme
        $this->addMenuArea(['primary', 'user']);

        // Option to show section description on the journal's homepage; turned off by default
        $this->addOption('sectionDescriptionSetting', 'FieldOptions', [
            'label' => __('plugins.themes.immersion.options.sectionDescription.label'),
            'description' => __('plugins.themes.immersion.options.sectionDescription.description'),
            'type' => 'radio',
            'options' => [
                [
                    'value' => 'disable',
                    'label' => __('plugins.themes.immersion.options.sectionDescription.disable'),
                ],
                [
                    'value' => 'enable',
                    'label' => __('plugins.themes.immersion.options.sectionDescription.enable'),
                ],
            ]
        ]);

        $this->addOption('journalDescription', 'FieldOptions', [
            'label' => __('plugins.themes.immersion.options.journalDescription.label'),
            'description' => __('plugins.themes.immersion.options.journalDescription.description'),
            'type' => 'radio',
            'options' => [
                [
                    'value' => 0,
                    'label' => __('plugins.themes.immersion.options.journalDescription.disable'),
                ],
                [
                    'value' => 1,
                    'label' => __('plugins.themes.immersion.options.journalDescription.enable'),
                ],
            ]
        ]);

        $this->addOption('journalDescriptionColour', 'FieldColor', [
            'label' => __('plugins.themes.immersion.options.journalDescriptionColour.label'),
            'description' => __('plugins.themes.immersion.options.journalDescriptionColour.description'),
            'default' => '#000',
        ]);

        $this->addOption('immersionAnnouncementsColor', 'FieldColor', [
            'label' => __('plugins.themes.immersion.announcements.colorPick'),
            'default' => '#000',
        ]);

        // Add usage stats display options
        $this->addOption('displayStats', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.immersion.option.displayStats.label'),
            'options' => [
                [
                    'value' => 'none',
                    'label' => __('plugins.themes.immersion.option.displayStats.none'),
                ],
                [
                    'value' => 'bar',
                    'label' => __('plugins.themes.immersion.option.displayStats.bar'),
                ],
                [
                    'value' => 'line',
                    'label' => __('plugins.themes.immersion.option.displayStats.line'),
                ],
            ],
            'default' => 'none',
        ]);

        // Additional data to the templates
        HookRegistry::add('TemplateManager::display', [$this, 'addIssueTemplateData']);
        HookRegistry::add('TemplateManager::display', [$this, 'addSiteWideData']);
        HookRegistry::add('TemplateManager::display', [$this, 'homepageAnnouncements']);
        HookRegistry::add('TemplateManager::display', [$this, 'homepageJournalDescription']);
        HookRegistry::add('issueform::display', [$this, 'addToIssueForm']);

        // Additional variable for the issue form
        HookRegistry::register('Schema::get::issue', [$this, 'addToSchema']);
        HookRegistry::add('issueform::initdata', [$this, 'initDataIssueFormFields']);
        HookRegistry::add('issueform::readuservars', [$this, 'readIssueFormFields']);
        HookRegistry::add('issueform::execute', [$this, 'executeIssueFormFields']);
        HookRegistry::add(
            'Templates::Editor::Issues::IssueData::AdditionalMetadata',
            [$this, 'callbackTemplateIssueForm']
        );

        // Load colorpicker on issue management page
        $this->addStyle('spectrum', '/resources/dist/spectrum-1.8.0.css', [
            'contexts' => 'backend-manageIssues',
        ]);
        $this->addScript('spectrum', '/resources/dist/spectrum-1.8.0.js', [
            'contexts' => 'backend-manageIssues',
        ]);
    }

    /**
     * Get the display name of this theme
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.themes.immersion.name');
    }

    /**
     * Get the description of this plugin
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.themes.immersion.description');
    }

    /**
     * @param $hookname string
     * @param $args array [
     *
     * @option TemplateManager
     * @option string relative path to the template
     * ]
     *
     * @brief Add section-specific data to the indexJournal and issue templates
     */

    public function addIssueTemplateData($hookname, $args)
    {
        $templateMgr = $args[0]; /** @var TemplateManager $templateMgr */
        $template = $args[1];
        $request = $this->getRequest(); /** @var Request $request */

        if ($template !== 'frontend/pages/issue.tpl' && $template !== 'frontend/pages/indexJournal.tpl') {
            return false;
        }

        $context = $request->getContext(); /** @var Context $context */
        $contextId = $context->getId();

        /** @var Issue $issue */
        if ($template === 'frontend/pages/indexJournal.tpl') {
            $issue = Repo::issue()->getCurrent($contextId, true);
        } else {
            $issue = $templateMgr->getTemplateVars('issue');
        }

        if (!$issue) {
            return false;
        }

        $publishedSubmissionsInSection = $templateMgr->getTemplateVars('publishedSubmissions');

        // Section color
        $immersionSectionColors = $issue->getData('immersionSectionColor');
        if (empty($immersionSectionColors)) {
            return false;
        } // Section background colors aren't set

        $sections = Repo::section()->getByIssueId($issue->getId()); /** @var \Illuminate\Support\LazyCollection $sections */
        $lastSectionColor = null;

        // Section description; check if this option and BrowseBySection plugin is enabled
        $sectionDescriptionSetting = $this->getOption('sectionDescriptionSetting');
        $pluginSettingsDAO = DAORegistry::getDAO('PluginSettingsDAO'); /** @var PluginSettingsDAO $pluginSettingsDAO */
        $browseBySectionSettings = $pluginSettingsDAO->getPluginSettings($contextId, 'browsebysectionplugin');
        $isBrowseBySectionEnabled = false;
        if (!empty($browseBySectionSettings) && array_key_exists('enabled', $browseBySectionSettings) && $browseBySectionSettings['enabled']) {
            $isBrowseBySectionEnabled = true;
        }

        $locale = Locale::getLocale();
        foreach ($publishedSubmissionsInSection as $sectionId => $publishedArticlesBySection) {
            foreach ($sections as $section) { /** @var \APP\section\Section $section */
                if ($section->getId() == $sectionId) {
                    // Set section and its background color
                    $publishedSubmissionsInSection[$sectionId]['section'] = $section;
                    $publishedSubmissionsInSection[$sectionId]['sectionColor'] = $immersionSectionColors[$sectionId];

                    // Check if section background color is dark
                    $isSectionDark = false;
                    if ($immersionSectionColors[$sectionId] && $this->isColourDark($immersionSectionColors[$sectionId])) {
                        $isSectionDark = true;
                    }
                    $publishedSubmissionsInSection[$sectionId]['isSectionDark'] = $isSectionDark;

                    // Section description
                    if ($sectionDescriptionSetting == 'enable' && $isBrowseBySectionEnabled && $section->getData('browseByDescription', $locale)) {
                        $publishedSubmissionsInSection[$sectionId]['sectionDescription'] = $section->getData('browseByDescription', $locale);
                    }

                    // Need only the color of the last section that contains articles
                    if ($publishedSubmissionsInSection[$sectionId]['articles'] && $immersionSectionColors[$sectionId]) {
                        $lastSectionColor = $immersionSectionColors[$sectionId];
                    }
                }
            }
        }

        $templateMgr->assign([
            'publishedSubmissions' => $publishedSubmissionsInSection,
            'lastSectionColor' => $lastSectionColor
        ]);
    }

    /**
     * @param $hookname string
     * @param $args array [
     *
     * @option TemplateManager
     * @option string relative path to the template
     * ]
     *
     * @return boolean|void
     *
     * @brief background color for announcements section on the journal index page
     */
    public function homepageAnnouncements($hookname, $args)
    {

        $templateMgr = $args[0];
        $template = $args[1];

        if ($template !== 'frontend/pages/indexJournal.tpl') {
            return false;
        }

        $request = $this->getRequest();
        $journal = $request->getJournal();

        // Announcements on index journal page
        $announcementsIntro = $journal->getLocalizedData('announcementsIntroduction');
        $immersionAnnouncementsColor = $this->getOption('immersionAnnouncementsColor');

        $isAnnouncementDark = false;
        if ($immersionAnnouncementsColor && $this->isColourDark($immersionAnnouncementsColor)) {
            $isAnnouncementDark = true;
        }

        $templateMgr->assign([
            'announcementsIntroduction' => $announcementsIntro,
            'isAnnouncementDark' => $isAnnouncementDark,
            'immersionAnnouncementsColor' => $immersionAnnouncementsColor
        ]);
    }

    /**
     * @param $hookname string
     * @param $args array [
     *
     * @option TemplateManager
     * @option string relative path to the template
     * ]
     *
     * @brief Assign additional data to Smarty templates
     */
    public function addSiteWideData($hookname, $args)
    {
        $templateMgr = $args[0];

        $request = $this->getRequest();
        $journal = $request->getJournal();

        if (!defined('SESSION_DISABLE_INIT')) {

            // Check locales
            if ($journal) {
                $locales = $journal->getSupportedLocaleNames();
            } else {
                $locales = $request->getSite()->getSupportedLocaleNames();
            }

            // Load login form
            $loginUrl = $request->url(null, 'login', 'signIn');
            if (Config::getVar('security', 'force_login_ssl')) {
                $loginUrl = PKPString::regexp_replace('/^http:/', 'https:', $loginUrl);
            }

            $orcidImageUrl = $this->getPluginPath() . '/templates/images/orcid.png';

            if ($request->getContext()) {
                $templateMgr->assign('immersionHomepageImage', $journal->getLocalizedSetting('homepageImage'));
            }

            $templateMgr->assign([
                'languageToggleLocales' => $locales,
                'loginUrl' => $loginUrl,
                'orcidImageUrl' => $orcidImageUrl
            ]);
        }
    }

    /**
     * @param $args array [
     *
     * @option TemplateManager
     * @option string relative path to the template
     * ]
     *
     * @return boolean|void
     *
     * @brief Show Journal Description on the journal landing page depending on theme settings
     */
    public function homepageJournalDescription($hookName, $args)
    {
        $templateMgr = $args[0];
        $template = $args[1];

        if ($template != 'frontend/pages/indexJournal.tpl') {
            return false;
        }

        $journalDescriptionColour = $this->getOption('journalDescriptionColour');
        $isJournalDescriptionDark = false;
        if ($journalDescriptionColour && $this->isColourDark($journalDescriptionColour)) {
            $isJournalDescriptionDark = true;
        }

        $templateMgr->assign([
            'showJournalDescription' => $this->getOption('journalDescription'),
            'journalDescriptionColour' => $journalDescriptionColour,
            'isJournalDescriptionDark' => $isJournalDescriptionDark
        ]);
    }

    /**
     * Add section settings to IssueDAO
     *
     * @param $hookName string
     * @param $args array [
     *
     * @option IssueDAO
     * @option array List of additional fields
     * ]
     */
    public function addToSchema($hookName, $args)
    {
        $schema = $args[0];
        $prop = '{
            "type": "array",
			"multilingual": false,
			"apiSummary": true,
			"validation": [
				"nullable"
			],
			"items": {
				"type": "string"
			}
        }';
        $schema->properties->{'immersionSectionColor'} = json_decode($prop);
    }


    /**
     * Initialize data when form is first loaded
     *
     * @param $hookName string `issueform::initData`
     *
     * @parram $args array [
     *
     * @option IssueForm
     * ]
     */
    public function initDataIssueFormFields($hookName, $args)
    {
        $issueForm = $args[0];
        $issueForm->setData('immersionSectionColor', $issueForm->issue->getData('immersionSectionColor'));
    }

    /**$$
     * Read user input from additional fields in the issue editing form
     *
     * @param $hookName string `issueform::readUserVars`
     * @parram $args array [
     * @option IssueForm
     * @option array User vars
     * ]
     */
    public function readIssueFormFields($hookName, $args)
    {
        $issueForm = & $args[0];
        $request = $this->getRequest();

        $issueForm->setData('immersionSectionColor', $request->getUserVar('immersionSectionColor'));
    }

    /**
     * Save additional fields in the issue editing form
     *
     * @param $hookName string `issueform::execute`
     * @param $args array [
     *
     * @option IssueForm
     * @option Issue
     * @option Request
     * ]
     */
    public function executeIssueFormFields($hookName, $args)
    {
        $issueForm = $args[0];
        $issue = $args[1];

        // The issueform::execute hook fires twice, once at the start of the
        // method when no issue exists. Only update the object during the
        // second request
        if (!$issue) {
            return;
        }

        $issue->setData('immersionSectionColor', $issueForm->getData('immersionSectionColor'));
    }

    /**
     * Add variables to the issue editing form
     *
     * @param $hookName string `issueform::display`; see fetch()
     * @param $args array [
     *
     * @option IssueForm
     * ]
     */
    public function addToIssueForm($hookName, $args)
    {
        $issueForm = $args[0];

        // Display only if available as per IssueForm::fetch()
        if ($issueForm->issue) {
            $request = $this->getRequest();

            $sections = Repo::section()->getByIssueId($issueForm->issue->getId())->all(); /** @var \APP\section\Section[] $sections */

            $templateMgr = TemplateManager::getManager($request);

            $templateMgr->assign('sections', $sections);
        }
    }

    /**
     * Add variables to the issue editing form
     *
     * @param $hookName string
     * @param $args array [
     *
     * @option TemplateManager
     * @option string
     * ]
     */
    public function callbackTemplateIssueForm($hookName, $args)
    {
        $templateMgr = $args[1];
        $output = & $args[2];
        $output .= $templateMgr->fetch($this->getTemplateResource('issueForm.tpl'));
    }
}
