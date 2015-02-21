<?php

/**
 * @file pages/subscriptionManager/SubscriptionManagerHandler.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionManagerHandler
 * @ingroup pages_subscriptionManager
 *
 * @brief Handle requests for subscription management functions.
 */

import('classes.handler.Handler');

class SubscriptionManagerHandler extends Handler {
	/**
	 * Constructor
	 **/
	function SubscriptionManagerHandler() {
		parent::Handler();
		$this->addCheck(new HandlerValidatorJournal($this));
		$this->addCheck(new HandlerValidatorRoles($this, true, null, null, array(ROLE_ID_SUBSCRIPTION_MANAGER)));
	}

	function index() {
		$this->subscriptionsSummary();
	}

	/**
	 * Display subscriptions summary page for the current journal.
	 */
	function subscriptionsSummary() {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionsSummary();
	}

	/**
	 * Display a list of subscriptions for the current journal.
	 */
	function subscriptions($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
			} else {
				$institutional = true;
			}
		} else {
			Request::redirect(null, 'subscriptionManager');
		}

		$this->validate();
		$this->setupTemplate();

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptions($institutional);
	}

	/**
	 * Delete a subscription.
	 * @param $args array first parameter is the ID of the subscription to delete
	 */
	function deleteSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'subscriptionManager');
		}

		$this->validate();
		$this->setupTemplate();

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::deleteSubscription($args, $institutional);

		Request::redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Renew a subscription.
	 * @param $args array first parameter is the ID of the subscription to renew
	 */
	function renewSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'subscriptionManager');
		}

		$this->validate();
		$this->setupTemplate();

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::renewSubscription($args, $institutional);

		Request::redirect(null, null, 'subscriptions', $redirect);
	}

	/**
	 * Display form to edit a subscription.
	 * @param $args array optional, first parameter is the ID of the subscription to edit
	 */
	function editSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'subscriptionManager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		$editSuccess = SubscriptionAction::editSubscription($args, $institutional);

		if (!$editSuccess) {
			Request::redirect(null, null, 'subscriptions', $redirect);
		}
	}

	/**
	 * Display form to create new subscription.
	 */
	function createSubscription($args) {
		$this->editSubscription($args);
	}

	/**
	 * Display a list of users from which to choose a subscriber.
	 */
	function selectSubscriber($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'subscriptionManager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::selectSubscriber($args, $institutional);
	}

	/**
	 * Save changes to a subscription.
	 */
	function updateSubscription($args) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'subscriptionManager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		import('classes.subscription.SubscriptionAction');
		$updateSuccess = SubscriptionAction::updateSubscription($args, $institutional);

		if ($updateSuccess && Request::getUserVar('createAnother')) {
			Request::redirect(null, null, 'selectSubscriber', $redirect);
		} elseif ($updateSuccess) {
			Request::redirect(null, null, 'subscriptions', $redirect);
		}
	}

	/**
	 * Reset a subscription reminder date.
	 */
	function resetDateReminded($args, &$request) {
		if (isset($args) && !empty($args)) {
			if ($args[0] == 'individual') {
				$institutional  = false;
				$redirect = 'individual';
			} else {
				$institutional = true;
				$redirect = 'institutional';
			}
		} else {
			Request::redirect(null, 'subscriptionManager');
		}

		$this->validate();
		$this->setupTemplate(true, $institutional);

		array_shift($args);
		$subscriptionId = (int) $args[0];
		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::resetDateReminded($args, $institutional);

		Request::redirect(null, null, 'editSubscription', array($redirect, $subscriptionId));
	}
	/**
	 * Display a list of subscription types for the current journal.
	 */
	function subscriptionTypes() {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->addJavaScript('lib/pkp/js/lib/jquery/plugins/jquery.tablednd.js');
		$templateMgr->addJavaScript('lib/pkp/js/functions/tablednd.js');

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionTypes();
	}

	/**
	 * Rearrange the order of subscription types.
	 */
	function moveSubscriptionType($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::moveSubscriptionType($args);

		Request::redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Delete a subscription type.
	 * @param $args array first parameter is the ID of the subscription type to delete
	 */
	function deleteSubscriptionType($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::deleteSubscriptionType($args);

		Request::redirect(null, null, 'subscriptionTypes');
	}

	/**
	 * Display form to edit a subscription type.
	 * @param $args array optional, first parameter is the ID of the subscription type to edit
	 */
	function editSubscriptionType($args = array()) {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, 'subscriptionManager', 'subscriptionTypes'), 'subscriptionManager.subscriptionTypes'));

		import('classes.subscription.SubscriptionAction');
		$editSuccess = SubscriptionAction::editSubscriptionType($args);

		if (!$editSuccess) {
			Request::redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display form to create new subscription type.
	 */
	function createSubscriptionType() {
		$this->editSubscriptionType();
	}

	/**
	 * Save changes to a subscription type.
	 */
	function updateSubscriptionType() {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->append('pageHierarchy', array(Request::url(null, 'subscriptionManager', 'subscriptionTypes'), 'subscriptionManager.subscriptionTypes'));

		import('classes.subscription.SubscriptionAction');
		$updateSuccess = SubscriptionAction::updateSubscriptionType();

		if ($updateSuccess && Request::getUserVar('createAnother')) {
			Request::redirect(null, null, 'createSubscriptionType', null, array('subscriptionTypeCreated' => 1));
		} elseif ($updateSuccess) {
			Request::redirect(null, null, 'subscriptionTypes');
		}
	}

	/**
	 * Display subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function subscriptionPolicies($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::subscriptionPolicies($args, $request);
	}

	/**
	 * Save subscription policies for the current journal.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function saveSubscriptionPolicies($args, &$request) {
		$this->validate();
		$this->setupTemplate();

		import('classes.subscription.SubscriptionAction');
		SubscriptionAction::saveSubscriptionPolicies($args, $request);
	}

	/**
	 * Display form to create a user profile.
	 * @param $args array optional
	 */
	function createUser($args = array()) {
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();

		$templateMgr =& TemplateManager::getManager();

		import('classes.manager.form.UserManagementForm');

		$templateMgr->assign('currentUrl', Request::url(null, null, 'createUser'));
		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$userForm = new UserManagementForm();
		} else {
			$userForm =& new UserManagementForm();
		}
		if ($userForm->isLocaleResubmit()) {
			$userForm->readInputData();
		} else {
			$userForm->initData();
		}
		$userForm->display();
	}

	/**
	 * Save changes to a user profile.
	 */
	function updateUser() {
		$this->validate();
		$this->setupTemplate(true);

		$journal =& Request::getJournal();

		import('classes.manager.form.UserManagementForm');

		if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
			$userForm = new UserManagementForm();
		} else {
			$userForm =& new UserManagementForm();
		}
		$userForm->readInputData();

		if ($userForm->validate()) {
			$userForm->execute();

			if (Request::getUserVar('createAnother')) {
				$this->setupTemplate(true);
				$templateMgr =& TemplateManager::getManager();
				$templateMgr->assign('currentUrl', Request::url(null, null, 'index'));
				$templateMgr->assign('userCreated', true);
				if (checkPhpVersion('5.0.0')) { // WARNING: This form needs $this in constructor
					$userForm = new UserManagementForm();
				} else {
					$userForm =& new UserManagementForm();
				}
				$userForm->initData();
				$userForm->display();

			} else {
				$source = Request::getUserVar('source');
				if (isset($source) && !empty($source)) {
					Request::redirectUrl($source);
				} else {
					Request::redirect(null, null, 'selectSubscriber');
				}
			}

		} else {
			$userForm->display();
		}
	}

	/**
	 * Display payments settings form
	 */
	function payments($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::payments($args);
	}

	/**
	 * Execute the payments form or display it again if there are problems
	 */
	function savePaymentSettings($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.payment.ojs.OJSPaymentAction');
		$success = OJSPaymentAction::savePaymentSettings($args);

		if ($success) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'payments'),
				'pageTitle' => 'manager.payment.feePaymentOptions',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, 'payments'),
				'backLinkLabel' => 'manager.payment.feePaymentOptions'
			));
			$templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Display all payments previously made
	 */
	function viewPayments($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayments($args);
	}

	/**
	 * Display a single Completed payment
	 */
	function viewPayment($args) {
		$this->validate();
		$this->setupTemplate();

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::viewPayment($args);
	}

	/**
	* Display form to edit program settings.
	*/
	function payMethodSettings() {
		$this->validate();
		$this->setupTemplate();

		import('classes.payment.ojs.OJSPaymentAction');
		OJSPaymentAction::payMethodSettings();
	}

	/**
	 * Save changes to payment settings.
	 */
	function savePayMethodSettings() {
		$this->validate();
		$this->setupTemplate();

		import('classes.payment.ojs.OJSPaymentAction');
		$success = OJSPaymentAction::savePayMethodSettings();

		if ($success) {
			$templateMgr =& TemplateManager::getManager();
			$templateMgr->assign(array(
				'currentUrl' => Request::url(null, null, 'payMethodSettings'),
				'pageTitle' => 'manager.payment.paymentMethods',
				'message' => 'common.changesSaved',
				'backLink' => Request::url(null, null, 'payMethodSettings'),
				'backLinkLabel' => 'manager.payment.paymentMethods'
			));
			$templateMgr->display('common/message.tpl');
		}
	}

	/**
	 * Get a suggested username, making sure it's not
	 * already used by the system. (Poor-man's AJAX.)
	 */
	function suggestUsername() {
		$this->validate();
		$suggestion = Validation::suggestUsername(
			Request::getUserVar('firstName'),
			Request::getUserVar('lastName')
		);
		echo $suggestion;
	}

	/**
	 * Display a user's profile.
	 * @param $args array first parameter is the ID or username of the user to display
	 */
	function userProfile($args) {
		$this->validate();
		$this->setupTemplate();

		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('currentUrl', Request::url(null, null, 'viewPayments'));
		$templateMgr->assign('helpTopicId', 'journal.managementPages.payments');

		$userDao =& DAORegistry::getDAO('UserDAO');
		$userId = isset($args[0]) ? $args[0] : 0;
		if (is_numeric($userId)) {
			$userId = (int) $userId;
			$user = $userDao->getById($userId);
		} else {
			$user = $userDao->getByUsername($userId);
		}

		if ($user == null) {
			// Non-existent user requested
			$templateMgr->assign('pageTitle', 'user.profile');
			$templateMgr->assign('errorMsg', 'manager.people.invalidUser');
			$templateMgr->assign('backLink', Request::url(null, null, 'viewPayments'));
			$templateMgr->assign('backLinkLabel', 'manager.payment.feePaymentOptions');
			$templateMgr->display('common/error.tpl');
		} else {
			$site =& Request::getSite();
			$journal =& Request::getJournal();

			$countryDao =& DAORegistry::getDAO('CountryDAO');
			$country = null;
			if ($user->getCountry() != '') {
				$country = $countryDao->getCountry($user->getCountry());
			}
			$templateMgr->assign('country', $country);

			$templateMgr->assign('userInterests', $user->getInterestString());

			$templateMgr->assign_by_ref('user', $user);
			$templateMgr->assign('localeNames', AppLocale::getAllLocales());
			$templateMgr->display('subscription/userProfile.tpl');
		}
	}

	/**
	 * Setup common template variables.
	 */
	function setupTemplate($subclass = false, $institutional = false) {
		parent::setupTemplate(true);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_OJS_MANAGER);
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('pageHierarchy', array(array(Request::url(null, 'user'), 'navigation.user'), array(Request::url(null, 'subscriptionManager'), 'subscriptionManager.subscriptionManagement')));
		if ($subclass) {
			if ($institutional) {
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'subscriptionManager', 'subscriptions', 'institutional'), 'subscriptionManager.institutionalSubscriptions'));
			} else {
				$templateMgr->append('pageHierarchy', array(Request::url(null, 'subscriptionManager', 'subscriptions', 'individual'), 'subscriptionManager.individualSubscriptions'));
			}
		}
	}
}

?>
