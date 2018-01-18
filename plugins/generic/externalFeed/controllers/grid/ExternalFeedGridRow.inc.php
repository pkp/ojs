<?php
/**
 * @file controllers/grid/ExternalFeedGridRow.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedGridRow
 * @ingroup controllers_grid_externalFeed
 *
 * @brief Handle custom blocks grid row requests.
 */
import('lib.pkp.classes.controllers.grid.GridRow');

class ExternalFeedGridRow extends GridRow {
	//
	// Overridden template methods
	//
	/**
	 * @copydoc GridRow::initialize()
	 */
	function initialize($request, $template = null) {
		parent::initialize($request, $template);
		$feedId = $this->getId();
		
		if (!empty($feedId)) {
			$router = $request->getRouter();
			
			// edit action
			import('lib.pkp.classes.linkAction.request.AjaxModal');
			$this->addAction(
				new LinkAction(
					'editStaticPage',
					new AjaxModal(
						$router->url($request, null, null, 'editExternalFeed', null, array('feedId' => $feedId)),
						__('grid.action.edit'),
						'modal_edit',
						true),
					__('grid.action.edit'),
					'edit'
				)
			);
			
			// delete action
			import('lib.pkp.classes.linkAction.request.RemoteActionConfirmationModal');
			$this->addAction(
				new LinkAction(
					'delete',
					new RemoteActionConfirmationModal(
						$request->getSession(),
						__('common.confirmDelete'),
						__('grid.action.delete'),
						$router->url($request, null, null, 'delete', null, array('feedId' => $feedId)), 'modal_delete'
						),
					__('grid.action.delete'),
					'delete'
				)
			);
		}
	}
}
