<?php

/**
 * @file pages/admin/AdminContextHandler.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AdminContextHandler
 * @ingroup pages_admin
 *
 * @brief Handle requests for context management in site administration.
 */

import('lib.pkp.pages.admin.AdminHandler');

class AdminContextHandler extends AdminHandler {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();

		$this->addRoleAssignment(
			array(ROLE_ID_SITE_ADMIN),
			array('contexts')
		);
	}

	/**
	 * Display a list of the contexts hosted on the site.
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function contexts($args, $request) {
		$this->setupTemplate($request);
		$templateMgr = TemplateManager::getManager($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_MANAGER);
		if ($request->getUserVar('openWizard')) {
			// Get the open wizard link action.
			import('lib.pkp.classes.linkAction.request.WizardModal');
			$dispatcher = $request->getDispatcher();
			$templateMgr->assign(
				'openWizardLinkAction',
				new LinkAction(
					'openWizard',
					new WizardModal(
						$dispatcher->url($request, ROUTE_COMPONENT, null,
								'wizard.settings.ContextSettingsWizardHandler', 'startWizard', null),
						__('manager.settings.wizard')
					),
					__('manager.settings.wizard'),
					null
				)
			);
		}

		$templateMgr->display('admin/contexts.tpl');
	}
}

?>
