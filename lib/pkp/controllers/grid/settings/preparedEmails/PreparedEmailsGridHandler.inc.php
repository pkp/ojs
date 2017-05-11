<?php

/**
 * @file controllers/grid/settings/preparedEmails/PreparedEmailsGridHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreparedEmailsGridHandler
 * @ingroup controllers_grid_settings_preparedEmails
 *
 * @brief Handle preparedEmails grid requests.
 */

// Import grid base classes
import('lib.pkp.classes.controllers.grid.GridHandler');

// Import classes specific to this grid handler
import('lib.pkp.controllers.grid.settings.preparedEmails.PreparedEmailsGridRow');

class PreparedEmailsGridHandler extends GridHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		$this->addRoleAssignment(
			array(ROLE_ID_MANAGER),
			array(
				'fetchRow', 'fetchGrid', 'addPreparedEmail', 'editPreparedEmail',
				'updatePreparedEmail', 'resetEmail', 'resetAllEmails',
				'disableEmail', 'enableEmail', 'deleteCustomEmail'
			)
		);
		parent::__construct();
	}

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, $args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
		$this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @copydoc GridHandler::initialize()
	 */
	function initialize($request, $args = null) {
		parent::initialize($request, $args);
		// Basic grid configuration
		$this->setId('preparedEmailsGrid');

		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_PKP_USER);

		// Set the grid title.
		$this->setTitle('grid.preparedEmails.title');

		// Grid actions
		import('lib.pkp.controllers.grid.settings.preparedEmails.linkAction.EditEmailLinkAction');
		$addEmailLinkAction = new EditEmailLinkAction($request);
		$this->addAction($addEmailLinkAction);

		import('lib.pkp.classes.linkAction.LinkAction');
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$router = $request->getRouter();
		$this->addAction(
			new LinkAction(
				'resetAll',
				new RemoteActionConfirmationModal(
					$request->getSession(),
					__('manager.emails.resetAll.message'), null,
					$router->url($request, null,
						'grid.settings.preparedEmails.PreparedEmailsGridHandler', 'resetAllEmails')
				),
				__('manager.emails.resetAll'),
				'reset_default'
			)
		);


		// Columns
		import('lib.pkp.controllers.grid.settings.preparedEmails.PreparedEmailsGridCellProvider');
		$cellProvider = new PreparedEmailsGridCellProvider();
		$this->addColumn(new GridColumn('name', 'common.name', null, null, $cellProvider, array('width' => 40)));
		$this->addColumn(new GridColumn('sender', 'email.sender', null, null, $cellProvider, array('width' => 10)));
		$this->addColumn(new GridColumn('recipient', 'email.recipient', null, null, $cellProvider));
		$this->addColumn(new GridColumn('subject', 'common.subject', null, null, $cellProvider));
		$this->addColumn(new GridColumn('enabled', 'common.enabled', null, 'controllers/grid/common/cell/selectStatusCell.tpl', $cellProvider, array('width' => 5)));
	}

	/**
	 * @see GridHandler::initFeatures()
	 */
	function initFeatures($request, $args) {
		import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
		return array(new PagingFeature());
	}

	/**
	 * @see GridHandler::loadData()
	 */
	protected function loadData($request, $filter) {
		// Elements to be displayed in the grid
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$context = $request->getContext();
		$emailTemplates = $emailTemplateDao->getEmailTemplates(AppLocale::getLocale(), $context->getId());
		foreach ($emailTemplates as $emailTemplate) {
			$rowData[$emailTemplate->getEmailKey()] = $emailTemplate;
		}
		return $rowData;
	}


	//
	// Overridden methods from GridHandler
	//
	/**
	 * Get the row handler - override the default row handler
	 * @return PreparedEmailsGridRow
	 */
	protected function getRowInstance() {
		return new PreparedEmailsGridRow();
	}


	//
	// Public handler methods
	//
	/**
	 * Create a new prepared email
	 * @param $args array
	 * @param $request PKPRequest
	 * @return string Serialized JSON object
	 */
	function addPreparedEmail($args, $request) {
		return $this->editPreparedEmail($args, $request);
	}

	/**
	 * Edit a prepared email
	 * Will create a new prepared email if their is no emailKey in the request
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editPreparedEmail($args, $request) {
		$context = $request->getContext();
		$emailKey = $request->getUserVar('emailKey');

		import('lib.pkp.controllers.grid.settings.preparedEmails.form.PreparedEmailForm');
		$preparedEmailForm = new PreparedEmailForm($emailKey, $context);
		$preparedEmailForm->initData($request);

		return new JSONMessage(true, $preparedEmailForm->fetch($request));
	}

	/**
	 * Save the email editing form
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function updatePreparedEmail($args, $request) {
		$context = $request->getContext();
		$emailKey = $request->getUserVar('emailKey');

		import('lib.pkp.controllers.grid.settings.preparedEmails.form.PreparedEmailForm');
		$preparedEmailForm = new PreparedEmailForm($emailKey, $context);
		$preparedEmailForm->readInputData();

		if ($preparedEmailForm->validate()) {
			$preparedEmailForm->execute();

			// Create notification.
			$notificationMgr = new NotificationManager();
			$user = $request->getUser();
			$notificationMgr->createTrivialNotification($user->getId());

			// Let the calling grid reload itself
			return DAO::getDataChangedEvent($emailKey);
		} else {
			return new JSONMessage(false);
		}
	}

	/**
	 * Reset a single email
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function resetEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		assert(is_string($emailKey));

		$context = $request->getContext();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		if ($request->checkCSRF() && $emailTemplateDao->templateExistsByKey($emailKey, $context->getId())) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $context->getId());
			return DAO::getDataChangedEvent($emailKey);
		}
		return new JSONMessage(false);
	}

	/**
	 * Reset all email to stock.
	 * @param $args array
	 * @param $request Request
	 */
	function resetAllEmails($args, $request) {
		if (!$request->checkCSRF()) return new JSONMessage(false);

		$context = $request->getContext();
		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$emailTemplateDao->deleteEmailTemplatesByContext($context->getId());
		return DAO::getDataChangedEvent();
	}

	/**
	 * Disables an email template.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function disableEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		assert(is_string($emailKey));

		$context = $request->getContext();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($emailKey, $context->getId());

		if ($request->checkCSRF() && isset($emailTemplate)) {
			if ($emailTemplate->getCanDisable()) {
				$emailTemplate->setEnabled(0);

				if ($emailTemplate->getAssocId() == null) {
					$emailTemplate->setAssocId($context->getId());
					$emailTemplate->setAssocType(ASSOC_TYPE_JOURNAL);
				}

				if ($emailTemplate->getEmailId() != null) {
					$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
				} else {
					$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
				}

				return DAO::getDataChangedEvent($emailKey);
			}
		}
		return new JSONMessage(false);
	}


	/**
	 * Enables an email template.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function enableEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		assert(is_string($emailKey));

		$context = $request->getContext();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		$emailTemplate = $emailTemplateDao->getBaseEmailTemplate($emailKey, $context->getId());

		if ($request->checkCSRF() && isset($emailTemplate)) {
			if ($emailTemplate->getCanDisable()) {
				$emailTemplate->setEnabled(1);

				if ($emailTemplate->getEmailId() != null) {
					$emailTemplateDao->updateBaseEmailTemplate($emailTemplate);
				} else {
					$emailTemplateDao->insertBaseEmailTemplate($emailTemplate);
				}

				return DAO::getDataChangedEvent($emailKey);
			}
		}
		return new JSONMessage(false);
	}

	/**
	 * Delete a custom email.
	 * @param $args array
	 * @param $request Request
	 * @return JSONMessage JSON object
	 */
	function deleteCustomEmail($args, $request) {
		$emailKey = $request->getUserVar('emailKey');
		$context = $request->getContext();

		$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
		if ($request->checkCSRF() && $emailTemplateDao->customTemplateExistsByKey($emailKey, $context->getId())) {
			$emailTemplateDao->deleteEmailTemplateByKey($emailKey, $context->getId());
			return DAO::getDataChangedEvent($emailKey);
		}
		return new JSONMessage(false);
	}
}

?>
