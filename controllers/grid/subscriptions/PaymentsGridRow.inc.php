<?php

/**
 * @file controllers/grid/subscriptions/PaymentsGridRow.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PaymentsGridRow
 * @ingroup controllers_grid_subscriptions
 *
 * @brief Payments grid row definition
 */

import('lib.pkp.classes.controllers.grid.GridRow');
import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
import('lib.pkp.classes.linkAction.request.RedirectConfirmationModal');
import('lib.pkp.classes.linkAction.request.JsEventConfirmationModal');

class PaymentsGridRow extends GridRow {
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
		assert(is_a($element, 'Payment'));

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
					'view',
					new AjaxModal(
						$router->url($request, null, null, 'viewPayment', null, $actionArgs),
						__('manager.payment.details'),
						'modal_edit',
						true
						),
					__('manager.payment.details'),
					'edit')
			);
		}
	}
}

?>
