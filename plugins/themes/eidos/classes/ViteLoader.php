<?php

namespace APP\plugins\themes\eidos\classes;

use APP\template\TemplateManager;
use PKP\plugins\ThemePlugin;
use RuntimeException;

/**
 * Initialize Vite integration
 *
 * If Vite is in dev mode, it registers assets pointing
 * to Vite's server. Otherwise, it llads the assets from
 * the manifest.json file.
 *
 * All assets are registered through PKP's TemplateManager
 * class or a ThemePlugin class if passed in the constructor.
 */
class ViteLoader
{
    public const DEFAULT_VITE_SERVER_URL = 'http://localhost:5173/';

    public bool $devMode;
    protected string $baseUrl;

    public function __construct(
        /**
         * TemplateManager from the PKP application
         * (OJS, OMP, or OJS)
         */
        protected TemplateManager $templateManager,

        /**
         * Absolute path to vite's manifest.json file
         */
        protected string $manifestPath,

        /**
         * Base URL to vite build directory
         */
        protected string $buildUrl,

        /**
         * Absolute path to vite server configuration
         *
         * Usually a .vite.server.json file in the root directory
         * of the plugin.
         */
        public string $serverPath,

        /**
         * Unique prefix to use with script and style assets
         *
         * Typically the plugin name.
         */
        protected string $prefix,

        /**
         * Register assets as part of a theme
         *
         * By default, assets are registered using the PKPTemplateManager
         * methods. If a ThemePlugin is provided, the assets will be
         * registered using the ThemePlugin methods.
         *
         * This makes it easier to work with child themes.
         */
        public ?ThemePlugin $theme = null,

        /**
         * Arguments to pass when registering scripts or styles
         *
         * These are arguments supported by ThemePlugin::addStyle(),
         * ThemePlugin::addScript(), TemplateManager::addStyleSheet(),
         * or TemplateManager::addJavaScript()
         */
        public ?array $args = null,
    ) {
        $this->buildUrl = rtrim($buildUrl, '/') . '/';
        $this->setMode();
    }

    /**
     * Sets devMode and baseUrl
     *
     * In dev mode, this returns the local or network
     * address to the vite server.
     *
     * In production, this returns the relative path to the
     * vite's build directory.
     *
     * Typically, this is `/plugins/themes/<plugin>/dist`.
     */
    protected function setMode(): void
    {
        if (!file_exists($this->serverPath)) {
            $this->devMode = false;
            $this->baseUrl = $this->buildUrl;
            return;
        }

        $config = json_decode(file_get_contents($this->serverPath), true);

        if (!$config) {
            $this->devMode = false;
            $this->baseUrl = $this->buildUrl;
            return;
        }

        $this->devMode = true;

        if (empty($config['network'])) {
            $this->baseUrl = isset($config['local']) ? $config['local'][0] : self::DEFAULT_VITE_SERVER_URL;
            return;
        }

        $this->baseUrl = $config['network'][0];
    }

    /**
     * Load vite assets for one or more entry points
     *
     * Adds the scripts, styles, and other assets to the template
     * using the TemplateManager class from OJS, OMP or OPS.
     */
    public function load(array $entryPoints): void
    {
        if ($this->devMode) {
            $this->loadDev($entryPoints);
        } else {
            $this->loadProd($entryPoints);
        }
    }

    /**
     * Load assets for vite dev server
     */
    protected function loadDev(array $entryPoints): void
    {
        $this->loadScript($this->prefix, "{$this->baseUrl}@vite/client", ['type' => 'module']);
        foreach ($entryPoints as $entryPoint) {
            $this->loadScript("{$this->prefix}-" . $entryPoint, "{$this->baseUrl}{$entryPoint}", ['type' => 'module']);
        }
    }

    /**
     * Load built assets from vite manifest
     */
    protected function loadProd(array $entryPoints): void
    {
        $files = $this->getFiles($entryPoints);
        foreach ($files as $file) {
            if (str_ends_with($file->file, '.js')) {
                $this->templateManager->addHeader("{$this->prefix}-{$file->file}-preload", $this->getPreload($file->file, true), $this->getArgs([]));
            }
            if ($file->isEntry) {
                $this->loadScript("{$this->prefix}-{$file->file}", "{$this->baseUrl}{$file->file}", ['type' => 'module']);
            }
            foreach ($file->css as $css) {
                $this->loadStyle("{$this->prefix}-{$file->file}-{$css}", "{$this->baseUrl}{$css}");
            }
        }
    }

    /**
     * @return ViteManifestFile[]
     */
    protected function getFiles(array $entryPoints): array
    {
        if (!is_readable($this->manifestPath)) {
            throw new RuntimeException(
                file_exists($this->manifestPath)
                    ? "Manifest file is not readable: {$this->manifestPath}"
                    : "Manifest file not found: {$this->manifestPath}"
            );
        }

        return array_filter(
            array_map(
                fn (array $chunk) => ViteManifestFile::create($chunk),
                json_decode(file_get_contents($this->manifestPath), true)
            ),
            fn (ViteManifestFile $file) => in_array($file->src, $entryPoints)
        );
    }

    /**
     * Get preload tag
     */
    protected function getPreload(string $url, bool $module = false): string
    {
        $rel = $module ? 'modulepreload' : 'preload';
        return "<link rel=\"{$rel}\" href=\"{$this->baseUrl}{$url}\" />";
    }

    /**
     * Load script asset
     */
    protected function loadScript(string $name, string $path, array $args): void
    {
        if ($this->theme) {
            $args['baseUrl'] = '';
            $this->theme->addScript($name, $path, $this->getArgs($args));
        } else {
            $this->templateManager->addJavaScript($name, $path, $this->getArgs($args));
        }
    }

    /**
     * Load style asset
     */
    protected function loadStyle(string $name, string $path, array $args = []): void
    {
        if ($this->theme) {
            $args['baseUrl'] = '';
            $this->theme->addStyle($name, $path, $this->getArgs($args));
        } else {
            $this->templateManager->addStyleSheet($name, $path, $this->getArgs($args));
        }
    }

    /**
     * Compile final args array
     */
    protected function getArgs(array $args): array
    {
        if ($this->args) {
            return array_merge($this->args, $args);
        }
        return $args;
    }
}
