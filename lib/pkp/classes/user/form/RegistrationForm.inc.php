<?php
/**
 * @defgroup user_form User Forms
 */

/**
 * @file classes/user/form/RegistrationForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class RegistrationForm
 * @ingroup user_form
 *
 * @brief Form for user registration.
 */

import('lib.pkp.classes.form.Form');

class RegistrationForm extends Form {

	/** @var boolean user is already registered with another context */
	var $existingUser;

	/** @var AuthPlugin default authentication source, if specified */
	var $defaultAuth;

	/** @var boolean whether or not captcha is enabled for this form */
	var $captchaEnabled;

	/**
	 * Constructor.
	 */
	function __construct($site) {
		parent::__construct('frontend/pages/userRegister.tpl');

		// Validation checks for this form
		$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByUsername'), array(), true));
		$this->addCheck(new FormValidator($this, 'username', 'required', 'user.profile.form.usernameRequired'));
		$this->addCheck(new FormValidator($this, 'password', 'required', 'user.profile.form.passwordRequired'));
		$this->addCheck(new FormValidatorUsername($this, 'username', 'required', 'user.register.form.usernameAlphaNumeric'));
		$this->addCheck(new FormValidatorLength($this, 'password', 'required', 'user.register.form.passwordLengthRestriction', '>=', $site->getMinPasswordLength()));
		$this->addCheck(new FormValidatorCustom($this, 'password', 'required', 'user.register.form.passwordsDoNotMatch', create_function('$password,$form', 'return $password == $form->getData(\'password2\');'), array(&$this)));

		$this->addCheck(new FormValidator($this, 'firstName', 'required', 'user.profile.form.firstNameRequired'));
		$this->addCheck(new FormValidator($this, 'lastName', 'required', 'user.profile.form.lastNameRequired'));
		$this->addCheck(new FormValidator($this, 'country', 'required', 'user.profile.form.countryRequired'));

		// Email checks
		$this->addCheck(new FormValidatorEmail($this, 'email', 'required', 'user.profile.form.emailRequired'));
		$this->addCheck(new FormValidatorCustom($this, 'email', 'required', 'user.register.form.emailExists', array(DAORegistry::getDAO('UserDAO'), 'userExistsByEmail'), array(), true));

		$this->captchaEnabled = Config::getVar('captcha', 'captcha_on_register') && Config::getVar('captcha', 'recaptcha');
		if ($this->captchaEnabled) {
			$this->addCheck(new FormValidatorReCaptcha($this, Request::getRemoteAddr(), 'common.captcha.error.invalid-input-response'));
		}

		$authDao = DAORegistry::getDAO('AuthSourceDAO');
		$this->defaultAuth = $authDao->getDefaultPlugin();
		if (isset($this->defaultAuth)) {
			$this->addCheck(new FormValidatorCustom($this, 'username', 'required', 'user.register.form.usernameExists', create_function('$username,$form,$auth', 'return (!$auth->userExists($username) || $auth->authenticate($username, $form->getData(\'password\')));'), array(&$this, $this->defaultAuth)));
		}

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$site = $request->getSite();
		$context = $request->getContext();

		if ($this->captchaEnabled) {
			$publicKey = Config::getVar('captcha', 'recaptcha_public_key');
			$reCaptchaHtml = '<div class="g-recaptcha" data-sitekey="' . $publicKey . '"></div>';
			$templateMgr->assign(array(
				'reCaptchaHtml' => $reCaptchaHtml,
				'captchaEnabled' => true,
			));
		}

		$countryDao = DAORegistry::getDAO('CountryDAO');
		$countries = $countryDao->getCountries();
		$templateMgr->assign('countries', $countries);

		$userDao = DAORegistry::getDAO('UserDAO');
		$templateMgr->assign('genderOptions', $userDao->getGenderOptions());

		$site = $request->getSite();
		$templateMgr->assign('availableLocales', $site->getSupportedLocaleNames());

		import('lib.pkp.classes.user.form.UserFormHelper');
		$userFormHelper = new UserFormHelper();
		$userFormHelper->assignRoleContent($templateMgr, $request);

		$templateMgr->assign(array(
			'source' =>$request->getUserVar('source'),
			'minPasswordLength' => $site->getMinPasswordLength(),
		));

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::initData()
	 * @param $request Request
	 */
	function initData($request) {
		$userGroupIds = array();

		// If a context exists, opt the user into reader and author roles in
		// that context by default.
		if ($request->getContext()) {
			$context = $request->getContext();
			$userGroupDao = DAORegistry::getDAO('UserGroupDAO');

			$readerUserGroups = $userGroupDao->getByRoleId($context->getId(), ROLE_ID_READER);
			while ($userGroup = $readerUserGroups->next()) {
				if ($userGroup->getPermitSelfRegistration()) $userGroupIds[] = $userGroup->getId();
			}

			$authorUserGroups = $userGroupDao->getByRoleId($context->getId(), ROLE_ID_AUTHOR);
			while ($userGroup = $authorUserGroups->next()) {
				if ($userGroup->getPermitSelfRegistration()) $userGroupIds[] = $userGroup->getId();
			}
		}

		$this->_data = array(
			'userLocales' => array(),
			'userGroupIds' => $userGroupIds,
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();

		$this->readUserVars(array(
			'username',
			'password',
			'password2',
			'firstName',
			'middleName',
			'lastName',
			'affiliation',
			'email',
			'country',
			'interests',
			'reviewerGroup',
			'authorGroup',
			'readerGroup',
		));

		if ($this->captchaEnabled) {
			$this->readUserVars(array(
				'g-recaptcha-response',
			));
		}

		// Collect the specified user group IDs into a single piece of data
		$this->setData('userGroupIds', array_merge(
			array_keys((array) $this->getData('reviewerGroup')),
			array_keys((array) $this->getData('authorGroup')),
			array_keys((array) $this->getData('readerGroup'))
		));
	}

	/**
	 * Validate the form
	 */
	function validate() {
		if (	count((array) $this->getData('reviewerGroup')) == 0 &&
			count((array) $this->getData('authorGroup')) == 0 &&
			count((array) $this->getData('readerGroup')) == 0
		) {
			$this->addError('userGroups', __('user.register.form.userGroupRequired'));
		}

		return parent::validate();
	}

	/**
	 * Register a new user.
	 * @param $request PKPRequest
	 * @return int|null User ID, or false on failure
	 */
	function execute($request) {
		$requireValidation = Config::getVar('email', 'require_validation');
		$userDao = DAORegistry::getDAO('UserDAO');

		// New user
		$user = $userDao->newDataObject();

		$user->setUsername($this->getData('username'));

		// Set the base user fields (name, etc.)
		$user->setFirstName($this->getData('firstName'));
		$user->setMiddleName($this->getData('middleName'));
		$user->setLastName($this->getData('lastName'));
		$user->setInitials($this->getData('initials'));
		$user->setEmail($this->getData('email'));
		$user->setCountry($this->getData('country'));
		$user->setAffiliation($this->getData('affiliation'), null); // Localized

		$user->setDateRegistered(Core::getCurrentDate());
		$user->setInlineHelp(1); // default new users to having inline help visible.

		if (isset($this->defaultAuth)) {
			$user->setPassword($this->getData('password'));
			// FIXME Check result and handle failures
			$this->defaultAuth->doCreateUser($user);
			$user->setAuthId($this->defaultAuth->authId);
		}
		$user->setPassword(Validation::encryptCredentials($this->getData('username'), $this->getData('password')));

		if ($requireValidation) {
			// The account should be created in a disabled
			// state.
			$user->setDisabled(true);
			$user->setDisabledReason(__('user.login.accountNotValidated', array('email' => $this->getData('email'))));
		}

		parent::execute($user);

		$userDao->insertObject($user);
		$userId = $user->getId();
		if (!$userId) {
			return false;
		}

		// Associate the new user with the existing session
		$sessionManager = SessionManager::getManager();
		$session = $sessionManager->getUserSession();
		$session->setSessionVar('username', $user->getUsername());

		// Save the roles
		import('lib.pkp.classes.user.form.UserFormHelper');
		$userFormHelper = new UserFormHelper();
		$userFormHelper->saveRoleContent($this, $user);

		// Insert the user interests
		import('lib.pkp.classes.user.InterestManager');
		$interestManager = new InterestManager();
		$interestManager->setInterestsForUser($user, $this->getData('interests'));

		import('lib.pkp.classes.mail.MailTemplate');
		if ($requireValidation) {
			// Create an access key
			import('lib.pkp.classes.security.AccessKeyManager');
			$accessKeyManager = new AccessKeyManager();
			$accessKey = $accessKeyManager->createKey('RegisterContext', $user->getId(), null, Config::getVar('email', 'validation_timeout'));

			// Send email validation request to user
			$mail = new MailTemplate('USER_VALIDATE');
			$this->_setMailFrom($request, $mail);
			$context = $request->getContext();
			$mail->assignParams(array(
				'userFullName' => $user->getFullName(),
				'activateUrl' => $request->url($context->getPath(), 'user', 'activateUser', array($this->getData('username'), $accessKey))
			));
			$mail->addRecipient($user->getEmail(), $user->getFullName());
			$mail->send();
			unset($mail);
		}
		return $userId;
	}

	/**
	 * Set mail from address
	 * @param $request PKPRequest
	 * @param $mail MailTemplate
	 */
	function _setMailFrom($request, $mail) {
		$site = $request->getSite();
		$context = $request->getContext();

		// Set the sender based on the current context
		if ($context && $context->getSetting('supportEmail')) {
			$mail->setReplyTo($context->getSetting('supportEmail'), $context->getSetting('supportName'));
		} else {
			$mail->setReplyTo($site->getLocalizedContactEmail(), $site->getLocalizedContactName());
		}
	}
}

?>
