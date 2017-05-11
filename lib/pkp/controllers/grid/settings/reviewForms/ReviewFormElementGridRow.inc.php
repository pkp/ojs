<?php

/**
 * @file controllers/grid/settings/reviewForms/ReviewFormElementGridRow.inc.php 
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormElementGridRow
 * @ingroup controllers_grid_settings_reviewForms
 *
 * @brief ReviewFormElements grid row definition
 */
import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class ReviewFormElementGridRow extends GridRow {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Overridden methods from GridRow
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);
		// add grid row actions: edit, delete

		$element = parent::getData();
		assert(is_a($element, 'ReviewFormElement'));
		$rowId = $this->getId();

		$router = $request->getRouter();
		if (!empty($rowId) && is_numeric($rowId)) {
			// add 'edit' grid row action
			$this->addAction(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'editReviewFormElement', null, array('rowId' => $rowId, 'reviewFormId' => $element->getReviewFormId())),
						__('grid.action.edit'),
						'modal_edit',
						true
					),
				__('grid.action.edit'),
				'edit')
			);
			// add 'delete' grid row action
			$this->addAction(
				new LinkAction(
					'delete',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.reviewFormElements.confirmDelete'),
						null,
						$router->url($request, null, null, 'deleteReviewFormElement', null, array('rowId' => $rowId, 'reviewFormId' => $element->getReviewFormId()))
					),
					__('grid.action.delete'),
					'delete')
			);
		} 
	}
}
?>
