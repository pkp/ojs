<?php

/**
 * @file pages/submission/SubmissionHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionHandler
 *
 * @ingroup pages_submission
 *
 * @brief Handles page requests to the submission wizard
 */

namespace APP\pages\submission;

use APP\components\forms\submission\ReconfigureSubmission;
use APP\components\forms\submission\StartSubmission;
use APP\core\Application;
use APP\core\Request;
use APP\publication\Publication;
use APP\section\Section;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Support\LazyCollection;
use PKP\components\forms\FormComponent;
use PKP\components\forms\publication\Details;
use PKP\components\forms\publication\TitleAbstractForm;
use PKP\components\forms\submission\ForTheEditors;
use PKP\context\Context;
use PKP\facades\Locale;
use PKP\pages\submission\PKPSubmissionHandler;

class SubmissionHandler extends PKPSubmissionHandler
{
    /**
     * Display the screen to start a new submission
     */
    protected function start(array $args, Request $request): void
    {
        $context = $request->getContext();
        $userGroups = $this->getSubmitUserGroups($context, $request->getUser());
        if (!$userGroups->count()) {
            $this->showErrorPage(
                'submission.wizard.notAllowed',
                __('submission.wizard.notAllowed.description', [
                    'email' => $context->getData('contactEmail'),
                    'name' => $context->getData('contactName'),
                ])
            );
            return;
        }

        $sections = $this->getSubmitSections($context);
        if (empty($sections)) {
            $this->showErrorPage(
                'submission.wizard.notAllowed',
                __('submission.wizard.noSectionAllowed.description', [
                    'email' => $context->getData('contactEmail'),
                    'name' => $context->getData('contactName'),
                ])
            );
            return;
        }

        $apiUrl = $request->getDispatcher()->url(
            $request,
            Application::ROUTE_API,
            $context->getPath(),
            'submissions'
        );

        $form = new StartSubmission($apiUrl, $context, $userGroups, $sections);

        $templateMgr = TemplateManager::getManager($request);

        $templateMgr->setState([
            'form' => $form->getConfig(),
        ]);

        parent::start($args, $request);
    }

    protected function getSubmittingTo(Context $context, Submission $submission, array $sections, LazyCollection $categories): string
    {
        $languageCount = count($context->getSupportedSubmissionLocales()) > 1;
        $sectionCount = count($sections) > 1;
        $section = collect($sections)->first(fn ($section) => $section->getId() === $submission->getCurrentPublication()->getData('sectionId'));

        if ($sectionCount && $languageCount) {
            return __(
                'submission.wizard.submittingToSectionInLanguage',
                [
                    'section' => $section->getLocalizedTitle(),
                    'language' => Locale::getMetadata($submission->getData('locale'))->getDisplayName(),
                ]
            );
        } elseif ($sectionCount) {
            return __(
                'submission.wizard.submittingToSection',
                [
                    'section' => $section->getLocalizedTitle(),
                ]
            );
        } elseif ($languageCount) {
            return __(
                'submission.wizard.submittingInLanguage',
                [
                    'language' => Locale::getMetadata($submission->getData('locale'))->getDisplayName(),
                ]
            );
        }
        return '';
    }

    protected function getReconfigureForm(Context $context, Submission $submission, Publication $publication, array $sections, LazyCollection $categories): ReconfigureSubmission
    {
        return new ReconfigureSubmission(
            FormComponent::ACTION_EMIT,
            $submission,
            $publication,
            $context,
            $sections
        );
    }

    protected function getDetailsForm(string $publicationApiUrl, array $locales, Publication $publication, Context $context, array $sections, string $suggestionUrlBase): TitleAbstractForm
    {
        /** @var Section $section */
        $section = collect($sections)->first(fn ($section) => $section->getId() === $publication->getData('sectionId'));

        return new Details(
            $publicationApiUrl,
            $locales,
            $publication,
            $context,
            $suggestionUrlBase,
            (int) $section->getData('wordCount'),
            !$section->getData('abstractsNotRequired')
        );
    }

    protected function getForTheEditorsForm(string $publicationApiUrl, array $locales, Publication $publication, Submission $submission, Context $context, string $suggestionUrlBase, LazyCollection $categories): ForTheEditors
    {
        return new ForTheEditors(
            $publicationApiUrl,
            $locales,
            $publication,
            $submission,
            $context,
            $suggestionUrlBase,
            $categories
        );
    }

    protected function getReconfigurePublicationProps(): array
    {
        return [
            'sectionId',
        ];
    }

    protected function getReconfigureSubmissionProps(): array
    {
        return [
            'locale',
        ];
    }
}
