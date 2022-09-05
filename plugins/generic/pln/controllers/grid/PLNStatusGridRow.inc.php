<?php

/**
 * @file controllers/grid/PLNStatusGridRow.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class PLNStatusGridRow
 * @brief Handle PLNStatus deposit grid row requests.
 */

import('lib.pkp.classes.controllers.grid.GridRow');

class PLNStatusGridRow extends GridRow {
	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	public function initialize($request, $template = null) {
		parent::initialize($request, $template);

		$rowId = $this->getId();
		$actionArgs['depositId'] = $rowId;
		if (!empty($rowId)) {
			$router = $request->getRouter();

			// Create the "reset deposit" action
			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'resetDeposit',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('plugins.generic.pln.status.confirmReset'),
						__('common.reset'),

						$router->url($request, null, null, 'resetDeposit', null, $actionArgs, 'modal_reset')
					),
					__('common.reset'),
					'reset'
				)
			);
		}
	}
}
