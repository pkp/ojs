<?php

/**
 * @file classes/services/ContextService.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ContextService
 *
 * @ingroup services
 *
 * @brief Extends the base context service class with app-specific
 *  requirements.
 */

namespace APP\services;

use APP\article\ArticleTombstoneManager;
use APP\core\Application;
use APP\facades\Repo;
use APP\file\PublicFileManager;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\InstitutionalSubscriptionDAO;
use APP\subscription\SubscriptionTypeDAO;
use PKP\config\Config;
use PKP\db\DAORegistry;
use PKP\file\TemporaryFileManager;
use PKP\plugins\Hook;
use PKP\submission\GenreDAO;

class ContextService extends \PKP\services\PKPContextService
{
    /** @copydoc \PKP\services\PKPContextService::$contextsFileDirName */
    public $contextsFileDirName = 'journals';

    /**
     * Initialize hooks for extending PKPContextService
     */
    public function __construct()
    {
        $this->installFileDirs = [
            Config::getVar('files', 'files_dir') . '/%s/%d',
            Config::getVar('files', 'files_dir') . '/%s/%d/articles',
            Config::getVar('files', 'files_dir') . '/%s/%d/issues',
            Config::getVar('files', 'public_files_dir') . '/%s/%d',
        ];

        Hook::add('Context::add', $this->afterAddContext(...));
        Hook::add('Context::edit', $this->afterEditContext(...));
        Hook::add('Context::delete::before', $this->beforeDeleteContext(...));
        Hook::add('Context::delete', $this->afterDeleteContext(...));
        Hook::add('Context::validate', $this->validateContext(...));
    }

    /**
     * Take additional actions after a new context has been added
     *
     * @param string $hookName
     * @param array $args [
     *
     *		@option Journal The new context
     *		@option Request
     * ]
     */
    public function afterAddContext($hookName, $args)
    {
        $context = $args[0];

        // Create a default section
        $section = Repo::section()->newDataObject();
        $section->setTitle(__('section.default.title'), $context->getPrimaryLocale());
        $section->setAbbrev(__('section.default.abbrev'), $context->getPrimaryLocale());
        $section->setUrlPath(__('section.default.urlPath'), $context->getPrimaryLocale());
        $section->setNotBrowsable(false);
        $section->setMetaIndexed(true);
        $section->setMetaReviewed(true);
        $section->setPolicy(__('section.default.policy'), $context->getPrimaryLocale());
        $section->setEditorRestricted(false);
        $section->setHideTitle(false);
        $section->setContextId($context->getId());
        Repo::section()->add($section);
    }

    /**
     * Update journal-specific settings when a context is edited
     *
     * @param string $hookName
     * @param array $args [
     *
     *		@option Journal The new context
     *		@option Journal The current context
     *		@option array The params to edit
     *		@option Request
     * ]
     */
    public function afterEditContext($hookName, $args)
    {
        $newContext = $args[0];
        $currentContext = $args[1];
        $params = $args[2];
        $request = $args[3];

        // Move an uploaded journal thumbnail and set the updated data
        if (!empty($params['journalThumbnail'])) {
            $supportedLocales = $newContext->getSupportedFormLocales();
            foreach ($supportedLocales as $localeKey) {
                if (!array_key_exists($localeKey, $params['journalThumbnail'])) {
                    continue;
                }
                $localeValue = $this->_saveFileParam(
                    $newContext,
                    $params['journalThumbnail'][$localeKey],
                    'journalThumbnail',
                    $request->getUser()->getId(),
                    $localeKey,
                    true
                );
                $newContext->setData('journalThumbnail', $localeValue, $localeKey);
            }
        }

        // If the context is enabled or disabled, create or delete
        // tombstones for all published submissions
        if ($newContext->getData('enabled') !== $currentContext->getData('enabled')) {
            $articleTombstoneManager = new ArticleTombstoneManager();
            if ($newContext->getData('enabled')) {
                $articleTombstoneManager->deleteTombstonesByContextId($newContext->getId());
            } else {
                $articleTombstoneManager->insertTombstonesByContext($newContext);
            }
        }
    }

    /**
     * Perform actions before a context has been deleted
     *
     * This should only be used in cases where you need the context to still exist
     * in the database to complete the actions. Otherwise, use
     * ContextService::afterDeleteContext().
     *
     * @param string $hookName
     * @param array $args [
     *
     *		@option Context The new context
     *		@option Request
     * ]
     */
    public function beforeDeleteContext($hookName, $args)
    {
        $context = $args[0];

        // Create tombstones for all published submissions
        $articleTombstoneManager = new ArticleTombstoneManager();
        $articleTombstoneManager->insertTombstonesByContext($context);
        /** @var GenreDAO */
        $genreDao = DAORegistry::getDAO('GenreDAO');
        $genreDao->deleteByContextId($context->getId());
    }

    /**
     * Take additional actions after a context has been deleted
     *
     * @param string $hookName
     * @param array $args [
     *
     *		@option Journal The new context
     *		@option Request
     * ]
     */
    public function afterDeleteContext($hookName, $args)
    {
        $context = $args[0];

        Repo::section()->deleteByContextId($context->getId());

        Repo::issue()->deleteByContextId($context->getId());
        /** @var IndividualSubscriptionDAO */
        $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO');
        $subscriptionDao->deleteByJournalId($context->getId());
        /** @var InstitutionalSubscriptionDAO */
        $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO');
        $subscriptionDao->deleteByJournalId($context->getId());
        /** @var SubscriptionTypeDAO */
        $subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');
        $subscriptionTypeDao->deleteByJournal($context->getId());

        Repo::submission()->deleteByContextId($context->getId());

        $publicFileManager = new PublicFileManager();
        $publicFileManager->rmtree($publicFileManager->getContextFilesPath($context->getId()));
    }

    /**
     * Make additional validation checks
     *
     * @param string $hookName
     * @param array $args [
     *
     *		@option Journal The new context
     *		@option Request
     * ]
     */
    public function validateContext($hookName, $args)
    {
        $errors = & $args[0];
        $props = $args[2];
        $allowedLocales = $args[3];

        if (!isset($props['journalThumbnail'])) {
            return;
        }

        // If a journal thumbnail is passed, check that the temporary file exists
        // and the current user owns it
        $user = Application::get()->getRequest()->getUser();
        $userId = $user ? $user->getId() : null;
        $temporaryFileManager = new TemporaryFileManager();
        if (isset($props['journalThumbnail']) && empty($errors['journalThumbnail'])) {
            foreach ($allowedLocales as $localeKey) {
                if (empty($props['journalThumbnail'][$localeKey]) || empty($props['journalThumbnail'][$localeKey]['temporaryFileId'])) {
                    continue;
                }
                if (!$temporaryFileManager->getFile($props['journalThumbnail'][$localeKey]['temporaryFileId'], $userId)) {
                    if (!is_array($errors['journalThumbnail'])) {
                        $errors['journalThumbnail'] = [];
                    }
                    $errors['journalThumbnail'][$localeKey] = [__('common.noTemporaryFile')];
                }
            }
        }
    }
}
