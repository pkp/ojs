<?php

/**
 * @file pages/authorDashboard/AuthorDashboardHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class AuthorDashboardHandler
 * @ingroup pages_authorDashboard
 *
 * @brief Handle requests for the author dashboard.
 */

namespace APP\pages\authorDashboard;

use APP\journal\SectionDAO;
use APP\publication\Publication;
use PKP\components\forms\publication\TitleAbstractForm;
use PKP\context\Context;
use PKP\core\PKPApplication;
use PKP\db\DAORegistry;
use PKP\pages\authorDashboard\PKPAuthorDashboardHandler;

class AuthorDashboardHandler extends PKPAuthorDashboardHandler
{
    protected function _getRepresentationsGridUrl($request, $submission)
    {
        return $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_COMPONENT,
            null,
            'grid.articleGalleys.ArticleGalleyGridHandler',
            'fetchGrid',
            null,
            [
                'submissionId' => $submission->getId(),
                'publicationId' => '__publicationId__',
            ]
        );
    }

    protected function getTitleAbstractForm(string $latestPublicationApiUrl, array $locales, Publication $latestPublication, Context $context): TitleAbstractForm
    {
        /** @var SectionDAO $sectionDao */
        $sectionDao = DAORegistry::getDAO('SectionDAO');
        $section = $sectionDao->getById($latestPublication->getData('sectionId'), $context->getId());

        return new TitleAbstractForm(
            $latestPublicationApiUrl,
            $locales,
            $latestPublication,
            (int) $section->getData('wordCount'),
            !$section->getData('abstractsNotRequired')
        );
    }
}
