<?php

/**
 * @file pages/information/InformationHandler.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class InformationHandler
 *
 * @ingroup pages_information
 *
 * @brief Display journal information.
 */

namespace APP\pages\information;

use APP\handler\Handler;
use APP\template\TemplateManager;
use PKP\security\authorization\ContextRequiredPolicy;

class InformationHandler extends Handler
{
    /**
     * @see PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $context = $request->getContext();
        if (!$context || !$context->getSetting('restrictSiteAccess')) {
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->setCacheability(TemplateManager::CACHEABILITY_PUBLIC);
        }

        $this->addPolicy(new ContextRequiredPolicy($request));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * Display the information page for the journal.
     *
     * @param array $args
     * @param \APP\core\Request $request
     */
    public function index($args, $request)
    {
        $this->setupTemplate($request);
        $this->validate(null, $request);
        $journal = $request->getJournal();

        switch (array_shift($args)) {
            case 'readers':
                $content = $journal->getLocalizedData('readerInformation');
                $pageTitle = 'navigation.infoForReaders.long';
                break;
            case 'authors':
                $content = $journal->getLocalizedData('authorInformation');
                $pageTitle = 'navigation.infoForAuthors.long';
                break;
            case 'librarians':
                $content = $journal->getLocalizedData('librarianInformation');
                $pageTitle = 'navigation.infoForLibrarians.long';
                break;
            case 'competingInterestGuidelines':
                $content = $journal->getLocalizedData('competingInterestsPolicy');
                $pageTitle = 'navigation.competingInterestGuidelines';
                break;
            case 'sampleCopyrightWording':
                $content = __('manager.setup.copyrightNotice.sample');
                $pageTitle = 'manager.setup.copyrightNotice';
                break;
            default:
                return $request->redirect($journal->getPath());
        }

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign('pageTitle', $pageTitle);
        $templateMgr->assign('content', $content);
        $templateMgr->display('frontend/pages/information.tpl');
    }

    public function readers($args, $request)
    {
        $this->index(['readers'], $request);
    }

    public function authors($args, $request)
    {
        $this->index(['authors'], $request);
    }

    public function librarians($args, $request)
    {
        $this->index(['librarians'], $request);
    }

    public function competingInterestGuidelines($args, $request)
    {
        $this->index(['competingInterestGuidelines'], $request);
    }

    public function sampleCopyrightWording($args, $request)
    {
        $this->index(['sampleCopyrightWording'], $request);
    }

    /**
     * Initialize the template.
     *
     * @param \APP\core\Request $request
     */
    public function setupTemplate($request)
    {
        parent::setupTemplate($request);
        if (!$request->getJournal()->getData('restrictSiteAccess')) {
            $templateMgr = TemplateManager::getManager($request);
            $templateMgr->setCacheability(TemplateManager::CACHEABILITY_PUBLIC);
        }
    }
}
