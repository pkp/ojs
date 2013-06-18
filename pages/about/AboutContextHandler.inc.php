<?php

/**
 * @file pages/about/AboutContextHandler.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AboutContextHandler
 * @ingroup pages_about
 *
 * @brief Handle requests for context-level about functions.
 */

import('classes.handler.Handler');

class AboutContextHandler extends Handler {
	/**
	 * Constructor
	 */
	function AboutContextHandler() {
		parent::Handler();
		AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON);
	}

	/**
	 * @see PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		$context = $request->getContext();
		if (!$context || !$context->getSetting('restrictSiteAccess')) {
			$templateMgr = TemplateManager::getManager($request);
			$templateMgr->setCacheability(CACHEABILITY_PUBLIC);
		}

		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * Display contact page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function contact($args, $request) {
		$settingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('contextSettings', $settingsDao->getSettings($context->getId()));
		$templateMgr->display('about/contact.tpl');
	}

	/**
	 * Display description page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function description($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('about/description.tpl');
	}

	/**
	 * Display sponsorship page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function sponsorship($args, $request) {
		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('contributorNote', $context->getLocalizedSetting('contributorNote'));
		$templateMgr->assign('contributors', $context->getSetting('contributors'));
		$templateMgr->assign('sponsorNote', $context->getLocalizedSetting('sponsorNote'));
		$templateMgr->assign('sponsors', $context->getSetting('sponsors'));
		$templateMgr->display('about/sponsorship.tpl');
	}

	/**
	 * Display editorialTeam page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorialTeam($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('about/editorialTeam.tpl');
	}

	/**
	 * Display subscriptions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptions($args, $request) {
		$journalDao = DAORegistry::getDAO('JournalSettingsDAO');
		$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$subscriptionTypeDao = DAORegistry::getDAO('SubscriptionTypeDAO');

		$journal = $request->getJournal();
		$journalId = $journal->getId();

		$subscriptionName = $journalSettingsDao->getSetting($journalId, 'subscriptionName');
		$subscriptionEmail = $journalSettingsDao->getSetting($journalId, 'subscriptionEmail');
		$subscriptionPhone = $journalSettingsDao->getSetting($journalId, 'subscriptionPhone');
		$subscriptionFax = $journalSettingsDao->getSetting($journalId, 'subscriptionFax');
		$subscriptionMailingAddress =& $journalSettingsDao->getSetting($journalId, 'subscriptionMailingAddress');
		$subscriptionAdditionalInformation = $journal->getLocalizedSetting('subscriptionAdditionalInformation');
		$individualSubscriptionTypes = $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, false, false);
		$institutionalSubscriptionTypes = $subscriptionTypeDao->getSubscriptionTypesByInstitutional($journalId, true, false);

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);
		$acceptGiftSubscriptionPayments = $paymentManager->acceptGiftSubscriptionPayments();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('subscriptionName', $subscriptionName);
		$templateMgr->assign('subscriptionEmail', $subscriptionEmail);
		$templateMgr->assign('subscriptionPhone', $subscriptionPhone);
		$templateMgr->assign('subscriptionFax', $subscriptionFax);
		$templateMgr->assign('subscriptionMailingAddress', $subscriptionMailingAddress);
		$templateMgr->assign('subscriptionAdditionalInformation', $subscriptionAdditionalInformation);
		$templateMgr->assign('acceptGiftSubscriptionPayments', $acceptGiftSubscriptionPayments);
		$templateMgr->assign('individualSubscriptionTypes', $individualSubscriptionTypes);
		$templateMgr->assign('institutionalSubscriptionTypes', $institutionalSubscriptionTypes);

		$templateMgr->display('about/subscriptions.tpl');
	}

	/**
	 * Display subscriptions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function memberships($args, $request) {
		$journal = $request->getJournal();
		$journalId = $journal->getId();

		import('classes.payment.ojs.OJSPaymentManager');
		$paymentManager = new OJSPaymentManager($request);

		$membershipEnabled = $paymentManager->membershipEnabled();

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('membershipEnabled', $membershipEnabled);
		if ($membershipEnabled) {
			$templateMgr->assign('membershipFee', $journal->getSetting('membershipFee'));
			$templateMgr->assign('currency', $journal->getSetting('currency'));
			$templateMgr->assign('membershipFeeName', $journal->getLocalizedSetting('membershipFeeName'));
			$templateMgr->assign('membershipFeeDescription', $journal->getLocalizedSetting('membershipFeeDescription'));
			$templateMgr->display('about/memberships.tpl');
			return;
		}
		$request->redirect(null, 'about');
	}

	/**
	 * Display editorialPolicies page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function editorialPolicies($args, $request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->display('about/editorialPolicies.tpl');
	}

	/**
	 * Display submissions page.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function submissions($args, $request) {
		$settingsDao = DAORegistry::getDAO('JournalSettingsDAO');
		$context = $request->getContext();
		$templateMgr = TemplateManager::getManager($request);
		$submissionChecklist = $context->getLocalizedSetting('submissionChecklist');
		if (!empty($submissionChecklist)) {
			ksort($submissionChecklist);
			reset($submissionChecklist);
		}
		$templateMgr->assign('submissionChecklist', $submissionChecklist);
		$templateMgr->display('about/submissions.tpl');
	}
}

?>
