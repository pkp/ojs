<?php

/**
 * @file controllers/grid/settings/sections/form/SectionForm.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SectionForm
 *
 * @ingroup controllers_grid_settings_section_form
 *
 * @brief Form for adding/editing a section
 */

namespace APP\controllers\grid\settings\sections\form;

use APP\core\Application;
use APP\core\Request;
use APP\facades\Repo;
use APP\template\TemplateManager;
use PKP\controllers\grid\settings\sections\form\PKPSectionForm;
use PKP\db\DAORegistry;
use PKP\reviewForm\ReviewFormDAO;

class SectionForm extends PKPSectionForm
{
    /**
     * Constructor.
     *
     * @param Request $request
     * @param int $sectionId optional
     */
    public function __construct($request, $sectionId = null)
    {
        parent::__construct(
            $request,
            'controllers/grid/settings/sections/form/sectionForm.tpl',
            $sectionId
        );

        // Validation checks for this form
        $this->addCheck(new \PKP\form\validation\FormValidatorLocale($this, 'title', 'required', 'manager.setup.form.section.nameRequired'));
        $this->addCheck(new \PKP\form\validation\FormValidatorLocale($this, 'abbrev', 'required', 'manager.sections.form.abbrevRequired'));
        $journal = $request->getJournal();
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'reviewFormId', 'optional', 'manager.sections.form.reviewFormId', [DAORegistry::getDAO('ReviewFormDAO'), 'reviewFormExists'], [Application::ASSOC_TYPE_JOURNAL, $journal->getId()]));
    }

    /**
     * Initialize form data from current settings.
     */
    public function initData()
    {
        $request = Application::get()->getRequest();
        $journal = $request->getJournal();

        $sectionId = $this->getSectionId();
        if ($sectionId) {
            $this->section = Repo::section()->get($sectionId, $journal->getId());
        }

        if (isset($this->section)) {
            $this->setData([
                'title' => $this->section->getTitle(null), // Localized
                'abbrev' => $this->section->getAbbrev(null), // Localized
                'reviewFormId' => $this->section->getReviewFormId(),
                'isInactive' => $this->section->getIsInactive(),
                'metaIndexed' => !$this->section->getMetaIndexed(), // #2066: Inverted
                'metaReviewed' => !$this->section->getMetaReviewed(), // #2066: Inverted
                'abstractsNotRequired' => $this->section->getAbstractsNotRequired(),
                'identifyType' => $this->section->getIdentifyType(null), // Localized
                'editorRestricted' => $this->section->getEditorRestricted(),
                'hideTitle' => $this->section->getHideTitle(),
                'hideAuthor' => $this->section->getHideAuthor(),
                'policy' => $this->section->getPolicy(null), // Localized
                'wordCount' => $this->section->getAbstractWordCount(),
            ]);
        }

        parent::initData();
    }

    /**
     * @see Form::validate()
     */
    public function validate($callHooks = true)
    {
        // Validate if it can be inactive
        if ($this->getData('isInactive')) {
            $request = Application::get()->getRequest();
            $context = $request->getContext();
            $sectionId = $this->getSectionId();

            $activeSections = Repo::section()->getCollector()->filterByContextIds([$context->getId()])->excludeInactive()->getMany();
            $otherActiveSections = $activeSections->filter(function ($activeSection) use ($sectionId) {
                return $activeSection->getId() != $sectionId;
            });
            if ($otherActiveSections->count() < 1) {
                $this->addError('isInactive', __('manager.sections.confirmDeactivateSection.error'));
            }
        }

        return parent::validate($callHooks);
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('sectionId', $this->getSectionId());

        $journal = $request->getContext();

        $reviewFormDao = DAORegistry::getDAO('ReviewFormDAO'); /** @var ReviewFormDAO $reviewFormDao */
        $reviewForms = $reviewFormDao->getActiveByAssocId(Application::ASSOC_TYPE_JOURNAL, $journal->getId());
        $reviewFormOptions = [];
        while ($reviewForm = $reviewForms->next()) {
            $reviewFormOptions[$reviewForm->getId()] = $reviewForm->getLocalizedTitle();
        }
        $templateMgr->assign('reviewFormOptions', $reviewFormOptions);

        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        parent::readInputData();
        $this->readUserVars(['abbrev', 'policy', 'reviewFormId', 'identifyType', 'isInactive', 'metaIndexed', 'metaReviewed', 'abstractsNotRequired', 'editorRestricted', 'hideTitle', 'hideAuthor', 'wordCount']);
    }

    /**
     * Get the names of fields for which localized data is allowed.
     *
     * @return array
     */
    public function getLocaleFieldNames()
    {
        return ['title', 'policy', 'abbrev', 'identifyType'];
    }

    /**
     * Save section.
     */
    public function execute(...$functionArgs)
    {
        $request = Application::get()->getRequest();
        $journal = $request->getJournal();

        // Get or create the section object
        if ($this->getSectionId()) {
            $section = Repo::section()->get($this->getSectionId(), $journal->getId());
        } else {
            $section = Repo::section()->newDataObject();
            $section->setContextId($journal->getId());
        }

        // Populate/update the section object from the form
        $section->setTitle($this->getData('title'), null); // Localized
        $section->setAbbrev($this->getData('abbrev'), null); // Localized

        $reviewFormId = $this->getData('reviewFormId');
        if (!$reviewFormId) {
            $reviewFormId = null;
        }

        $section->setReviewFormId($reviewFormId);
        $section->setIsInactive($this->getData('isInactive') ? 1 : 0);
        $section->setMetaIndexed($this->getData('metaIndexed') ? 0 : 1); // #2066: Inverted
        $section->setMetaReviewed($this->getData('metaReviewed') ? 0 : 1); // #2066: Inverted
        $section->setAbstractsNotRequired($this->getData('abstractsNotRequired') ? 1 : 0);
        $section->setIdentifyType($this->getData('identifyType'), null); // Localized
        $section->setEditorRestricted($this->getData('editorRestricted') ? 1 : 0);
        $section->setHideTitle($this->getData('hideTitle') ? 1 : 0);
        $section->setHideAuthor($this->getData('hideAuthor') ? 1 : 0);
        $section->setPolicy($this->getData('policy'), null); // Localized
        $section->setAbstractWordCount((int) $this->getData('wordCount'));

        // Insert or update the section in the DB
        if ($this->getSectionId()) {
            Repo::section()->edit($section, []);
        } else {
            $section->setSequence(REALLY_BIG_NUMBER);
            $sectionId = Repo::section()->add($section);
            $this->setSectionId($sectionId);
            Repo::section()->resequence($journal->getId());
        }

        return parent::execute(...$functionArgs);
    }
}
