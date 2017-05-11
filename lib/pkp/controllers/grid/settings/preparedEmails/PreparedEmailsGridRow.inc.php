<?php

/**
 * @file classes/controllers/grid/settings/preparedEmails/PreparedEmailsGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PreparedEmailsGridRow
 * @ingroup controllers_grid_settings_PreparedEmails
 *
 * @brief Handle PreparedEmails grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PreparedEmailsGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		// add Grid Row Actions
		$rowId = $this->getId();
		if (isset($rowId) && is_string($rowId)) {
			$context = $request->getContext();
			$router = $request->getRouter();

			// Row action to edit the email template
			import('lib.pkp.controllers.grid.settings.preparedEmails.linkAction.EditEmailLinkAction');
			$this->addAction(new EditEmailLinkAction($request, $rowId));

			// Row action to disable/delete the email template
			$emailTemplateDao = DAORegistry::getDAO('EmailTemplateDAO'); /* @var $emailTemplateDao EmailTemplateDAO */
			$emailTemplate = $emailTemplateDao->getLocaleEmailTemplate($rowId, $context->getId());
			if (isset($emailTemplate) && $emailTemplate->isCustomTemplate()) {
				$this->addAction(
					new LinkAction(
						'deleteEmail',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('manager.emails.confirmDelete'), __('common.delete'),
							$router->url($request, null, 'grid.settings.preparedEmails.PreparedEmailsGridHandler',
								'deleteCustomEmail', null, array('emailKey' => $rowId)), 'modal_delete'
						),
						__('common.delete'),
						'disable'
					)
				);
			}

			// Row action to reset the email template to stock
			if (isset($emailTemplate) && !$emailTemplate->isCustomTemplate()) {
				$this->addAction(
					new LinkAction(
						'resetEmail',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('manager.emails.reset.message'), null,
							$router->url($request, null, 'grid.settings.preparedEmails.PreparedEmailsGridHandler',
								'resetEmail', null, array('emailKey' => $rowId)), 'modal_delete'
						),
						__('manager.emails.reset'),
						'delete'
					)
				);
			}
		}
	}
}

?>
