<?php

/**
 * @file controllers/grid/admin/journal/JournalGridHandler.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalGridHandler
 * @ingroup controllers_grid_admin_journal
 *
 * @brief Handle journal grid requests.
 */

import('lib.pkp.controllers.grid.admin.context.ContextGridHandler');

class JournalGridHandler extends ContextGridHandler {

	//
	// Public grid actions.
	//
	/**
	 * Edit an existing journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function editContext($args, $request) {
		import('classes.core.Services');
		$contextService = Services::get('context');
		$context = null;

		if ($request->getUserVar('rowId')) {
			$context = $contextService->get((int) $request->getUserVar('rowId'));
			if (!$context) {
				return new JSONMessage(false);
			}
		}

		$dispatcher = $request->getDispatcher();
		if ($context) {
			$apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), 'contexts/' . $context->getId());
			$successMessage = __('admin.contexts.form.edit.success');
			$supportedLocales = $context->getSupportedFormLocales();
		} else {
			$apiUrl = $dispatcher->url($request, ROUTE_API, CONTEXT_ID_ALL, 'contexts');
			$successMessage = __('admin.contexts.form.create.success');
			$supportedLocales = $request->getSite()->getSupportedLocales();
		}

		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedLocales);

		$contextForm = new \APP\components\forms\context\ContextForm($apiUrl, $successMessage, $locales, $request->getBaseUrl(), $context);
		$contextFormConfig = $contextForm->getConfig();

		// Pass the URL to the context settings wizard so that the AddContextForm
		// component can redirect to it when a new context is added.
		if (!$context) {
			$contextFormConfig['editContextUrl'] = $request->getDispatcher()->url($request, ROUTE_PAGE, 'index', 'admin', 'wizard', '__id__');
		}

		$containerData = [
			'forms' => [
				FORM_CONTEXT => $contextFormConfig,
			],
		];

		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign([
			'containerData' => $containerData,
			'isAddingNewContext' => !$context,
		]);

		return new JSONMessage(true, $templateMgr->fetch('admin/editContext.tpl'));
	}

	/**
	 * Delete a journal.
	 * @param $args array
	 * @param $request PKPRequest
	 * @return JSONMessage JSON object
	 */
	function deleteContext($args, $request) {

		if (!$request->checkCSRF()) {
			return new JSONMessage(false);
		}

		import('classes.core.Services');
		$contextService = Services::get('context');

		$context = $contextService->get((int) $request->getUserVar('rowId'));

		if (!$context) {
			return new JSONMessage(false);
		}

		$contextService->delete($context);

		return DAO::getDataChangedEvent($journalId);
	}
}
