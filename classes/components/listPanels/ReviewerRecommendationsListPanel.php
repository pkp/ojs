<?php

/**
 * @file classes/components/listPanels/ReviewerRecommendationsListPanel.php
 *
 * Copyright (c) 2025 Simon Fraser University
 * Copyright (c) 2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ReviewerRecommendationsListPanel
 *
 * @brief A ListPanel component for displaying reviewer recommendations in workflow settings
 */

namespace APP\components\listPanels;

use APP\components\forms\context\ReviewerRecommendationForm;
use APP\core\Application;
use PKP\components\listPanels\ListPanel;
use PKP\context\Context;

class ReviewerRecommendationsListPanel extends ListPanel
{
    public const COMPONENT_ID = 'reviewerRecommendations';

    /** URL to the API endpoint where items can be retrieved */
    public string $apiUrl = '';

    /** How many items to display on one page in this list */
    public int $count = 30;

    /** Query parameters to pass if this list executes GET requests  */
    public array $getParams = [];

    /** Max number of items available to display in this list panel  */
    public int $itemsMax;

    public Context $context;
    public array $locales;

    public function __construct(
        string $title,
        Context $context,
        array $locales,
        array $items = [],
        int $itemsMax = 0
    ) {
        parent::__construct(static::COMPONENT_ID, $title);

        $this->context = $context;
        $this->locales = $locales;
        $this->items = $items;
        $this->itemsMax = $itemsMax;
    }

    /**
     * @copydoc ListPanel::getConfig()
     */
    public function getConfig()
    {
        $config = parent::getConfig();

        $config = array_merge(
            $config,
            [
                'form' => $this->getLocalizedForm(),
                'items' => $this->items,
            ]
        );

        return $config;
    }

    /**
     * Construct the api url to perform actions on recommendations
     */
    protected function getReviewerRecommendationsApiUrl(): string
    {
        return Application::get()->getRequest()->getDispatcher()->url(
            Application::get()->getRequest(),
            Application::ROUTE_API,
            $this->context->getPath(),
            'reviewers/recommendations'
        );
    }

    /**
     * Get the form data localized to the context's locale
     */
    protected function getLocalizedForm(): array
    {
        $apiUrl = $this->getReviewerRecommendationsApiUrl();

        $contextLocale = $this->context->getData('primaryLocale');
        $data = $this->getForm($apiUrl)->getConfig();

        $data['primaryLocale'] = $contextLocale;
        $data['visibleLocales'] = [$contextLocale];

        $data['supportedFormLocales'] = collect($this->locales)
            ->sortBy([fn (array $a, array $b) => $b['key'] === $contextLocale ? 1 : -1])
            ->values()
            ->toArray();

        return $data;
    }

    /**
     * Get the reviewer recommendation form
     */
    protected function getForm(string $url): ReviewerRecommendationForm
    {
        return new ReviewerRecommendationForm($url, $this->locales);
    }
}
