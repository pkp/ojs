<?php

/**
 * @file controllers/grid/articleGalleys/form/ArticleGalleyForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyForm
 *
 * @ingroup controllers_grid_articleGalleys_form
 *
 * @see Galley
 *
 * @brief Article galley editing form.
 */

namespace APP\controllers\grid\articleGalleys\form;

use APP\core\Request;
use APP\facades\Repo;
use APP\publication\Publication;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\galley\Galley;

class ArticleGalleyForm extends Form
{
    /** @var Submission */
    public $_submission = null;

    /** @var Publication */
    public $_publication = null;

    /** @var Galley current galley */
    public $_articleGalley = null;

    public bool $_isEditable = true;

    /**
     * Constructor.
     *
     * @param Request $request
     * @param Submission $submission
     * @param Publication $publication
     * @param Galley $articleGalley (optional)
     * @param bool $isEditable (optional, default = true)
     */
    public function __construct($request, $submission, $publication, $articleGalley = null, bool $isEditable = true)
    {
        parent::__construct('controllers/grid/articleGalleys/form/articleGalleyForm.tpl');
        $this->_submission = $submission;
        $this->_publication = $publication;
        $this->_articleGalley = $articleGalley;
        $this->_isEditable = $isEditable;

        $this->addCheck(new \PKP\form\validation\FormValidator($this, 'label', 'required', 'editor.issues.galleyLabelRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorRegExp($this, 'urlPath', 'optional', 'validator.alpha_dash_period', '/^[a-zA-Z0-9]+([\\.\\-_][a-zA-Z0-9]+)*$/'));
        $this->addCheck(new \PKP\form\validation\FormValidatorPost($this));
        $this->addCheck(new \PKP\form\validation\FormValidatorCSRF($this));

        // Ensure a locale is provided and valid
        $journal = $request->getJournal();
        $this->addCheck(
            new \PKP\form\validation\FormValidator(
                $this,
                'locale',
                'required',
                'editor.issues.galleyLocaleRequired'
            ),
            function ($locale) use ($journal) {
                return in_array($locale, $journal->getSupportedSubmissionLocaleNames());
            }
        );
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        if ($this->_articleGalley) {
            $articleGalleyFile = $this->_articleGalley->getFile();
            $templateMgr->assign([
                'representationId' => $this->_articleGalley->getId(),
                'articleGalley' => $this->_articleGalley,
                'articleGalleyFile' => $articleGalleyFile,
                'supportsDependentFiles' => $articleGalleyFile ? Repo::submissionFile()->supportsDependentFiles($articleGalleyFile) : null,
            ]);
        }
        $context = $request->getContext();
        $templateMgr->assign([
            'supportedLocales' => $context->getSupportedSubmissionLocaleNames(),
            'submissionId' => $this->_submission->getId(),
            'publicationId' => $this->_publication->getId(),
            'formDisabled' => !$this->_isEditable
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::validate
     */
    public function validate($callHooks = true)
    {
        // Validate the urlPath
        if (strlen((string) $this->getData('urlPath'))) {
            if (ctype_digit((string) $this->getData('urlPath'))) {
                $this->addError('urlPath', __('publication.urlPath.numberInvalid'));
                $this->addErrorField('urlPath');
            } else {
                $existingGalley = Repo::galley()->getByUrlPath((string) $this->getData('urlPath'), $this->_publication);
                if ($existingGalley && $this->_articleGalley?->getId() !== $existingGalley->getId()) {
                    $this->addError('urlPath', __('publication.urlPath.duplicate'));
                    $this->addErrorField('urlPath');
                }
            }
        }

        if (!$this->_isEditable) {
            $this->addError('', __('galley.cantEditPublished'));
        }

        return parent::validate($callHooks);
    }

    /**
     * Initialize form data from current galley (if applicable).
     */
    public function initData()
    {
        if ($this->_articleGalley) {
            $this->_data = [
                'label' => $this->_articleGalley->getLabel(),
                'locale' => $this->_articleGalley->getLocale(),
                'urlPath' => $this->_articleGalley->getData('urlPath'),
                'urlRemote' => $this->_articleGalley->getData('urlRemote'),
            ];
        } else {
            $this->_data = [];
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars(
            [
                'label',
                'locale',
                'urlPath',
                'urlRemote',
            ]
        );
    }

    /**
     * Save changes to the galley.
     *
     * @return Galley The resulting article galley.
     */
    public function execute(...$functionArgs)
    {
        $data = [
            'publicationId' => $this->_publication->getId(),
            'label' => $this->getData('label'),
            'locale' => $this->getData('locale'),
            'urlPath' => strlen($urlPath = (string) $this->getData('urlPath')) ? $urlPath : null,
            'urlRemote' => strlen($urlRemote = (string) $this->getData('urlRemote')) ? $urlRemote : null
        ];

        if ($this->_articleGalley) {
            // Update galley in the db
            Repo::galley()->edit($this->_articleGalley, $data);
            $articleGalleyId = $this->_articleGalley->getId();
        } else {
            // Create a new galley
            $articleGalleyId = Repo::galley()->add(Repo::galley()->newDataObject($data));
        }

        parent::execute(...$functionArgs);

        return $this->_articleGalley = Repo::galley()->get($articleGalleyId);
    }
}
