<?php

namespace APP\plugins\themes\eidos;

use APP\core\Application;
use APP\plugins\themes\eidos\classes\HomepageBlocks;
use APP\plugins\themes\eidos\classes\MetadataBlocks;
use APP\plugins\themes\eidos\classes\Options;
use APP\plugins\themes\eidos\classes\ViteLoader;
use APP\template\TemplateManager;
use Illuminate\Support\Collection;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\i18n\LocaleMetadata;
use PKP\plugins\Hook;
use PKP\plugins\interfaces\HasHomepageBlocks;
use PKP\plugins\interfaces\HasMetadataBlocks;
use PKP\plugins\PluginSettingsDAO;
use PKP\plugins\ThemePlugin;
use PKP\view\Block;
use PKP\view\HomepageBlocksRegistry;
use PKP\view\MetadataBlocksRegistry;

class EidosTheme extends ThemePlugin implements HasMetadataBlocks, HasHomepageBlocks
{
    public HomepageBlocks $homepageBlocks;
    public MetadataBlocks $metadataBlocks;
    public Options $optionsHelper;

    public function isActive()
    {
        if (defined('SESSION_DISABLE_INIT')) {
            return true;
        }
        return parent::isActive();
    }

    public function init()
    {
        $this->homepageBlocks = new HomepageBlocks($this);
        $this->metadataBlocks = new MetadataBlocks($this);
        $enabledFonts = $this->getEnabledFonts();
        $this->optionsHelper = new Options($this, $enabledFonts);
        $this->optionsHelper->addOptions();
        $this->addStyle('variables', $this->optionsHelper->getCssVariablesString(), ['inline' => true, 'contexts' => ['frontend', 'htmlGalley']]);
        $this->requiresVueRuntime();
        $this->addViteAssets(['src/main.js']);
        $this->addViteAssets(['src/system.js'], ['contexts' => ['system']]);
        $this->addMenuArea(['primary', 'user', 'homepage', 'policy']);
        Hook::add('TemplateManager::display', [$this, 'addTemplateData']);
    }

    public function getDisplayName()
    {
        return __('plugins.themes.eidos.name');
    }

    public function getDescription()
    {
        return __('plugins.themes.eidos.description');
    }

    /**
     * Add the script, style and other assets compiled by Vite
     *
     * @param array $args Pass arguments to ThemePlugin::addStyle() or TemplateManager::addStylesheet()
     */
    protected function addViteAssets(array $entryPoints, ?array $args = null): void
    {
        $templateMgr = TemplateManager::getManager(
            Application::get()->getRequest()
        );

        $viteLoader = new ViteLoader(
            templateManager: $templateMgr,
            theme: $this,
            manifestPath: dirname(__FILE__) . '/dist/.vite/manifest.json',
            serverPath: join('/', [dirname(__FILE__), '.vite.server.json']),
            buildUrl: join('/', [$this->getPluginUrl(), 'dist/']),
            prefix: $this->getPluginPath(),
            args: $args
        );

        $viteLoader->load($entryPoints);
    }

    /**
     * Get the URL to the theme's root directory
     */
    public function getPluginUrl(): string
    {
        $request = Application::get()->getRequest();
        $baseUrl = rtrim($request->getBaseUrl(), '/');
        $pluginPath = rtrim($this->getPluginPath(), '/');
        return "{$baseUrl}/{$pluginPath}";
    }

    /**
     * Get enabled fonts from the Google Fonts plugin
     *
     * Font options rely upon the Google Fonts plugin, but the
     * theme is initialized before other generic plugins. For this
     * reason, we go directly to the database to get the Google
     * Fonts plugin's settings.
     */
    protected function getEnabledFonts(?int $contextId = null): array
    {
        if (is_null($contextId)) {
            $contextId = Application::get()->getRequest()->getContext()?->getId() ?? Application::SITE_CONTEXT_ID;
        }
        /** @var PluginSettingsDAO $pluginSettingsDao */
        $pluginSettingsDao = DAORegistry::getDAO('PluginSettingsDAO');
        $isEnabled = $pluginSettingsDao->getSetting($contextId, 'googlefontsplugin', 'enabled');
        if (!$isEnabled) {
            return [];
        }
        $enabledFonts = $pluginSettingsDao->getSetting($contextId, 'googlefontsplugin', 'fonts');
        if (is_array($enabledFonts)) {
            return $enabledFonts;
        }
        return [];
    }

