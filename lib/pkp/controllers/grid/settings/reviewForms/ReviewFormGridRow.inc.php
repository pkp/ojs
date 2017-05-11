<?php

/**
 * @file controllers/grid/settings/reviewForms/ReviewFormGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewFormGridRow
 * @ingroup controllers_grid_settings_reviewForms
 *
 * @brief ReviewForm grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');

class ReviewFormGridRow extends GridRow {
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

		// Is this a new row or an existing row?
		$element = $this->getData();
		assert(is_a($element, 'ReviewForm'));

		$rowId = $this->getId();

		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();

			// determine whether or not this Review Form is editable.
			$canEdit = ($element->getIncompleteCount() == 0 && $element->getCompleteCount() == 0);

			// if review form is editable, add 'edit' grid row action
			if($canEdit) {
				$this->addAction(
					new LinkAction(
						'edit',
						new AjaxModal(
							$router->url($request, null, null, 'editReviewForm', null, array('rowId' => $rowId)),
							__('grid.action.edit'),
							'modal_edit',
							true
						),
					__('grid.action.edit'),
					'edit')
				);
			}

			// if review form is not editable, add 'copy' grid row action
			$this->addAction(
				new LinkAction(
					'copy',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.reviewForms.confirmCopy'),
						null,
						$router->url($request, null, null, 'copyReviewForm', null, array('rowId' => $rowId))
						),
					__('grid.action.copy'),
					'copy'
					)
			);

			// add 'preview' grid row action
			$this->addAction(
				new LinkAction(
					'preview',
					new AjaxModal(
						$router->url($request, null, null, 'editReviewForm', null, array('rowId' => $rowId, 'preview' => 1)),
						__('grid.action.preview'),
						'preview',
						true
					),
					__('grid.action.preview'),
					'preview'
				)
			);

			// if review form is editable, add 'delete' grid row action.
			if($canEdit) {
				$this->addAction(
					new LinkAction(
						'delete',
						new RemoteActionConfirmationModal(
							$request->getSession(),
							__('manager.reviewForms.confirmDelete'),
							null,
							$router->url($request, null, null, 'deleteReviewForm', null, array('rowId' => $rowId))
						),
						__('grid.action.delete'),
						'delete')
				);
			}
		}
	}
}

?>
