<?php

/**
 * @file controllers/grid/articleGalleys/ArticleGalleyGridRow.inc.php
 *
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleGalleyGridRow
 * @ingroup controllers_grid_articleGalleys
 *
 * @brief Handle article galley grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class ArticleGalleyGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function ArticleGalleyGridRow($submissionId) {
		parent::GridRow();
		$this->setRequestArgs(
			((array) $this->getRequestArgs()) + array(
				'submissionId' => $submissionId
			)
		);
	}

	//
	// Overridden template methods
	//
	/*
	 * Configure the grid row
	 * @param $request PKPRequest
	 */
	function initialize($request) {
		parent::initialize($request);

		// Is this a new row or an existing row?
		$articleGalleyId = $this->getId();
		if (!empty($articleGalleyId) && is_numeric($articleGalleyId)) {
			$galley = $this->getData();
			assert(is_a($galley, 'ArticleGalley'));
			$router = $request->getRouter();

			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'edit', null, $this->getRequestArgs() + array('articleGalleyId' => $articleGalleyId)),
						__('submission.layout.editGalley'),
						'modal_edit',
						true),
					__('grid.action.edit'),
					'edit'
				)
			);

			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'delete',
					new RemoteActionConfirmationModal(
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'delete', null, $this->getRequestArgs() + array('articleGalleyId' => $articleGalleyId)),
						'modal_delete'
					),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}
}

?>
