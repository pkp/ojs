<?php

namespace APP\plugins\themes\eidos\classes;

use APP\core\Application;
use APP\plugins\themes\eidos\EidosTheme;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\view\MetadataBlock;
use PKP\view\MetadataBlocksRegistry;

/**
 * Helper class to add metadata blocks
 */
class MetadataBlocks
{
    public function __construct(
        /**
         * Instance of the theme
         */
        protected EidosTheme $theme,
    ) {
        //
    }

    /**
     * Register custom metadata blocks
     */
    public function register(MetadataBlocksRegistry $blocks): void
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());

        $blocks->register(
            new MetadataBlock(
                component: 'metadata-blocks.metrics',
                title: __('plugins.themes.eidos.metrics'),
                loader: function (Publication $publication, Submission $submission) use ($templateMgr) {
                    $metrics = $templateMgr->getTemplateVars('metricsByType');
                    if (!$metrics) {
                        return;
                    }
                    $downloads = 0;
                    foreach ($metrics as $type => $metric) {
                        if ($type === 'abstract') {
                            continue;
                        }
                        $downloads += $metric;
                    }
                    view()->share('metricsViews', $metrics['abstract']);
                    view()->share('metricsDownloads', $downloads);
                }
            )
        );
    }
}
