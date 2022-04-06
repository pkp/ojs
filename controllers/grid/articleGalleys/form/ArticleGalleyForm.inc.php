<?php

/**
 * @file controllers/grid/articleGalleys/form/ArticleGalleyForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyForm
 * @ingroup controllers_grid_articleGalleys_form
 *
 * @see Galley
 *
 * @brief Article galley editing form.
 */

use APP\facades\Repo;
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

    /**
     * Constructor.
     *
     * @param Submission $submission
     * @param Publication $publication
     * @param Galley $articleGalley (optional)
     */
    public function __construct($request, $submission, $publication, $articleGalley = null)
    {
        parent::__construct('controllers/grid/articleGalleys/form/articleGalleyForm.tpl');
        $this->_submission = $submission;
        $this->_publication = $publication;
        $this->_articleGalley = $articleGalley;

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
        ]);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::validate
     */
    public function validate($callHooks = true)
    {
        // Validate the urlPath
        if ($this->getData('urlPath')) {
            if (ctype_digit((string) $this->getData('urlPath'))) {
                $this->addError('urlPath', __('publication.urlPath.numberInvalid'));
                $this->addErrorField('urlPath');
            } else {
                $existingGalley = Repo::galley()->getByUrlPath((string) $this->getData('urlPath'), $this->_publication);
                if ($existingGalley && (!$this->_articleGalley || $this->_articleGalley->getId() !== $existingGalley->getId())) {
                    $this->addError('urlPath', __('publication.urlPath.duplicate'));
                    $this->addErrorField('urlPath');
                }
            }
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
        $articleGalley = $this->_articleGalley;

        if ($articleGalley) {

            // Update galley in the db
            $newData = [
                'label' => $this->getData('label'),
                'locale' => $this->getData('locale'),
                'urlPath' => $this->getData('urlPath'),
                'urlRemote' => $this->getData('urlRemote')
            ];
            Repo::galley()->edit($articleGalley, $newData);
        } else {
            // Create a new galley
            $articleGalley = Repo::galley()->newDataObject([
                'publicationId' => $this->_publication->getId(),
                'label' => $this->getData('label'),
                'locale' => $this->getData('locale'),
                'urlPath' => $this->getData('urlPath'),
                'urlRemote' => $this->getData('urlRemote')
            ]);


            $galleyId = Repo::galley()->add($articleGalley);
            $articleGalley = Repo::galley()->get($galleyId);
            $this->_articleGalley = $articleGalley;
        }

        parent::execute(...$functionArgs);

        return $articleGalley;
    }
}
