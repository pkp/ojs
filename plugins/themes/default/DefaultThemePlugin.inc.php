<?php

/**
 * @file plugins/themes/default/DefaultThemePlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DefaultThemePlugin
 * @ingroup plugins_themes_default
 *
 * @brief Default theme
 */

use APP\core\Application;
use APP\core\Services;
use APP\facades\Repo;
use APP\statistics\StatisticsHelper;
use APP\template\TemplateManager;
use PKP\cache\CacheManager;
use PKP\cache\FileCache;
use PKP\config\Config;
use PKP\plugins\HookRegistry;
use PKP\session\SessionManager;

class DefaultThemePlugin extends \PKP\plugins\ThemePlugin
{
    /**
     * @copydoc ThemePlugin::register
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->isActive() && ($this->getOption('usageStatsDisplay') !== 'none')) {
            HookRegistry::register('Templates::Article::Main', [$this, 'displayUsageStatsGraph']);
        }
        return $success;
    }

    /**
     * @copydoc ThemePlugin::isActive()
     */
    public function isActive()
    {
        if (SessionManager::isDisabled()) {
            return true;
        }
        return parent::isActive();
    }

    /**
     * Initialize the theme's styles, scripts and hooks. This is run on the
     * currently active theme and it's parent themes.
     *
     */
    public function init()
    {
        // Register theme options
        $this->addOption('typography', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.default.option.typography.label'),
            'description' => __('plugins.themes.default.option.typography.description'),
            'options' => [
                [
                    'value' => 'notoSans',
                    'label' => __('plugins.themes.default.option.typography.notoSans'),
                ],
                [
                    'value' => 'notoSerif',
                    'label' => __('plugins.themes.default.option.typography.notoSerif'),
                ],
                [
                    'value' => 'notoSerif_notoSans',
                    'label' => __('plugins.themes.default.option.typography.notoSerif_notoSans'),
                ],
                [
                    'value' => 'notoSans_notoSerif',
                    'label' => __('plugins.themes.default.option.typography.notoSans_notoSerif'),
                ],
                [
                    'value' => 'lato',
                    'label' => __('plugins.themes.default.option.typography.lato'),
                ],
                [
                    'value' => 'lora',
                    'label' => __('plugins.themes.default.option.typography.lora'),
                ],
                [
                    'value' => 'lora_openSans',
                    'label' => __('plugins.themes.default.option.typography.lora_openSans'),
                ],
            ],
            'default' => 'notoSans',
        ]);

        $this->addOption('baseColour', 'FieldColor', [
            'label' => __('plugins.themes.default.option.colour.label'),
            'description' => __('plugins.themes.default.option.colour.description'),
            'default' => '#1E6292',
        ]);

