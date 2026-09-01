<?php

namespace APP\plugins\themes\eidos\classes;

use APP\core\Application;
use APP\journal\Journal;
use APP\plugins\themes\eidos\EidosTheme;
use APP\template\TemplateManager;
use Illuminate\Support\Collection;
use PKP\view\HomepageBlock;
use PKP\view\MetadataBlock;

/**
 * Helper class to add theme options
 */
class Options
{
    public const HEADER_DEFAULT = 'default';
    public const HEADER_BOXED = 'boxed';
    public const HEADER_LINE = 'line';

    public const HOMEPAGE_IMAGE_POSITION_ABOVE = 'above';
    public const HOMEPAGE_IMAGE_POSITION_BEHIND = 'behind';
    public const HOMEPAGE_IMAGE_POSITION_BELOW = 'below';
    public const HOMEPAGE_IMAGE_POSITION_NONE = 'none';

    public const FONT_DEFAULT = 'noto-sans';

    public const SITE_WIDTH_FULL = 'full';
    public const SITE_WIDTH_FIXED = 'fixed';

    public const ISSUE_ARCHIVE_DEFAULT = 'default';
    public const ISSUE_ARCHIVE_COVERS = 'covers';
    public const ISSUE_ARCHIVE_LIST = 'list';

    public const ISSUE_TOC_DEFAULT = 'none';
    public const ISSUE_TOC_COVERS = 'covers';
    public const ISSUE_TOC_GALLEYS = 'galleys';

    public const HOMEPAGE_BLOCKS_DEFAULT = [
        'homepage-blocks.announcement',
        'homepage-blocks.about',
        'homepage-blocks.how-to-submit',
        'homepage-blocks.issue-toc',
    ];

    public const ARTICLE_HIGHLIGHT_METADATA_DEFAULT = [
        'metadata-blocks.doi',
    ];

    public const ARTICLE_SIDEBAR_METADATA_DEFAULT = [
        'metadata-blocks.version',
        'metadata-blocks.date-published',
        'metadata-blocks.date-submitted',
        'metadata-blocks.peer-review',
    ];

    public const SHARE_OPTIONS_FACEBOOK = 'facebook';
    public const SHARE_OPTIONS_X = 'x';
    public const SHARE_OPTIONS_WHATSAPP = 'whatsapp';
    public const SHARE_OPTIONS_EMAIL = 'email';
    public const SHARE_OPTIONS_REDDIT = 'reddit';
    public const SHARE_OPTIONS_TELEGRAM = 'telegram';
    public const SHARE_OPTIONS_LINKEDIN = 'linkedin';
    public const SHARE_OPTIONS_COPY = 'copy';
    public const SHARE_OPTIONS_DEFAULT = [
        'email',
        'linkedin',
        'whatsapp',
        'facebook',
        'reddit',
        'x',
        'telegram',
        'copy'
    ];

    public const COLOR_MODE_DEFAULT = 'default';
    public const COLOR_MODE_ADVANCED = 'advanced';

    public const COLOR_PRIMARY = '#22252A';
    public const COLOR_ACCENT = '#22252A';
    public const COLOR_PAGE_BACKGROUND = '#FDFBF7';
    public const COLOR_PAGE_TEXT = '#22252A';
    public const COLOR_PRIMARY_TEXT = '#FFFFFF';

    /**
     * Primary locale of current context
     */
    protected string $primaryLocale;

    /**
     * Current context
     */
    protected ?Journal $context;

    public function __construct(
        /**
         * Instance of the theme
         */
        protected EidosTheme $theme,

        /**
         * List of enabled fonts
         *
         * From the Google Fonts plugin. Empty array
         * if the plugin is disabled or no fonts have
         * been added through the plugin.
         */
        protected array $enabledFonts
    ) {
        $request = Application::get()->getRequest();
        $this->context = $request->getContext();
        $this->primaryLocale = $this->context
            ? $this->context->getPrimaryLocale()
            : $request->getSite()->getPrimaryLocale();
    }

    /**
     * Add all theme options
     */
    public function addOptions(): void
    {
        $this->addHeaderOption();
        $this->addHomepageImageOption();
        $this->addTaglineOption();
        $this->addSiteWidthOption();
        $this->addIssueTocOption();
        $this->addIssueArchivesOption();
        $this->addHomepageBlockOption();
        $this->addHowToSubmitBlock();
        $this->addLatestArticlesBlock();
        $this->addSearchBlock();
        $this->addBrowseByCategoryBlock();
        $this->addArticleHighlightMetadataOption();
        $this->addArticleSidebarMetadataOption();
        $this->addShareOption();
        $this->addFontOptions();
        $this->addColorOptions();
    }

