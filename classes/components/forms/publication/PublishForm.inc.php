<?php
/**
 * @file classes/components/form/publication/PublishForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class PublishForm
 * @ingroup classes_controllers_form
 *
 * @brief A preset form for confirming a publication's issue before publishing.
 *   It may also be used for scheduling a publication in an issue for later
 *   publication.
 */

namespace APP\components\forms\publication;

use APP\core\Application;
use APP\facades\Repo;
use Illuminate\Support\LazyCollection;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FormComponent;
use PKP\context\Context;
use PKP\core\PKPString;

define('FORM_PUBLISH', 'publish');

class PublishForm extends FormComponent
{
    /** @copydoc FormComponent::$id */
    public $id = FORM_PUBLISH;

    /** @copydoc FormComponent::$method */
    public $method = 'PUT';

    /** @var \Publication */
    public $publication;

    /** @var \Context */
    public $submissionContext;

    /**
     * Constructor
     *
     * @param string $action URL to submit the form to
     * @param Publication $publication The publication to change settings for
     * @param \Context $submissionContext journal or press
     * @param array $requirementErrors A list of pre-publication requirements that are not met.
     */
    public function __construct($action, $publication, $submissionContext, $requirementErrors)
    {
        $this->action = $action;
        $this->errors = $requirementErrors;
        $this->publication = $publication;
        $this->submissionContext = $submissionContext;

        // Set separate messages and buttons if publication requirements have passed
        if (empty($requirementErrors)) {
            $msg = __('publication.publish.confirmation');
            $submitLabel = __('publication.publish');
            if ($publication->getData('issueId')) {
                $issue = Repo::issue()->get($publication->getData('issueId'));
                if ($issue) {
                    if ($issue->getData('published')) {
                        $msg = __('publication.publish.confirmation.backIssue', ['issue' => $issue->getIssueIdentification()]);
                    } else {
                        $msg = __('publication.publish.confirmation.futureIssue', ['issue' => $issue->getIssueIdentification()]);
                        $submitLabel = __('editor.submission.schedulePublication');
                    }
                }
            }
            // If a publication date has already been set and the date has passed this will
            // be published immediately regardless of the issue assignment
            if ($publication->getData('datePublished') && $publication->getData('datePublished') <= \Core::getCurrentDate()) {
                $timestamp = strtotime($publication->getData('datePublished'));
                $dateFormatLong = PKPString::convertStrftimeFormat($submissionContext->getLocalizedDateFormatLong());
                $msg = __(
                    'publication.publish.confirmation.datePublishedInPast',
                    [
                        'datePublished' => date($dateFormatLong, $timestamp),
                    ]
                );
                $submitLabel = __('publication.publish');
            }
            $this->addPage([
                'id' => 'default',
                'submitButton' => [
                    'label' => $submitLabel,
                ],
            ]);
        } else {
            $msg = '<p>' . __('publication.publish.requirements') . '</p>';
            $msg .= '<ul>';
            foreach ($requirementErrors as $error) {
                $msg .= '<li>' . $error . '</li>';
            }
            $msg .= '</ul>';
            $this->addPage([
                'id' => 'default',
            ]);
        }

        $this->addGroup([
            'id' => 'default',
            'pageId' => 'default',
        ])
            ->addField(new FieldHTML('validation', [
                'description' => $msg,
                'groupId' => 'default',
            ]));

        if ($submissionContext->areDoisEnabled()) {
            $this->addField(new \PKP\components\forms\FieldHTML('doi', [
                'description' => $this->_getDoiMessage(),
                'groupId' => 'default',
            ]));
        }
    }

    /**
     * Assembes message about DOIs and their creation status.
     */
    private function _getDoiMessage(): string
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $enabledDoiTypes = $context->getData(Context::SETTING_ENABLED_DOI_TYPES) ?? [];

        $publicationDoiEnabled = in_array(Repo::doi()::TYPE_PUBLICATION, $enabledDoiTypes);
        $galleyDoiEnabled = in_array(Repo::doi()::TYPE_REPRESENTATION, $enabledDoiTypes);
        $warningIconHtml = '<span class="fa fa-exclamation-triangle pkpIcon--inline"></span>';

        $returnValue = '';

        if (!$publicationDoiEnabled && ! $galleyDoiEnabled) {
            $returnValue = '';

        // Use a simplified view when only assigning to the publication
        } elseif (!$galleyDoiEnabled) {
            if ($this->publication->getDoi()) {
                $msg = __('doi.editor.preview.publication', ['doi' => $this->publication->getDoi()]);
            } else {
                $assignedDoi = $this->publication->getDoi();
                if ($assignedDoi != null) {
                    $msg = __('doi.editor.preview.publication', ['doi' => $assignedDoi]);
                } else {
                    $msg = '<div class="pkpNotification pkpNotification--warning">' . $warningIconHtml . __('doi.editor.preview.publication.none') . '</div>';
                }

                $returnValue = $msg;
            }
            // Show a table if more than one DOI is going to be created
        } else {
            $doiTableRows = [];
            if ($publicationDoiEnabled) {
                if ($this->publication->getDoi()) {
                    $doiTableRows[] = [$this->publication->getDoi(), 'Publication'];
                } else {
                    $assignedDoi = $this->publication->getDoi();
                    if ($assignedDoi != null) {
                        $doiTableRows[] = [$assignedDoi, 'Publication'];
                    } else {
                        $doiTableRows[] = [$warningIconHtml . __('submission.status.unassigned'), 'Publication'];
                    }
                }
            }

            if ($galleyDoiEnabled) {

                /** @var LazyCollection $galleys */
                $galleys = $this->publication->getData('galleys');
                $galleys->each(function ($galley) use (&$doiTableRows, $warningIconHtml) {
                    if ($galley->getDoi()) {
                        $doiTableRows[] = [$galley->getDoi(), __('doi.editor.preview.galleys', ['galleyLabel' => $galley->getGalleyLabel()])];
                    } else {
                        $assignedDoi = $this->publication->getDoi();
                        if ($assignedDoi != null) {
                            $doiTableRows[] = [$assignedDoi, __('doi.editor.preview.galleys', ['galleyLabel' => $galley->getGalleyLabel()])];
                        } else {
                            $doiTableRows[] = [$warningIconHtml . __('submission.status.unassigned'), __('doi.editor.preview.galleys', ['galleyLabel' => $galley->getGalleyLabel()])];
                        }
                    }
                });

                if (!empty($doiTableRows)) {
                    $table = '<table class="pkpTable"><thead><tr>' .
                        '<th>' . __('doi.editor.doi') . '</th>' .
                        '<th>' . __('doi.editor.preview.objects') . '</th>' .
                        '</tr></thead><tbody>';
                    foreach ($doiTableRows as $doiTableRow) {
                        $table .= '<tr><td>' . $doiTableRow[0] . '</td><td>' . $doiTableRow[1] . '</td></tr>';
                    }
                    $table .= '</tbody></table>';

                    $returnValue = $table;
                }
            }
        }

        return $returnValue;
    }
}
