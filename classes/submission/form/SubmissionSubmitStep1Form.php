<?php

/**
 * @file classes/submission/form/SubmissionSubmitStep1Form.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubmissionSubmitStep1Form
 * @ingroup submission_form
 *
 * @brief Form for Step 1 of author submission.
 */

namespace APP\submission\form;

use APP\core\Application;
use APP\template\TemplateManager;
use PKP\core\PKPString;
use PKP\db\DAORegistry;

use PKP\security\Role;
use PKP\submission\form\PKPSubmissionSubmitStep1Form;

class SubmissionSubmitStep1Form extends PKPSubmissionSubmitStep1Form
{
    /**
     * Constructor.
     *
     * @param null|mixed $submission
     */
    public function __construct($context, $submission = null)
    {
        parent::__construct($context, $submission);
        $this->addCheck(new \PKP\form\validation\FormValidatorCustom($this, 'sectionId', 'required', 'author.submit.form.sectionRequired', [DAORegistry::getDAO('SectionDAO'), 'sectionExists'], [$context->getId()]));
    }

    /**
     * @copydoc SubmissionSubmitForm::fetch
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $roleDao = DAORegistry::getDAO('RoleDAO'); /** @var RoleDAO $roleDao */
        $user = $request->getUser();
        $canSubmitAll = $roleDao->userHasRole($this->context->getId(), $user->getId(), Role::ROLE_ID_MANAGER) ||
            $roleDao->userHasRole($this->context->getId(), $user->getId(), Role::ROLE_ID_SUB_EDITOR) ||
            $roleDao->userHasRole(Application::CONTEXT_SITE, $user->getId(), Role::ROLE_ID_SITE_ADMIN);

        // Get section options for this context
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $sections = [];
        $sectionsIterator = $sectionDao->getByContextId($this->context->getId(), null, !$canSubmitAll);
        while ($section = $sectionsIterator->next()) {
            if (!$section->getIsInactive()) {
                $sections[$section->getId()] = $section->getLocalizedTitle();
            }
        }
        $sectionOptions = ['0' => ''] + $sections;

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('sectionOptions', $sectionOptions);
        $templateMgr->assign('sectionId', $request->getUserVar('sectionId'));

        // Get section policies for this context
        $sectionPolicies = [];
        foreach ($sectionOptions as $sectionId => $sectionTitle) {
            $section = $sectionDao->getById($sectionId);

            $sectionPolicy = $section ? $section->getLocalizedPolicy() : null;
            if ($this->doesSectionPolicyContainAnyText($sectionPolicy)) {
                $sectionPolicies[$sectionId] = $sectionPolicy;
            }
        }

        $templateMgr->assign('sectionPolicies', $sectionPolicies);

        return parent::fetch($request, $template, $display);
    }

    /**
     * Checks whether a section policy contains any text (plain / readable).
     */
    private function doesSectionPolicyContainAnyText($sectionPolicy)
    {
        $sectionPolicyPlainText = trim(PKPString::html2text($sectionPolicy));
        return strlen($sectionPolicyPlainText) > 0;
    }

    /**
     * @copydoc PKPSubmissionSubmitStep1Form::initData
     */
    public function initData($data = [])
    {
        if (isset($this->submission)) {
            parent::initData([
                'sectionId' => $this->submission->getCurrentPublication()->getData('sectionId'),
            ]);
        } else {
            parent::initData();
        }
    }

    /**
     * Assign form data to user-submitted data.
     */
    public function readInputData()
    {
        $this->readUserVars([
            'sectionId',
        ]);
        parent::readInputData();
    }

    /**
     * Perform additional validation checks
     *
     * @copydoc Form::validate
     */
    public function validate($callHooks = true)
    {
        if (!parent::validate($callHooks)) {
            return false;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $sectionDao = DAORegistry::getDAO('SectionDAO'); /** @var SectionDAO $sectionDao */
        $section = $sectionDao->getById($this->getData('sectionId'), $context->getId());

        // Validate that the section ID is attached to this journal.
        if (!$section) {
            return false;
        }

        // Ensure that submissions are enabled and the assigned section is activated
        if ($context->getData('disableSubmissions') || $section->getIsInactive()) {
            return false;
        }

        return true;
    }

    /**
     * Set the publication data from the form.
     *
     * @param Publication $publication
     * @param Submission $submission
     */
    public function setPublicationData($publication, $submission)
    {
        $publication->setData('sectionId', $this->getData('sectionId'));
        parent::setPublicationData($publication, $submission);
    }
}

if (!PKP_STRICT_MODE) {
    class_alias('\APP\submission\form\SubmissionSubmitStep1Form', '\SubmissionSubmitStep1Form');
}