    /**
     * Add option for the header layout
     */
    protected function addHeaderOption(): void
    {
        $this->theme->addOption('header', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.header.label'),
            'description' => __('plugins.themes.eidos.option.header.description'),
            'options' => [
                [
                    'value' => self::HEADER_DEFAULT,
                    'label' => __('plugins.themes.eidos.option.header.default'),
                ],
                [
                    'value' => self::HEADER_BOXED,
                    'label' => __('plugins.themes.eidos.option.header.default-boxed'),
                ],
                [
                    'value' => self::HEADER_LINE,
                    'label' => __('plugins.themes.eidos.option.header.line'),
                ],
            ],
            'default' => self::HEADER_DEFAULT,
        ]);
    }

    /**
     * Get CSS variables based on the theme options
     */
    public function getCssVariables(): Collection
    {
        $variables = new Collection([]);

        if ($this->theme->getOption('colorMode') === self::COLOR_MODE_DEFAULT) {
            $variables['--color-primary'] = $this->theme->getOption('primaryColor');
            $variables['--color-secondary'] = $this->theme->getOption('accentColor');
            if ($this->theme->isColourDark($this->theme->getOption('primaryColor'))) {
                $variables['--color-text-on-primary'] = 'white';
                $variables['--color-button-text'] = 'var(--color-primary)';
            } else {
                $variables['--color-text-on-primary'] = 'rgba(0, 0, 0, 0.85)';
            }
            if ($this->theme->isColourDark($this->theme->getOption('accentColor'))) {
                $variables['--color-page-links'] = 'var(--color-secondary)';
                $variables['--color-button-text'] = 'var(--color-secondary)';
            } else {
                $variables['--color-page-links'] = 'var(--color-text)';
                $variables['--color-button-text'] = 'var(--color-text)';
            }
            $variables['--color-header-background'] = 'var(--color-primary)';
            $variables['--color-header-text'] = 'var(--color-text-on-primary)';
            $variables['--color-button-background'] = 'var(--color-background)';
            $variables['--color-button-text'] = 'var(--color-secondary)';
            $variables['--color-block-background'] = 'var(--color-primary)';
            $variables['--color-block-text'] = 'var(--color-text-on-primary)';
            $variables['--color-overlay-background'] = 'var(--color-background)';
            $variables['--color-overlay-text'] = 'var(--color-text)';
            $variables['--color-footer-background'] = 'var(--color-primary)';
            $variables['--color-footer-text'] = 'var(--color-text-on-primary)';
        } else {
            $variables['--color-header-background'] = $this->theme->getOption('headerBackgroundColor');
            $variables['--color-header-text'] = $this->theme->getOption('headerTextColor');
            $variables['--color-page-background'] = $this->theme->getOption('pageBackgroundColor');
            $variables['--color-page-text'] = $this->theme->getOption('pageTextColor');
            $variables['--color-page-links'] = $this->theme->getOption('pageLinkColor');
            $variables['--color-button-background'] = $this->theme->getOption('buttonBackgroundColor');
            $variables['--color-button-text'] = $this->theme->getOption('buttonTextColor');
            $variables['--color-block-background'] = $this->theme->getOption('blockBackgroundColor');
            $variables['--color-block-text'] = $this->theme->getOption('blockTextColor');
            $variables['--color-overlay-background'] = $this->theme->getOption('blockBackgroundColor');
            $variables['--color-overlay-text'] = $this->theme->getOption('blockTextColor');
            $variables['--color-footer-background'] = $this->theme->getOption('footerBackgroundColor');
            $variables['--color-footer-text'] = $this->theme->getOption('footerTextColor');
        }

        if ($this->usesCustomFonts()) {
            foreach ($this->enabledFonts as $font) {
                if ($font['id'] === $this->theme->getOption('font')) {
                    $variables['--font-base'] = "'{$font['family']}', {$this->getFontFallback($font['category'])}";
                }
                if ($font['id'] === $this->theme->getOption('titlesFont')) {
                    $variables['--font-titles'] = "'{$font['family']}', {$this->getFontFallback($font['category'])}";
                }
                if ($font['id'] === $this->theme->getOption('actionsFont')) {
                    $variables['--font-actions'] = "'{$font['family']}', {$this->getFontFallback($font['category'])}";
                }
            }
        }

        return $variables;
    }

    /**
     * Get a CSS string that assigns all variables to
     * the passed CSS selector
     *
     * For example, if $selector='body' it will return:
     *
     * body {
     *    // variables
     * }
     */
    public function getCssVariablesString(string $selector = 'body'): string
    {
        $string = $this->getCssVariables()
            ->map(fn ($val, $var) => "{$var}: {$val};")
            ->join('');

        return "{$selector} {{$string}}";
    }

    /**
     * Add option for where to display the homepage image
     */
    protected function addHomepageImageOption(): void
    {
        if (!$this->context) {
            return;
        }

        $this->theme->addOption('homepageImagePosition', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.homepageImagePosition.label'),
            'description' => __('plugins.themes.eidos.option.homepageImagePosition.description'),
            'options' => [
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_ABOVE,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.above'),
                ],
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_BEHIND,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.behind'),
                ],
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_BELOW,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.below'),
                ],
                [
                    'value' => self::HOMEPAGE_IMAGE_POSITION_NONE,
                    'label' => __('plugins.themes.eidos.option.homepageImagePosition.none'),
                ],
            ],
            'default' => self::HOMEPAGE_IMAGE_POSITION_ABOVE,
        ]);
    }

    /**
     * Add option for the tagline to display beside the logo
     */
    protected function addTaglineOption(): void
    {
        $this->theme->addOption('tagline', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.tagline.label'),
            'description' => __('plugins.themes.eidos.option.tagline.description'),
            'isMultilingual' => true,
        ]);
    }

    /**
     * Add option to set the site width
     */
    protected function addSiteWidthOption(): void
    {
        $this->theme->addOption('siteWidth', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.siteWidth.label'),
            'description' => __('plugins.themes.eidos.option.siteWidth.description'),
            'options' => [
                [
                    'value' => self::SITE_WIDTH_FULL,
                    'label' => __('plugins.themes.eidos.option.siteWidth.full'),
                ],
                [
                    'value' => self::SITE_WIDTH_FIXED,
                    'label' => __('plugins.themes.eidos.option.siteWidth.fixed'),
                ],
            ],
            'default' => self::SITE_WIDTH_FULL,
        ]);
    }

    /**
     * Add options to set typography
     */
    protected function addFontOptions(): void
    {
        if (!count($this->enabledFonts)) {
            return;
        }

        $options = [];
        foreach ($this->enabledFonts as $font) {
            $options[] = [
                'value' => $font['id'],
                'label' => $font['family'],
            ];
        }

        $this->theme->addOption('font', 'FieldSelect', [
            'label' => __('plugins.themes.eidos.option.font.label'),
            'description' => __('plugins.themes.eidos.option.font.description'),
            'options' => $options,
            'default' => self::FONT_DEFAULT,
        ]);

        $this->theme->addOption('titlesFont', 'FieldSelect', [
            'label' => __('plugins.themes.eidos.option.titlesFont.label'),
            'description' => __('plugins.themes.eidos.option.titlesFont.description'),
            'options' => $options,
            'default' => self::FONT_DEFAULT,
        ]);

        $this->theme->addOption('actionsFont', 'FieldSelect', [
            'label' => __('plugins.themes.eidos.option.actionsFont.label'),
            'description' => __('plugins.themes.eidos.option.actionsFont.description'),
            'options' => $options,
            'default' => self::FONT_DEFAULT,
        ]);
    }

    /**
     * Get the fallback font statement based on a font category
     *
     * The category is usually serif or sans-serif, but may be
     * other categories from Google Fonts, such as display and
     * handwriting.
     */
    protected function getFontFallback(string $category): string
    {
        switch ($category) {
            case 'serif':
                return 'serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';
            case 'sans-serif':
            default:
                return 'system-ui, -apple-system, BlinkMacSystemFont, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"';
        }
    }

    /**
     * Whether or not this theme uses custom fonts
     *
     * Checks if Google Fonts have been enabled and the theme
     * option has been set.
     */
    public function usesCustomFonts(): bool
    {
        return count($this->enabledFonts)
            && (
                $this->theme->getOption('font')
                || $this->theme->getOption('titlesFont')
                || $this->theme->getOption('actionsFont')
            );
    }

    /**
     * Add option to change how articles are displayed in
     * the issue table of contents
     */
    protected function addIssueTocOption(): void
    {
        if (!$this->context) {
            return;
        }

        $this->theme->addOption('issueToc', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.issueToc.label'),
            'description' => __('plugins.themes.eidos.option.issueToc.description'),
            'options' => [
                [
                    'value' => self::ISSUE_TOC_GALLEYS,
                    'label' => __('plugins.themes.eidos.option.issueToc.galleys'),
                ],
                [
                    'value' => self::ISSUE_TOC_COVERS,
                    'label' => __('plugins.themes.eidos.option.issueToc.covers'),
                ],
                [
                    'value' => self::ISSUE_TOC_DEFAULT,
                    'label' => __('plugins.themes.eidos.option.issueToc.default'),
                ],
            ],
            'default' => self::ISSUE_TOC_DEFAULT,
        ]);
    }

    /**
     * Add option to change how issues are displayed in
     * the issue archive
     */
    protected function addIssueArchivesOption(): void
    {
        if (!$this->context) {
            return;
        }

        $this->theme->addOption('issueArchives', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.issueArchives.label'),
            'description' => __('plugins.themes.eidos.option.issueArchives.description'),
            'options' => [
                [
                    'value' => self::ISSUE_ARCHIVE_DEFAULT,
                    'label' => __('plugins.themes.eidos.option.issueArchives.default'),
                ],
                [
                    'value' => self::ISSUE_ARCHIVE_COVERS,
                    'label' => __('plugins.themes.eidos.option.issueArchives.covers'),
                ],
                [
                    'value' => self::ISSUE_ARCHIVE_LIST,
                    'label' => __('plugins.themes.eidos.option.issueArchives.list'),
                ],
            ],
            'default' => self::ISSUE_ARCHIVE_DEFAULT,
        ]);
    }

    /**
     * Add option to show blocks on the homepage
     */
    protected function addHomepageBlockOption(): void
    {
        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $blocks = $templateMgr->homepageBlocks->get();

        $this->theme->addOption('homepageBlocks', 'FieldOptions', [
            'type' => 'checkbox',
            'isOrderable' => true,
            'label' => __('plugins.themes.eidos.option.homepageBlocks.label'),
            'description' => __('plugins.themes.eidos.option.homepageBlocks.description'),
            'options' => $blocks->map(
                fn (HomepageBlock $block) => [
                    'value' => $block->id,
                    'label' => $block->title,
                ]
            )
                ->values(),
            'default' => self::HOMEPAGE_BLOCKS_DEFAULT,
        ]);
    }

    /**
     * Add text fields for the how to submit homepage block
     */
    protected function addHowToSubmitBlock(): void
    {
        if (!$this->context) {
            return;
        }

        $this->theme->addOption('howToSubmitTitle', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.howToSubmitTitle.label'),
            'description' => __('plugins.themes.eidos.option.howToSubmitTitle.description'),
            'isMultilingual' => true,
            'default' => [
                $this->primaryLocale => __('navigation.submissions'),
            ],
        ]);
        $this->theme->addOption('howToSubmitText', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.howToSubmitText.label'),
            'description' => __('plugins.themes.eidos.option.howToSubmitText.description'),
            'isMultilingual' => true,
            'size' => 'large',
            'default' => [
                $this->primaryLocale => __('plugins.themes.eidos.option.howToSubmitText.default'),
            ],
        ]);
        $this->theme->addOption('howToSubmitAction', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.howToSubmitAction.label'),
            'description' => __('plugins.themes.eidos.option.howToSubmitAction.description'),
            'isMultilingual' => true,
            'size' => 'small',
            'default' => [
                $this->primaryLocale => __('plugins.themes.eidos.option.howToSubmitAction.default'),
            ],
        ]);
    }

    /**
     * Add text fields for the latest articles homepage block
     */
    protected function addLatestArticlesBlock(): void
    {
        $this->theme->addOption('latestArticlesTitle', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.homepageBlocks.latestArticlesTitle.label'),
            'description' => __('plugins.themes.eidos.option.homepageBlocks.latestArticlesTitle.desc'),
            'isMultilingual' => true,
            'default' => [
                $this->primaryLocale => __('submissions.published.latest'),
            ],
        ]);
        $this->theme->addOption('latestArticlesDescription', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.homepageBlocks.latestArticlesDescription.label'),
            'description' => __('plugins.themes.eidos.option.homepageBlocks.latestArticlesDescription.desc'),
            'isMultilingual' => true,
            'size' => 'large',
            'default' => [
                $this->primaryLocale => $this->context
                    ? __('submissions.published.latest.description')
                    : __('submissions.published.latest.description.site'),
            ],
        ]);
    }

    /**
     * Add text fields for the search homepage block
     */
    protected function addSearchBlock(): void
    {
        $this->theme->addOption('searchBlockTitle', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.homepageBlocks.searchBlockTitle.label'),
            'description' => __('plugins.themes.eidos.option.homepageBlocks.searchBlockTitle.desc'),
            'isMultilingual' => true,
            'default' => [
                $this->primaryLocale => __('submissions.search.findArticle'),
            ],
        ]);
        $this->theme->addOption('searchBlockDescription', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.homepageBlocks.searchBlockDescription.label'),
            'description' => __('plugins.themes.eidos.option.homepageBlocks.searchBlockDescription.desc'),
            'isMultilingual' => true,
            'size' => 'large',
            'default' => [
                $this->primaryLocale => __('submissions.search.searchArchiveCount'),
            ],
        ]);
    }

    /**
     * Add text fields for the browse by category homepage block
     */
    protected function addBrowseByCategoryBlock(): void
    {
        $this->theme->addOption('browseByCategoryTitle', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.homepageBlocks.browseByCategoryTitle.label'),
            'description' => __('plugins.themes.eidos.option.homepageBlocks.browseByCategoryTitle.desc'),
            'isMultilingual' => true,
            'default' => [
                $this->primaryLocale => __('submissions.browseByCategory'),
            ],
        ]);
        $this->theme->addOption('browseByCategoryDescription', 'FieldText', [
            'label' => __('plugins.themes.eidos.option.homepageBlocks.browseByCategoryDescription.label'),
            'description' => __('plugins.themes.eidos.option.homepageBlocks.browseByCategoryDescription.desc'),
            'isMultilingual' => true,
            'size' => 'large',
            'default' => [
                $this->primaryLocale => __('submissions.browseByCategory.description'),
            ],
        ]);
    }

    /**
     * Add option to highlight some metadata at the top
     * of the article landing page
     */
    protected function addArticleHighlightMetadataOption(): void
    {
        if (!$this->context) {
            return;
        }

        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $blocks = $templateMgr->metadataBlocks->get();

        $this->theme->addOption('highlightArticleMetadata', 'FieldOptions', [
            'type' => 'checkbox',
            'isOrderable' => true,
            'label' => __('plugins.themes.eidos.option.highlightArticleMetadata.label'),
            'description' => __('plugins.themes.eidos.option.highlightArticleMetadata.description'),
            'options' => $blocks->map(
                fn (MetadataBlock $block) => [
                    'value' => $block->id,
                    'label' => $block->title,
                ]
            )
                ->values(),
            'default' => self::ARTICLE_HIGHLIGHT_METADATA_DEFAULT,
        ]);
    }

    /**
     * Add option to show some metadata in the sidebar
     * of the article landing page
     */
    protected function addArticleSidebarMetadataOption(): void
    {
        if (!$this->context) {
            return;
        }

        $templateMgr = TemplateManager::getManager(Application::get()->getRequest());
        $blocks = $templateMgr->metadataBlocks->get();

        $this->theme->addOption('sidebarArticleMetadata', 'FieldOptions', [
            'type' => 'checkbox',
            'isOrderable' => true,
            'label' => __('plugins.themes.eidos.option.sidebarArticleMetadata.label'),
            'description' => __('plugins.themes.eidos.option.sidebarArticleMetadata.description'),
            'options' => $blocks->map(
                fn (MetadataBlock $block) => [
                    'value' => $block->id,
                    'label' => $block->title,
                ]
            )
                ->values(),
            'default' => self::ARTICLE_SIDEBAR_METADATA_DEFAULT,
        ]);
    }

    /**
     * Add option to choose social share targets in article landing page
     */
    protected function addShareOption(): void
    {
        $this->theme->addOption('shareOptions', 'FieldOptions', [
            'type' => 'checkbox',
            'isOrderable' => true,
            'label' => __('plugins.themes.eidos.option.shareOptions.label'),
            'description' => __('plugins.themes.eidos.option.shareOptions.description'),
            'options' => [
                [
                    'value' => self::SHARE_OPTIONS_EMAIL,
                    'label' => __('email.email'),
                ],
                [
                    'value' => self::SHARE_OPTIONS_LINKEDIN,
                    'label' => __('plugins.themes.eidos.share.linkedin'),
                ],
                [
                    'value' => self::SHARE_OPTIONS_WHATSAPP,
                    'label' => __('plugins.themes.eidos.share.whatsapp'),
                ],
                [
                    'value' => self::SHARE_OPTIONS_FACEBOOK,
                    'label' => __('plugins.themes.eidos.share.facebook'),
                ],
                [
                    'value' => self::SHARE_OPTIONS_REDDIT,
                    'label' => __('plugins.themes.eidos.share.reddit'),
                ],
                [
                    'value' => self::SHARE_OPTIONS_X,
                    'label' => __('plugins.themes.eidos.share.twitterX'),
                ],
                [
                    'value' => self::SHARE_OPTIONS_TELEGRAM,
                    'label' => __('plugins.themes.eidos.share.telegram'),
                ],
                [
                    'value' => self::SHARE_OPTIONS_COPY,
                    'label' => __('submission.howToCite.copyToClipboard'),
                ],
            ],
            'default' => self::SHARE_OPTIONS_DEFAULT,
        ]);
    }

    /**
     * Add options to set the colors
     */
    protected function addColorOptions(): void
    {
        $this->theme->addOption('colorMode', 'FieldOptions', [
            'type' => 'radio',
            'label' => __('plugins.themes.eidos.option.colorMode.label'),
            'description' => __('plugins.themes.eidos.option.colorMode.description'),
            'options' => [
                [
                    'value' => self::COLOR_MODE_DEFAULT,
                    'label' => __('plugins.themes.eidos.option.colorMode.default'),
                ],
                [
                    'value' => self::COLOR_MODE_ADVANCED,
                    'label' => __('plugins.themes.eidos.option.colorMode.advanced'),
                ],
            ],
            'default' => self::COLOR_MODE_DEFAULT,
        ]);

        // Simple mode
        $this->theme->addOption('primaryColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.primaryColor.label'),
            'description' => __('plugins.themes.eidos.option.primaryColor.description'),
            'default' => self::COLOR_PRIMARY,
            'showWhen' => ['colorMode', self::COLOR_MODE_DEFAULT],
        ]);
        $this->theme->addOption('accentColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.accentColor.label'),
            'description' => __('plugins.themes.eidos.option.accentColor.description'),
            'default' => self::COLOR_ACCENT,
            'showWhen' => ['colorMode', self::COLOR_MODE_DEFAULT],
        ]);

        // Advanced mode
        $this->theme->addOption('pageBackgroundColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.pageBackgroundColor.label'),
            'default' => self::COLOR_PAGE_BACKGROUND,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('pageTextColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.pageTextColor.label'),
            'default' => self::COLOR_PAGE_TEXT,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('pageLinkColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.pageLinkColor.label'),
            'default' => self::COLOR_ACCENT,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('headerBackgroundColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.headerBackgroundColor.label'),
            'default' => self::COLOR_PRIMARY,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('headerTextColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.headerTextColor.label'),
            'default' => self::COLOR_PRIMARY_TEXT,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('buttonBackgroundColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.buttonBackgroundColor.label'),
            'default' => self::COLOR_PAGE_BACKGROUND,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('buttonTextColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.buttonTextColor.label'),
            'default' => self::COLOR_PRIMARY,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('blockBackgroundColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.blockBackgroundColor.label'),
            'default' => self::COLOR_PRIMARY,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('blockTextColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.blockTextColor.label'),
            'default' => self::COLOR_PRIMARY_TEXT,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('footerBackgroundColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.footerBackgroundColor.label'),
            'default' => self::COLOR_PRIMARY,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
        $this->theme->addOption('footerTextColor', 'FieldColor', [
            'label' => __('plugins.themes.eidos.option.footerTextColor.label'),
            'default' => self::COLOR_PRIMARY_TEXT,
            'showWhen' => ['colorMode', self::COLOR_MODE_ADVANCED],
        ]);
    }
}
