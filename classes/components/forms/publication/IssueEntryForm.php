<?php

/**
 * @file classes/components/form/publication/IssueEntryForm.php
 *
 * Copyright (c) 2014-2025 Simon Fraser University
 * Copyright (c) 2000-2025 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryForm
 *
 * @brief A preset form for setting a publication's issue, section, categories,
 *  pages, etc.
 */

namespace APP\components\forms\publication;

use APP\facades\Repo;
use PKP\components\forms\FieldAutosuggestPreset;
use PKP\components\forms\FieldRichTextarea;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FieldText;
use PKP\components\forms\FieldUploadImage;
use PKP\components\forms\FormComponent;
use PKP\publication\enums\UpdateType;

class IssueEntryForm extends FormComponent
{
    public const FORM_ISSUE_ENTRY = 'issueEntry';

    public const GROUP_PLACEMENT = 'placement';
    public const GROUP_PUBLICATION_TIMING = 'publicationTiming';
    public const GROUP_VERSION_AND_UPDATES = 'versionAndUpdates';
    public const GROUP_DISPLAY = 'display';
    public const GROUP_ACCESS = 'access';

    public $id = self::FORM_ISSUE_ENTRY;
    public $method = 'PUT';

    /**
     * Constructor
     *
     * @param string $action URL to submit the form to
     * @param array $locales Supported locales
     * @param \APP\publication\Publication $publication The publication to change settings for
     * @param \APP\journal\Journal $publicationContext The context of the publication
     * @param string $baseUrl Site's base URL. Used for image previews.
     * @param string $temporaryFileApiUrl URL to upload files to
     */
    public function __construct(
        $action,
        $locales,
        $publication,
        $publicationContext,
        $baseUrl,
        $temporaryFileApiUrl
    ) {
        $this->action = $action;
        $this->locales = $locales;

        $this
            ->addGroup(['id' => self::GROUP_PLACEMENT, 'label' => __('publication.placement')])
            ->addGroup(['id' => self::GROUP_PUBLICATION_TIMING, 'label' => __('publication.publicationTiming')])
            ->addGroup(['id' => self::GROUP_VERSION_AND_UPDATES, 'label' => __('publication.versionAndUpdates')])
            ->addGroup(['id' => self::GROUP_DISPLAY, 'label' => __('publication.display')])
            ->addGroup(['id' => self::GROUP_ACCESS, 'label' => __('publication.access')]);

        // Section options
        $sections = Repo::section()->getSectionList($publicationContext->getId());
        $sectionOptions = [];
        foreach ($sections as $section) {
            $sectionOptions[] = [
                'label' => (($section['group']) ? __('publication.inactiveSection', ['section' => $section['title']]) : $section['title']),
                'value' => (int) $section['id'],
            ];
        }

        $this->addField(new FieldSelect('sectionId', [
            'groupId' => self::GROUP_PLACEMENT,
            'label' => __('section.section'),
            'description' => __('publication.section.description'),
            'options' => $sectionOptions,
            'value' => (int) $publication->getData('sectionId'),
            'size' => 'large',
        ]));

        // Categories
        $categoryOptions = [];
        $categories = Repo::category()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->getMany();

        $categoriesBreadcrumb = Repo::category()->getBreadcrumbs($categories);
        foreach ($categoriesBreadcrumb as $categoryId => $breadcrumb) {
            $categoryOptions[] = [
                'value' => $categoryId,
                'label' => $breadcrumb,
            ];
        }

        $hasAllBreadcrumbs = count($categories) === $categoriesBreadcrumb->count();
        if (!empty($categoryOptions)) {

            $vocabulary = Repo::category()->getCategoryVocabularyStructure($categories);

            $this->addField(new FieldAutosuggestPreset('categoryIds', [
                'groupId' => self::GROUP_PLACEMENT,
                'label' => __('submission.submit.placement.categories'),
                'description' => __('publication.categories.description') . ($hasAllBreadcrumbs ? '' : ' ' . __('submission.categories.circularReferenceWarning')),
                'value' => $publication->getData('categoryIds'),
                'options' => $categoryOptions,
                'vocabularies' => [
                    [
                        'addButtonLabel' => __('manager.selectCategories'),
                        'modalTitleLabel' => __('manager.selectCategories'),
                        'items' => $vocabulary
                    ]
                ]

            ]));
        }

        $this
            ->addField(new FieldText('datePublished', [
                'groupId' => self::GROUP_PUBLICATION_TIMING,
                'label' => __('publication.publicationDate'),
                'description' => __('publication.datePublished.description'),
                'value' => $publication->getData('datePublished'),
                'size' => 'large',
            ]))
            ->addField(new FieldSelect('updateType', [
                'groupId' => self::GROUP_VERSION_AND_UPDATES,
                'label' => __('publication.updateType.label'),
                'description' => __('publication.updateType.description'),
                'options' => array_map(
                    fn (UpdateType $case) => ['value' => $case->value, 'label' => $case->label()],
                    UpdateType::cases()
                ),
                'value' => $publication->getData('updateType'),
                'size' => 'large',
            ]))
            ->addField(new FieldRichTextarea('summaryOfChanges', [
                'groupId' => self::GROUP_VERSION_AND_UPDATES,
                'label' => __('submission.form.summaryOfChanges'),
                'description' => __('publication.summaryOfChanges.description'),
                'isMultilingual' => true,
                'value' => $publication->getData('summaryOfChanges'),
            ]))
            ->addField(new FieldUploadImage('coverImage', [
                'groupId' => self::GROUP_DISPLAY,
                'label' => __('editor.article.coverImage'),
                'description' => __('publication.coverImage.description'),
                'value' => $publication->getData('coverImage'),
                'isMultilingual' => true,
                'baseUrl' => $baseUrl,
                'options' => [
                    'url' => $temporaryFileApiUrl,
                ],
            ]))
            ->addField(new FieldText('pages', [
                'groupId' => self::GROUP_DISPLAY,
                'label' => __('editor.issues.pages'),
                'description' => __('publication.pages.description'),
                'value' => $publication->getData('pages'),
                'size' => 'large',
            ]))
            ->addField(new FieldText('urlPath', [
                'groupId' => self::GROUP_ACCESS,
                'label' => __('publication.urlPath'),
                'description' => __('publication.publicationSettings.urlPath.description'),
                'value' => $publication->getData('urlPath'),
                'size' => 'large',
            ]));
    }
}
