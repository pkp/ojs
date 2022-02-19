<?php

/**
 * @file controllers/grid/issues/form/IssueForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class IssueForm
 * @ingroup controllers_grid_issues_form
 *
 * @see Issue
 *
 * @brief Form to create or edit an issue
 */

use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\facades\Locale;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;

class IssueForm extends Form
{
    /** @var Issue current issue */
    public $issue;

    /**
     * Constructor.
     *
     * @param Issue $issue (optional)
     */
    public function __construct($issue = null)
    {
        parent::__construct('controllers/grid/issues/form/issueForm.tpl');

        $form = $this;
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'volume', 'optional', 'editor.issues.volumeRequired', '/^[0-9]+$/i'));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'showVolume', 'optional', 'editor.issues.volumeRequired', function ($showVolume) use ($form) {
            return !$showVolume || $form->getData('volume') ? true : false;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'showNumber', 'optional', 'editor.issues.numberRequired', function ($showNumber) use ($form) {
            return !$showNumber || $form->getData('number') ? true : false;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'showYear', 'optional', 'editor.issues.yearRequired', function ($showYear) use ($form) {
            return !$showYear || $form->getData('year') ? true : false;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'showTitle', 'optional', 'editor.issues.titleRequired', function ($showTitle) use ($form) {
            return !$showTitle || implode('', $form->getData('title')) != '' ? true : false;
        }));
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'urlPath', 'optional', 'validator.alpha_dash_period', '/^[a-zA-Z0-9]+([\\.\\-_][a-zA-Z0-9]+)*$/'));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));
        $this->issue = $issue;
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        if ($this->issue) {
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->assign([
                'issue' => $this->issue,
                'issueId' => $this->issue->getId(),
            ]);

            // Cover image delete link action
            if ($coverImage = $this->issue->getCoverImage(Locale::getLocale())) {
                $templateMgr->assign(
                    'deleteCoverImageLinkAction',
                    new LinkAction(
                        'deleteCoverImage',
                        new RemoteActionConfirmationModal(
                            $request->getSession(),
                            __('common.confirmDelete'),
                            null,
                            $request->getRouter()->url(
                                $request,
                                null,
                                null,
                                'deleteCoverImage',
                                null,
                                [
                                    'coverImage' => $coverImage,
                                    'issueId' => $this->issue->getId(),
                                ]
                            ),
                            'modal_delete'
                        ),
                        __('common.delete'),
                        null
                    )
                );
            }
        }

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::validate()
     */
    public function validate($callHooks = true)
    {
        if ($temporaryFileId = $this->getData('temporaryFileId')) {
            $request = Application::get()->getRequest();
            $user = $request->getUser();
            $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /** @var TemporaryFileDAO $temporaryFileDao */
            $temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

            $publicFileManager = new PublicFileManager();
            if (!$publicFileManager->getImageExtension($temporaryFile->getFileType())) {
                $this->addError('coverImage', __('editor.issues.invalidCoverImageFormat'));
            }
        }

        // Check if urlPath is already being used
        if ($this->getData('urlPath')) {
            if (ctype_digit((string) $this->getData('urlPath'))) {
                $this->addError('urlPath', __('publication.urlPath.numberInvalid'));
                $this->addErrorField('urlPath');
            } else {
                $issue = Repo::issue()->getByBestId($this->getData('urlPath'), Application::get()->getRequest()->getContext()->getId());
                if ($issue &&
                    (!$this->issue || $this->issue->getId() !== $issue->getId())
                ) {
                    $this->addError('urlPath', __('publication.urlPath.duplicate'));
                    $this->addErrorField('urlPath');
                }
            }
        }

        return parent::validate($callHooks);
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData()
    {
        if (isset($this->issue)) {
            $locale = Locale::getLocale();
            $this->_data = [
                'title' => $this->issue->getTitle(null), // Localized
                'volume' => $this->issue->getVolume(),
                'number' => $this->issue->getNumber(),
                'year' => $this->issue->getYear(),
                'datePublished' => $this->issue->getDatePublished(),
                'description' => $this->issue->getDescription(null), // Localized
                'showVolume' => $this->issue->getShowVolume(),
                'showNumber' => $this->issue->getShowNumber(),
                'showYear' => $this->issue->getShowYear(),
                'showTitle' => $this->issue->getShowTitle(),
                'coverImage' => $this->issue->getCoverImage($locale),
                'coverImageAltText' => $this->issue->getCoverImageAltText($locale),
                'urlPath' => $this->issue->getData('urlPath'),
            ];
            parent::initData();
        } else {
            $this->_data = [
                'showVolume' => 1,
                'showNumber' => 1,
                'showYear' => 1,
                'showTitle' => 1,
            ];
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars([
            'title',
            'volume',
            'number',
            'year',
            'description',
            'showVolume',
            'showNumber',
            'showYear',
            'showTitle',
            'temporaryFileId',
            'coverImageAltText',
            'datePublished',
            'urlPath',
        ]);

        $form = $this;
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'issueForm', 'required', 'editor.issues.issueIdentificationRequired', function () use ($form) {
            return $form->getData('showVolume') || $form->getData('showNumber') || $form->getData('showYear') || $form->getData('showTitle');
        }));
    }

    /**
     * Save issue settings.
     */
    public function execute(...$functionArgs)
    {
        parent::execute(...$functionArgs);

        $request = Application::get()->getRequest();
        $journal = $request->getJournal();

        if ($this->issue) {
            $isNewIssue = false;
            $issue = $this->issue;
        } else {
            $issue = Repo::issue()->newDataObject();
            switch ($journal->getData('publishingMode')) {
                case \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION:
                case \APP\journal\Journal::PUBLISHING_MODE_NONE:
                    $issue->setAccessStatus(\APP\issue\Issue::ISSUE_ACCESS_SUBSCRIPTION);
                    break;
                case \APP\journal\Journal::PUBLISHING_MODE_OPEN:
                default:
                    $issue->setAccessStatus(\APP\issue\Issue::ISSUE_ACCESS_OPEN);
                    break;
            }
            $isNewIssue = true;
        }
        $volume = $this->getData('volume');
        $number = $this->getData('number');
        $year = $this->getData('year');

        $issue->setJournalId($journal->getId());
        $issue->setTitle($this->getData('title'), null); // Localized
        $issue->setVolume(empty($volume) ? null : $volume);
        $issue->setNumber(empty($number) ? null : $number);
        $issue->setYear(empty($year) ? null : $year);
        if (!$isNewIssue) {
            $issue->setDatePublished($this->getData('datePublished'));
        }
        $issue->setDescription($this->getData('description'), null); // Localized
        $issue->setShowVolume((int) $this->getData('showVolume'));
        $issue->setShowNumber((int) $this->getData('showNumber'));
        $issue->setShowYear((int) $this->getData('showYear'));
        $issue->setShowTitle((int) $this->getData('showTitle'));
        $issue->setData('urlPath', $this->getData('urlPath'));

        // If it is a new issue, first insert it, then update the cover
        // because the cover name needs an issue id.
        if ($isNewIssue) {
            $issue->setPublished(0);
            Repo::issue()->add($issue);
        }

        $locale = Locale::getLocale();
        // Copy an uploaded cover file for the issue, if there is one.
        if ($temporaryFileId = $this->getData('temporaryFileId')) {
            $user = $request->getUser();
            $temporaryFileDao = DAORegistry::getDAO('TemporaryFileDAO'); /** @var TemporaryFileDAO $temporaryFileDao */
            $temporaryFile = $temporaryFileDao->getTemporaryFile($temporaryFileId, $user->getId());

            $publicFileManager = new PublicFileManager();
            $newFileName = 'cover_issue_' . $issue->getId() . '_' . $locale . $publicFileManager->getImageExtension($temporaryFile->getFileType());
            $journal = $request->getJournal();
            $publicFileManager->copyContextFile($journal->getId(), $temporaryFile->getFilePath(), $newFileName);
            $issue->setCoverImage($newFileName, $locale);
            Repo::issue()->edit($issue, []);
        }

        $issue->setCoverImageAltText($this->getData('coverImageAltText'), $locale);

        HookRegistry::call('issueform::execute', [$this, $issue]);

        Repo::issue()->edit($issue, []);
    }
}
