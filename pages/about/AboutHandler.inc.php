<?php

/**
 * @file pages/about/AboutHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AboutHandler
 * @ingroup pages_about
 *
 * @brief Handle requests for journal about functions.
 */

import('lib.pkp.pages.about.AboutContextHandler');

class AboutHandler extends AboutContextHandler {
	/**
	 * Display about page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptions($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$this->setupTemplate($request);
		$journal = $request->getJournal();
		$subscriptionTypeDao =& DAORegistry::getDAO('SubscriptionTypeDAO');

		if ($journal) {
			$paymentManager = \Application::getPaymentManager($journal);
			if (!($journal->getSetting('paymentsEnabled') && $paymentManager->isConfigured())) {
				$request->redirect(null, 'index');
			}
		}

		$templateMgr->assign(array(
			'subscriptionAdditionalInformation' => $journal->getLocalizedSetting('subscriptionAdditionalInformation'),
			'subscriptionMailingAddress' => $journal->getSetting('subscriptionMailingAddress'),
			'subscriptionName' => $journal->getSetting('subscriptionName'),
			'subscriptionPhone' => $journal->getSetting('subscriptionPhone'),
			'subscriptionEmail' => $journal->getSetting('subscriptionEmail'),
			'individualSubscriptionTypes' => $subscriptionTypeDao->getByInstitutional($journal->getId(), false, false),
			'institutionalSubscriptionTypes' => $subscriptionTypeDao->getByInstitutional($journal->getId(), true, false),
		));
		$templateMgr->display('frontend/pages/subscriptions.tpl');
	}
}

?>