        $this->addOption('showDescriptionInJournalIndex', 'FieldOptions', [
            'label' => __('manager.setup.contextSummary'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.default.option.showDescriptionInJournalIndex.option'),
                ],
            ],
            'default' => false,
        ]);
        $this->addOption('useHomepageImageAsHeader', 'FieldOptions', [
            'label' => __('plugins.themes.default.option.useHomepageImageAsHeader.label'),
            'description' => __('plugins.themes.default.option.useHomepageImageAsHeader.description'),
            'options' => [
                [
                    'value' => true,
                    'label' => __('plugins.themes.default.option.useHomepageImageAsHeader.option')
                ],
            ],
            'default' => false,
        ]);
        $this->addOption('usageStatsDisplay', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.default.option.usageStatsDisplay.label'),
            'options' => [
                [
                    'value' => 'none',
                    'label' => __('plugins.themes.default.option.usageStatsDisplay.none'),
                ],
                [
                    'value' => 'bar',
                    'label' => __('plugins.themes.default.option.usageStatsDisplay.bar'),
                ],
                [
                    'value' => 'line',
                    'label' => __('plugins.themes.default.option.usageStatsDisplay.line'),
                ],
            ],
            'default' => 'none',
        ]);


        // Load primary stylesheet
        $this->addStyle('stylesheet', 'styles/index.less');

        // Store additional LESS variables to process based on options
        $additionalLessVariables = [];

        if ($this->getOption('typography') === 'notoSerif') {
            $this->addStyle('font', 'styles/fonts/notoSerif.less');
            $additionalLessVariables[] = '@font: "Noto Serif", -apple-system, BlinkMacSystemFont, "Segoe UI", "Roboto", "Oxygen-Sans", "Ubuntu", "Cantarell", "Helvetica Neue", sans-serif;';
        } elseif (strpos($this->getOption('typography'), 'notoSerif') !== false) {
            $this->addStyle('font', 'styles/fonts/notoSans_notoSerif.less');
            if ($this->getOption('typography') == 'notoSerif_notoSans') {
                $additionalLessVariables[] = '@font-heading: "Noto Serif", serif;';
            } elseif ($this->getOption('typography') == 'notoSans_notoSerif') {
                $additionalLessVariables[] = '@font: "Noto Serif", serif;@font-heading: "Noto Sans", serif;';
            }
        } elseif ($this->getOption('typography') == 'lato') {
            $this->addStyle('font', 'styles/fonts/lato.less');
            $additionalLessVariables[] = '@font: Lato, sans-serif;';
        } elseif ($this->getOption('typography') == 'lora') {
            $this->addStyle('font', 'styles/fonts/lora.less');
            $additionalLessVariables[] = '@font: Lora, serif;';
        } elseif ($this->getOption('typography') == 'lora_openSans') {
            $this->addStyle('font', 'styles/fonts/lora_openSans.less');
            $additionalLessVariables[] = '@font: "Open Sans", sans-serif;@font-heading: Lora, serif;';
        } else {
            $this->addStyle('font', 'styles/fonts/notoSans.less');
        }

        // Update colour based on theme option
        if ($this->getOption('baseColour') !== '#1E6292') {
            $additionalLessVariables[] = '@bg-base:' . $this->getOption('baseColour') . ';';
            if (!$this->isColourDark($this->getOption('baseColour'))) {
                $additionalLessVariables[] = '@text-bg-base:rgba(0,0,0,0.84);';
                $additionalLessVariables[] = '@bg-base-border-color:rgba(0,0,0,0.2);';
            }
        }

        // Pass additional LESS variables based on options
        if (!empty($additionalLessVariables)) {
            $this->modifyStyle('stylesheet', ['addLessVariables' => join("\n", $additionalLessVariables)]);
        }

        $request = Application::get()->getRequest();

        // Load icon font FontAwesome - http://fontawesome.io/
        $this->addStyle(
            'fontAwesome',
            $request->getBaseUrl() . '/lib/pkp/styles/fontawesome/fontawesome.css',
            ['baseUrl' => '']
        );

        // Get homepage image and use as header background if useAsHeader is true
        $context = Application::get()->getRequest()->getContext();
        if ($context && $this->getOption('useHomepageImageAsHeader')) {
            $publicFileManager = new PublicFileManager();
            $publicFilesDir = $request->getBaseUrl() . '/' . $publicFileManager->getContextFilesPath($context->getId());

            $homepageImage = $context->getLocalizedData('homepageImage');

            $homepageImageUrl = $publicFilesDir . '/' . $homepageImage['uploadName'];

            $this->addStyle(
                'homepageImage',
                '.pkp_structure_head { background: center / cover no-repeat url("' . $homepageImageUrl . '");}',
                ['inline' => true]
            );
        }

        // Load jQuery from a CDN or, if CDNs are disabled, from a local copy.
        $min = Config::getVar('general', 'enable_minified') ? '.min' : '';
        $jquery = $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jquery/jquery' . $min . '.js';
        $jqueryUI = $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jqueryui/jquery-ui' . $min . '.js';
        // Use an empty `baseUrl` argument to prevent the theme from looking for
        // the files within the theme directory
        $this->addScript('jQuery', $jquery, ['baseUrl' => '']);
        $this->addScript('jQueryUI', $jqueryUI, ['baseUrl' => '']);

        // Load Bootsrap's dropdown
        $this->addScript('popper', 'js/lib/popper/popper.js');
        $this->addScript('bsUtil', 'js/lib/bootstrap/util.js');
        $this->addScript('bsDropdown', 'js/lib/bootstrap/dropdown.js');

        // Load custom JavaScript for this theme
        $this->addScript('default', 'js/main.js');

        // Add navigation menu areas for this theme
        $this->addMenuArea(['primary', 'user']);
    }

    /**
     * Get the name of the settings file to be installed on new journal
     * creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the name of the settings file to be installed site-wide when
     * OJS is installed.
     *
     * @return string
     */
    public function getInstallSitePluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the display name of this plugin
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.themes.default.name');
    }

    /**
     * Get the description of this plugin
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.themes.default.description');
    }

    /**
     * Add usage statistics graph to article view page
     *
     * Hooked to `Templates::Article::Main`
     *
     * @param $hookName string
     * @param $params array [
     *  @option Smarty
     *  @option string HTML output to return
     * ]
     */
    public function displayUsageStatsGraph(string $hookName, array $params): bool
    {
        $smarty = & $params[1];

        $submission = $smarty->getTemplateVars('article');
        assert(is_a($submission, 'Submission'));
        $submissionId = $submission->getId();

        $this->addJavascriptData($this->getAllDownloadsStats($submissionId), $submissionId);
        $this->loadJavascript();
        return false;
    }

    /**
     * Add submission's monthly statistics data to the script data output for graph display
     */
    private function addJavascriptData(array $statsByMonth, int $submissionId): void
    {
        // Initialize the name space
        $script_data = 'var pkpUsageStats = pkpUsageStats || {};';
        $script_data .= 'pkpUsageStats.data = pkpUsageStats.data || {};';
        $script_data .= 'pkpUsageStats.data.Submission = pkpUsageStats.data.Submission || {};';
        $namespace = 'Submission[' . $submissionId . ']';
        $script_data .= 'pkpUsageStats.data.' . $namespace . ' = ' . json_encode($statsByMonth) . ';';

        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->addJavaScript(
            'pkpUsageStatsData',
            $script_data,
            [
                'inline' => true,
                'contexts' => 'frontend-article-view',
            ]
        );
    }

    /**
     * Load JavaScript assets for usage statistics display and pass data to the scripts
     */
    private function loadJavascript(): void
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);

        // Register Chart.js on the frontend article view
        $templateMgr->addJavaScript(
            'chartJS',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.0.1/Chart.js',
            [
                'contexts' => 'frontend-article-view',
            ]
        );

        // Add locale and configuration data
        $chartType = $this->getOption('usageStatsDisplay');
        $script_data = 'var pkpUsageStats = pkpUsageStats || {};';
        $script_data .= 'pkpUsageStats.locale = pkpUsageStats.locale || {};';
        $script_data .= 'pkpUsageStats.locale.months = ' . json_encode(explode(' ', __('plugins.themes.default.usageStatsDisplay.monthInitials'))) . ';';
        $script_data .= 'pkpUsageStats.config = pkpUsageStats.config || {};';
        $script_data .= 'pkpUsageStats.config.chartType = ' . json_encode($chartType) . ';';

        $templateMgr->addJavaScript(
            'pkpUsageStatsConfig',
            $script_data,
            [
                'inline' => true,
                'contexts' => 'frontend-article-view',
            ]
        );

        // Register the JS which initializes the chart
        $baseImportPath = $request->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR;
        $templateMgr->addJavaScript(
            'usageStatsFrontend',
            $baseImportPath . 'js/UsageStatsFrontendHandler.js',
            [
                'contexts' => 'frontend-article-view',
            ]
        );
    }

    /**
     * Retrieve download metrics for the given submission
     */
    private function getAllDownloadsStats(int $submissionId): array
    {
        $cache = CacheManager::getManager()->getCache('downloadStats', $submissionId, [$this, 'downloadStatsCacheMiss']);
        if (time() - $cache->getCacheTime() > 60 * 60 * 24) {
            // Cache is older than one day, erase it.
            $cache->flush();
        }
        $statsByMonth = [];
        $totalDownloads = 0;
        $data = $cache->get($submissionId);
        foreach ($data as $monthlyDownloadStats) {
            [$year, $month] = explode('-', $monthlyDownloadStats['date']);
            $month = ltrim($month, '0');
            $statsByMonth[$year][$month] = $monthlyDownloadStats['value'];
            $totalDownloads += $monthlyDownloadStats['value'];
        }
        return [
            'data' => $statsByMonth,
            'label' => __('common.allDownloads'),
            'color' => $this->getColor(REALLY_BIG_NUMBER),
            'total' => $totalDownloads
        ];
    }

    /**
     * Callback to fill cache with submission's download usage statistics data, if empty.
     */
    public function downloadStatsCacheMiss(FileCache $cache, int $submissionId): array
    {
        $request = Application::get()->getRequest();
        $submission = Repo::submission()->get($submissionId);
        $firstPublication = $submission->getCurrentPublication();
        $earliestDatePublished = $firstPublication->getData('datePublished');
        $allowedParams = [
            'contextIds' => $request->getContext()->getId(),
            'submissionIds' => $submissionId,
            'assocTypes' => Application::ASSOC_TYPE_SUBMISSION_FILE,
            'timelineInterval' => StatisticsHelper::STATISTICS_DIMENSION_MONTH,
            'dateStart' => $earliestDatePublished
        ];
        $statsService = Services::get('publicationStats');
        $data = $statsService->getTimeline($allowedParams['timelineInterval'], $allowedParams);
        $cache->setEntireCache([$submissionId => $data]);
        return $data;
    }

    /**
     * Return a color RGB code to be used in the usage statistics diplay graph.
     */
    private function getColor(int $num): string
    {
        $hash = md5('color' . $num * 2);
        return hexdec(substr($hash, 0, 2)) . ',' . hexdec(substr($hash, 2, 2)) . ',' . hexdec(substr($hash, 4, 2));
    }
}
