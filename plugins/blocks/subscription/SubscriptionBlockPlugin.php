<?php

/**
 * @file plugins/blocks/subscription/SubscriptionBlockPlugin.php
 *
 * Copyright (c) 2013-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionBlockPlugin
 *
 * @brief Class for subscription block plugin
 *
 */

namespace APP\plugins\blocks\subscription;

use APP\core\Application;
use APP\facades\Repo;
use APP\subscription\IndividualSubscriptionDAO;
use APP\subscription\InstitutionalSubscriptionDAO;
use PKP\db\DAORegistry;
use PKP\plugins\BlockPlugin;

class SubscriptionBlockPlugin extends BlockPlugin
{
    /**
     * Install default settings on journal creation.
     *
     * @return string
     */
    public function getContextSpecificPluginSettingsFile()
    {
        return $this->getPluginPath() . '/settings.xml';
    }

    /**
     * Get the display name of this plugin.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.block.subscription.displayName');
    }

    /**
     * Get a description of the plugin.
     */
    public function getDescription()
    {
        return __('plugins.block.subscription.description');
    }

    /**
     * Get the HTML contents for this block.
     *
     * @param \APP\template\TemplateManager $templateMgr
     * @param \APP\core\Request $request
     *
     * @return string
     */
    public function getContents($templateMgr, $request = null)
    {
        $journal = $request->getContext();
        if (!$journal) {
            return '';
        }

        if ($journal->getData('publishingMode') != \APP\journal\Journal::PUBLISHING_MODE_SUBSCRIPTION) {
            return '';
        }

        $user = $request->getUser();
        $userId = ($user) ? $user->getId() : null;
        $templateMgr->assign('userLoggedIn', isset($userId) ? true : false);

        if (isset($userId)) {
            $subscriptionDao = DAORegistry::getDAO('IndividualSubscriptionDAO'); /** @var IndividualSubscriptionDAO $subscriptionDao */
            $individualSubscription = $subscriptionDao->getByUserIdForJournal($userId, $journal->getId());
            $templateMgr->assign('individualSubscription', $individualSubscription);
        }

        // If no individual subscription or if not valid, check for institutional subscription
        if (!isset($individualSubscription) || !$individualSubscription->isValid()) {
            $ip = $request->getRemoteAddr();
            $domain = $request->getRemoteDomain();
            $subscriptionDao = DAORegistry::getDAO('InstitutionalSubscriptionDAO'); /** @var InstitutionalSubscriptionDAO $subscriptionDao */
            $subscriptionId = $subscriptionDao->isValidInstitutionalSubscription($domain, $ip, $journal->getId());
            if ($subscriptionId) {
                $institutionalSubscription = $subscriptionDao->getById($subscriptionId);
                $institution = Repo::institution()->get($institutionalSubscription->getInstitutionId());
                $templateMgr->assign([
                    'institutionalSubscription' => $institutionalSubscription,
                    'institution' => $institution,
                    'userIP' => $ip,
                ]);
            }
        }

        $paymentManager = Application::get()->getPaymentManager($journal);

        if (isset($individualSubscription) || isset($institutionalSubscription)) {
            $templateMgr->assign('acceptSubscriptionPayments', $paymentManager->isConfigured());
        }

        return parent::getContents($templateMgr, $request);
    }
}
