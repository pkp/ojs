<?php
/**
 * @file classes/components/form/publication/IssueEntryForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueEntryForm
 *
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for setting a publication's issue, section, categories,
 *  pages, etc.
 */

namespace APP\components\forms\publication;

use APP\components\forms\FieldSelectIssue;
use APP\facades\Repo;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldSelect;
use PKP\components\forms\FieldText;
use PKP\components\forms\FieldUploadImage;
use PKP\components\forms\FormComponent;

define('FORM_ISSUE_ENTRY', 'issueEntry');

class IssueEntryForm extends FormComponent
{
    /** @copydoc FormComponent::$id */
    public $id = FORM_ISSUE_ENTRY;

    /** @copydoc FormComponent::$method */
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
    public function __construct($action, $locales, $publication, $publicationContext, $baseUrl, $temporaryFileApiUrl)
    {
        $this->action = $action;
        $this->locales = $locales;

        // Issue options
        $issueOptions = [['value' => '', 'label' => '']];

        $unpublishedIssues = Repo::issue()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->filterByPublished(false)
            ->getMany()
            ->toArray();

        if (count($unpublishedIssues)) {
            $issueOptions[] = ['value' => '', 'label' => '--- ' . __('editor.issues.futureIssues') . ' ---'];
            foreach ($unpublishedIssues as $issue) {
                $issueOptions[] = [
                    'value' => (int) $issue->getId(),
                    'label' => htmlspecialchars($issue->getIssueIdentification()),
                ];
            }
        }
        $publishedIssues = Repo::issue()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->filterByPublished(true)
            ->getMany()
            ->toArray();

        if (count($publishedIssues)) {
            $issueOptions[] = ['value' => '', 'label' => '--- ' . __('editor.issues.backIssues') . ' ---'];
            foreach ($publishedIssues as $issue) {
                $issueOptions[] = [
                    'value' => (int) $issue->getId(),
                    'label' => htmlspecialchars($issue->getIssueIdentification()),
                ];
            }
        }

        // Section options
        $sections = Repo::section()->getSectionList($publicationContext->getId());
        $sectionOptions = [];
        foreach ($sections as $section) {
            $sectionOptions[] = [
                'label' => (($section['group']) ? __('publication.inactiveSection', ['section' => $section['title']]) : $section['title']),
                'value' => (int) $section['id'],
            ];
        }

        $this->addField(new FieldSelectIssue('issueId', [
            'label' => __('issue.issue'),
            'options' => $issueOptions,
            'publicationStatus' => $publication->getData('status'),
            'value' => $publication->getData('issueId') ? $publication->getData('issueId') : 0,
        ]))
            ->addField(new FieldSelect('sectionId', [
                'label' => __('section.section'),
                'options' => $sectionOptions,
                'value' => (int) $publication->getData('sectionId'),
            ]));

        // Categories
        $categoryOptions = [];
        $categories = Repo::category()->getCollector()
            ->filterByContextIds([$publicationContext->getId()])
            ->getMany()
            ->toArray();

        foreach ($categories as $category) {
            $label = $category->getLocalizedTitle();
            if ($category->getParentId()) {
                $label = $categories[$category->getParentId()]->getLocalizedTitle() . ' > ' . $label;
            }
            $categoryOptions[] = [
                'value' => (int) $category->getId(),
                'label' => $label,
            ];
        }
        if (!empty($categoryOptions)) {
            $this->addField(new FieldOptions('categoryIds', [
                'label' => __('submission.submit.placement.categories'),
                'value' => $publication->getData('categoryIds'),
                'options' => $categoryOptions,
            ]));
        }

        $this->addField(new FieldUploadImage('coverImage', [
            'label' => __('editor.article.coverImage'),
            'value' => $publication->getData('coverImage'),
            'isMultilingual' => true,
            'baseUrl' => $baseUrl,
            'options' => [
                'url' => $temporaryFileApiUrl,
            ],
        ]))
            ->addField(new FieldText('pages', [
                'label' => __('editor.issues.pages'),
                'value' => $publication->getData('pages'),
            ]))
            ->addField(new FieldText('urlPath', [
                'label' => __('publication.urlPath'),
                'description' => __('publication.urlPath.description'),
                'value' => $publication->getData('urlPath'),
            ]))
            ->addField(new FieldText('datePublished', [
                'label' => __('publication.datePublished'),
                'description' => __('publication.datePublished.description'),
                'value' => $publication->getData('datePublished'),
                'size' => 'small',
            ]));
    }
}
