<?php

/**
 * @file controllers/grid/subscriptions/SubscriptionTypesGridRow.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SubscriptionTypesGridRow
 * @ingroup controllers_grid_subscriptions
 *
 * @brief User grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
import('lib.pkp.classes.linkAction.request.RedirectConfirmationModal');
import('lib.pkp.classes.linkAction.request.JsEventConfirmationModal');

class SubscriptionTypesGridRow extends GridRow {
	//
	// Overridden methods from GridRow
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);

		// Is this a new row or an existing row?
		$element =& $this->getData();
		assert(is_a($element, 'SubscriptionType'));

		$rowId = $this->getId();
		if (!empty($rowId) && is_numeric($rowId)) {
			// Only add row actions if this is an existing row
			$router = $request->getRouter();
			$actionArgs = array(
				'gridId' => $this->getGridId(),
				'rowId' => $rowId
			);

			$actionArgs = array_merge($actionArgs, $this->getRequestArgs());

			$this->addAction(
				new LinkAction(
					'edit',
					new AjaxModal(
						$router->url($request, null, null, 'editSubscriptionType', null, $actionArgs),
						__('manager.subscriptionTypes.edit'),
						'modal_edit',
						true
						),
					__('common.edit'),
					'edit')
			);
			$this->addAction(
				new LinkAction(
					'delete',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('manager.subscriptionTypes.confirmDelete'),
						__('common.delete'),
						$router->url($request, null, null, 'deleteSubscriptionType', null, $actionArgs),
						'modal_delete'
						),
					__('grid.action.delete'),
					'delete')
			);
		}
	}
}

?>
