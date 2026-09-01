<?php

namespace APP\plugins\themes\eidos\classes;

use APP\core\Application;
use APP\plugins\themes\eidos\EidosTheme;
use APP\template\TemplateManager;
use APP\view\HomepageBlocksRegistry;
use PKP\context\Context;
use PKP\view\HomepageBlock;

/**
 * Helper class to add metadata blocks
 */
class HomepageBlocks
{
    public ?Context $context;

    public function __construct(
        /**
         * Instance of the theme
         */
        protected EidosTheme $theme,
    ) {
        $this->context = Application::get()->getRequest()->getContext();
    }

    /**
     * Register custom homepage blocks
     */
    public function register(HomepageBlocksRegistry $blocks): void
    {
        $blocks->register(
            new HomepageBlock(
                component: 'homepage-blocks.how-to-submit',
                title: __('plugins.themes.eidos.option.homepageBlocks.how-to-submit'),
                forSite: false,
            )
        );
        $blocks->register(
            new HomepageBlock(
                component: 'homepage-blocks.about',
                title: $this->context
                    ? __('plugins.themes.eidos.option.homepageBlocks.aboutContext')
                    : __('plugins.themes.eidos.option.homepageBlocks.aboutSite'),
            )
        );
        $blocks->register(
            new HomepageBlock(
                component: 'homepage-blocks.issue-toc',
                title: __('manager.homepageBlocks.issueToc'),
                forSite: false,
                loader: function () {
                    $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
                    $templateMgr->assign([
                        'showArticleGalleysInToc' => false,
                        'showArticleCoversInToc' => false,
                    ]);
                },
            )
        );
        $blocks->register(
            new HomepageBlock(
                component: 'homepage-blocks.contexts',
                title: __('plugins.themes.eidos.option.homepageBlocks.contexts'),
                forContext: false,
            )
        );
    }
}