    public function registerMetadataBlocks(MetadataBlocksRegistry $blocks): void
    {
        $this->metadataBlocks->register($blocks);
    }

    public function registerHomepageBlocks(HomepageBlocksRegistry $blocks): void
    {
        $this->homepageBlocks->register($blocks);
    }

    /**
     * Pass data to the templates
     *
     * This should be called before rendering has begun.
     */
    public function addTemplateData(string $hookName, array $args): void
    {
        view()->share('getStringSize', $this->getStringSize(...));
        view()->share('contextName', $this->contextName());
        view()->share('eidosUrl', $this->getPluginUrl());
        view()->share('usesCustomFonts', $this->optionsHelper->usesCustomFonts());
        view()->share('getBlocks', $this->getBlocks(...));
        view()->share('locales', $this->getLocales());

        $templateMgr = $args[0];
        $template = $args[1];
        if ($template === 'frontend/pages/article.tpl') {
            $article = $templateMgr->getTemplateVars('article');
            if ($article) {
                view()->share('metricsChartConfig', $this->getUsageStatsChartData($article->getId()));
            }
        }

        if ($template === 'frontend/pages/indexJournal.tpl' || $template === 'frontend/pages/indexSite.tpl') {
            $urlAllContent = Application::get()->getRequest()->getContext()
                ? Application::get()->getRequest()->url(null, 'issue', 'archive')
                : Application::get()->getRequest()->url(null, 'search');
            $latestPublicationsTitle = $this->getLocalizedOption('latestArticlesTitle');
            $latestPublicationsDescription = str_replace('{$url}', $urlAllContent, $this->getLocalizedOption('latestArticlesDescription'));
            $searchBlockDescription = str_replace(
                '{$count}',
                $templateMgr->getTemplateVars('searchBlockCountAllPublications') ?? '',
                $this->getLocalizedOption('searchBlockDescription')
            );
            $templateMgr->assign([
                'latestPublicationsTitle' => $latestPublicationsTitle,
                'latestPublicationsDescription' => $latestPublicationsDescription,
                'latestPublicationsByCategoryTitle' => $latestPublicationsTitle,
                'latestPublicationsByCategoryDescription' => $latestPublicationsDescription,
                'browseByCategoryTitle' => $this->getLocalizedOption('browseByCategoryTitle'),
                'browseByCategoryDescription' => str_replace('{$url}', $urlAllContent, $this->getLocalizedOption('browseByCategoryDescription')),
                'showArticleGalleysInToc' => $this->getOption('issueToc') === 'galleys',
                'showArticleCoversInToc' => $this->getOption('issueToc') === 'covers',
                'searchBlockTitle' => $this->getLocalizedOption('searchBlockTitle'),
                'searchBlockDescription' => $searchBlockDescription,
            ]);
        }
    }

    /**
     * Get a short `size` string indicating the length of a string
     *
     * @return string 'xs' | 'sm' | 'md' | 'lg'
     */
    public function getStringSize(string $str): string
    {
        $length = strlen($str);
        return $length <= 40 ? 'xs' : ($length <= 80 ? 'sm' : ($length <= 100 ? 'md' : 'lg'));
    }

    /**
     * Get the blocks that match a particular theme option
     *
     * @param Collection<Block> $blocks
     *
     * @return Collection<Block> $blocks
     */
    public function getBlocks(Collection $blocks, string $option): Collection
    {
        $blockIds = $this->getOption($option);
        $matchingBlocks = collect([]);
        foreach ($blockIds as $blockId) {
            $block = $blocks->first(fn ($block) => $block->id === $blockId);
            if ($block) {
                $matchingBlocks->push($block);
            }
        }
        return $matchingBlocks;
    }

    /**
     * Get the name of the context or site, depending
     * on what kind of page we're viewing.
     */
    public function contextName(): string
    {
        $context = $this->request->getContext();
        return $context
            ? $context->getLocalizedName()
            : $this->request->getSite()->getLocalizedTitle();
    }

    /**
     * Get an array of all locales supported by the
     * current context or site.
     */
    public function getLocales(): array
    {
        $request = $this->request;
        $context = $request->getContext();

        $locales = Locale::getFormattedDisplayNames(
            isset($context)
                ? $context->getSupportedLocales()
                : $request->getSite()->getSupportedLocales(),
            Locale::getLocales(),
            LocaleMetadata::LANGUAGE_LOCALE_ONLY
        );

        return $locales;
    }
}
