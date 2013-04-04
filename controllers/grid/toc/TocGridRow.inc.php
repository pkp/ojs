<?php

/**
 * @file controllers/grid/settings/issue/TocGridRow.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TocGridRow
 * @ingroup controllers_grid_settings_issue
 *
 * @brief Handle issue grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class TocGridRow extends GridRow {
	/** @var $issueId int */
	var $issueId;

	/**
	 * Constructor
	 * @var $issueId int
	 */
	function TocGridRow($issueId) {
		parent::GridRow();
		$this->issueId = $issueId;
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param $request PKPRequest
	 */
	function initialize(&$request) {
		parent::initialize($request);

		// Is this a new row or an existing row?
		$router = $request->getRouter();

		import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
		$this->addAction(
			new LinkAction(
				'removeArticle',
				new RemoteActionConfirmationModal(
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

?>
