<?php

/**
 * @file controllers/grid/toc/TocGridRow.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TocGridRow
 * @ingroup controllers_grid_settings_issue
 *
 * @brief Handle issue grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class TocGridRow extends GridRow {
	/** @var int */
	var $issueId;

	/**
	 * Constructor
	 * @param $issueId int
	 */
	function __construct($issueId) {
		parent::__construct();
		$this->issueId = $issueId;
	}

	//
	// Overridden template methods
	//
	/*
	 * @copydoc GridRow::initialize
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		$dispatcher = $request->getDispatcher();
		import('lib.pkp.classes.linkAction.request.RedirectAction');
		$this->addAction(
			new LinkAction(
				'workflow',
				new RedirectAction(
					$dispatcher->url($request, ROUTE_PAGE, null, 'workflow', 'access', array($this->getId()))
				),
				__('submission.submission'),
				'information'
			)
		);

		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$this->addAction(
			new LinkAction(
				'removeArticle',
				new RemoteActionConfirmationModal(
					$request->getSession(),
					__('editor.article.remove.confirm'),
					__('grid.action.removeArticle'),
					$router->url($request, null, null, 'removeArticle', null, array('articleId' => $this->getId(), 'issueId' => $this->issueId)), 'modal_delete'
				),
				__('editor.article.remove'),
				'delete'
			)
		);
	}
}


