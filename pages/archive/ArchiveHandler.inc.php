<?php

/**
 * @file pages/archive/ArchiveHandler.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArchiveHandler
 * @ingroup pages_archive
 *
 * @brief Handle requests for archive functions.
 */

import('classes.handler.Handler');

class ArchiveHandler extends Handler {

	/**
	 * @copydoc PKPHandler::authorize()
	 */
	function authorize($request, &$args, $roleAssignments) {
		import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
		$this->addPolicy(new ContextRequiredPolicy($request));

		import('classes.security.authorization.PpsServerMustPublishPolicy');
		$this->addPolicy(new PpsServerMustPublishPolicy($request));

		return parent::authorize($request, $args, $roleAssignments);
	}

	/**
	 * @see PKPHandler::initialize()
	 * @param $args array Arguments list
	 */
	function initialize($request, $args = array()) {

	}

	/**
	 * Display about index page.
	 */
	function index($args, $request) {
		$this->archive($args, $request);
	}

	/**
	 * Display the issue archive listings
	 * @param $args array
	 * @param $request PKPRequest
	 */
	function archive($args, $request) {
		$this->setupTemplate($request);
		$page = isset($args[0]) ? (int) $args[0] : 1;
		$templateMgr = TemplateManager::getManager($request);
		$context = $request->getContext();

		$count = $context->getData('itemsPerPage') ? $context->getData('itemsPerPage') : Config::getVar('interface', 'items_per_page');
		$offset = $page > 1 ? ($page - 1) * $count : 0;


		import('classes.core.Services');
		$issueService = Services::get('issue');
		$params = array(
			'contextId' => $context->getId(),
			'orderBy' => 'seq',
			'orderDirection' => 'ASC',
			'count' => $count,
			'offset' => $offset,
			'isPublished' => true,
		);
		$issues = $issueService->getMany($params);
		$total = $issueService->getMax($params);

		$showingStart = $offset + 1;
		$showingEnd = min($offset + $count, $offset + count($issues));
		$nextPage = $total > $showingEnd ? $page + 1 : null;
		$prevPage = $showingStart > 1 ? $page - 1 : null;

		$templateMgr->assign(array(
			'issues' => $issues,
			'showingStart' => $showingStart,
			'showingEnd' => $showingEnd,
			'total' => $total,
			'nextPage' => $nextPage,
			'prevPage' => $prevPage,
		));

		$templateMgr->display('frontend/pages/archive.tpl');
	}

	function setupTemplate($request) {
		parent::setupTemplate($request);
		AppLocale::requireComponents(LOCALE_COMPONENT_PKP_READER, LOCALE_COMPONENT_APP_EDITOR);
	}


}
